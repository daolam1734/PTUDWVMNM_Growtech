<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/functions.php";

// Actions: update quantities, remove, clear, apply voucher
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["update"]) && isset($_POST["qty"]) && is_array($_POST["qty"])) {
        foreach ($_POST["qty"] as $id => $q) {
            $id = (int)$id; $q = max(0, (int)$q);
            if ($q === 0) unset($_SESSION["cart"][$id]); else $_SESSION["cart"][$id] = $q;
        }
    }
    if (isset($_POST["remove"]) && isset($_POST["product_id"])) {
        $pid = (int)$_POST["product_id"];
        unset($_SESSION["cart"][$pid]);
    }
    if (isset($_POST["clear"])) {
        unset($_SESSION["cart"]);
        unset($_SESSION["voucher"]);
    }
    if (isset($_POST["apply_voucher"])) {
        $code = trim($_POST["voucher_code"] ?? "");
        $voucher = getVoucherByCode($code);
        if ($voucher) {
            $_SESSION["voucher"] = $voucher;
            set_flash("Áp dụng mã giảm giá thành công!", "success");
        } else {
            unset($_SESSION["voucher"]);
            set_flash("Mã giảm giá không hợp lệ hoặc đã hết hạn.", "danger");
        }
    }
    if (isset($_POST["remove_voucher"])) {
        unset($_SESSION["voucher"]);
        set_flash("Đã xóa mã giảm giá.", "info");
    }
    header("Location: cart.php"); exit;
}

require_once __DIR__ . "/includes/header.php";

$cart = $_SESSION["cart"] ?? [];
$items = [];
$subtotal = 0.0;
if ($cart) {
    $ids = implode(",", array_map("intval", array_keys($cart)));
    $sql = "SELECT p.*, pi.url as image_url 
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0
            WHERE p.id IN ($ids)";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        $r["quantity"] = $cart[$r["id"]];
        $r["subtotal"] = $r["quantity"] * $r["price"];
        $subtotal += $r["subtotal"];
        $r["image"] = $r["image_url"];
        if (!$r["image"] || (strpos($r["image"], 'http') !== 0 && strpos($r["image"], '/') !== 0)) {
            if ($r["image"] && (preg_match('/^\d+x\d+/', $r["image"]) || strpos($r["image"], 'text=') !== false)) {
                $r["image"] = 'https://placehold.co/' . $r["image"];
            } else {
                $r["image"] = 'https://placehold.co/150?text=No+Image';
            }
        }
        $items[] = $r;
    }
}

$discount = 0;
if (isset($_SESSION["voucher"])) {
    $discount = calculateDiscount($_SESSION["voucher"], $subtotal);
    if ($discount == 0) {
        unset($_SESSION["voucher"]); // Remove if no longer valid (e.g. subtotal decreased)
    }
}
$total = $subtotal - $discount;
?>

