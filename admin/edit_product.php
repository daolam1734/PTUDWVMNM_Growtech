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
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
          <h4 class="mb-0">Sửa sản phẩm: <?php echo htmlspecialchars($p['name']); ?></h4>
        </div>
        <div class="card-body">
          <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
          <form method="post">
            <div class="row">
              <div class="col-md-8">
                <div class="mb-3"><label class="form-label">Tên sản phẩm</label><input class="form-control" name="name" value="<?php echo htmlspecialchars($p['name']); ?>" required></div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">SKU</label><input class="form-control" name="sku" value="<?php echo htmlspecialchars($p['sku']); ?>"></div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">Slug</label><input class="form-control" name="slug" value="<?php echo htmlspecialchars($p['slug']); ?>"></div>
                  </div>
                </div>
                <div class="mb-3"><label class="form-label">Mô tả ngắn</label><input class="form-control" name="short_description" value="<?php echo htmlspecialchars($p['short_description']); ?>"></div>
                <div class="mb-3"><label class="form-label">Mô tả chi tiết</label><textarea class="form-control" name="description" rows="5"><?php echo htmlspecialchars($p['description']); ?></textarea></div>
                
                <h5 class="mt-4 mb-3">Thông số kỹ thuật</h5>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">CPU</label><input class="form-control" name="cpu" value="<?php echo htmlspecialchars($specs['cpu'] ?? ''); ?>" placeholder="Ví dụ: Intel Core i5-12500H"></div>
                    <div class="mb-3"><label class="form-label">RAM</label><input class="form-control" name="ram" value="<?php echo htmlspecialchars($specs['ram'] ?? ''); ?>" placeholder="Ví dụ: 16GB DDR4 3200MHz"></div>
                    <div class="mb-3"><label class="form-label">Ổ cứng</label><input class="form-control" name="storage" value="<?php echo htmlspecialchars($specs['storage'] ?? ''); ?>" placeholder="Ví dụ: 512GB SSD NVMe"></div>
                    <div class="mb-3"><label class="form-label">Card đồ họa</label><input class="form-control" name="gpu" value="<?php echo htmlspecialchars($specs['gpu'] ?? ''); ?>" placeholder="Ví dụ: RTX 3050 4GB"></div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">Màn hình</label><input class="form-control" name="screen" value="<?php echo htmlspecialchars($specs['screen'] ?? ''); ?>" placeholder="Ví dụ: 15.6 inch FHD 144Hz"></div>
                    <div class="mb-3"><label class="form-label">Hệ điều hành</label><input class="form-control" name="os" value="<?php echo htmlspecialchars($specs['os'] ?? ''); ?>" placeholder="Ví dụ: Windows 11 Home"></div>
                    <div class="mb-3"><label class="form-label">Trọng lượng</label><input class="form-control" name="weight" value="<?php echo htmlspecialchars($specs['weight'] ?? ''); ?>" placeholder="Ví dụ: 2.1 kg"></div>
                    <div class="mb-3"><label class="form-label">Pin</label><input class="form-control" name="battery" value="<?php echo htmlspecialchars($specs['battery'] ?? ''); ?>" placeholder="Ví dụ: 3-cell, 52.5 Wh"></div>
                  </div>
                  <div class="col-12">
                    <div class="mb-3"><label class="form-label">Cổng kết nối</label><input class="form-control" name="ports" value="<?php echo htmlspecialchars($specs['ports'] ?? ''); ?>" placeholder="Ví dụ: 1x USB-C, 3x USB-A, 1x HDMI"></div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3"><label class="form-label">Danh mục</label>
                  <select class="form-select" name="category_id">
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?php echo $cat['id']; ?>" <?php echo $p['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3"><label class="form-label">Thương hiệu</label>
                  <select class="form-select" name="brand_id">
                    <option value="">-- Chọn thương hiệu --</option>
                    <?php foreach ($brands as $b): ?>
                      <option value="<?php echo $b['id']; ?>" <?php echo $p['brand_id'] == $b['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3"><label class="form-label">Giá bán</label><input type="number" step="0.01" class="form-control" name="price" value="<?php echo $p['price']; ?>" required></div>
                <div class="mb-3"><label class="form-label">Giá khuyến mãi</label><input type="number" step="0.01" class="form-control" name="sale_price" value="<?php echo $p['sale_price']; ?>"></div>
                <div class="mb-3"><label class="form-label">Số lượng kho</label><input type="number" class="form-control" name="stock" value="<?php echo $p['stock']; ?>" required></div>
                <div class="mb-3"><label class="form-label">URL ảnh chính</label><input class="form-control" name="image" value="<?php echo htmlspecialchars($current_image); ?>"></div>
                <div class="mb-3 form-check">
                  <input type="checkbox" class="form-check-input" name="is_active" id="is_active" <?php echo $p['is_active'] ? 'checked' : ''; ?>>
                  <label class="form-check-label" for="is_active">Hiển thị sản phẩm</label>
                </div>
              </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
              <button class="btn btn-primary px-4">Lưu thay đổi</button>
              <a href="products.php" class="btn btn-outline-secondary">Quay lại</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
