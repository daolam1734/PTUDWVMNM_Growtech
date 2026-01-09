<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/functions.php";

// 1. Check login
if (empty($_SESSION["user_id"])) {
    set_flash("warning", "Vui lòng đăng nhập để thanh toán.");
    header("Location: /weblaptop/auth/login.php?next=/weblaptop/checkout.php?" . http_build_query($_GET));
    exit;
}

$user_id = $_SESSION["user_id"];
$full_cart = $_SESSION["cart"] ?? [];

// 2. Filter selected items
$selected_ids = isset($_GET['selected']) ? explode(',', $_GET['selected']) : [];
$cart = [];

if (!empty($selected_ids)) {
    foreach ($selected_ids as $sid) {
        if (isset($full_cart[$sid])) {
            $cart[$sid] = $full_cart[$sid];
        }
    }
} else {
    $cart = $full_cart;
}

if (empty($cart)) {
    set_flash("info", "Vui lòng chọn sản phẩm để thanh toán.");
    header("Location: /weblaptop/cart.php");
    exit;
}

// Fetch cart items details
$items = [];
$subtotal = 0.0;
$ids = implode(",", array_map("intval", array_keys($cart)));
$sql = "SELECT p.* FROM products p WHERE p.id IN ($ids) GROUP BY p.id";
$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $p) {
    $qty = $cart[$p["id"]];
    $p["quantity"] = $qty;
    $p["item_subtotal"] = $p["price"] * $qty;
    $subtotal += $p["item_subtotal"];
    $p["image"] = getProductImage($p["id"]);
    $items[] = $p;
}

$shipping_fee = $subtotal >= 10000000 ? 0 : 30000; 

// Multiple Voucher logic
$discount = 0;
$shipping_discount = 0;
$vouchers = $_SESSION["vouchers"] ?? [];

// 1. Product Discount
if (!empty($vouchers['product'])) {
    $vp = getVoucherByCode($vouchers['product']['code']);
    if ($vp && $subtotal >= $vp["min_spend"]) {
        $discount = calculateDiscount($vp, $subtotal, $shipping_fee);
    } else {
        unset($_SESSION["vouchers"]["product"]);
    }
}

// 2. Shipping Discount
if (!empty($vouchers['shipping'])) {
    $vs = getVoucherByCode($vouchers['shipping']['code']);
    if ($vs && $subtotal >= $vs["min_spend"]) {
        $shipping_discount = calculateDiscount($vs, $subtotal, $shipping_fee);
    } else {
        unset($_SESSION["vouchers"]["shipping"]);
    }
}

$total = $subtotal + $shipping_fee - $discount - $shipping_discount;
$addresses = getUserAddresses($user_id);

// 2.1 Handle Add Address (New)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "add_address") {
    $recipient_name = trim($_POST['recipient_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $label = trim($_POST['label'] ?? 'Nhà riêng');
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    if ($recipient_name && $phone && $address_line1) {
        if ($is_default) {
            $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")->execute([$user_id]);
        }
        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_line1, city, district, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $label, $recipient_name, $phone, $address_line1, $city, $district, $is_default])) {
            set_flash('success', 'Thêm địa chỉ giao hàng thành công.');
            header("Location: checkout.php?" . $_SERVER['QUERY_STRING']);
            exit;
        }
    } else {
        set_flash('error', 'Vui lòng điền đầy đủ các thông tin bắt buộc.');
    }
}

// 3. Handle Order Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $address_id = $_POST["address_id"] ?? 0;
    $payment_method = $_POST["payment_method"] ?? "";
    $notes = trim($_POST["notes"] ?? "");

    $errors = [];
    if (!$address_id) $errors[] = "Vui lòng chọn địa chỉ giao hàng.";
    if (!$payment_method) $errors[] = "Vui lòng chọn phương thức thanh toán.";

    if (empty($errors)) {
        // Collect product voucher ID if exists
        $product_voucher_id = null;
        if (!empty($_SESSION['vouchers']['product'])) {
            $vp = getVoucherByCode($_SESSION['vouchers']['product']['code']);
            if ($vp) $product_voucher_id = $vp['id'];
        }

        $orderData = [
            "user_id" => $user_id,
            "address_id" => $address_id,
            "voucher_id" => $product_voucher_id,
            "subtotal" => $subtotal,
            "shipping_fee" => $shipping_fee,
            "shipping_discount" => $shipping_discount,
            "discount" => $discount,
            "discount_amount" => $discount,
            "total" => $total,
            "payment_method" => $payment_method,
            "notes" => $notes,
            "items" => $items
        ];

        $order_no = createOrder($orderData);
        if ($order_no) {
            // Update shipping voucher usage if exists
            if (!empty($_SESSION['vouchers']['shipping'])) {
                $vs = getVoucherByCode($_SESSION['vouchers']['shipping']['code']);
                if ($vs) {
                    $stmtV = $pdo->prepare("UPDATE vouchers SET usage_count = usage_count + 1 WHERE id = ?");
                    $stmtV->execute([$vs['id']]);
                }
            }

            // Remove ONLY selected items from original cart
            foreach ($items as $placed_item) {
                if (isset($_SESSION["cart"][$placed_item['id']])) {
                    unset($_SESSION["cart"][$placed_item['id']]);
                }
            }
            unset($_SESSION["vouchers"]);

            createNotification($user_id, "Đặt hàng thành công", "Cảm ơn bạn đã đặt hàng! Đơn hàng $order_no của bạn đang được xử lý.", "order", "/weblaptop/orders.php");

            set_flash("success", "Đặt hàng thành công! Mã đơn hàng của bạn là " . $order_no);
            header("Location: /weblaptop/orders.php");
            exit;
        } else {
            $errors[] = "Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.";
        }
    }
    if (!empty($errors)) set_flash("error", implode("<br>", $errors));
}

