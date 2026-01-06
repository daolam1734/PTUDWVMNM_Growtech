<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

// Handle Add or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_category']) || isset($_POST['update_category']))) {
    $name = trim($_POST['name']);
    $slug = $_POST['slug'] ?: slugify($name);
    $desc = $_POST['description'];
    $cat_id = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;

    if ($cat_id > 0) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ?, description = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $desc, $cat_id]);
        set_flash("success", "Cập nhật danh mục thành công.");
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $slug, $desc]);
        set_flash("success", "Thêm danh mục thành công.");
    }
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
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item small"><a href="dashboard.php" class="text-decoration-none text-muted">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item small active" aria-current="page">Sản phẩm & Kho</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0">Quản Lý Danh Mục</h4>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Form Column -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold" id="form-title"><i class="bi bi-plus-circle me-2 text-primary"></i>Thêm danh mục mới</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" id="category-form">
                            <input type="hidden" name="cat_id" id="cat_id" value="0">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase opacity-75">Tên danh mục</label>
                                <input type="text" name="name" id="cat_name" class="form-control form-control-lg fs-6 rounded-3" placeholder="VD: Laptop Gaming" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase opacity-75">Slug (Đường dẫn)</label>
                                <input type="text" name="slug" id="cat_slug" class="form-control rounded-3" placeholder="laptop-gaming">
                                <div class="form-text x-small">Để trống để tự động tạo từ tên.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase opacity-75">Mô tả</label>
                                <textarea name="description" id="cat_desc" class="form-control rounded-3" rows="4" placeholder="Mô tả ngắn về danh mục..."></textarea>
                            </div>
                            <div class="d-grid gap-2 pt-2">
                                <button type="submit" name="add_category" id="submit-btn" class="btn btn-primary py-2 fw-bold rounded-3 shadow-sm">
                                    <i class="bi bi-save me-2"></i>Lưu danh mục
                                </button>
                                <button type="button" id="cancel-btn" class="btn btn-light border py-2 fw-bold rounded-3 d-none">
                                    Hủy bỏ chỉnh sửa
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- List Column -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2 text-primary"></i>Danh sách danh mục</h6>
                        <span class="badge bg-soft-primary text-primary px-3 rounded-pill"><?php echo count($categories); ?> danh mục</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4" style="width: 80px;">ID</th>
                                    <th>Cơ cấu danh mục</th>
                                    <th>Slug</th>
                                    <th class="text-center">Số sản phẩm</th>
                                    <th class="text-end pe-4">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $c): 
                                    // Count products
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                                    $stmt->execute([$c['id']]);
                                    $count = $stmt->fetchColumn();
                                ?>
                                    <tr>
                                        <td class="ps-4 text-muted small">#<?php echo $c['id']; ?></td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['name']); ?></div>
                                            <?php if($c['description']): ?>
                                                <div class="text-muted x-small text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($c['description']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><code class="small text-primary bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($c['slug']); ?></code></td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-3"><?php echo $count; ?></span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary px-3 edit-cat-btn" 
                                                        data-id="<?php echo $c['id']; ?>"
                                                        data-name="<?php echo htmlspecialchars($c['name']); ?>"
                                                        data-slug="<?php echo htmlspecialchars($c['slug']); ?>"
                                                        data-desc="<?php echo htmlspecialchars($c['description']); ?>"
                                                        title="Chỉnh sửa">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <a href="?delete=<?php echo $c['id']; ?>" 
                                                   class="btn btn-outline-danger px-3 delete-confirm" 
                                                   title="Xóa">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted bg-light">
                                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>
                                            Chưa có danh mục sản phẩm nào.
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

<style>
.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
.delete-confirm:hover { background-color: var(--bs-danger); color: white; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit Category
    document.querySelectorAll('.edit-cat-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const slug = this.getAttribute('data-slug');
            const desc = this.getAttribute('data-desc');

            document.getElementById('form-title').innerHTML = '<i class="bi bi-pencil-square me-2 text-warning"></i>Sửa danh mục';
            document.getElementById('cat_id').value = id;
            document.getElementById('cat_name').value = name;
            document.getElementById('cat_slug').value = slug;
            document.getElementById('cat_desc').value = desc;
            
            document.getElementById('submit-btn').name = 'update_category';
            document.getElementById('submit-btn').innerHTML = '<i class="bi bi-check-circle me-2"></i>Cập nhật';
            document.getElementById('cancel-btn').classList.remove('d-none');
            
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });

    // Cancel Edit
    document.getElementById('cancel-btn').addEventListener('click', function() {
        document.getElementById('form-title').innerHTML = '<i class="bi bi-plus-circle me-2 text-primary"></i>Thêm danh mục mới';
        document.getElementById('cat_id').value = '0';
        document.getElementById('category-form').reset();
        document.getElementById('submit-btn').name = 'add_category';
        document.getElementById('submit-btn').innerHTML = '<i class="bi bi-save me-2"></i>Lưu danh mục';
        this.classList.add('d-none');
    });

    // Delete confirmation
    document.querySelectorAll('.delete-confirm').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if(!confirm('Xóa danh mục này có thể ảnh hưởng đến hiển thị sản phẩm. Bạn chắc chứ?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
