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
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Đánh giá</li>
                </ol>
            </nav>
            <h4 class="fw-bold">Quản Lý Đánh Giá</h4>
            <p class="text-muted small">Duyệt hoặc phản hồi các đánh giá từ khách hàng.</p>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold">Khách hàng</th>
                            <th class="py-3 text-muted small fw-bold">Sản phẩm</th>
                            <th class="py-3 text-muted small fw-bold">Đánh giá</th>
                            <th class="py-3 text-muted small fw-bold">Trạng thái</th>
                            <th class="py-3 text-muted small fw-bold text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold"><?php echo htmlspecialchars($r['full_name']); ?></div>
                                <div class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="small text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($r['product_name']); ?></div>
                            </td>
                            <td>
                                <div class="text-warning mb-1">
                                    <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $r['rating'] ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="small text-dark fw-bold"><?php echo htmlspecialchars($r['title']); ?></div>
                                <div class="small text-muted line-clamp-2"><?php echo htmlspecialchars($r['comment']); ?></div>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'dang_cho'): ?>
                                    <span class="badge bg-warning text-dark">Chờ duyệt</span>
                                <?php elseif ($r['status'] === 'duyet'): ?>
                                    <span class="badge bg-success">Đã duyệt</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Từ chối</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                    <?php if ($r['status'] === 'dang_cho'): ?>
                                        <button type="submit" name="action" value="approve" class="btn btn-sm btn-outline-success" title="Duyệt"><i class="bi bi-check-lg"></i></button>
                                        <button type="submit" name="action" value="reject" class="btn btn-sm btn-outline-danger" title="Từ chối"><i class="bi bi-x-lg"></i></button>
                                    <?php endif; ?>
                                    <button type="submit" name="action" value="delete" class="btn btn-sm btn-outline-secondary" title="Xóa" onclick="return confirm('Xóa đánh giá này?')"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reviews)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">Chưa có đánh giá nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
