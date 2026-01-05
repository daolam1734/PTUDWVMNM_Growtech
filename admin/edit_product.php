<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
$brands = $pdo->query("SELECT * FROM brands")->fetchAll();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?"); $stmt->execute([$id]); $p = $stmt->fetch();
if (!$p) { set_flash("error", "Không tìm thấy sản phẩm."); header('Location: products.php'); exit; }

// Get current image
$stmt_img = $pdo->prepare("SELECT url FROM product_images WHERE product_id = ? AND position = 0");
$stmt_img->execute([$id]);
$current_image = $stmt_img->fetchColumn() ?: '';

// Get current specs
$stmt_specs = $pdo->prepare("SELECT * FROM product_specifications WHERE product_id = ?");
$stmt_specs->execute([$id]);
$specs = $stmt_specs->fetch() ?: [];

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
        $stmt = $pdo->prepare("UPDATE products SET sku=?, name=?, slug=?, brand_id=?, category_id=?, short_description=?, description=?, price=?, sale_price=?, stock=?, is_active=? WHERE id=?");
        $stmt->execute([$sku, $name, $slug, $brand_id, $category_id, $short_desc, $desc, $price, $sale_price, $stock, $is_active, $id]);
        
        // Update or insert image
        $stmt_check = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ? AND position = 0");
        $stmt_check->execute([$id]);
        $img_id = $stmt_check->fetchColumn();
        
        if ($img_id) {
            $stmt_upd = $pdo->prepare("UPDATE product_images SET url = ? WHERE id = ?");
            $stmt_upd->execute([$image, $img_id]);
        } elseif ($image) {
            $stmt_ins = $pdo->prepare("INSERT INTO product_images (product_id, url, position) VALUES (?, ?, 0)");
            $stmt_ins->execute([$id, $image]);
        }

        // Update or insert specs
        $stmt_spec_check = $pdo->prepare("SELECT id FROM product_specifications WHERE product_id = ?");
        $stmt_spec_check->execute([$id]);
        if ($stmt_spec_check->fetch()) {
            $stmt_spec_upd = $pdo->prepare("UPDATE product_specifications SET cpu=?, ram=?, storage=?, gpu=?, screen=?, os=?, weight=?, battery=?, ports=? WHERE product_id=?");
            $stmt_spec_upd->execute([$cpu, $ram, $storage, $gpu, $screen, $os, $weight, $battery, $ports, $id]);
        } else {
            $stmt_spec_ins = $pdo->prepare("INSERT INTO product_specifications (product_id, cpu, ram, storage, gpu, screen, os, weight, battery, ports) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_spec_ins->execute([$id, $cpu, $ram, $storage, $gpu, $screen, $os, $weight, $battery, $ports]);
        }
        
        $pdo->commit();
        set_flash("success", "Cập nhật sản phẩm thành công.");
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
                    <li class="breadcrumb-item active">Sửa sản phẩm</li>
                </ol>
            </nav>
            <h4 class="fw-bold">Sửa sản phẩm: <?php echo htmlspecialchars($p['name']); ?></h4>
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
                                <input class="form-control" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">SKU</label>
                                        <input class="form-control" name="sku" value="<?php echo htmlspecialchars($p['sku']); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Slug</label>
                                        <input class="form-control" name="slug" value="<?php echo htmlspecialchars($p['slug']); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Mô tả ngắn</label>
                                <input class="form-control" name="short_description" value="<?php echo htmlspecialchars($p['short_description']); ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Mô tả chi tiết</label>
                                <textarea class="form-control" name="description" rows="8"><?php echo htmlspecialchars($p['description']); ?></textarea>
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
                                        <input class="form-control" name="cpu" value="<?php echo htmlspecialchars($specs['cpu'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">RAM</label>
                                        <input class="form-control" name="ram" value="<?php echo htmlspecialchars($specs['ram'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Ổ cứng</label>
                                        <input class="form-control" name="storage" value="<?php echo htmlspecialchars($specs['storage'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Card đồ họa</label>
                                        <input class="form-control" name="gpu" value="<?php echo htmlspecialchars($specs['gpu'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Màn hình</label>
                                        <input class="form-control" name="screen" value="<?php echo htmlspecialchars($specs['screen'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Hệ điều hành</label>
                                        <input class="form-control" name="os" value="<?php echo htmlspecialchars($specs['os'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Trọng lượng</label>
                                        <input class="form-control" name="weight" value="<?php echo htmlspecialchars($specs['weight'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Pin</label>
                                        <input class="form-control" name="battery" value="<?php echo htmlspecialchars($specs['battery'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-0">
                                        <label class="form-label small fw-bold">Cổng kết nối</label>
                                        <input class="form-control" name="ports" value="<?php echo htmlspecialchars($specs['ports'] ?? ''); ?>">
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
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $p['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Thương hiệu</label>
                                <select class="form-select" name="brand_id">
                                    <option value="">-- Chọn thương hiệu --</option>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?php echo $b['id']; ?>" <?php echo $p['brand_id'] == $b['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-check form-switch mt-4">
                                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" <?php echo $p['is_active'] ? 'checked' : ''; ?>>
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
                                <input type="number" step="0.01" class="form-control" name="price" value="<?php echo $p['price']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Giá khuyến mãi (đ)</label>
                                <input type="number" step="0.01" class="form-control" name="sale_price" value="<?php echo $p['sale_price']; ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label small fw-bold">Số lượng kho</label>
                                <input type="number" class="form-control" name="stock" value="<?php echo $p['stock']; ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold"><i class="bi bi-image me-2 text-primary"></i>Hình ảnh</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">URL ảnh chính</label>
                                <input class="form-control" name="image" value="<?php echo htmlspecialchars($current_image); ?>">
                            </div>
                            <?php if ($current_image): ?>
                                <div class="mt-2 text-center bg-light rounded-3 p-3">
                                    <img src="<?php echo htmlspecialchars($current_image); ?>" alt="Preview" class="img-fluid rounded shadow-sm" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-4">
                            <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mb-2">
                                <i class="bi bi-check-lg me-2"></i>Lưu thay đổi
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
