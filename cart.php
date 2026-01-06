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
$shipping_discount = 0;
$shipping_fee = 30000; // Phí vận chuyển mặc định để hiển thị

if (isset($_SESSION["voucher"])) {
    if ($_SESSION["voucher"]["discount_type"] === 'shipping') {
        $shipping_discount = calculateDiscount($_SESSION["voucher"], $subtotal, $shipping_fee);
    } else {
        $discount = calculateDiscount($_SESSION["voucher"], $subtotal);
    }
    
    if ($discount == 0 && $shipping_discount == 0) {
        unset($_SESSION["voucher"]); // Remove if no longer valid
    }
}
$total = $subtotal + $shipping_fee - $discount - $shipping_discount;
?>

<style>
    body { background-color: #f8f9fa; }
    .cart-container { margin-top: 30px; margin-bottom: 50px; }
    .cart-card { border: none; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; background: #fff; }
    .cart-header { background: #fff; padding: 20px; border-bottom: 1px solid #eee; font-weight: 700; color: #333; }
    .cart-item { padding: 20px; border-bottom: 1px solid #f8f9fa; transition: all 0.3s; display: flex; align-items: center; }
    .cart-item:hover { background-color: #fffcfc; }
    .cart-item:last-child { border-bottom: none; }
    
    .cart-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 10px; border: 1px solid #eee; margin-right: 20px; }
    .cart-item-info { flex: 1; }
    .cart-item-name { font-size: 16px; font-weight: 600; color: #333; text-decoration: none; margin-bottom: 5px; display: block; transition: color 0.2s; }
    .cart-item-name:hover { color: var(--tet-red); }
    .cart-item-price { font-size: 15px; color: #666; }
    .cart-item-total { font-size: 18px; font-weight: 700; color: var(--tet-red); width: 150px; text-align: right; }
    
    .qty-control { display: flex; align-items: center; background: #f1f1f1; border-radius: 8px; padding: 2px; width: fit-content; }
    .qty-btn { border: none; background: none; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #555; transition: all 0.2s; border-radius: 6px; }
    .qty-btn:hover { background: #fff; color: var(--tet-red); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .qty-input { width: 45px; border: none; background: none; text-align: center; font-weight: 600; font-size: 14px; outline: none; }
    /* Remove arrows from number input */
    .qty-input::-webkit-outer-spin-button, .qty-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

    .cart-summary { background: #fff; border-radius: 15px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); position: sticky; top: 100px; }
    .summary-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid var(--tet-gold); color: var(--tet-red); }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 15px; color: #555; }
    .summary-total { border-top: 1px dashed #ddd; padding-top: 15px; margin-top: 15px; font-size: 20px; font-weight: 800; color: var(--tet-red); }
    
    .btn-checkout { background: linear-gradient(45deg, var(--tet-red), #ff4d4d); color: #fff; border: none; border-radius: 10px; padding: 15px; width: 100%; font-weight: 700; font-size: 16px; text-transform: uppercase; letter-spacing: 1px; transition: all 0.3s; margin-top: 20px; box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3); }
    .btn-checkout:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(211, 47, 47, 0.4); color: #fff; }
    
    .voucher-box { background: #fff8f8; border: 1px dashed var(--tet-red); border-radius: 10px; padding: 15px; margin-bottom: 20px; }
    .voucher-input-group { display: flex; gap: 10px; margin-top: 10px; }
    
    .empty-cart { text-align: center; padding: 80px 20px; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
    .empty-cart-icon { font-size: 80px; color: #ddd; margin-bottom: 20px; }
    .btn-tet-outline { border: 2px solid var(--tet-red); color: var(--tet-red); font-weight: 700; border-radius: 10px; padding: 10px 30px; transition: all 0.3s; }
    .btn-tet-outline:hover { background: var(--tet-red); color: #fff; }

    .remove-btn { color: #bbb; transition: all 0.2s; cursor: pointer; font-size: 18px; }
    .remove-btn:hover { color: var(--tet-red); transform: scale(1.2); }
</style>

<div class="container cart-container">
    <h2 class="mb-4 fw-bold"><i class="bi bi-cart3 text-danger me-2"></i> Giỏ Hàng Của Bạn</h2>
    
    <?php if (empty($items)): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon"><i class="bi bi-bag-x"></i></div>
            <h4 class="fw-bold">Giỏ hàng trống!</h4>
            <p class="text-muted mb-4">Có vẻ như bạn chưa chọn được sản phẩm ưng ý cho dịp Tết này.</p>
            <a href="/weblaptop/index.php" class="btn btn-tet-outline">TIẾP TỤC MUA SẮM</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="cart-card">
                    <div class="cart-header d-none d-md-flex">
                        <div style="flex: 1;">Sản phẩm</div>
                        <div style="width: 150px; text-align: center;">Số lượng</div>
                        <div style="width: 150px; text-align: right;">Thành tiền</div>
                        <div style="width: 50px;"></div>
                    </div>

                    <form method="post" id="cart-form">
                        <?php foreach ($items as $it): ?>
                            <div class="cart-item" id="cart-item-<?php echo $it['id']; ?>">
                                <img src="<?php echo htmlspecialchars($it["image"]); ?>" alt="<?php echo htmlspecialchars($it["name"]); ?>">
                                <div class="cart-item-info">
                                    <a href="product.php?id=<?php echo $it["id"]; ?>" class="cart-item-name"><?php echo htmlspecialchars($it["name"]); ?></a>
                                    <div class="cart-item-price"><?php echo number_format($it["price"], 0, ",", "."); ?> đ</div>
                                    <div class="mt-2 d-md-none">
                                        <div class="qty-control">
                                            <button type="button" class="qty-btn" onclick="updateQty(<?php echo $it['id']; ?>, -1)"><i class="bi bi-dash"></i></button>
                                            <input type="number" value="<?php echo $it['quantity']; ?>" readonly class="qty-input" id="qty-mobile-<?php echo $it['id']; ?>">
                                            <button type="button" class="qty-btn" onclick="updateQty(<?php echo $it['id']; ?>, 1)"><i class="bi bi-plus"></i></button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-none d-md-block" style="width: 150px;">
                                    <div class="qty-control mx-auto">
                                        <button type="button" class="qty-btn" onclick="updateQty(<?php echo $it['id']; ?>, -1)"><i class="bi bi-dash"></i></button>
                                        <input type="number" value="<?php echo $it['quantity']; ?>" readonly class="qty-input" id="qty-<?php echo $it['id']; ?>">
                                        <button type="button" class="qty-btn" onclick="updateQty(<?php echo $it['id']; ?>, 1)"><i class="bi bi-plus"></i></button>
                                    </div>
                                </div>

                                <div class="cart-item-total d-none d-md-block" id="total-<?php echo $it['id']; ?>">
                                    <?php echo number_format($it["subtotal"], 0, ",", "."); ?> đ
                                </div>

                                <div style="width: 50px; text-align: right;">
                                    <button type="button" class="btn p-0 remove-btn" onclick="removeItem(<?php echo $it['id']; ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </form>
                </div>
                
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <a href="/weblaptop/index.php" class="text-decoration-none text-muted small"><i class="bi bi-arrow-left me-1"></i> Tiếp tục mua sắm</a>
                    <button type="button" class="btn btn-link text-danger btn-sm text-decoration-none" onclick="clearCart()"><i class="bi bi-trash me-1"></i> Xóa tất cả</button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="cart-summary">
                    <div class="summary-title">ĐƠN HÀNG</div>
                    
                    <div class="voucher-box">
                        <div class="small fw-bold mb-2"><i class="bi bi-ticket-perforated me-2"></i> GrowTech Voucher</div>
                        <?php if (isset($_SESSION["voucher"])): ?>
                            <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded border border-danger">
                                <span class="text-danger fw-bold small"><?php echo $_SESSION["voucher"]["code"]; ?></span>
                                <form method="post" class="m-0">
                                    <button type="submit" name="remove_voucher" class="btn btn-sm text-muted p-0"><i class="bi bi-x-circle"></i></button>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="post" class="voucher-input-group">
                                <input type="text" name="voucher_code" class="form-control form-control-sm" placeholder="Nhập mã giảm giá">
                                <button type="submit" name="apply_voucher" class="btn btn-danger btn-sm px-3">Áp dụng</button>
                            </form>
                            <div class="mt-2">
                                <button class="btn btn-link btn-sm p-0 text-decoration-none small" type="button" data-bs-toggle="modal" data-bs-target="#voucherModal">
                                    Xem danh sách voucher
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span id="summary-subtotal"><?php echo number_format($subtotal, 0, ",", "."); ?> đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Giảm giá sản phẩm:</span>
                        <span class="text-success" id="summary-discount">- <?php echo number_format($discount, 0, ",", "."); ?> đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span id="summary-shipping"><?php echo number_format($shipping_fee, 0, ",", "."); ?> đ</span>
                    </div>
                    <div class="summary-row" id="shipping-discount-row" style="<?php echo $shipping_discount > 0 ? '' : 'display: none;'; ?>">
                        <span>Giảm giá vận chuyển:</span>
                        <span class="text-success" id="summary-shipping-discount">- <?php echo number_format($shipping_discount, 0, ",", "."); ?> đ</span>
                    </div>
                    
                    <div class="summary-total">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Tổng cộng:</span>
                            <span id="summary-total"><?php echo number_format($total, 0, ",", "."); ?> đ</span>
                        </div>
                        <div class="small text-muted fw-normal mt-1" style="font-size: 12px;">(Đã bao gồm VAT nếu có)</div>
                    </div>

                    <a href="/weblaptop/checkout.php" class="btn-checkout">TIẾN HÀNH ĐẶT HÀNG</a>
                    
                    <div class="mt-4 text-center">
                        <img src="https://frontend.tikicdn.com/_desktop-next/static/img/icons/checkout/icon-payment-method-viettelpay.png" height="20" class="me-2">
                        <img src="https://frontend.tikicdn.com/_desktop-next/static/img/icons/checkout/icon-payment-method-momo.png" height="20" class="me-2">
                        <img src="https://frontend.tikicdn.com/_desktop-next/static/img/icons/checkout/icon-payment-method-vnpay.png" height="20">
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Voucher Modal -->
<div class="modal fade" id="voucherModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger">Chọn Voucher Lì Xì</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php
                $available_vouchers = $pdo->query("SELECT * FROM vouchers WHERE is_active = 1 AND (start_date <= NOW() OR start_date IS NULL) AND (end_date >= NOW() OR end_date IS NULL) AND (usage_limit IS NULL OR usage_count < usage_limit) ORDER BY end_date ASC")->fetchAll();
                if (empty($available_vouchers)):
                ?>
                    <div class="text-center py-4">
                        <i class="bi bi-ticket-perforated text-muted fs-1"></i>
                        <p class="text-muted mt-2">Hiện chưa có voucher nào khả dụng.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($available_vouchers as $v): 
                        $is_eligible = $subtotal >= $v['min_spend'];
                    ?>
                        <div class="card mb-3 border-0 shadow-sm <?php echo !$is_eligible ? 'opacity-50' : ''; ?>" style="border-radius: 15px; background: #fff8f8;">
                            <div class="card-body d-flex align-items-center p-3">
                                <div class="bg-danger text-white p-3 rounded-3 me-3 text-center" style="min-width: 80px;">
                                    <div class="small">GIẢM</div>
                                    <div class="fw-bold fs-5"><?php echo $v['discount_type'] == 'percentage' ? $v['discount_value'].'%' : number_format($v['discount_value']/1000, 0).'K'; ?></div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark"><?php echo $v['code']; ?></div>
                                    <div class="small text-muted">Đơn tối thiểu <?php echo number_format($v['min_spend'], 0, ',', '.'); ?>đ</div>
                                    <div class="text-danger" style="font-size: 11px;">Hết hạn: <?php echo date('d/m/Y', strtotime($v['end_date'])); ?></div>
                                </div>
                                <?php if ($is_eligible): ?>
                                    <form method="post" class="m-0">
                                        <input type="hidden" name="voucher_code" value="<?php echo $v['code']; ?>">
                                        <button type="submit" name="apply_voucher" class="btn btn-danger btn-sm rounded-pill px-3">Dùng</button>
                                    </form>
                                <?php else: ?>
                                    <div class="text-muted small fw-bold">Chưa đủ ĐK</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
async function updateQty(id, delta) {
    const input = document.getElementById("qty-" + id);
    const mobileInput = document.getElementById("qty-mobile-" + id);
    let currentQty = parseInt(input.value);
    let newQty = currentQty + delta;
    
    if (newQty < 1) return;
    
    // Disable buttons during update
    const buttons = document.querySelectorAll(`#cart-item-${id} .qty-btn`);
    buttons.forEach(btn => btn.disabled = true);
    
    try {
        const response = await fetch('cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&id=${id}&qty=${newQty}`
        });
        const data = await response.json();
        
        if (data.success) {
            // Update UI
            input.value = newQty;
            if (mobileInput) mobileInput.value = newQty;
            
            document.getElementById(`total-${id}`).innerText = data.item_total;
            document.getElementById('summary-subtotal').innerText = data.subtotal;
            document.getElementById('summary-discount').innerText = '- ' + data.discount;
            
            const shipDiscountEl = document.getElementById('summary-shipping-discount');
            const shipDiscountRow = document.getElementById('shipping-discount-row');
            if (shipDiscountEl && shipDiscountRow) {
                shipDiscountEl.innerText = '- ' + data.shipping_discount;
                shipDiscountRow.style.display = (parseFloat(data.shipping_discount.replace(/[^\d]/g, '')) > 0) ? '' : 'none';
            }
            
            document.getElementById('summary-total').innerText = data.total;
            
            const badge = document.querySelector('.cart-badge');
            if (badge) badge.innerText = data.cart_count;
        } else {
            alert(data.message);
            if (data.max_stock) {
                input.value = data.max_stock;
                if (mobileInput) mobileInput.value = data.max_stock;
            }
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
    } finally {
        buttons.forEach(btn => btn.disabled = false);
    }
}

async function removeItem(id) {
    if (!confirm('Xóa sản phẩm này khỏi giỏ hàng?')) return;
    
    const itemRow = document.getElementById(`cart-item-${id}`);
    itemRow.style.opacity = '0.5';
    
    try {
        const response = await fetch('cart_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove&id=${id}`
        });
        const data = await response.json();
        if (data.success) {
            if (data.cart_empty) {
                location.reload();
            } else {
                itemRow.remove();
                document.getElementById('summary-subtotal').innerText = data.subtotal;
                document.getElementById('summary-discount').innerText = '- ' + data.discount;
                
                const shipDiscountEl = document.getElementById('summary-shipping-discount');
                const shipDiscountRow = document.getElementById('shipping-discount-row');
                if (shipDiscountEl && shipDiscountRow) {
                    shipDiscountEl.innerText = '- ' + data.shipping_discount;
                    shipDiscountRow.style.display = (parseFloat(data.shipping_discount.replace(/[^\d]/g, '')) > 0) ? '' : 'none';
                }
                
                document.getElementById('summary-total').innerText = data.total;
                
                const badge = document.querySelector('.cart-badge');
                if (badge) badge.innerText = data.cart_count;
            }
        }
    } catch (error) {
        console.error('Error removing item:', error);
        itemRow.style.opacity = '1';
    }
}

function clearCart() {
    if (!confirm('Xóa toàn bộ giỏ hàng?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'clear';
    input.value = '1';
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
