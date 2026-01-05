<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// Fetch customers with order statistics
$query = "
    SELECT 
        u.id, 
        u.full_name, 
        u.email, 
        u.phone, 
        u.created_at,
        COUNT(o.id) as total_orders,
        SUM(CASE WHEN o.order_status NOT IN ('huy', 'tra_lai') THEN o.total ELSE 0 END) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$customers = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .customer-section { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.04); border: 1px solid #eee; }
    .customer-table thead th { 
        background-color: #f8f9fa; 
        color: #6c757d; 
        font-weight: 600; 
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: none;
        padding: 15px;
    }
    .customer-table tbody td { 
        padding: 15px; 
        font-size: 14px; 
        color: #2c3e50;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: middle;
    }
    .customer-avatar {
        width: 40px;
        height: 40px;
        background: #f0f2f5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #65676b;
        font-weight: bold;
        font-size: 16px;
        margin-right: 12px;
    }
    .search-bar { background: #f8f9fa; border-radius: 12px; padding: 20px; margin-bottom: 24px; border: 1px solid #eee; }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Khách hàng</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Quản Lý Khách Hàng</h4>
                    <p class="text-muted small mb-0">Xem thông tin chi tiết và lịch sử mua hàng của khách hàng.</p>
                </div>
                <div class="text-muted small fw-bold bg-white px-3 py-2 rounded-3 shadow-sm border">
                    Tổng cộng: <span class="text-primary"><?php echo count($customers); ?></span> khách hàng
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="row align-items-center g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-0" placeholder="Tìm theo tên, email hoặc số điện thoại...">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <button class="btn btn-light border fw-bold rounded-3">
                            <i class="bi bi-download me-2"></i>Xuất Excel
                        </button>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold text-uppercase">Khách hàng</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase">Liên hệ</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase text-center">Đơn hàng</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase text-end">Tổng chi tiêu</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase">Ngày tham gia</th>
                            <th class="pe-4 py-3 text-muted small fw-bold text-uppercase text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-placeholder me-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
                                        <?php echo strtoupper(substr($c['full_name'] ?: 'U', 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($c['full_name'] ?: 'Chưa cập nhật'); ?></div>
                                        <div class="small text-muted">ID: #<?php echo $c['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small mb-1"><i class="bi bi-envelope me-2 text-muted"></i><?php echo htmlspecialchars($c['email']); ?></div>
                                <div class="small"><i class="bi bi-telephone me-2 text-muted"></i><?php echo htmlspecialchars($c['phone'] ?: 'N/A'); ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border fw-normal rounded-pill px-3"><?php echo $c['total_orders']; ?> đơn</span>
                            </td>
                            <td class="text-end fw-bold text-primary">
                                <?php echo number_format($c['total_spent'] ?: 0, 0, ',', '.'); ?>đ
                            </td>
                            <td>
                                <div class="small text-muted"><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></div>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-3 border-0" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                        <li><a class="dropdown-item py-2" href="orders.php?customer_id=<?php echo $c['id']; ?>"><i class="bi bi-cart3 me-2"></i>Xem đơn hàng</a></li>
                                        <li><a class="dropdown-item py-2" href="#"><i class="bi bi-chat-dots me-2"></i>Gửi thông báo</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item py-2 text-danger" href="#"><i class="bi bi-slash-circle me-2"></i>Khóa tài khoản</a></li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($customers)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="bi bi-people fs-1 text-muted mb-3 d-block"></i>
                                <p class="text-muted">Chưa có dữ liệu khách hàng</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white py-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">Hiển thị <?php echo count($customers); ?> khách hàng</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link rounded-start-3" href="#">Trước</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link rounded-end-3" href="#">Sau</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