require_once __DIR__ . "/includes/header.php";
?>

<style>
    :root {
        --primary-red: #C62222;
        --bg-gray: #f4f6f8;
    }
    body { background-color: var(--bg-gray); }

    .checkout-title { font-size: 24px; font-weight: 700; margin-bottom: 25px; color: #333; }
    
    .checkout-section { 
        background: #fff; 
        padding: 24px; 
        margin-bottom: 20px; 
        border-radius: 8px; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .section-title { 
        font-size: 16px; 
        font-weight: 700; 
        margin-bottom: 20px; 
        display: flex; 
        align-items: center; 
        gap: 10px;
        color: var(--primary-red);
        text-transform: uppercase;
    }

    .address-card {
        border: 2px solid #f0f0f0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 12px;
        cursor: pointer;
        transition: 0.2s;
        position: relative;
    }
    .address-card:hover { border-color: #ddd; }
    .address-card.active { border-color: var(--primary-red); background-color: #fff9f9; }
    .address-card input { position: absolute; opacity: 0; }
    .address-card .name { font-weight: 700; font-size: 15px; }

    .item-row { display: flex; gap: 15px; padding: 12px 0; border-bottom: 1px solid #f8f9fa; }
    .item-row:last-child { border-bottom: none; }
    .item-img { width: 60px; height: 60px; object-fit: contain; border: 1px solid #eee; border-radius: 4px; }
    .item-info { flex: 1; }
    .item-name { font-size: 14px; font-weight: 500; color: #333; margin-bottom: 4px; }
    .item-meta { font-size: 13px; color: #666; display: flex; justify-content: space-between; }

    .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; color: #555; }
    .summary-total { border-top: 1px dashed #eee; padding-top: 15px; margin-top: 15px; font-weight: 800; font-size: 20px; color: var(--primary-red); }

    .payment-option {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
    }
    .payment-option:hover { background: #fcfcfc; }
    .payment-option input:checked + .payment-content { font-weight: 600; }

    .btn-place-order {
        background: var(--primary-red);
        color: #fff;
        border: none;
        width: 100%;
        padding: 16px;
        font-weight: 700;
        font-size: 16px;
        border-radius: 6px;
        margin-top: 20px;
        text-transform: uppercase;
        transition: 0.3s;
    }
    .btn-place-order:hover { background: #a51b1b; transform: translateY(-2px); }

    .back-to-cart { color: #666; text-decoration: none; font-size: 14px; transition: 0.2s; }
    .back-to-cart:hover { color: var(--primary-red); }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="checkout-title mb-0">Thanh toán đơn hàng</h1>
        <a href="cart.php" class="back-to-cart"><i class="bi bi-arrow-left"></i> Quay lại giỏ hàng</a>
    </div>

    <form method="POST" id="checkout-form">
        <div class="row g-4">
            <!-- Left: Info -->
            <div class="col-lg-8">
                <!-- Address -->
                <div class="checkout-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="section-title mb-0"><i class="bi bi-geo-alt-fill"></i> Địa chỉ nhận hàng</h5>
                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                            <i class="bi bi-plus-circle"></i> Thêm địa chỉ mới
                        </button>
                    </div>
                    <?php if (empty($addresses)): ?>
                        <div class="alert alert-warning py-4 text-center">
                            <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                            Bạn chưa có địa chỉ nhận hàng. <br>
                            <a href="/weblaptop/account.php" class="btn btn-outline-danger btn-sm mt-3 px-4">Thêm địa chỉ ngay</a>
                        </div>
                    <?php else: ?>
                        <div class="address-list">
                            <?php foreach ($addresses as $addr): ?>
                                <label class="address-card <?php echo $addr['is_default'] ? 'active' : ''; ?>">
                                    <input type="radio" name="address_id" value="<?php echo $addr['id']; ?>" <?php echo $addr['is_default'] ? 'checked' : ''; ?> onchange="updateActiveCard(this)">
                                    <div class="name">
                                        <?php echo htmlspecialchars($addr['recipient_name']); ?> 
                                        <span class="text-muted fw-normal ms-2">| <?php echo htmlspecialchars($addr['phone']); ?></span>
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <?php echo htmlspecialchars($addr['label']); ?>: 
                                        <?php echo htmlspecialchars($addr['address_line1']); ?><?php echo !empty($addr['address_line2']) ? ', '.$addr['address_line2'] : ''; ?>, 
                                        <?php echo htmlspecialchars($addr['district']); ?>, 
                                        <?php echo htmlspecialchars($addr['city']); ?>
                                    </div>
                                    <?php if ($addr['is_default']): ?>
                                        <span class="badge position-absolute top-0 end-0 m-3 bg-danger" style="font-weight: 500; font-size: 10px;">Mặc định</span>
                                    <?php endif; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Products -->
                <div class="checkout-section">
                    <h5 class="section-title"><i class="bi bi-box-seam-fill"></i> Sản phẩm đã chọn</h5>
                    <div class="items-list">
                        <?php foreach ($items as $it): ?>
                            <div class="item-row">
                                <img src="<?php echo htmlspecialchars($it['image']); ?>" class="item-img">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($it['name']); ?></div>
                                    <div class="item-meta">
                                        <span>Số lượng: x<?php echo $it['quantity']; ?></span>
                                        <span class="fw-bold"><?php echo number_format($it['item_subtotal'], 0, ',', '.'); ?> đ</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Notes -->
                <div class="checkout-section">
                    <h5 class="section-title"><i class="bi bi-chat-left-text-fill"></i> Ghi chú cho đơn hàng</h5>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi đến..."></textarea>
                </div>
            </div>

            <!-- Right: Payment & Summary -->
            <div class="col-lg-4">
                <div class="checkout-section">
                    <h5 class="section-title"><i class="bi bi-credit-card-fill"></i> Phương thức thanh toán</h5>
                    
                    <label class="payment-option w-100">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <div class="payment-content d-flex align-items-center">
                            <img src="https://cdn-icons-png.flaticon.com/512/6491/6491517.png" width="30" class="me-3" alt="">
                            <span>Thanh toán khi nhận hàng (COD)</span>
                        </div>
                    </label>

                    <label class="payment-option w-100">
                        <input type="radio" name="payment_method" value="banking">
                        <div class="payment-content d-flex align-items-center">
                            <img src="https://cdn-icons-png.flaticon.com/512/2830/2830284.png" width="30" class="me-3" alt="">
                            <span>Chuyển khoản ngân hàng</span>
                        </div>
                    </label>

                    <!-- Banking Info (Hidden by default) -->
                    <div id="banking-details" class="mt-3 p-3 rounded-3 bg-light border border-dashed d-none">
                        <small class="text-muted fw-bold d-block mb-2">Thông tin chuyển khoản:</small>
                        <div class="small">
                            <p class="mb-1">Ngân hàng: <b><?php echo BANK_NAME; ?></b></p>
                            <p class="mb-1">Chủ TK: <b><?php echo BANK_ACCOUNT_NAME; ?></b></p>
                            <p class="mb-1 text-danger">Số TK: <b class="fs-6"><?php echo BANK_ACCOUNT_NUMBER; ?></b></p>
                            <p class="mb-0">Nội dung: <b>WL [Mã đơn hàng]</b></p>
                        </div>
                        <div class="mt-2 text-center">
                            <?php 
                            $vietqr_url = "https://img.vietqr.io/image/" . BANK_ID . "-" . BANK_ACCOUNT_NUMBER . "-compact.png?amount=" . $total . "&addInfo=WL Order&accountName=" . urlencode(BANK_ACCOUNT_NAME);
                            ?>
                            <img src="<?php echo $vietqr_url; ?>" alt="QR VietQR" class="img-thumbnail" width="150">
                            <div class="mt-1 small text-muted">Quét VietQR để thanh toán nhanh</div>
                        </div>
                    </div>

                    <label class="payment-option w-100">
                        <input type="radio" name="payment_method" value="momo">
                        <div class="payment-content d-flex align-items-center">
                            <img src="https://developers.momo.vn/v3/img/logo2.svg" width="30" class="me-3" alt="">
                            <span>Ví điện tử MoMo</span>
                        </div>
                    </label>

                    <!-- MoMo Info (Hidden by default) -->
                    <div id="momo-details" class="mt-3 p-3 rounded-3 bg-light border border-dashed d-none" style="border-color: #a50064 !important;">
                        <small class="fw-bold d-block mb-2" style="color: #a50064;">Thông tin ví MoMo:</small>
                        <div class="small">
                            <p class="mb-1">Chủ tài khoản: <b><?php echo MOMO_NAME; ?></b></p>
                            <p class="mb-1 text-danger">Số điện thoại: <b class="fs-6"><?php echo MOMO_PHONE; ?></b></p>
                        </div>
                        <div class="mt-2 text-center">
                            <?php 
                            $momo_qr_url = "https://api.vietqr.io/image/970422-" . MOMO_PHONE . "-compact.png?amount=" . $total . "&addInfo=WL Order&accountName=" . urlencode(MOMO_NAME);
                            ?>
                            <img src="<?php echo $momo_qr_url; ?>" alt="QR MoMo" class="img-thumbnail" width="150">
                            <div class="mt-1 small text-muted">Quét MoMo để thanh toán nhanh</div>
                        </div>
                    </div>
                </div>

                <div class="checkout-section mt-4">
                    <h5 class="section-title">Tổng kết đơn hàng</h5>
                    
                    <div class="summary-row">
                        <span>Tạm tính (<?php echo count($items); ?> sản phẩm):</span>
                        <span><?php echo number_format($subtotal, 0, ',', '.'); ?> đ</span>
                    </div>

                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span><?php echo number_format($shipping_fee, 0, ',', '.'); ?> đ</span>
                    </div>

                    <?php if ($discount > 0): ?>
                    <div class="summary-row text-success">
                        <span>Giảm giá voucher:</span>
                        <span>-<?php echo number_format($discount, 0, ',', '.'); ?> đ</span>
                    </div>
                    <?php endif; ?>

                    <?php if ($shipping_discount > 0): ?>
                    <div class="summary-row text-success">
                        <span>Giảm phí vận chuyển:</span>
                        <span>-<?php echo number_format($shipping_discount, 0, ',', '.'); ?> đ</span>
                    </div>
                    <?php endif; ?>

                    <div class="summary-total">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Thành tiền:</span>
                            <span><?php echo number_format($total, 0, ',', '.'); ?> đ</span>
                        </div>
                        <?php if ($discount + $shipping_discount > 0): ?>
                            <div class="small fw-normal text-success text-end mt-1" style="font-size: 13px;">
                                <i class="bi bi-gift"></i> Bạn đã tiết kiệm được <?php echo number_format($discount + $shipping_discount, 0, ',', '.'); ?> đ
                            </div>
                        <?php endif; ?>
                        <div class="small fw-normal text-muted text-end mt-1" style="font-size: 11px;">(Đã bao gồm VAT nếu có)</div>
                    </div>

                    <button type="submit" class="btn-place-order">
                        Xác nhận đặt hàng
                    </button>

                    <div class="text-center mt-3 small text-muted">
                        Bằng việc nhấn "Xác nhận đặt hàng", bạn đồng ý với <a href="#" class="text-decoration-none">Điều khoản dịch vụ</a> của chúng tôi.
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Modal Thêm địa chỉ mới -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addAddressModalLabel">Thêm địa chỉ giao hàng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_address">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tên người nhận *</label>
                            <input type="text" name="recipient_name" class="form-control" placeholder="Họ và tên" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Số điện thoại *</label>
                            <input type="tel" name="phone" class="form-control" placeholder="Số điện thoại" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Loại địa chỉ</label>
                            <select name="label" class="form-select">
                                <option value="Nhà riêng">Nhà riêng</option>
                                <option value="Văn phòng">Văn phòng</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Địa chỉ chi tiết *</label>
                            <input type="text" name="address_line1" class="form-control" placeholder="Số nhà, tên đường, phường/xã..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Quận/Huyện</label>
                            <input type="text" name="district" class="form-control" placeholder="VD: Quận 1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tỉnh/Thành phố</label>
                            <input type="text" name="city" class="form-control" placeholder="VD: TP. Hồ Chí Minh">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default_check">
                                <label class="form-check-label small" for="is_default_check">
                                    Đặt làm địa chỉ mặc định
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger px-4" style="background-color: var(--primary-red);">Lưu địa chỉ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateActiveCard(input) {
    document.querySelectorAll('.address-card').forEach(card => card.classList.remove('active'));
    if (input.checked) {
        input.closest('.address-card').classList.add('active');
    }
}

// Payment method toggle
document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
        const bankingDetails = document.getElementById('banking-details');
        const momoDetails = document.getElementById('momo-details');
        
        // Hide all first
        bankingDetails.classList.add('d-none');
        momoDetails.classList.add('d-none');
        
        // Show selected
        if (e.target.value === 'banking') {
            bankingDetails.classList.remove('d-none');
        } else if (e.target.value === 'momo') {
            momoDetails.classList.remove('d-none');
        }
    });
});
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
