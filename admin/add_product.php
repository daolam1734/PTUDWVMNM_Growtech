<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands")->fetchAll();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $sku = $_POST['sku'] ?? '';
    $slug = $_POST['slug'] ?: slugify($name);
    $brand_id = $_POST['brand_id'] ?: null;
    $category_id = $_POST['category_id'] ?: null;
    $short_desc = $_POST['short_description'] ?? '';
    $desc = $_POST['description'] ?? '';
    $price = (float)$_POST['price'];
    $sale_price = $_POST['sale_price'] ? (float)$_POST['sale_price'] : null;
    $stock = (int)$_POST['stock'];
    $image = $_POST['image'] ?? '';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Specs
    $cpu = $_POST['cpu'] ?? '';
    $ram = $_POST['ram'] ?? '';
    $storage = $_POST['storage'] ?? '';
    $gpu = $_POST['gpu'] ?? '';
    $screen = $_POST['screen'] ?? '';
    $os = $_POST['os'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $battery = $_POST['battery'] ?? '';
    $ports = $_POST['ports'] ?? '';
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO products (sku, name, slug, brand_id, category_id, short_description, description, price, sale_price, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $name, $slug, $brand_id, $category_id, $short_desc, $desc, $price, $sale_price, $stock, $is_active]);
        $product_id = $pdo->lastInsertId();
        
        if ($image) {
            $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, url, position) VALUES (?, ?, 0)");
            $stmt_img->execute([$product_id, $image]);
        }

        // Insert specs
        $stmt_specs = $pdo->prepare("INSERT INTO product_specifications (product_id, cpu, ram, storage, gpu, screen, os, weight, battery, ports) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_specs->execute([$product_id, $cpu, $ram, $storage, $gpu, $screen, $os, $weight, $battery, $ports]);

        $pdo->commit();
        set_flash("success", "Thêm sản phẩm thành công.");
        header('Location: products.php'); exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Lỗi: " . $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="products.php" class="text-decoration-none">Sản phẩm</a></li>
                    <li class="breadcrumb-item active">Thêm sản phẩm mới</li>
                </ol>
            </nav>
            <h4 class="fw-bold">Thêm sản phẩm mới</h4>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Basic Info -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-info-circle me-2 text-primary"></i>Thông tin cơ bản</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tên sản phẩm</label>
                                <input class="form-control" name="name" placeholder="Nhập tên sản phẩm..." required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">SKU</label>
                                        <input class="form-control" name="sku" placeholder="Ví dụ: LAP-001">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Slug (để trống sẽ tự tạo)</label>
                                        <input class="form-control" name="slug" placeholder="laptop-dell-xps-13">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Mô tả ngắn</label>
                                <input class="form-control" name="short_description" placeholder="Tóm tắt đặc điểm nổi bật...">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Mô tả chi tiết</label>
                                <textarea class="form-control" name="description" rows="8" placeholder="Mô tả chi tiết về sản phẩm..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Specifications -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-cpu me-2 text-primary"></i>Thông số kỹ thuật</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">CPU</label>
                                        <input class="form-control" name="cpu" placeholder="Intel Core i5-12500H">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">RAM</label>
                                        <input class="form-control" name="ram" placeholder="16GB DDR4 3200MHz">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Ổ cứng</label>
                                        <input class="form-control" name="storage" placeholder="512GB SSD NVMe">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Card đồ họa</label>
                                        <input class="form-control" name="gpu" placeholder="RTX 3050 4GB">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Màn hình</label>
                                        <input class="form-control" name="screen" placeholder="15.6 inch FHD 144Hz">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Hệ điều hành</label>
                                        <input class="form-control" name="os" placeholder="Windows 11 Home">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Trọng lượng</label>
                                        <input class="form-control" name="weight" placeholder="2.1 kg">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Pin</label>
                                        <input class="form-control" name="battery" placeholder="3-cell, 52.5 Wh">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-0">
                                        <label class="form-label small fw-bold">Cổng kết nối</label>
                                        <input class="form-control" name="ports" placeholder="1x USB-C, 3x USB-A, 1x HDMI">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Organization -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-tags me-2 text-primary"></i>Phân loại & Trạng thái</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Danh mục</label>
                                <select class="form-select" name="category_id">
                                    <option value="">-- Chọn danh mục --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Thương hiệu</label>
                                <select class="form-select" name="brand_id">
                                    <option value="">-- Chọn thương hiệu --</option>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-check form-switch mt-4">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" checked>
                                <label class="form-check-label fw-bold" for="is_active">Hiển thị sản phẩm</label>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Inventory -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-currency-dollar me-2 text-primary"></i>Giá & Kho hàng</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Giá bán (đ)</label>
                                <input type="number" step="0.01" class="form-control" name="price" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Giá khuyến mãi (đ)</label>
                                <input type="number" step="0.01" class="form-control" name="sale_price">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Số lượng kho</label>
                                <input type="number" class="form-control" name="stock" required>
                            </div>
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-image me-2 text-primary"></i>Hình ảnh</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-0">
                                <label class="form-label small fw-bold">URL ảnh chính</label>
                                <input class="form-control" name="image" placeholder="https://...">
                                <div class="form-text x-small mt-2">Sử dụng URL ảnh từ thư viện hoặc link ngoài.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mb-2">
                                <i class="bi bi-plus-lg me-2"></i>Thêm sản phẩm
                            </button>
                            <a href="products.php" class="btn btn-white border w-100 fw-bold py-2">Hủy bỏ</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
