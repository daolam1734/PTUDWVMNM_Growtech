<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/functions.php";

// 1. Check login
if (empty($_SESSION["user_id"])) {
    set_flash("warning", "Vui lòng đăng nhập để thanh toán.");
    header("Location: /weblaptop/auth/login.php?next=/weblaptop/checkout.php");
    exit;
}

$user_id = $_SESSION["user_id"];
$cart = $_SESSION["cart"] ?? [];

// 2. Check cart
if (empty($cart)) {
    set_flash("info", "Giỏ hàng của bạn đang trống.");
    header("Location: /weblaptop/cart.php");
    exit;
}

// Fetch cart items details
$items = [];
$subtotal = 0.0;
$ids = implode(",", array_map("intval", array_keys($cart)));
$sql = "SELECT p.*, pi.url as image_url 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0
        WHERE p.id IN ($ids)";
$stmt = $pdo->query($sql);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $p) {
    $qty = $cart[$p["id"]];
    $p["quantity"] = $qty;
    $p["item_subtotal"] = $p["price"] * $qty;
    $subtotal += $p["item_subtotal"];
    $p["image"] = $p["image_url"];
    if (!$p["image"] || (strpos($p["image"], 'http') !== 0 && strpos($p["image"], '/') !== 0)) {
        if ($p["image"] && (preg_match('/^\d+x\d+/', $p["image"]) || strpos($p["image"], 'text=') !== false)) {
            $p["image"] = 'https://placehold.co/' . $p["image"];
        } else {
            $p["image"] = 'https://placehold.co/150?text=No+Image';
        }
    }
    $items[] = $p;
}

$shipping_fee = 30000; 

// Voucher logic
$discount = 0;
$shipping_discount = 0;
$voucher = $_SESSION["voucher"] ?? null;
if ($voucher) {
    // Re-validate voucher
    $v = getVoucherByCode($voucher["code"]);
    if ($v && $subtotal >= $v["min_spend"]) {
        if ($v['discount_type'] === 'shipping') {
            $shipping_discount = calculateDiscount($v, $subtotal, $shipping_fee);
        } else {
            $discount = calculateDiscount($v, $subtotal, $shipping_fee);
        }
        $voucher = $v; // Update with latest data
    } else {
        unset($_SESSION["voucher"]);
        $voucher = null;
    }
}

$total = $subtotal + $shipping_fee - $discount - $shipping_discount;

// Fetch user addresses
$addresses = getUserAddresses($user_id);

