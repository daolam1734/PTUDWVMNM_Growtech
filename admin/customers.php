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
    .customer-section { background: #fff; padding: 24px; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .customer-table thead th { 
        background-color: #f6f6f6; 
        color: rgba(0,0,0,.54); 
        font-weight: 500; 
        font-size: 14px;
        border-bottom: none;
        padding: 12px 16px;
    }
    .customer-table tbody td { 
        padding: 16px; 
        font-size: 14px; 
        color: rgba(0,0,0,.87);
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    .avatar-placeholder {
        width: 40px;
        height: 40px;
        background: #f5f5f5;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
        font-size: 20px;
    }
</style>

<div class="container-fluid">
    <div class="customer-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold">Danh sách khách hàng</h4>
            <div class="text-muted small">Tổng cộng: <?php echo count($customers); ?> khách hàng</div>
        </div>

        <!-- Search Bar (Shopee Style) -->
        <div class="search-bar mb-4 p-3 bg-light rounded">
            <form class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Tìm theo tên, email hoặc số điện thoại">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-shopee-primary px-4">Tìm kiếm</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table customer-table mb-0">
                <thead>
                    <tr>
                        <th width="60">ID</th>
                        <th>Khách hàng</th>
                        <th>Liên hệ</th>
                        <th class="text-center">Đơn hàng</th>
                        <th class="text-end">Tổng chi tiêu</th>
                        <th>Ngày tham gia</th>
                        <th width="100">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-placeholder me-3">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($c['full_name'] ?: 'Chưa cập nhật'); ?></div>
                                    <div class="small text-muted">@<?php echo htmlspecialchars($c['email']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small"><i class="bi bi-envelope me-1"></i> <?php echo htmlspecialchars($c['email']); ?></div>
                            <div class="small"><i class="bi bi-telephone me-1"></i> <?php echo htmlspecialchars($c['phone'] ?: 'N/A'); ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border"><?php echo $c['total_orders']; ?></span>
                        </td>
                        <td class="text-end fw-bold text-shopee">
                            <?php echo number_format($c['total_spent'] ?: 0, 0, ',', '.'); ?> đ
                        </td>
                        <td>
                            <div class="small"><?php echo date('d/m/Y', strtotime($c['created_at'])); ?></div>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                                    Chi tiết
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                    <li><a class="dropdown-item" href="orders.php?customer_id=<?php echo $c['id']; ?>">Xem đơn hàng</a></li>
                                    <li><a class="dropdown-item" href="#">Gửi thông báo</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="#">Khóa tài khoản</a></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            Chưa có dữ liệu khách hàng
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
