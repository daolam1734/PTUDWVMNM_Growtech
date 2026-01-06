<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/functions.php";

$id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
$product = getProduct($id);
$specs = getProductSpecs($id);
$images = getProductImages($id);

require_once __DIR__ . "/includes/header.php";

if (!$product) {
    echo "<div class='alert alert-danger'>Không tìm thấy sản phẩm.</div>";
    require_once __DIR__ . "/includes/footer.php";
    exit;
}
?>

<style>
    .product-detail-card { background: #fff; padding: 30px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #eee; }
    .product-price { font-size: 32px; color: #d32f2f; background: #fff5f5; padding: 15px 20px; margin: 20px 0; border-radius: 12px; font-weight: bold; }
    .main-img-container { height: 400px; display: flex; align-items: center; justify-content: center; background: #fff; border-radius: 12px; overflow: hidden; margin-bottom: 15px; border: 1px solid #f0f0f0; }
    .main-img-container img { max-height: 100%; max-width: 100%; object-fit: contain; }
    .thumb-img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid transparent; transition: all 0.2s; }
    .thumb-img:hover, .thumb-img.active { border-color: #d32f2f; }
    .spec-table { border-radius: 12px; overflow: hidden; border: 1px solid #eee; }
    .spec-table th { width: 35%; background: #f8f9fa; color: #555; font-weight: 600; padding: 12px 20px; }
    .spec-table td { color: #333; padding: 12px 20px; }
    .btn-add-cart { border: 2px solid #d32f2f; color: #d32f2f; font-weight: bold; transition: all 0.3s; border-radius: 10px; }
    .btn-add-cart:hover { background: #d32f2f; color: #fff; }
    .btn-buy-now { background-color: #d32f2f; border: none; font-weight: bold; transition: all 0.3s; border-radius: 10px; }
    .btn-buy-now:hover { background-color: #b71c1c; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(211, 47, 47, 0.3); }
    .badge-stock { padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
</style>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/weblaptop" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item active text-dark fw-bold"><?php echo htmlspecialchars($product["name"]); ?></li>
        </ol>
    </nav>

    <div class="product-detail-card mb-5">
        <div class="row g-4">
            <div class="col-md-5">
                <div class="main-img-container">
                    <img id="mainImage" src="<?php echo htmlspecialchars(getProductImage($product["id"])); ?>" alt="<?php echo htmlspecialchars($product["name"]); ?>">
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <?php if (!empty($images)): ?>
                        <?php foreach ($images as $index => $img): ?>
                            <img src="<?php echo htmlspecialchars($img['url']); ?>" 
                                 class="thumb-img <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 onclick="changeMainImage(this.src, this)"
                                 alt="Thumbnail <?php echo $index + 1; ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-7 ps-md-5">
                <div class="mb-2">
                    <span class="badge bg-light text-primary border me-2 small">Mới 100%</span>
                    <span class="badge bg-light text-success border small">Chính hãng</span>
                </div>
                <h1 class="fw-bold fs-3 mb-3"><?php echo htmlspecialchars($product["name"]); ?></h1>
                
                <div class="d-flex align-items-center mb-3">
                    <div class="text-warning me-3">
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-fill"></i>
                        <i class="bi bi-star-half"></i>
                        <span class="ms-1 text-dark fw-bold">4.5</span>
                    </div>
                    <span class="text-muted border-start ps-3">120 Đánh giá</span>
                    <span class="text-muted border-start ps-3 ms-3">500 Đã bán</span>
                </div>

                <div class="product-price d-flex align-items-center">
                    <?php echo number_format($product["price"], 0, ",", "."); ?> đ
                    <?php if ($product['old_price'] ?? false): ?>
                        <span class="text-muted text-decoration-line-through fs-6 ms-3"><?php echo number_format($product['old_price'], 0, ",", "."); ?> đ</span>
                    <?php endif; ?>
                </div>

                <div class="card bg-light border-0 rounded-4 mb-4">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center text-danger fw-bold mb-2">
                            <i class="bi bi-truck me-2"></i> Chính sách vận chuyển
                        </div>
                        <ul class="list-unstyled mb-0 small text-muted">
                            <li class="mb-1"><i class="bi bi-check2-circle text-success me-2"></i>Miễn phí vận chuyển cho đơn hàng trên 10tr</li>
                            <li><i class="bi bi-check2-circle text-success me-2"></i>Giao hàng hỏa tốc trong 2h tại TP.HCM & Hà Nội</li>
                        </ul>
                    </div>
                </div>

                <form id="add-to-cart-form">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    <div class="d-flex align-items-center mb-4">
                        <label class="text-muted me-4">Số lượng:</label>
                        <div class="input-group w-auto me-4 border rounded-3 overflow-hidden" style="width: 130px !important;">
                            <button type="button" class="btn btn-light border-0" onclick="changeQty(-1)">-</button>
                            <input type="number" name="qty" id="product-qty" value="1" min="1" max="<?php echo (int)$product["stock"]; ?>" class="form-control text-center border-0" style="width: 60px;">
                            <button type="button" class="btn btn-light border-0" onclick="changeQty(1)">+</button>
                        </div>
                        <span class="text-muted small"><?php echo (int)$product["stock"]; ?> sản phẩm có sẵn</span>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <button type="button" id="btn-add-cart" class="btn btn-outline-danger btn-add-cart w-100 py-3">
                                <i class="bi bi-cart-plus me-2"></i> Thêm vào giỏ hàng
                            </button>
                        </div>
                        <div class="col-sm-6">
                            <button type="button" id="btn-buy-now" class="btn btn-danger btn-buy-now w-100 py-3">
                                Mua ngay
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Collapsible Sections or Tabs -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="product-detail-card mb-4">
                <h5 class="fw-bold mb-4"><i class="bi bi-body-text me-2 text-danger"></i>Mô tả sản phẩm</h5>
                <div class="product-description text-muted">
                    <?php echo nl2br(htmlspecialchars($product["description"])); ?>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="product-detail-card">
                <h5 class="fw-bold mb-4"><i class="bi bi-cpu me-2 text-danger"></i>Thông số kỹ thuật</h5>
                <table class="table table-borderless spec-table mb-0">
                    <tbody>
                        <?php if ($specs): ?>
                            <tr><th>CPU</th><td><?php echo htmlspecialchars($specs['cpu'] ?: 'Đang cập nhật'); ?></td></tr>
                            <tr><th>RAM</th><td><?php echo htmlspecialchars($specs['ram'] ?: 'Đang cập nhật'); ?></td></tr>
                            <tr><th>Ổ cứng</th><td><?php echo htmlspecialchars($specs['storage'] ?: 'Đang cập nhật'); ?></td></tr>
                            <tr><th>VGA</th><td><?php echo htmlspecialchars($specs['gpu'] ?: 'Đang cập nhật'); ?></td></tr>
                            <tr><th>Màn hình</th><td><?php echo htmlspecialchars($specs['screen'] ?: 'Đang cập nhật'); ?></td></tr>
                            <tr><th>OS</th><td><?php echo htmlspecialchars($specs['os'] ?: 'Đang cập nhật'); ?></td></tr>
                            <tr><th>Trọng lượng</th><td><?php echo htmlspecialchars($specs['weight'] ?: 'Đang cập nhật'); ?></td></tr>
                            <tr><th>Pin</th><td><?php echo htmlspecialchars($specs['battery'] ?: 'Đang cập nhật'); ?></td></tr>
                        <?php else: ?>
                            <tr><td colspan="2" class="text-center py-4">Chưa có thông số kỹ thuật.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function changeMainImage(src, thumb) {
    document.getElementById('mainImage').src = src;
    document.querySelectorAll('.thumb-img').forEach(img => img.classList.remove('active'));
    thumb.classList.add('active');
}
</script>

    <script>
    function changeQty(delta) {
        const input = document.getElementById('product-qty');
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        if (val > <?php echo (int)$product["stock"]; ?>) val = <?php echo (int)$product["stock"]; ?>;
        input.value = val;
    }

    document.getElementById('btn-add-cart').addEventListener('click', function() {
        addToCart(false);
    });

    document.getElementById('btn-buy-now').addEventListener('click', function() {
        addToCart(true);
    });

    function addToCart(redirect) {
        const btnAdd = document.getElementById('btn-add-cart');
        const btnBuy = document.getElementById('btn-buy-now');
        const qty = document.getElementById('product-qty').value;
        const id = <?php echo $product['id']; ?>;

        btnAdd.disabled = true;
        btnBuy.disabled = true;

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('id', id);
        formData.append('qty', qty);

        fetch('cart_api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update cart count in header if exists
                const cartBadge = document.querySelector('.nav-link .badge');
                if (cartBadge) cartBadge.innerText = data.cart_count;

                if (redirect) {
                    window.location.href = 'cart.php';
                } else {
                    alert(data.message);
                }
            } else {
                alert(data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Có lỗi xảy ra, vui lòng thử lại.');
        })
        .finally(() => {
            btnAdd.disabled = false;
            btnBuy.disabled = false;
        });
    }
    </script>

    <div class="row">
        <div class="col-md-9">
            <div class="product-detail-card mb-4">
                <h5 class="fw-bold mb-4">CHI TIẾT SẢN PHẨM</h5>
                <table class="table spec-table">
                    <tbody>
                        <?php if ($specs): ?>
                            <tr><th>CPU</th><td><?php echo htmlspecialchars($specs["cpu"]); ?></td></tr>
                            <tr><th>RAM</th><td><?php echo htmlspecialchars($specs["ram"]); ?></td></tr>
                            <tr><th>Ổ cứng</th><td><?php echo htmlspecialchars($specs["storage"]); ?></td></tr>
                            <tr><th>Card đồ họa</th><td><?php echo htmlspecialchars($specs["gpu"]); ?></td></tr>
                            <tr><th>Màn hình</th><td><?php echo htmlspecialchars($specs["screen"]); ?></td></tr>
                            <tr><th>Hệ điều hành</th><td><?php echo htmlspecialchars($specs["os"]); ?></td></tr>
                            <tr><th>Trọng lượng</th><td><?php echo htmlspecialchars($specs["weight"]); ?></td></tr>
                        <?php else: ?>
                            <tr><td colspan="2" class="text-muted">Đang cập nhật thông số...</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <h5 class="fw-bold mt-5 mb-4">MÔ TẢ SẢN PHẨM</h5>
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product["description"])); ?>
                </div>
            </div>

            <!-- Related Products -->
            <h5 class="fw-bold mb-3 mt-5">SẢN PHẨM TƯƠNG TỰ</h5>
            <div class="row g-2">
                <?php
                $stmt_related = $pdo->prepare("
                    SELECT p.*, pi.url as image_url 
                    FROM products p 
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0
                    WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
                    LIMIT 6
                ");
                $stmt_related->execute([$product['category_id'], $product['id']]);
                $related = $stmt_related->fetchAll();
                
                foreach ($related as $rp):
                    $rimg = $rp["image_url"];
                    if (!$rimg || (strpos($rimg, 'http') !== 0 && strpos($rimg, '/') !== 0)) {
                        if ($rimg && (preg_match('/^\d+x\d+/', $rimg) || strpos($rimg, 'text=') !== false)) {
                            $rimg = 'https://placehold.co/' . $rimg;
                        } else {
                            $rimg = 'https://placehold.co/600x400?text=No+Image';
                        }
                    }
                ?>
                    <div class="col-6 col-md-4 col-lg-2">
                        <a href="product.php?id=<?php echo $rp["id"]; ?>" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm product-grid-item">
                                <img src="<?php echo htmlspecialchars($rimg); ?>" class="card-img-top" alt="" style="aspect-ratio: 1/1; object-fit: cover;">
                                <div class="card-body p-2">
                                    <div class="text-truncate-2 small mb-1" style="height: 32px; color: #333;"><?php echo htmlspecialchars($rp["name"]); ?></div>
                                    <div class="text-danger fw-bold small"><?php echo number_format($rp["price"], 0, ",", "."); ?> đ</div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">Ưu đãi đặc biệt</div>
                <div class="card-body">
                    <div class="d-flex gap-2 mb-3">
                        <span class="sparkle-effect text-danger"></span>
                        <small>Tặng Balo Laptop cao cấp</small>
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <span class="sparkle-effect text-danger"></span>
                        <small>Tặng Chuột không dây</small>
                    </div>
                    <div class="d-flex gap-2">
                        <span class="sparkle-effect text-danger"></span>
                        <small>Voucher giảm 500k cho lần mua sau</small>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold">Chính sách bán hàng</div>
                <div class="card-body">
                    <div class="d-flex gap-2 mb-3">
                        <i class="bi bi-truck text-danger"></i>
                        <small>Giao hàng toàn quốc</small>
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <i class="bi bi-shield-check text-danger"></i>
                        <small>Bảo hành chính hãng</small>
                    </div>
                    <div class="d-flex gap-2">
                        <i class="bi bi-arrow-repeat text-danger"></i>
                        <small>Đổi trả trong 7 ngày</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