// 3. Handle Order Submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $address_id = $_POST["address_id"] ?? 0;
    $payment_method = $_POST["payment_method"] ?? "";
    $notes = trim($_POST["notes"] ?? "");

    $errors = [];
    if (!$address_id) $errors[] = "Vui lòng chọn địa chỉ giao hàng.";
    if (!$payment_method) $errors[] = "Vui lòng chọn phương thức thanh toán.";

    if (empty($errors)) {
        $orderData = [
            "user_id" => $user_id,
            "address_id" => $address_id,
            "voucher_id" => $voucher ? $voucher['id'] : null,
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

        $order_id = createOrder($orderData);
        if ($order_id) {
            unset($_SESSION["cart"]);
            unset($_SESSION["voucher"]);
            set_flash("success", "Đặt hàng thành công! Mã đơn hàng của bạn là #" . $order_id);
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
        --tet-red: #d32f2f;
        --tet-gold: #ffc107;
        --tet-bg: #f8f9fa;
    }
    body { background-color: var(--tet-bg); }
    .checkout-container { max-width: 1000px; margin: 0 auto; }
    .checkout-section { 
        background: #fff; 
        padding: 25px; 
        margin-bottom: 20px; 
        border-radius: 12px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
    }
    .address-border { 
        height: 4px; 
        width: 100%; 
        background-position-x: -30px; 
        background-size: 116px 4px; 
        background-image: repeating-linear-gradient(45deg, #d32f2f, #d32f2f 33px, transparent 0, transparent 41px, #ffc107 0, #ffc107 74px, transparent 0, transparent 82px); 
    }
    .section-title { 
        color: var(--tet-red); 
        font-size: 1.1rem; 
        font-weight: 700;
        margin-bottom: 20px; 
        display: flex; 
        align-items: center; 
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .section-title i { margin-right: 10px; font-size: 1.2rem; }
    
    .product-item {
        display: flex;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }
    .product-item:last-child { border-bottom: none; }
    .product-img { 
        width: 70px; 
        height: 70px; 
        object-fit: cover; 
        border-radius: 8px;
        margin-right: 15px;
        border: 1px solid #eee;
    }
    .product-info { flex: 1; }
    .product-name { font-weight: 500; color: #333; margin-bottom: 4px; }
    .product-meta { font-size: 0.85rem; color: #777; }
    .product-price-qty { text-align: right; }
    .product-price { font-weight: 600; color: var(--tet-red); }
    
    .payment-option { 
        border: 2px solid #eee; 
        padding: 15px 20px; 
        margin-bottom: 10px; 
        cursor: pointer; 
        border-radius: 10px; 
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        position: relative;
    }
    .payment-option:hover { border-color: var(--tet-gold); background: #fffdf5; }
    .payment-option.active { 
        border-color: var(--tet-red); 
        background: #fff5f5; 
    }
    .payment-option i { font-size: 1.5rem; margin-right: 15px; color: #555; }
    .payment-option.active i { color: var(--tet-red); }
    .payment-option.active::after { 
        content: "\f058"; 
        font-family: "Font Awesome 5 Free"; 
        font-weight: 900;
        position: absolute; 
        right: 15px; 
        color: var(--tet-red); 
        font-size: 1.2rem;
    }

    .summary-card {
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        position: sticky;
        top: 100px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; color: #555; }
    .summary-total { 
        display: flex; 
        justify-content: space-between; 
        margin-top: 15px; 
        padding-top: 15px; 
        border-top: 2px dashed #eee;
        font-weight: 700;
        font-size: 1.25rem;
        color: var(--tet-red);
    }
    .btn-order { 
        background: var(--tet-red); 
        color: #fff; 
        width: 100%;
        padding: 15px; 
        border: none; 
        border-radius: 10px; 
        font-size: 1.1rem; 
        font-weight: 700;
        margin-top: 20px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 10px rgba(211, 47, 47, 0.3);
    }
    .btn-order:hover { 
        background: #b71c1c; 
        color: #fff; 
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(211, 47, 47, 0.4);
    }
    
    .address-item {
        padding: 15px;
        border: 1px solid #eee;
        border-radius: 10px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .address-item:hover { border-color: var(--tet-gold); }
    .address-item.active { border-color: var(--tet-red); background: #fff5f5; }
</style>

<div class="container py-5">
    <div class="checkout-container">
        <h2 class="mb-4 fw-bold text-center"><i class="fas fa-check-circle text-success me-2"></i>Xác Nhận Thanh Toán</h2>
        
        <form method="POST" id="checkout-form">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Address Section -->
                    <div class="checkout-section p-0 overflow-hidden">
                        <div class="address-border"></div>
                        <div class="p-4">
                            <div class="section-title">
                                <i class="fas fa-map-marker-alt"></i> Địa Chỉ Nhận Hàng
                            </div>
                            <?php if (empty($addresses)): ?>
                                <div class="text-center py-3">
                                    <p class="text-muted mb-3">Bạn chưa có địa chỉ giao hàng.</p>
                                    <a href="account.php" class="btn btn-outline-danger rounded-pill px-4">
                                        <i class="fas fa-plus me-2"></i>Thêm địa chỉ mới
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($addresses as $addr): ?>
                                    <div class="address-item <?php echo $addr["is_default"] ? "active" : ""; ?>" onclick="document.getElementById('addr-<?php echo $addr["id"]; ?>').checked = true; updateAddressStyle(this)">
                                        <div class="form-check">
                                            <input class="form-check-input d-none" type="radio" name="address_id" id="addr-<?php echo $addr["id"]; ?>" value="<?php echo $addr["id"]; ?>" <?php echo $addr["is_default"] ? "checked" : ""; ?>>
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="fw-bold mb-1">
                                                        <?php echo htmlspecialchars($addr["recipient_name"]); ?> 
                                                        <span class="text-muted fw-normal ms-2">| <?php echo htmlspecialchars($addr["phone"]); ?></span>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <?php echo htmlspecialchars($addr["address_line1"]); ?>, <?php echo htmlspecialchars($addr["district"]); ?>, <?php echo htmlspecialchars($addr["city"]); ?>
                                                    </div>
                                                    <?php if ($addr["is_default"]): ?>
                                                        <span class="badge bg-danger mt-2" style="font-size: 0.7rem;">MẶC ĐỊNH</span>
                                                    <?php endif; ?>
                                                </div>
                                                <a href="account.php" class="text-primary small">Thay đổi</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div class="checkout-section">
                        <div class="section-title">
                            <i class="fas fa-shopping-bag"></i> Sản Phẩm Đã Chọn
                        </div>
                        <div class="product-list">
                            <?php foreach ($items as $it): ?>
                                <div class="product-item">
                                    <img src="<?php echo htmlspecialchars($it["image"]); ?>" class="product-img">
                                    <div class="product-info">
                                        <div class="product-name text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($it["name"]); ?></div>
                                        <div class="product-meta">Số lượng: <?php echo $it["quantity"]; ?></div>
                                    </div>
                                    <div class="product-price-qty">
                                        <div class="product-price"><?php echo number_format($it["item_subtotal"], 0, ",", "."); ?> đ</div>
                                        <div class="text-muted small"><?php echo number_format($it["price"], 0, ",", "."); ?> đ / cái</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-4 pt-3 border-top">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0"><i class="far fa-sticky-note text-muted"></i></span>
                                        <input type="text" name="notes" class="form-control border-start-0 bg-light" placeholder="Lưu ý cho người bán (tùy chọn)...">
                                    </div>
                                </div>
                                <div class="col-md-5 text-end mt-3 mt-md-0">
                                    <div class="small text-muted">Đơn vị vận chuyển: <span class="text-dark fw-bold">Giao Hàng Nhanh <i class="fas fa-shipping-fast text-primary ms-1"></i></span></div>
                                    <div class="small text-success">Dự kiến nhận hàng sau 2-3 ngày</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div class="checkout-section">
                        <div class="section-title">
                            <i class="fas fa-credit-card"></i> Phương Thức Thanh Toán
                        </div>
                        <div class="payment-options">
                            <div class="payment-option active" onclick="selectPayment(this, 'tien_mat')">
                                <i class="fas fa-money-bill-wave"></i>
                                <div>
                                    <div class="fw-bold">Thanh toán khi nhận hàng (COD)</div>
                                    <div class="small text-muted">Thanh toán bằng tiền mặt khi nhận hàng</div>
                                </div>
                            </div>
                            <div class="payment-option" onclick="selectPayment(this, 'chuyen_khoan')">
                                <i class="fas fa-university"></i>
                                <div>
                                    <div class="fw-bold">Chuyển khoản ngân hàng</div>
                                    <div class="small text-muted">Chuyển khoản qua ứng dụng ngân hàng hoặc ATM</div>
                                </div>
                            </div>
                            <input type="hidden" name="payment_method" id="payment_method" value="tien_mat">
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="summary-card">
                        <h5 class="fw-bold mb-4">Tổng Đơn Hàng</h5>
                        
                        <div class="summary-row">
                            <span>Tạm tính (<?php echo count($items); ?> sản phẩm)</span>
                            <span><?php echo number_format($subtotal, 0, ",", "."); ?> đ</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Phí vận chuyển</span>
                            <span><?php echo number_format($shipping_fee, 0, ",", "."); ?> đ</span>
                        </div>

                        <?php if ($discount > 0): ?>
                            <div class="summary-row text-danger">
                                <span>Giảm giá Voucher</span>
                                <span>-<?php echo number_format($discount, 0, ",", "."); ?> đ</span>
                            </div>
                            <div class="small text-muted mb-3 text-end">
                                <i class="fas fa-ticket-alt me-1"></i> Mã: <?php echo htmlspecialchars($voucher["code"]); ?>
                            </div>
                        <?php endif; ?>

                        <div class="summary-total">
                            <span>Tổng cộng</span>
                            <span><?php echo number_format($total, 0, ",", "."); ?> đ</span>
                        </div>
                        
                        <div class="text-muted small mt-3 text-center">
                            <i class="fas fa-shield-alt text-success me-1"></i> Thanh toán an toàn & bảo mật
                        </div>

                        <button type="submit" class="btn-order">
                            ĐẶT HÀNG NGAY
                        </button>
                        
                        <div class="text-center mt-3">
                            <a href="cart.php" class="text-decoration-none small text-muted">
                                <i class="fas fa-arrow-left me-1"></i> Quay lại giỏ hàng
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function selectPayment(el, method) {
    document.querySelectorAll(".payment-option").forEach(opt => opt.classList.remove("active"));
    el.classList.add("active");
    document.getElementById("payment_method").value = method;
}

function updateAddressStyle(el) {
    document.querySelectorAll(".address-item").forEach(item => item.classList.remove("active"));
    el.classList.add("active");
}
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

