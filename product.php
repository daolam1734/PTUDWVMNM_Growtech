<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/functions.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
// Improved query to get Brand name
$stmt = $pdo->prepare("SELECT p.*, b.name as brand_name FROM products p LEFT JOIN brands b ON p.brand_id = b.id WHERE p.id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

$specs = getProductSpecs($id);
$images = getProductImages($id);

require_once __DIR__ . "/includes/header.php";

if (!$product) {
    echo "<div class='container py-5 text-center'>
            <div class='alert alert-danger shadow-sm border-0 rounded-4 p-5'>
                <i class='bi bi-exclamation-triangle-fill fs-1 d-block mb-3'></i>
                <h3 class='fw-bold'>Không tìm thấy sản phẩm</h3>
                <p class='text-muted mb-4'>Sản phẩm này có thể đã bị xóa hoặc không còn tồn tại trên hệ thống.</p>
                <a href='index.php' class='btn btn-danger px-4 rounded-pill'>Quay lại trang chủ</a>
            </div>
          </div>";
    require_once __DIR__ . "/includes/footer.php";
    exit;
}
?>

<style>
    :root {
        --primary-red: #C62222;
        --secondary-gold: #FFD700;
        --bg-gray: #f8f9fa;
        --text-dark: #212529;
    }
    
    body { background-color: #f4f6f8; color: var(--text-dark); }

    /* Breadcrumb */
    .breadcrumb-nav { background: #fff; border-bottom: 1px solid #eee; padding: 12px 0; margin-bottom: 25px; }
    .breadcrumb { margin-bottom: 0; font-size: 14px; }
    .breadcrumb-item a { color: #6c757d; text-decoration: none; }
    .breadcrumb-item a:hover { color: var(--primary-red); }

    /* Main Container */
    .product-main-container { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 25px; margin-bottom: 30px; }

    /* Gallery */
    .gallery-wrapper { position: sticky; top: 100px; }
    .main-image-box { 
        width: 100%; 
        aspect-ratio: 1/1; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        border: 1px solid #f0f0f0; 
        border-radius: 8px; 
        margin-bottom: 15px;
        background: #fff;
        padding: 20px;
    }
    .main-image-box img { max-width: 100%; max-height: 100%; object-fit: contain; }
    
    .thumbnail-list { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 5px; }
    .thumbnail-item { 
        width: 70px; 
        height: 70px; 
        border: 2px solid #eee; 
        border-radius: 6px; 
        cursor: pointer; 
        padding: 5px; 
        background: #fff;
        transition: all 0.2s;
    }
    .thumbnail-item.active, .thumbnail-item:hover { border-color: var(--primary-red); }
    .thumbnail-item img { width: 100%; height: 100%; object-fit: contain; }

    /* Product Info */
    .product-brand { color: #0066cc; font-weight: 600; font-size: 14px; text-transform: uppercase; margin-bottom: 8px; display: block; text-decoration: none; }
    .product-name { font-size: 24px; font-weight: 700; margin-bottom: 12px; line-height: 1.3; }
    
    .product-meta-row { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; font-size: 14px; }
    .rating-box { color: var(--secondary-gold); display: flex; align-items: center; gap: 5px; }
    .sku-box { color: #6c757d; border-left: 1px solid #ddd; padding-left: 15px; }

    .price-container { background: #fafafa; padding: 20px; border-radius: 8px; margin-bottom: 25px; }
    .price-current { font-size: 32px; font-weight: 800; color: var(--primary-red); }
    .price-old { font-size: 18px; color: #adb5bd; text-decoration: line-through; margin-left: 10px; }
    .price-discount { background: var(--primary-red); color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 700; margin-left: 10px; vertical-align: middle; }

    .buy-box { margin-bottom: 30px; }
    .qty-select { display: flex; align-items: center; margin-bottom: 20px; }
    .qty-btn { width: 36px; height: 36px; border: 1px solid #ddd; background: #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .qty-btn:hover { background: #f8f9fa; }
    .qty-input { width: 60px; height: 36px; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; border-left: none; border-right: none; text-align: center; font-weight: 700; }

    .action-btns { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .btn-buy-now { background: var(--primary-red); color: #fff; border: none; padding: 15px; font-weight: 700; border-radius: 6px; text-transform: uppercase; }
    .btn-add-cart { background: #fff; color: var(--primary-red); border: 1px solid var(--primary-red); padding: 15px; font-weight: 700; border-radius: 6px; text-transform: uppercase; }
    .btn-buy-now:hover { opacity: 0.9; }
    .btn-add-cart:hover { background: #fff5f5; }

    /* Promotions */
    .promo-box { border: 1px solid #ffeeba; background: #fffdf5; border-radius: 8px; padding: 15px; margin-bottom: 25px; }
    .promo-header { font-weight: 700; color: #856404; margin-bottom: 10px; font-size: 15px; display: flex; align-items: center; gap: 8px; }

    /* Sub Sections */
    .section-card { background: #fff; border-radius: 8px; padding: 25px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #eee; position: relative; }
    .section-title::after { content: ""; width: 60px; height: 2px; background: var(--primary-red); position: absolute; bottom: -2px; left: 0; }

    /* Spec Table */
    .spec-table { width: 100%; border-collapse: collapse; }
    .spec-table th { width: 35%; background: #f8f9fa; color: #495057; font-weight: 600; padding: 12px 15px; font-size: 14px; border: 1px solid #eee; }
    .spec-table td { padding: 12px 15px; font-size: 14px; border: 1px solid #eee; }
    .section-card .spec-table td { background: #fff; }

    /* Benefits View */
    .benefit-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 20px; }
    .benefit-item { display: flex; align-items: center; gap: 10px; font-size: 13px; color: #444; }
    .benefit-item i { color: #28a745; font-size: 16px; }

</style>

<!-- Breadcrumb -->
<div class="breadcrumb-nav">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="search.php">Laptop</a></li>
                <?php if ($product['brand_name']): ?>
                    <li class="breadcrumb-item"><a href="search.php?brand[]=<?php echo $product['brand_id']; ?>"><?php echo htmlspecialchars($product['brand_name']); ?></a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product["name"]); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="container">
    <div class="product-main-container">
        <div class="row g-4">
            <!-- Left: Gallery -->
            <div class="col-lg-5 col-md-12">
                <div class="gallery-wrapper">
                    <div class="main-image-box" id="zoomContainer">
                        <img id="mainImg" src="<?php echo htmlspecialchars(getProductImage($product["id"])); ?>" alt="<?php echo htmlspecialchars($product["name"]); ?>">
                    </div>
                    <?php if (!empty($images)): ?>
                    <div class="thumbnail-list">
                        <?php foreach ($images as $index => $img): ?>
                        <div class="thumbnail-item <?php echo $index === 0 ? 'active' : ''; ?>" onclick="updateGallery('<?php echo htmlspecialchars($img['url']); ?>', this)">
                            <img src="<?php echo htmlspecialchars($img['url']); ?>" alt="Thumbnail">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="benefit-row">
                        <div class="benefit-item"><i class="bi bi-shield-check"></i> Bảo hành 24 tháng</div>
                        <div class="benefit-item"><i class="bi bi-arrow-repeat"></i> Đổi trả trong 7 ngày</div>
                        <div class="benefit-item"><i class="bi bi-truck"></i> Miễn phí vận chuyển</div>
                        <div class="benefit-item"><i class="bi bi-patch-check"></i> Hàng chính hãng 100%</div>
                    </div>
                </div>
            </div>

            <!-- Right: Basic Info -->
            <div class="col-lg-7 col-md-12">
                <div class="product-info-wrapper">
                    <?php if ($product['brand_name']): ?>
                        <a href="#" class="product-brand">Thương hiệu: <?php echo htmlspecialchars($product['brand_name']); ?></a>
                    <?php endif; ?>
                    <h1 class="product-name"><?php echo htmlspecialchars($product["name"]); ?></h1>
                    
                    <div class="product-meta-row">
                        <div class="rating-box">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>
                            <span class="ms-1 text-dark">4.5</span>
                        </div>
                        <div class="sku-box">SKU: <?php echo htmlspecialchars(($product['sku'] ?? '') ?: 'Đang cập nhật'); ?></div>
                        <div class="sku-box text-success">Tình trạng: <?php echo ($product['stock'] ?? 0) > 0 ? 'Còn hàng' : 'Hết hàng'; ?></div>
                    </div>

                    <div class="price-container">
                        <span class="price-current"><?php echo number_format($product["price"], 0, ",", "."); ?> đ</span>
                        <?php if (!empty($product['old_price'])): ?>
                            <span class="price-old"><?php echo number_format($product['old_price'], 0, ",", "."); ?> đ</span>
                            <span class="price-discount">-<?php echo round((($product['old_price'] - $product['price']) / $product['old_price']) * 100); ?>%</span>
                        <?php endif; ?>
                    </div>

                    <!-- Promotions -->
                    <div class="promo-box">
                        <div class="promo-header"><i class="bi bi-gift-fill text-danger"></i> KHUYẾN MÃI CHI TIẾT</div>
                        <ul class="mb-0 small ps-3">
                            <li class="mb-1">Tặng Balo Laptop Gaming cao cấp trị giá 500k.</li>
                            <li class="mb-1">Tặng Mouse không dây & Bàn di chuột.</li>
                            <li>Lì xì ngay 200k khi mua kèm Microsoft Office.</li>
                        </ul>
                    </div>

                    <div class="buy-box">
                        <div class="qty-select">
                            <span class="me-3 fw-bold">Số lượng:</span>
                            <div class="d-flex border rounded">
                                <button type="button" class="qty-btn" onclick="updateQty(-1)"><i class="bi bi-dash"></i></button>
                                <input type="number" id="qtyInput" value="1" min="1" max="<?php echo $product['stock']; ?>" class="qty-input">
                                <button type="button" class="qty-btn" onclick="updateQty(1)"><i class="bi bi-plus"></i></button>
                            </div>
                            <span class="ms-3 text-muted small">(Còn <?php echo $product['stock']; ?> sản phẩm)</span>
                        </div>

                        <div class="action-btns">
                            <button type="button" class="btn btn-buy-now" onclick="handleAddToCart(true)">
                                <strong>Mua ngay</strong>
                                <div class="small fw-normal">Giao tận nơi hoặc nhận tại cửa hàng</div>
                            </button>
                            <button type="button" class="btn btn-add-cart" id="addToCartBtn" onclick="handleAddToCart(false)">
                                <i class="bi bi-cart-plus me-2"></i> Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Sections -->
    <div class="row g-4 mb-5">
        <!-- Details & Description -->
        <div class="col-lg-8">
            <div class="section-card">
                <h2 class="section-title">Mô tả sản phẩm</h2>
                <div class="description-content lh-lg">
                    <?php echo nl2br(htmlspecialchars($product["description"])); ?>
                </div>
            </div>

            <!-- Related Products -->
            <div class="section-card mt-4">
                <h2 class="section-title">Sản phẩm tương tự</h2>
                <div class="row g-3">
                    <?php
                    $stmt_related = $pdo->prepare("SELECT DISTINCT p.* FROM products p JOIN product_categories pc ON p.id = pc.product_id WHERE pc.category_id IN (SELECT category_id FROM product_categories WHERE product_id = ?) AND p.id != ? AND p.is_active = 1 LIMIT 4");
                    $stmt_related->execute([$id, $id]);
                    $related = $stmt_related->fetchAll();
                    foreach ($related as $rp):
                    ?>
                        <div class="col-6 col-md-3">
                            <a href="product.php?id=<?php echo $rp["id"]; ?>" class="text-decoration-none">
                                <div class="card h-100 border-0 shadow-sm transition-hover">
                                    <div class="p-2 d-flex align-items-center justify-content-center" style="height: 140px;">
                                        <img src="<?php echo htmlspecialchars(getProductImage($rp["id"])); ?>" class="card-img-top" alt="" style="max-height: 100%; object-fit: contain;">
                                    </div>
                                    <div class="card-body p-2 pt-0">
                                        <div class="text-truncate-2 small fw-bold mb-1" style="height: 34px; color: #1a1a1a;"><?php echo htmlspecialchars($rp["name"]); ?></div>
                                        <div class="text-danger fw-bold small"><?php echo number_format($rp["price"], 0, ",", "."); ?> đ</div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Specifications -->
        <div class="col-lg-4">
            <div class="section-card">
                <h2 class="section-title">Thông số kỹ thuật</h2>
                <table class="spec-table">
                    <tbody>
                        <?php if ($specs): ?>
                        <tr><th>CPU</th><td><?php echo htmlspecialchars(($specs['cpu'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>RAM</th><td><?php echo htmlspecialchars(($specs['ram'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>Ổ cứng</th><td><?php echo htmlspecialchars(($specs['storage'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>Card đồ họa</th><td><?php echo htmlspecialchars(($specs['gpu'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>Màn hình</th><td><?php echo htmlspecialchars(($specs['screen'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr>
                            <th>Cổng kết nối</th>
                            <td>
                                Wi-Fi: <?php echo htmlspecialchars(($specs['wifi'] ?? '') ?: 'N/A'); ?><br>
                                Bluetooth: <?php echo htmlspecialchars(($specs['bluetooth'] ?? '') ?: 'N/A'); ?><br>
                                Cổng giao tiếp:<br>
                                <?php echo nl2br(htmlspecialchars(($specs['ports'] ?? '') ?: 'Đang cập nhật')); ?>
                            </td>
                        </tr>
                        <tr><th>Trọng lượng</th><td><?php echo htmlspecialchars(($specs['weight'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>Dung lượng Pin</th><td><?php echo htmlspecialchars(($specs['battery'] ?? '') ?: 'Đang cập nhật'); ?></td></tr>
                        <tr><th>Hệ điều hành</th><td><?php echo htmlspecialchars(($specs['os'] ?? '') ?: 'N/A'); ?></td></tr>
                        <?php else: ?>
                        <tr><td colspan="2" class="text-center py-4 text-muted">Thông số đang được cập nhật</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if ($specs): ?>
                    <button class="btn btn-outline-secondary btn-sm w-100 mt-3" data-bs-toggle="modal" data-bs-target="#specModal">Xem cấu hình chi tiết</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Full Specs Modal -->
<?php if ($specs): ?>
<div class="modal fade" id="specModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Cấu hình chi tiết</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <table class="table table-striped spec-table mb-0">
                    <tbody>
                        <tr><th>CPU</th><td><?php echo htmlspecialchars(($specs['cpu'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>RAM</th><td><?php echo htmlspecialchars(($specs['ram'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>Ổ cứng</th><td><?php echo htmlspecialchars(($specs['storage'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>Card đồ họa</th><td><?php echo htmlspecialchars(($specs['gpu'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>Màn hình</th><td><?php echo htmlspecialchars(($specs['screen'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr>
                            <th>Cổng kết nối</th>
                            <td>
                                Wi-Fi: <?php echo htmlspecialchars(($specs['wifi'] ?? '') ?: 'N/A'); ?><br>
                                Bluetooth: <?php echo htmlspecialchars(($specs['bluetooth'] ?? '') ?: 'N/A'); ?><br>
                                Cổng giao tiếp:<br>
                                <?php echo nl2br(htmlspecialchars(($specs['ports'] ?? '') ?: 'Đang cập nhật')); ?>
                            </td>
                        </tr>
                        <tr><th>Trọng lượng</th><td><?php echo htmlspecialchars(($specs['weight'] ?? '') ?: 'N/A'); ?></td></tr>
                        <tr><th>Dung lượng Pin</th><td><?php echo htmlspecialchars(($specs['battery'] ?? '') ?: 'Đang cập nhật'); ?></td></tr>
                        <tr><th>Hệ điều hành</th><td><?php echo htmlspecialchars(($specs['os'] ?? '') ?: 'N/A'); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Scripts -->
<script>
function updateGallery(src, thumb) {
    document.getElementById('mainImg').src = src;
    document.querySelectorAll('.thumbnail-item').forEach(i => i.classList.remove('active'));
    thumb.classList.add('active');
}

function updateQty(delta) {
    const input = document.getElementById('qtyInput');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > <?php echo $product['stock']; ?>) val = <?php echo $product['stock']; ?>;
    input.value = val;
}

function handleAddToCart(redirect) {
    const btn = document.getElementById('addToCartBtn');
    const qty = document.getElementById('qtyInput').value;
    const id = <?php echo $product['id']; ?>;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

    const fd = new FormData();
    fd.append('action', 'add');
    fd.append('id', id);
    fd.append('qty', qty);

    fetch('cart_api.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            // Update cart UI
            const badge = document.querySelector('.cart-badge');
            if (badge) {
                badge.innerText = data.cart_count;
                badge.classList.add('bump');
                setTimeout(() => badge.classList.remove('bump'), 400);
            }
            
            // Sync header cart dropdown
            const cartDropdown = document.getElementById('header-cart-dropdown');
            if (cartDropdown && data.dropdown_html) {
                cartDropdown.innerHTML = data.dropdown_html;
            }

            if (redirect) {
                window.location.href = 'cart.php';
            } else {
                alert('Đã thêm sản phẩm vào giỏ hàng!');
            }
        } else {
            alert(data.message);
        }
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cart-plus me-2"></i> Thêm vào giỏ';
    });
}
</script>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
