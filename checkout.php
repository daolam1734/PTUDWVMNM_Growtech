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
$voucher = $_SESSION["voucher"] ?? null;
if ($voucher) {
    // Re-validate voucher
    $v = getVoucherByCode($voucher["code"]);
    if ($v && $subtotal >= $v["min_order_value"]) {
        $discount = calculateDiscount($v, $subtotal);
        $voucher = $v; // Update with latest data
    } else {
        unset($_SESSION["voucher"]);
        $voucher = null;
    }
}

$total = $subtotal + $shipping_fee - $discount;

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
            "subtotal" => $subtotal,
            "shipping_fee" => $shipping_fee,
            "discount" => $discount,
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
    body { background-color: #f5f5f5; }
    .checkout-section { background: #fff; padding: 25px; margin-bottom: 15px; box-shadow: 0 1px 1px rgba(0,0,0,.05); border-radius: 3px; }
    .address-border { height: 3px; width: 100%; background-position-x: -30px; background-size: 116px 3px; background-image: repeating-linear-gradient(45deg,#6fa6d6,#6fa6d6 33px,transparent 0,transparent 41px,#f18d9b 0,#f18d9b 74px,transparent 0,transparent 82px); }
    .section-title { color: #ee4d2d; font-size: 18px; margin-bottom: 20px; display: flex; align-items: center; }
    .section-title i { margin-right: 10px; font-size: 20px; }
    .product-table th { background: #fafafa; font-weight: 400; color: #888; border: none; }
    .product-img { width: 40px; height: 40px; object-fit: cover; margin-right: 10px; }
    .payment-option { border: 1px solid rgba(0,0,0,.09); padding: 10px 20px; margin-right: 10px; cursor: pointer; border-radius: 2px; display: inline-block; }
    .payment-option.active { border-color: #ee4d2d; color: #ee4d2d; position: relative; }
    .payment-option.active::after { content: ""; position: absolute; right: 0; bottom: 0; background: #ee4d2d; color: #fff; font-size: 10px; padding: 0 2px; }
    .summary-row { display: flex; justify-content: flex-end; padding: 10px 0; color: rgba(0,0,0,.54); }
    .summary-label { width: 200px; text-align: right; padding-right: 20px; }
    .summary-value { width: 150px; text-align: right; color: rgba(0,0,0,.8); }
    .total-value { color: #ee4d2d; font-size: 24px; }
    .btn-order { background: #ee4d2d; color: #fff; padding: 12px 60px; border: none; border-radius: 2px; font-size: 16px; transition: background 0.2s; }
    .btn-order:hover { background: #f05d40; color: #fff; }
</style>

<div class="container py-4">
    <form method="POST" id="checkout-form">
        <!-- Address Section -->
        <div class="checkout-section p-0 overflow-hidden">
            <div class="address-border"></div>
            <div class="p-4">
                <div class="section-title">
                    <span class="sparkle-effect"></span> Địa Chỉ Nhận Hàng
                </div>
                <?php if (empty($addresses)): ?>
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-muted">Bạn chưa có địa chỉ giao hàng.</span>
                        <a href="account.php" class="btn btn-outline-primary btn-sm">Thêm địa chỉ mới</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($addresses as $addr): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="address_id" id="addr-<?php echo $addr["id"]; ?>" value="<?php echo $addr["id"]; ?>" <?php echo $addr["is_default"] ? "checked" : ""; ?>>
                            <label class="form-check-label ms-2" for="addr-<?php echo $addr["id"]; ?>">
                                <strong><?php echo htmlspecialchars($addr["recipient_name"]); ?> <?php echo htmlspecialchars($addr["phone"]); ?></strong>
                                <span class="ms-3 text-muted"><?php echo htmlspecialchars($addr["address_line1"]); ?>, <?php echo htmlspecialchars($addr["district"]); ?>, <?php echo htmlspecialchars($addr["city"]); ?></span>
                                <?php if ($addr["is_default"]): ?>
                                    <span class="badge border border-danger text-danger ms-2">Mặc định</span>
                                <?php endif; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <div class="mt-2">
                        <a href="account.php" class="text-primary text-decoration-none small">Thay đổi</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Products Section -->
        <div class="checkout-section">
            <table class="table product-table">
                <thead>
                    <tr>
                        <th style="width: 50%;">Sản phẩm</th>
                        <th class="text-center">Đơn giá</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-end">Thành tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($it["image"]); ?>" class="product-img">
                                    <span class="text-truncate" style="max-width: 400px;"><?php echo htmlspecialchars($it["name"]); ?></span>
                                </div>
                            </td>
                            <td class="text-center text-muted"><?php echo number_format($it["price"], 0, ",", "."); ?> đ</td>
                            <td class="text-center text-muted"><?php echo $it["quantity"]; ?></td>
                            <td class="text-end"><?php echo number_format($it["item_subtotal"], 0, ",", "."); ?> đ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                <div class="flex-grow-1 me-5">
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Lưu ý cho người bán...">
                </div>
                <div class="text-muted small">Đơn vị vận chuyển: <span class="text-success fw-bold">Nhanh</span></div>
                <div class="ms-5 text-end">
                    <div class="text-primary"><?php echo number_format($shipping_fee, 0, ",", "."); ?> đ</div>
                    <div class="text-muted small">Nhận hàng vào 2-3 ngày tới</div>
                </div>
            </div>
        </div>

        <!-- Voucher Section -->
        <div class="checkout-section">
            <div class="d-flex align-items-center justify-content-between">
                <div class="section-title mb-0">
                    <span class="sparkle-effect text-danger"></span> GrowTech Voucher
                </div>
                <div class="d-flex align-items-center">
                    <?php if ($voucher): ?>
                        <span class="text-danger me-3">-<?php echo number_format($discount, 0, ",", "."); ?> đ (<?php echo htmlspecialchars($voucher["code"]); ?>)</span>
                    <?php endif; ?>
                    <a href="cart.php" class="text-primary text-decoration-none small">Thay đổi</a>
                </div>
            </div>
        </div>

        <!-- Payment Section -->
        <div class="checkout-section">
            <div class="section-title mb-4">Phương thức thanh toán</div>
            <div class="mb-4">
                <div class="payment-option active" onclick="selectPayment(this, 'tien_mat')">Thanh toán khi nhận hàng (COD)</div>
                <div class="payment-option" onclick="selectPayment(this, 'chuyen_khoan')">Chuyển khoản ngân hàng</div>
                <input type="hidden" name="payment_method" id="payment_method" value="tien_mat">
            </div>

            <div class="bg-light p-4">
                <div class="summary-row">
                    <div class="summary-label">Tổng tiền hàng</div>
                    <div class="summary-value"><?php echo number_format($subtotal, 0, ",", "."); ?> đ</div>
                </div>
                <div class="summary-row">
                    <div class="summary-label">Phí vận chuyển</div>
                    <div class="summary-value"><?php echo number_format($shipping_fee, 0, ",", "."); ?> đ</div>
                </div>
                <?php if ($discount > 0): ?>
                <div class="summary-row">
                    <div class="summary-label">Giảm giá voucher</div>
                    <div class="summary-value">-<?php echo number_format($discount, 0, ",", "."); ?> đ</div>
                </div>
                <?php endif; ?>
                <div class="summary-row">
                    <div class="summary-label">Tổng thanh toán</div>
                    <div class="summary-value total-value"><?php echo number_format($total, 0, ",", "."); ?> đ</div>
                </div>
                <div class="d-flex justify-content-end mt-4 pt-4 border-top">
                    <button type="submit" class="btn-order">Đặt hàng</button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function selectPayment(el, method) {
    document.querySelectorAll(".payment-option").forEach(opt => opt.classList.remove("active"));
    el.classList.add("active");
    document.getElementById("payment_method").value = method;
}
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
