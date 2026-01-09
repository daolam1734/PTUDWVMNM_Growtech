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
    
    // Process uploaded images
    $uploadedImages = [];
    $uploadDir = __DIR__ . '/../uploads/products/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!empty($_FILES['product_images']['name'][0])) {
        $fileCount = count($_FILES['product_images']['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['product_images']['error'][$i] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['product_images']['name'][$i];
                $fileTmp = $_FILES['product_images']['tmp_name'][$i];
                $fileType = $_FILES['product_images']['type'][$i];
                
                if (in_array($fileType, $allowedTypes) && $_FILES['product_images']['size'][$i] <= $maxSize) {
                    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
                    if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                        $uploadedImages[] = 'uploads/products/' . $newFileName;
                    }
                }
            }
        }
    }
    
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO products (sku, name, slug, brand_id, short_description, description, price, sale_price, stock, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $name, $slug, $brand_id, $short_desc, $desc, $price, $sale_price, $stock, $is_active]);
        $product_id = $pdo->lastInsertId();

        // Insert multiple categories
        if (!empty($category_ids)) {
            $stmt_pc = $pdo->prepare("INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)");
            foreach ($category_ids as $cat_id) {
                $stmt_pc->execute([$product_id, $cat_id]);
            }
        }
        
        $stmt_img = $pdo->prepare("INSERT INTO product_images (product_id, url, position) VALUES (?, ?, ?)");
        
        // Insert images (first one as main)
        foreach ($uploadedImages as $idx => $imageUrl) {
            $stmt_img->execute([$product_id, $imageUrl, $idx]);
        }

        // Insert specs
        $stmt_specs = $pdo->prepare("INSERT INTO product_specifications (product_id, cpu, ram, storage, gpu, screen, wifi, bluetooth, os, weight, battery, ports) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_specs->execute([$product_id, $cpu, $ram, $storage, $gpu, $screen, $wifi, $bluetooth, $os, $weight, $battery, $ports]);

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
                                        <input class="form-control" name="cpu" placeholder="Intel Core Ultra 7 255H...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">RAM</label>
                                        <input class="form-control" name="ram" placeholder="32GB DDR5">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Ổ cứng</label>
                                        <input class="form-control" name="storage" placeholder="512 GB PCIe Gen4 NVMe M.2 SSD">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Card đồ họa</label>
                                        <input class="form-control" name="gpu" placeholder="NVIDIA GeForce RTX 5070">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Màn hình</label>
                                        <input class="form-control" name="screen" placeholder="16 inches">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Trọng lượng</label>
                                        <input class="form-control" name="weight" placeholder="2.43 kg">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Dung lượng Pin</label>
                                        <input class="form-control" name="battery" placeholder="6-cell, 83 Wh Li-ion polymer">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Hệ điều hành</label>
                                        <input class="form-control" name="os" placeholder="Windows 11 Home">
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <hr class="my-2 opacity-50">
                                    <h6 class="fw-bold mb-3 mt-2 text-primary small text-uppercase" style="letter-spacing: 0.5px;">Cổng kết nối</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">Wi-Fi</label>
                                            <input class="form-control" name="wifi" placeholder="Wi-Fi Intel Wi-Fi 6E AX211 (2x2)">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label small fw-bold">Bluetooth</label>
                                            <input class="form-control" name="bluetooth" placeholder="Bluetooth 5.3">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small fw-bold">Cổng giao tiếp</label>
                                            <textarea class="form-control" name="ports" rows="3" placeholder="2 x USB-C (Thunderbolt 4), 1 x USB-A, HDMI 2.1..."></textarea>
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
                                            <input class="form-check-input" type="checkbox" name="category_ids[]" value="<?php echo $cat['id']; ?>" id="cat_<?php echo $cat['id']; ?>">
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
                            <h6 class="mb-0 fw-bold"><i class="bi bi-image me-2 text-primary"></i>Hình ảnh sản phẩm</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Chọn ảnh sản phẩm <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="product_images[]" id="product_images" multiple accept="image/*" required>
                                <div class="form-text x-small mt-2">
                                    <i class="bi bi-info-circle"></i> Ảnh đầu tiên sẽ được chọn làm ảnh chính.
                                </div>
                            </div>
                            <div id="image_preview" class="row g-2"></div>
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

<script>
let selectedFiles = [];

document.getElementById('product_images').addEventListener('change', function(e) {
    const newFiles = Array.from(e.target.files);
    selectedFiles = selectedFiles.concat(newFiles);
    updatePreviewAndInput();
});

function updatePreviewAndInput() {
    const preview = document.getElementById('image_preview');
    const input = document.getElementById('product_images');
    preview.innerHTML = '';
    
    const dt = new DataTransfer();
    
    selectedFiles.forEach((file, index) => {
        dt.items.add(file);
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-4 mb-2';
            col.innerHTML = `
                <div class="position-relative">
                    <img src="${e.target.result}" class="img-fluid rounded border shadow-sm" style="height: 100px; width: 100%; object-fit: cover;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle" onclick="removeImage(${index})" style="width: 22px; height: 22px; padding: 0; display: flex; align-items: center; justify-content: center; border: 2px solid white;">
                        <i class="bi bi-x"></i>
                    </button>
                    <div class="badge ${index === 0 ? 'bg-primary' : 'bg-dark bg-opacity-50'} position-absolute bottom-0 start-0 m-1 small">
                        ${index === 0 ? 'Ảnh chính' : 'Ảnh ' + (index + 1)}
                    </div>
                </div>
            `;
            preview.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
    
    input.files = dt.files;
}

function removeImage(index) {
    selectedFiles.splice(index, 1);
    updatePreviewAndInput();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
