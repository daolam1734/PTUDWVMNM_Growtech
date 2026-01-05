<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// Handle Banner Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $image_url = $_POST['image_url'];
        $link_url = $_POST['link_url'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $position = (int)$_POST['position'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO banners (image_url, link_url, title, description, position, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$image_url, $link_url, $title, $description, $position, $is_active]);
            set_flash("success", "Đã thêm banner mới.");
        } else {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE banners SET image_url = ?, link_url = ?, title = ?, description = ?, position = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$image_url, $link_url, $title, $description, $position, $is_active, $id]);
            set_flash("success", "Đã cập nhật banner.");
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        set_flash("success", "Đã xóa banner.");
    }
    header("Location: banners.php");
    exit;
}

$banners = $pdo->query("SELECT * FROM banners ORDER BY position ASC, created_at DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Banners</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="fw-bold mb-0">Quản lý Banner</h4>
                <button class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#bannerModal" onclick="resetModal()">
                    <i class="bi bi-plus-lg me-2"></i>Thêm Banner Mới
                </button>
            </div>
        </div>

        <div class="row g-4">
            <?php if (empty($banners)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <i class="bi bi-image fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted">Chưa có banner nào được tạo.</h5>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($banners as $b): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($b['image_url']); ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                            <div class="position-absolute top-0 end-0 m-3">
                                <?php if ($b['is_active']): ?>
                                    <span class="badge bg-success rounded-pill px-3 shadow-sm">Đang hiển thị</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary rounded-pill px-3 shadow-sm">Đã ẩn</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($b['title']); ?></h6>
                            <p class="text-muted small mb-3 line-clamp-2"><?php echo htmlspecialchars($b['description']); ?></p>
                            
                            <div class="bg-light rounded-3 p-3 mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted small">Vị trí:</span>
                                    <span class="fw-bold small"><?php echo $b['position']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Link:</span>
                                    <a href="<?php echo htmlspecialchars($b['link_url']); ?>" target="_blank" class="text-decoration-none small text-truncate ms-2" style="max-width: 150px;">
                                        <?php echo htmlspecialchars($b['link_url']); ?>
                                    </a>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button class="btn btn-light btn-sm flex-grow-1 fw-bold rounded-3" onclick='editBanner(<?php echo json_encode($b); ?>)'>
                                    <i class="bi bi-pencil me-1"></i> Sửa
                                </button>
                                <form method="POST" class="flex-grow-1" onsubmit="return confirm('Xác nhận xóa banner này?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                                    <button type="submit" class="btn btn-light btn-sm text-danger w-100 fw-bold rounded-3">
                                        <i class="bi bi-trash me-1"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Banner Modal -->
<div class="modal fade" id="bannerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold" id="modalTitle">Thêm Banner</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="bannerId">
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Tiêu đề banner</label>
                    <input type="text" name="title" id="bannerTitle" class="form-control" placeholder="Ví dụ: Khuyến mãi mùa hè" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label small fw-bold">Mô tả ngắn</label>
                    <textarea name="description" id="bannerDesc" class="form-control" rows="2" placeholder="Mô tả chương trình..."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">URL hình ảnh</label>
                    <input type="text" name="image_url" id="bannerImg" class="form-control" placeholder="https://..." required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Link liên kết</label>
                    <input type="text" name="link_url" id="bannerLink" class="form-control" placeholder="https://...">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Vị trí hiển thị</label>
                            <input type="number" name="position" id="bannerPos" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Trạng thái</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="bannerActive" checked>
                                <label class="form-check-label" for="bannerActive">Hiển thị</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top p-3">
                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary fw-bold">Lưu banner</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetModal() {
    document.getElementById('modalTitle').innerText = 'Thêm Banner';
    document.getElementById('modalAction').value = 'add';
    document.getElementById('bannerId').value = '';
    document.getElementById('bannerTitle').value = '';
    document.getElementById('bannerDesc').value = '';
    document.getElementById('bannerImg').value = '';
    document.getElementById('bannerLink').value = '';
    document.getElementById('bannerPos').value = '0';
    document.getElementById('bannerActive').checked = true;
}

function editBanner(banner) {
    document.getElementById('modalTitle').innerText = 'Sửa Banner';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('bannerId').value = banner.id;
    document.getElementById('bannerTitle').value = banner.title;
    document.getElementById('bannerDesc').value = banner.description;
    document.getElementById('bannerImg').value = banner.image_url;
    document.getElementById('bannerLink').value = banner.link_url;
    document.getElementById('bannerPos').value = banner.position;
    document.getElementById('bannerActive').checked = banner.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('bannerModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
