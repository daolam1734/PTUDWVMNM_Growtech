<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = (int)$_POST['id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'duyet' WHERE id = ?");
        $stmt->execute([$id]);
        set_flash("success", "Đã duyệt đánh giá.");
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("UPDATE reviews SET status = 'tu_choi' WHERE id = ?");
        $stmt->execute([$id]);
        set_flash("success", "Đã từ chối đánh giá.");
    } elseif ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        set_flash("success", "Đã xóa đánh giá.");
    }
    header("Location: reviews.php"); exit;
}

$reviews = $pdo->query("
    SELECT r.*, u.full_name, p.name as product_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN products p ON r.product_id = p.id 
    ORDER BY r.created_at DESC
")->fetchAll();

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
                        <li class="breadcrumb-item small active" aria-current="page">Chăm sóc khách hàng</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0">Quản Lý Đánh Giá</h4>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-star me-2 text-warning"></i>Tất cả phản hồi</h6>
                <div class="d-flex gap-2">
                    <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill border">
                        Có <?php echo count($reviews); ?> đánh giá
                    </span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Khách hàng</th>
                            <th>Sản phẩm</th>
                            <th>Nội dung đánh giá</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['full_name']); ?></div>
                                <div class="text-muted small" style="font-size: 11px;">
                                    <i class="bi bi-clock me-1"></i><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-bold text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($r['product_name']); ?>">
                                    <?php echo htmlspecialchars($r['product_name']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="text-warning mb-1" style="font-size: 12px;">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $r['rating'] ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="small fw-bold text-dark mb-1"><?php echo htmlspecialchars($r['title']); ?></div>
                                <div class="small text-muted line-clamp-2" style="max-width: 300px;"><?php echo htmlspecialchars($r['comment']); ?></div>
                            </td>
                            <td class="text-center">
                                <?php if ($r['status'] === 'dang_cho'): ?>
                                    <span class="badge bg-soft-warning text-warning rounded-pill px-3 border border-warning">Chờ duyệt</span>
                                <?php elseif ($r['status'] === 'duyet'): ?>
                                    <span class="badge bg-soft-success text-success rounded-pill px-3 border border-success">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge bg-soft-danger text-danger rounded-pill px-3 border border-danger">Từ chối</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-1">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                        <?php if ($r['status'] === 'dang_cho'): ?>
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success rounded-pill px-3" title="Duyệt">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger rounded-pill px-3" title="Từ chối">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button type="submit" name="action" value="delete" class="btn btn-sm btn-light border rounded-pill px-3" onclick="return confirm('Xóa đánh giá này?')" title="Xóa">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reviews)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted bg-light">
                                    <i class="bi bi-chat-left-dots fs-1 d-block mb-3 opacity-25"></i>
                                    Chưa có đánh giá nào từ khách hàng.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.bg-soft-warning { background-color: rgba(255, 193, 7, 0.1); }
.bg-soft-success { background-color: rgba(25, 135, 84, 0.1); }
.bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }
.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