<style>
    body { background-color: #f5f5f5; }
    .cart-header { background: #fff; padding: 15px 20px; border-radius: 3px; margin-bottom: 15px; box-shadow: 0 1px 1px rgba(0,0,0,.05); display: flex; align-items: center; }
    .cart-item { background: #fff; padding: 20px; border-radius: 3px; margin-bottom: 10px; box-shadow: 0 1px 1px rgba(0,0,0,.05); display: flex; align-items: center; }
    .cart-item img { width: 80px; height: 80px; object-fit: cover; margin-right: 15px; border: 1px solid #eee; }
    .cart-item-info { flex: 1; }
    .cart-item-name { font-size: 14px; color: rgba(0,0,0,.87); margin-bottom: 5px; display: block; text-decoration: none; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .cart-item-name:hover { color: #ee4d2d; }
    .cart-item-price { width: 120px; text-align: center; color: rgba(0,0,0,.87); }
    .cart-item-qty { width: 150px; text-align: center; }
    .cart-item-total { width: 120px; text-align: center; color: #ee4d2d; font-weight: 500; }
    .cart-item-action { width: 80px; text-align: center; }
    
    .qty-group { display: flex; align-items: center; justify-content: center; }
    .qty-input { width: 50px; text-align: center; border: 1px solid rgba(0,0,0,.09); border-left: 0; border-right: 0; height: 32px; outline: none; }
    .qty-btn { background: #fff; border: 1px solid rgba(0,0,0,.09); width: 32px; height: 32px; cursor: pointer; outline: none; display: flex; align-items: center; justify-content: center; }
    .qty-btn:hover { background: #f8f8f8; }

    .voucher-section { background: #fff; padding: 15px 20px; border-bottom: 1px dashed rgba(0,0,0,.09); display: flex; align-items: center; justify-content: flex-end; }
    .voucher-label { display: flex; align-items: center; color: #ee4d2d; margin-right: 20px; }
    .voucher-label i { font-size: 20px; margin-right: 10px; }
    
    .cart-footer { position: sticky; bottom: 0; background: #fff; padding: 20px; box-shadow: 0 -5px 10px rgba(0,0,0,.05); display: flex; align-items: center; justify-content: flex-end; border-radius: 0 0 3px 3px; z-index: 100; }
    .total-label { font-size: 16px; margin-right: 10px; color: #222; }
    .total-amount { font-size: 24px; color: #ee4d2d; font-weight: 500; margin-right: 20px; }
    .btn-checkout { background: #ee4d2d; color: #fff; padding: 10px 50px; border: none; border-radius: 2px; font-size: 16px; text-decoration: none; transition: background 0.2s; }
    .btn-checkout:hover { background: #f05d40; color: #fff; }
    
    .empty-cart { text-align: center; padding: 60px 0; background: #fff; border-radius: 3px; }
    .empty-cart img { width: 100px; margin-bottom: 20px; }
    .empty-cart p { color: rgba(0,0,0,.4); font-size: 14px; margin-bottom: 20px; }
</style>

<div class="container py-4">
    <?php if (empty($items)): ?>
        <div class="empty-cart">
            <img src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/cart/9bdd8040b334d31946f49e36be23d033.png" alt="Empty Cart">
            <p>Giỏ hàng của bạn còn trống</p>
            <a href="/weblaptop" class="btn btn-primary px-5" style="background-color: #ee4d2d; border: none;">MUA NGAY</a>
        </div>
    <?php else: ?>
        <div class="cart-header d-none d-md-flex text-muted small">
            <div style="flex: 1;">Sản phẩm</div>
            <div style="width: 120px; text-align: center;">Đơn giá</div>
            <div style="width: 150px; text-align: center;">Số lượng</div>
            <div style="width: 120px; text-align: center;">Số tiền</div>
            <div style="width: 80px; text-align: center;">Thao tác</div>
        </div>

        <form method="post" id="cart-form">
            <?php foreach ($items as $it): ?>
                <div class="cart-item">
                    <img src="<?php echo htmlspecialchars($it["image"]); ?>" alt="<?php echo htmlspecialchars($it["name"]); ?>">
                    <div class="cart-item-info">
                        <a href="product.php?id=<?php echo $it["id"]; ?>" class="cart-item-name"><?php echo htmlspecialchars($it["name"]); ?></a>
                        <div class="small text-muted">Mã: <?php echo htmlspecialchars($it["sku"]); ?></div>
                    </div>
                    <div class="cart-item-price">
                        <?php echo number_format($it["price"], 0, ",", "."); ?> đ
                    </div>
                    <div class="cart-item-qty">
                        <div class="qty-group">
                            <button type="button" class="qty-btn" onclick="changeQty(<?php echo $it["id"]; ?>, -1)">-</button>
                            <input type="number" name="qty[<?php echo $it["id"]; ?>]" id="qty-<?php echo $it["id"]; ?>" value="<?php echo $it["quantity"]; ?>" min="1" class="qty-input">
                            <button type="button" class="qty-btn" onclick="changeQty(<?php echo $it["id"]; ?>, 1)">+</button>
                        </div>
                    </div>
                    <div class="cart-item-total">
                        <?php echo number_format($it["subtotal"], 0, ",", "."); ?> đ
                    </div>
                    <div class="cart-item-action">
                        <button type="submit" name="remove" value="1" class="btn btn-link text-dark p-0" style="text-decoration: none; font-size: 14px;">
                            <input type="hidden" name="product_id" value="<?php echo $it["id"]; ?>">
                            Xóa
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="voucher-section">
                <div class="voucher-label">
                    <span class="sparkle-effect"></span>
                    <span>GrowTech Voucher</span>
                </div>
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION["voucher"])): ?>
                        <div class="me-3 d-flex align-items-center">
                            <span class="text-danger fw-bold me-2">Mã: <?php echo $_SESSION["voucher"]["code"]; ?> (-<?php echo number_format($discount, 0, ",", "."); ?> đ)</span>
                            <button type="submit" name="remove_voucher" class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size: 10px;">Gỡ bỏ</button>
                        </div>
                    <?php else: ?>
                        <input type="text" name="voucher_code" class="form-control form-control-sm me-2" style="width: 200px;" placeholder="Nhập mã giảm giá">
                        <button type="submit" name="apply_voucher" class="btn btn-link text-primary p-0" style="text-decoration: none;">Áp dụng</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="cart-footer">
                <button type="submit" name="clear" class="btn btn-link text-dark me-auto p-0" style="text-decoration: none; font-size: 14px;" onclick="return confirm('Xóa toàn bộ giỏ hàng?')">Xóa tất cả</button>
                <button type="submit" name="update" class="btn btn-outline-secondary me-4 btn-sm">Cập nhật giỏ hàng</button>
                <div class="total-label">Tổng thanh toán (<?php echo count($items); ?> sản phẩm):</div>
                <div class="total-amount"><?php echo number_format($total, 0, ",", "."); ?> đ</div>
                <a href="/weblaptop/checkout.php" class="btn-checkout">Mua Hàng</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
function changeQty(id, delta) {
    const input = document.getElementById("qty-" + id);
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    input.value = val;
}
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
