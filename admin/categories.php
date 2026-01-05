<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $slug = $_POST['slug'] ?: slugify($name);
    $desc = $_POST['description'];
    $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
    $stmt->execute([$name, $slug, $desc]);
    set_flash("success", "Thêm danh mục thành công.");
    header("Location: categories.php"); exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    set_flash("success", "Xóa danh mục thành công.");
    header("Location: categories.php"); exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY id DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Quản Lý Danh Mục</h4>
                <p class="text-muted small mb-0">Phân loại sản phẩm để khách hàng dễ dàng tìm kiếm.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm danh mục mới</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Tên danh mục</label>
                                <input type="text" name="name" class="form-control form-control-lg fs-6" placeholder="VD: Laptop Gaming" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Slug (Đường dẫn)</label>
                                <input type="text" name="slug" class="form-control" placeholder="laptop-gaming">
                                <div class="form-text x-small">Để trống để tự động tạo từ tên.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Mô tả</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Mô tả ngắn về danh mục..."></textarea>
                            </div>
                            <button type="submit" name="add_category" class="btn btn-primary w-100 py-2 fw-bold">
                                <i class="bi bi-save me-2"></i>Lưu danh mục
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Danh sách danh mục</h6>
                        <span class="badge bg-light text-dark border"><?php echo count($categories); ?> danh mục</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-muted small fw-bold" style="width: 80px;">ID</th>
                                    <th class="py-3 text-muted small fw-bold">Tên danh mục</th>
                                    <th class="py-3 text-muted small fw-bold">Slug</th>
                                    <th class="py-3 text-muted small fw-bold text-end pe-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $c): ?>
                                    <tr>
                                        <td class="ps-4 text-muted">#<?php echo $c['id']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['name']); ?></div>
                                            <?php if($c['description']): ?>
                                                <div class="text-muted x-small text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($c['description']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><code class="small text-primary bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($c['slug']); ?></code></td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-light border" title="Chỉnh sửa"><i class="bi bi-pencil"></i></button>
                                                <a href="?delete=<?php echo $c['id']; ?>" 
                                                   class="btn btn-sm btn-light border text-danger" 
                                                   onclick="return confirm('Xóa danh mục này?')"
                                                   title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($categories)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            Chưa có danh mục nào.
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
