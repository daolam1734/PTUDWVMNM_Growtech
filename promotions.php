<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/functions.php";
require_once __DIR__ . "/config/database.php";

require_once __DIR__ . "/includes/header.php";

// Fetch Flash Sale info
$flash_sale_end = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'flash_sale_end'")->fetchColumn() ?: date('Y-m-d 23:59:59');
$flash_sale_products = getFlashSaleProducts(20);

// Fetch Active Vouchers
$vouchers = $pdo->query("SELECT * FROM vouchers WHERE is_active = 1 AND (start_date <= NOW() OR start_date IS NULL) AND (end_date >= NOW() OR end_date IS NULL) AND (usage_limit IS NULL OR usage_count < usage_limit) ORDER BY created_at DESC")->fetchAll();

// Fetch other discounted products (not in flash sale)
$discounted_products = $pdo->query("SELECT p.*, pi.url as image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 WHERE p.sale_price IS NOT NULL AND p.sale_price < p.price AND p.is_active = 1 LIMIT 12")->fetchAll();
?>

<style>
    .promo-banner {
        background: linear-gradient(135deg, #d32f2f, #ffc107);
        border-radius: 15px;
        padding: 40px;
        color: #fff;
        margin-bottom: 40px;
        position: relative;
        overflow: hidden;
    }
    .promo-banner::after {
        content: '🧧';
        position: absolute;
        right: -20px;
        bottom: -20px;
        font-size: 150px;
        opacity: 0.2;
        transform: rotate(-15deg);
    }
    .section-title {
        font-weight: 800;
        color: var(--tet-red);
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title::after {
        content: '';
        flex-grow: 1;
        height: 2px;
        background: #eee;
    }
    
    /* Voucher Card */
    .voucher-card {
        background: #fff;
        border-radius: 12px;
        border: 1px dashed var(--tet-red);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        position: relative;
        transition: all 0.3s;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .voucher-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }
    .voucher-card::before, .voucher-card::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background: #f8f9fa;
        border-radius: 50%;
        left: -10px;
    }
    .voucher-card::before { top: 20%; }
    .voucher-card::after { bottom: 20%; }
    
    .voucher-icon {
        width: 60px;
        height: 60px;
        background: #fff5f5;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--tet-red);
        font-size: 24px;
    }
    .voucher-info { flex: 1; }
    .voucher-code {
        font-weight: 800;
        color: var(--tet-red);
        font-size: 18px;
        letter-spacing: 1px;
    }
    .copy-btn {
        background: var(--tet-red);
        color: #fff;
        border: none;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    /* Flash Sale */
    .flash-sale-header {
        background: #d32f2f;
        color: #fff;
        padding: 15px 25px;
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .countdown-box {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .time-unit {
        background: #000;
        color: #fff;
        padding: 5px 8px;
        border-radius: 4px;
        font-weight: 700;
        min-width: 35px;
        text-align: center;
    }
</style>

<div class="container py-4">
    <!-- Banner -->
    <div class="promo-banner shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="fw-bold mb-3">SIÊU KHUYẾN MÃI TẾT 2026</h1>
                <p class="fs-5 mb-4">Hàng ngàn ưu đãi hấp dẫn đang chờ đón bạn. Mua sắm ngay để nhận lộc đầu xuân!</p>
                <a href="#vouchers" class="btn btn-light text-danger fw-bold px-4 py-2 rounded-pill">SĂN VOUCHER NGAY</a>
            </div>
        </div>
    </div>

    <!-- Vouchers Section -->
    <div id="vouchers" class="mb-5">
        <h3 class="section-title"><i class="bi bi-ticket-perforated"></i> MÃ GIẢM GIÁ ĐANG HOT</h3>
        <div class="row g-4">
            <?php foreach ($vouchers as $v): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="voucher-card">
                        <div class="voucher-icon">
                            <i class="bi <?php echo $v['discount_type'] === 'shipping' ? 'bi-truck' : 'bi-lightning-charge'; ?>"></i>
                        </div>
                        <div class="voucher-info">
                            <div class="voucher-code"><?php echo htmlspecialchars($v['code']); ?></div>
                            <div class="small text-muted">
                                <?php 
                                if ($v['discount_type'] === 'percentage') echo "Giảm " . (int)$v['discount_value'] . "%";
                                elseif ($v['discount_type'] === 'fixed') echo "Giảm " . number_format($v['discount_value'], 0, ',', '.') . "đ";
                                else echo "Miễn phí vận chuyển";
                                ?>
                                <br>Đơn tối thiểu: <?php echo number_format($v['min_spend'], 0, ',', '.'); ?>đ
                            </div>
                        </div>
                        <button class="copy-btn" onclick="copyCode('<?php echo $v['code']; ?>')">SAO CHÉP</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Flash Sale Section -->
    <?php if (!empty($flash_sale_products)): ?>
    <div class="mb-5">
        <div class="flash-sale-header">
            <div class="d-flex align-items-center gap-3">
                <h3 class="mb-0 fw-bold"><i class="bi bi-lightning-fill text-warning"></i> FLASH SALE</h3>
                <div class="countdown-box" id="promo-countdown" data-end="<?php echo $flash_sale_end; ?>">
                    <span class="small">Kết thúc sau:</span>
                    <div class="time-unit" id="days">00</div>
                    <div class="time-unit" id="hours">00</div>
                    <div class="time-unit" id="mins">00</div>
                    <div class="time-unit" id="secs">00</div>
                </div>
            </div>
            <a href="/weblaptop/index.php" class="text-white text-decoration-none small">Xem tất cả <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="bg-white p-4 shadow-sm rounded-bottom">
            <div class="row g-3">
                <?php foreach ($flash_sale_products as $p): ?>
                    <div class="col-6 col-md-3 col-lg-2">
                        <a href="product.php?id=<?php echo $p['id']; ?>" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm product-grid-item">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars(getProductImage($p['id'])); ?>" class="card-img-top" alt="">
                                    <div class="position-absolute top-0 start-0 bg-danger text-white px-2 py-1 small fw-bold">
                                        -<?php echo round((1 - $p['sale_price'] / $p['price']) * 100); ?>%
                                    </div>
                                </div>
                                <div class="card-body p-2">
                                    <div class="text-truncate-2 small mb-1" style="height: 32px; color: #333;"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div class="text-danger fw-bold"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?> đ</div>
                                    <div class="text-muted small text-decoration-line-through"><?php echo number_format($p['price'], 0, ',', '.'); ?> đ</div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Other Discounts -->
    <div class="mb-5">
        <h3 class="section-title"><i class="bi bi-stars"></i> SẢN PHẨM GIẢM GIÁ KHÁC</h3>
        <div class="row g-3">
            <?php foreach ($discounted_products as $p): ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <a href="product.php?id=<?php echo $p['id']; ?>" class="text-decoration-none">
                        <div class="card h-100 border-0 shadow-sm product-grid-item">
                            <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://placehold.co/600x400?text=No+Image'); ?>" class="card-img-top" alt="">
                            <div class="card-body p-2">
                                <div class="text-truncate-2 small mb-1" style="height: 32px; color: #333;"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="text-danger fw-bold"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?> đ</div>
                                <div class="text-muted small text-decoration-line-through"><?php echo number_format($p['price'], 0, ',', '.'); ?> đ</div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('Đã sao chép mã: ' + code);
    });
}

function updateCountdown() {
    const countdownEl = document.getElementById('promo-countdown');
    if (!countdownEl) return;
    
    const endTime = new Date(countdownEl.dataset.end).getTime();
    const now = new Date().getTime();
    const diff = endTime - now;
    
    if (diff <= 0) {
        countdownEl.innerHTML = "Đã kết thúc";
        return;
    }
    
    const d = Math.floor(diff / (1000 * 60 * 60 * 24));
    const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const s = Math.floor((diff % (1000 * 60)) / 1000);
    
    document.getElementById('days').innerText = d.toString().padStart(2, '0');
    document.getElementById('hours').innerText = h.toString().padStart(2, '0');
    document.getElementById('mins').innerText = m.toString().padStart(2, '0');
    document.getElementById('secs').innerText = s.toString().padStart(2, '0');
}

setInterval(updateCountdown, 1000);
updateCountdown();
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
