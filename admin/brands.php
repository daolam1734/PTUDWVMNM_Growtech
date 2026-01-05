<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = trim($_POST['name']);
    $logo = $_POST['logo'];
    $stmt = $pdo->prepare("INSERT INTO brands (name, logo) VALUES (?, ?)");
    $stmt->execute([$name, $logo]);
    set_flash("success", "Thêm thương hiệu thành công.");
    header("Location: brands.php"); exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->execute([$id]);
    set_flash("success", "Xóa thương hiệu thành công.");
    header("Location: brands.php"); exit;
}

$brands = $pdo->query("SELECT * FROM brands ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Quản Lý Thương Hiệu</h4>
                <p class="text-muted small mb-0">Quản lý các đối tác và thương hiệu sản phẩm đang kinh doanh.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm thương hiệu mới</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tên thương hiệu</label>
                                <input type="text" name="name" class="form-control form-control-lg fs-6" placeholder="VD: Apple, Dell, ASUS" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">URL Logo</label>
                                <input type="text" name="logo" class="form-control" placeholder="https://example.com/logo.png">
                                <div class="form-text x-small">Nhập link ảnh logo thương hiệu.</div>
                            </div>
                            <button type="submit" name="add_brand" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="bi bi-save me-2"></i>Lưu thương hiệu
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-award me-2 text-primary"></i>Danh sách thương hiệu</h6>
                        <span class="badge bg-light text-dark border"><?php echo count($brands); ?> thương hiệu</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-muted small fw-bold" style="width: 80px;">ID</th>
                                    <th class="py-3 text-muted small fw-bold">Logo</th>
                                    <th class="py-3 text-muted small fw-bold">Tên thương hiệu</th>
                                    <th class="py-3 text-muted small fw-bold text-end pe-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($brands as $b): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?php echo $b['id']; ?></td>
                                        <td>
                                            <?php if ($b['logo']): ?>
                                                <div class="bg-white border rounded p-1 d-inline-block">
                                                    <img src="<?php echo htmlspecialchars($b['logo']); ?>" height="30" style="max-width: 80px; object-fit: contain;">
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-light border rounded p-1 d-inline-block text-muted x-small px-2">No Logo</div>
                                            <?php endif; ?>
                                        </td>
                                        <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($b['name']); ?></div></td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-light border" title="Chỉnh sửa"><i class="bi bi-pencil"></i></button>
                                                <a href="?delete=<?php echo $b['id']; ?>" 
                                                   class="btn btn-sm btn-light border text-danger" 
                                                   onclick="return confirm('Xóa thương hiệu này?')"
                                                   title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($brands)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-award fs-1 d-block mb-2"></i>
                                            Chưa có thương hiệu nào.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
