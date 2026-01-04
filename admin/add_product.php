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
<div class="container-fluid">
  <div class="row justify-content-center">
    <div class="col-md-10">
      <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
          <h4 class="mb-0">Thêm sản phẩm mới</h4>
        </div>
        <div class="card-body">
          <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
          <form method="post">
            <div class="row">
              <div class="col-md-8">
                <div class="mb-3"><label class="form-label">Tên sản phẩm</label><input class="form-control" name="name" required></div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">SKU</label><input class="form-control" name="sku"></div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">Slug (để trống sẽ tự tạo)</label><input class="form-control" name="slug"></div>
                  </div>
                </div>
                <div class="mb-3"><label class="form-label">Mô tả ngắn</label><input class="form-control" name="short_description"></div>
                <div class="mb-3"><label class="form-label">Mô tả chi tiết</label><textarea class="form-control" name="description" rows="5"></textarea></div>
                
                <h5 class="mt-4 mb-3">Thông số kỹ thuật</h5>
                <div class="row">
                  <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">CPU</label><input class="form-control" name="cpu" placeholder="Ví dụ: Intel Core i5-12500H"></div>
                    <div class="mb-3"><label class="form-label">RAM</label><input class="form-control" name="ram" placeholder="Ví dụ: 16GB DDR4 3200MHz"></div>
                    <div class="mb-3"><label class="form-label">Ổ cứng</label><input class="form-control" name="storage" placeholder="Ví dụ: 512GB SSD NVMe"></div>
                    <div class="mb-3"><label class="form-label">Card đồ họa</label><input class="form-control" name="gpu" placeholder="Ví dụ: RTX 3050 4GB"></div>
                  </div>
                  <div class="col-md-6">
                    <div class="mb-3"><label class="form-label">Màn hình</label><input class="form-control" name="screen" placeholder="Ví dụ: 15.6 inch FHD 144Hz"></div>
                    <div class="mb-3"><label class="form-label">Hệ điều hành</label><input class="form-control" name="os" placeholder="Ví dụ: Windows 11 Home"></div>
                    <div class="mb-3"><label class="form-label">Trọng lượng</label><input class="form-control" name="weight" placeholder="Ví dụ: 2.1 kg"></div>
                    <div class="mb-3"><label class="form-label">Pin</label><input class="form-control" name="battery" placeholder="Ví dụ: 3-cell, 52.5 Wh"></div>
                  </div>
                  <div class="col-12">
                    <div class="mb-3"><label class="form-label">Cổng kết nối</label><input class="form-control" name="ports" placeholder="Ví dụ: 1x USB-C, 3x USB-A, 1x HDMI"></div>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="mb-3"><label class="form-label">Danh mục</label>
                  <select class="form-select" name="category_id">
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                      <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3"><label class="form-label">Thương hiệu</label>
                  <select class="form-select" name="brand_id">
                    <option value="">-- Chọn thương hiệu --</option>
                    <?php foreach ($brands as $b): ?>
                      <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="mb-3"><label class="form-label">Giá bán</label><input type="number" step="0.01" class="form-control" name="price" required></div>
                <div class="mb-3"><label class="form-label">Giá khuyến mãi</label><input type="number" step="0.01" class="form-control" name="sale_price"></div>
                <div class="mb-3"><label class="form-label">Số lượng kho</label><input type="number" class="form-control" name="stock" required></div>
                <div class="mb-3"><label class="form-label">URL ảnh chính</label><input class="form-control" name="image" placeholder="https://..."></div>
                <div class="mb-3 form-check">
                  <input type="checkbox" class="form-check-input" name="is_active" id="is_active" checked>
                  <label class="form-check-label" for="is_active">Hiển thị sản phẩm</label>
                </div>
              </div>
            </div>
            <hr>
            <div class="d-flex gap-2">
              <button class="btn btn-success px-4">Thêm sản phẩm</button>
              <a href="products.php" class="btn btn-outline-secondary">Quay lại</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
