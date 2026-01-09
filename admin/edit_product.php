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

// Get current categories
$stmt_curr_cats = $pdo->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
$stmt_curr_cats->execute([$id]);
$current_category_ids = $stmt_curr_cats->fetchAll(PDO::FETCH_COLUMN);

// Get current image
$stmt_img = $pdo->prepare("SELECT url FROM product_images WHERE product_id = ? AND position = 0");
$stmt_img->execute([$id]);
$current_image = $stmt_img->fetchColumn() ?: '';

// Get current specs
$stmt_specs = $pdo->prepare("SELECT * FROM product_specifications WHERE product_id = ?");
$stmt_specs->execute([$id]);
$specs = $stmt_specs->fetch() ?: [];

// Handle image deletion
if (isset($_GET['delete_image_id'])) {
    $del_img_id = (int)$_GET['delete_image_id'];
    $stmt_sel = $pdo->prepare("SELECT url FROM product_images WHERE id = ? AND product_id = ?");
    $stmt_sel->execute([$del_img_id, $id]);
    $del_img_url = $stmt_sel->fetchColumn();
    
    if ($del_img_url) {
        $file_path = __DIR__ . '/../' . ltrim($del_img_url, '/');
        if (file_exists($file_path) && is_file($file_path)) {
            unlink($file_path);
        }
        $stmt_del = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
        $stmt_del->execute([$del_img_id]);
        set_flash("success", "Đã xóa ảnh.");
    }
    header("Location: edit_product.php?id=$id");
    exit;
}

// Handle setting main image
if (isset($_GET['set_main_id'])) {
    $set_main_id = (int)$_GET['set_main_id'];
    
    // Get target image position
    $stmt_target = $pdo->prepare("SELECT position FROM product_images WHERE id = ? AND product_id = ?");
    $stmt_target->execute([$set_main_id, $id]);
    $target_pos = $stmt_target->fetchColumn();
    
    if ($target_pos !== false) {
        $pdo->beginTransaction();
        try {
            // Find current main image
            $stmt_old_main = $pdo->prepare("SELECT id FROM product_images WHERE product_id = ? AND position = 0");
            $stmt_old_main->execute([$id]);
            $old_main_id = $stmt_old_main->fetchColumn();
            
            if ($old_main_id && $old_main_id != $set_main_id) {
                // Move old main image to target's old position
                $stmt_upd_old = $pdo->prepare("UPDATE product_images SET position = ? WHERE id = ?");
                $stmt_upd_old->execute([$target_pos, $old_main_id]);
            }
            
            // Set target as main
            $stmt_upd_new = $pdo->prepare("UPDATE product_images SET position = 0 WHERE id = ?");
            $stmt_upd_new->execute([$set_main_id]);
            
            $pdo->commit();
            set_flash("success", "Đã đổi ảnh chính.");
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash("error", "Lỗi: " . $e->getMessage());
        }
    }
    header("Location: edit_product.php?id=$id");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $sku = $_POST['sku'] ?? '';
    $slug = $_POST['slug'] ?: slugify($name);
    $brand_id = $_POST['brand_id'] ?: null;
    $category_ids = $_POST['category_ids'] ?? [];
    $short_desc = $_POST['short_description'] ?? '';
    $desc = $_POST['description'] ?? '';
    $price = (float)$_POST['price'];
    $sale_price = $_POST['sale_price'] ? (float)$_POST['sale_price'] : null;
    $stock = (int)$_POST['stock'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Specs
    $cpu = $_POST['cpu'] ?? '';
    $ram = $_POST['ram'] ?? '';
    $storage = $_POST['storage'] ?? '';
    $gpu = $_POST['gpu'] ?? '';
    $screen = $_POST['screen'] ?? '';
    $wifi = $_POST['wifi'] ?? '';
    $bluetooth = $_POST['bluetooth'] ?? '';
    $os = $_POST['os'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $battery = $_POST['battery'] ?? '';
    $ports = $_POST['ports'] ?? '';
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE products SET sku=?, name=?, slug=?, brand_id=?, short_description=?, description=?, price=?, sale_price=?, stock=?, is_active=? WHERE id=?");
        $stmt->execute([$sku, $name, $slug, $brand_id, $short_desc, $desc, $price, $sale_price, $stock, $is_active, $id]);
        
        // Update categories
        $pdo->prepare("DELETE FROM product_categories WHERE product_id = ?")->execute([$id]);
        if (!empty($category_ids)) {
            $stmt_cat = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $cat_id) {
                $stmt_cat->execute([$id, $cat_id]);
            }
        }

        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024;

        // Handle images upload
        if (!empty($_FILES['product_images']['name'][0])) {
            $fileCount = count($_FILES['product_images']['name']);
            
            $stmt_check_main = $pdo->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND position = 0");
            $stmt_check_main->execute([$id]);
            $has_main = $stmt_check_main->fetchColumn() > 0;

            $stmt_pos = $pdo->prepare("SELECT MAX(position) FROM product_images WHERE product_id = ?");
            $stmt_pos->execute([$id]);
            $max_pos = $stmt_pos->fetchColumn();
            if ($max_pos === null) $max_pos = -1;

            $stmt_img_ins = $pdo->prepare("INSERT INTO product_images (product_id, url, position) VALUES (?, ?, ?)");
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileName = $_FILES['product_images']['name'][$i];
                    $fileTmp = $_FILES['product_images']['tmp_name'][$i];
                    $fileType = $_FILES['product_images']['type'][$i];
                    
                    if (in_array($fileType, $allowedTypes) && $_FILES['product_images']['size'][$i] <= $maxSize) {
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
                        if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                            $pos = 0;
                            if (!$has_main && $i === 0) {
                                $pos = 0;
                                $has_main = true;
                            } else {
                                $max_pos++;
                                $pos = ($max_pos < 1) ? 1 : $max_pos;
                                if ($pos <= 0) $pos = 1;
                            }
                            $stmt_img_ins->execute([$id, 'uploads/products/' . $newFileName, $pos]);
                        }
                    }
                }
            }
        }

        // Update or insert specs
        $stmt_spec_check = $pdo->prepare("SELECT id FROM product_specifications WHERE product_id = ?");
        $stmt_spec_check->execute([$id]);
        if ($stmt_spec_check->fetch()) {
            $stmt_spec_upd = $pdo->prepare("UPDATE product_specifications SET cpu=?, ram=?, storage=?, gpu=?, screen=?, wifi=?, bluetooth=?, os=?, weight=?, battery=?, ports=? WHERE product_id=?");
            $stmt_spec_upd->execute([$cpu, $ram, $storage, $gpu, $screen, $wifi, $bluetooth, $os, $weight, $battery, $ports, $id]);
        } else {
            $stmt_spec_ins = $pdo->prepare("INSERT INTO product_specifications (product_id, cpu, ram, storage, gpu, screen, wifi, bluetooth, os, weight, battery, ports) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_spec_ins->execute([$id, $cpu, $ram, $storage, $gpu, $screen, $wifi, $bluetooth, $os, $weight, $battery, $ports]);
        }
        
        $pdo->commit();
        set_flash("success", "Cập nhật sản phẩm thành công.");
        header("Location: edit_product.php?id=$id"); exit;
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

        <form method="post" enctype="multipart/form-data">
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
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Màn hình</label>
                                        <input class="form-control" name="screen" value="<?php echo htmlspecialchars($specs['screen'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Trọng lượng</label>
                                        <input class="form-control" name="weight" value="<?php echo htmlspecialchars($specs['weight'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Dung lượng Pin</label>
                                        <input class="form-control" name="battery" value="<?php echo htmlspecialchars($specs['battery'] ?? ''); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Hệ điều hành</label>
                                        <input class="form-control" name="os" value="<?php echo htmlspecialchars($specs['os'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="col-12">
                                    <hr class="my-2 opacity-50">
                                    <h6 class="fw-bold mb-3 mt-2 text-primary small text-uppercase" style="letter-spacing: 0.5px;">Cổng kết nối</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">Wi-Fi</label>
                                            <input class="form-control" name="wifi" value="<?php echo htmlspecialchars($specs['wifi'] ?? ''); ?>" placeholder="Wi-Fi Intel Wi-Fi 6E AX211 (2x2)">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">Bluetooth</label>
                                            <input class="form-control" name="bluetooth" value="<?php echo htmlspecialchars($specs['bluetooth'] ?? ''); ?>" placeholder="Bluetooth 5.3">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">Cổng giao tiếp</label>
                                            <textarea class="form-control" name="ports" rows="3"><?php echo htmlspecialchars($specs['ports'] ?? ''); ?></textarea>
                                        </div>
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
                                <label class="form-label small fw-bold">Danh mục (Chọn nhiều)</label>
                                <div class="border rounded p-3 bg-light-subtle" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($categories as $cat): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="category_ids[]" 
                                                value="<?php echo $cat['id']; ?>" 
                                                id="cat_<?php echo $cat['id']; ?>"
                                                <?php echo in_array($cat['id'], $current_category_ids) ? 'checked' : ''; ?>>
                                            <label class="form-check-label small" for="cat_<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
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
                                <label class="form-label small fw-bold">Tải lên ảnh sản phẩm</label>
                                <input type="file" class="form-control" name="product_images[]" multiple accept="image/*">
                                <div class="form-text x-small mt-2">Định dạng: JPG, PNG, GIF, WEBP. Ảnh đầu tiên (nếu sản phẩm chưa có ảnh chính) sẽ tự động làm ảnh chính.</div>
                            </div>

                            <?php 
                            $stmt_all_imgs = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY position ASC");
                            $stmt_all_imgs->execute([$id]);
                            $all_images = $stmt_all_imgs->fetchAll();
                            
                            if (!empty($all_images)): ?>
                                <div class="row g-2 mt-3">
                                    <label class="form-label small fw-bold col-12 mb-1">Ảnh hiện tại:</label>
                                    <?php foreach ($all_images as $img): 
                                        $preview_url = (strpos($img['url'], 'http') === 0) ? $img['url'] : '../' . ltrim($img['url'], '/');
                                    ?>
                                        <div class="col-4 col-md-3">
                                            <div class="position-relative border rounded p-1 bg-white group">
                                                <img src="<?php echo htmlspecialchars($preview_url); ?>" class="img-fluid rounded" style="height: 80px; width: 100%; object-fit: contain;">
                                                <span class="position-absolute top-0 start-0 badge <?php echo $img['position'] == 0 ? 'bg-primary' : 'bg-dark opacity-75'; ?> m-1">
                                                    <?php echo $img['position'] == 0 ? 'Chính' : $img['position']; ?>
                                                </span>
                                                <div class="position-absolute top-0 end-0 m-1 d-flex flex-column gap-1">
                                                    <a href="?id=<?php echo $id; ?>&delete_image_id=<?php echo $img['id']; ?>" 
                                                       class="btn btn-danger btn-sm rounded-circle" 
                                                       onclick="return confirm('Xóa ảnh này?')"
                                                       style="width: 22px; height: 22px; padding: 0; display: flex; align-items: center; justify-content: center; border: 2px solid white;">
                                                        <i class="bi bi-x"></i>
                                                    </a>
                                                    <?php if ($img['position'] != 0): ?>
                                                        <a href="?id=<?php echo $id; ?>&set_main_id=<?php echo $img['id']; ?>" 
                                                           class="btn btn-warning btn-sm rounded-circle shadow-sm" 
                                                           title="Đặt làm ảnh chính"
                                                           style="width: 22px; height: 22px; padding: 0; display: flex; align-items: center; justify-content: center; border: 2px solid white;">
                                                            <i class="bi bi-star-fill text-white" style="font-size: 10px;"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
