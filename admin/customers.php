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
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item small"><a href="dashboard.php" class="text-decoration-none text-muted">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item small active" aria-current="page">Khách hàng</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0">Quản Lý Khách Hàng</h4>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="row align-items-center g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3 pe-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="customer-search" class="form-control border-start-0 py-2 rounded-end-pill shadow-none" placeholder="Tìm kiếm theo tên, email hoặc số điện thoại...">
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill border">
                            Tổng <span class="fw-bold"><?php echo count($customers); ?></span> hội viên
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0" id="customer-table">
                    <thead>
                        <tr>
                            <th class="ps-4">Khách hàng</th>
                            <th>Thông tin liên hệ</th>
                            <th class="text-center">Đơn hàng</th>
                            <th class="text-end">Tiêu dùng</th>
                            <th class="text-center">Tham gia</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): 
                            $initials = '';
                            $names = explode(' ', $c['full_name']);
                            foreach($names as $n) { if($n) $initials .= strtoupper(substr($n, 0, 1)); }
                            $initials = substr($initials, 0, 2) ?: 'U';
                        ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <div class="customer-avatar me-3">
                                            <?php echo $initials; ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($c['full_name'] ?: 'Khách ẩn danh'); ?></div>
                                            <div class="text-muted small">#USER-<?php echo $c['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small"><i class="bi bi-envelope me-2 text-muted"></i><?php echo htmlspecialchars($c['email']); ?></div>
                                    <div class="small mt-1"><i class="bi bi-telephone me-2 text-muted"></i><?php echo htmlspecialchars($c['phone'] ?: '---'); ?></div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border px-3 rounded-pill"><?php echo $c['total_orders']; ?> đơn</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold text-success"><?php echo number_format($c['total_spent'] ?: 0); ?>đ</span>
                                </td>
                                <td class="text-center small text-muted">
                                    <?php echo date('d/m/Y', strtotime($c['created_at'])); ?>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="dropdown">
                                        <button class="btn btn-light btn-sm rounded-circle p-2 border-0" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3">
                                            <li><a class="dropdown-item py-2" href="orders.php?user_id=<?php echo $c['id']; ?>"><i class="bi bi-list-check me-2"></i>Lịch sử mua hàng</a></li>
                                            <li><a class="dropdown-item py-2" href="#"><i class="bi bi-envelope-at me-2"></i>Gửi Email Marketing</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item py-2 text-danger" href="#"><i class="bi bi-slash-circle me-2"></i>Khóa tài khoản</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($customers)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted bg-light">
                                    <i class="bi bi-people fs-1 d-block mb-3 opacity-25"></i>
                                    Chưa có dữ liệu khách hàng nào.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer bg-white border-top py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="text-muted small mb-0">Hiển thị toàn bộ khách hàng trên hệ thống.</p>
                    <button class="btn btn-sm btn-outline-primary rounded-pill px-4">Tải thêm</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
.customer-avatar {
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, var(--accent-color) 0%, #00d2ff 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 700;
    font-size: 14px;
    box-shadow: 0 4px 10px rgba(13, 110, 253, 0.15);
}
.dropdown-item i { width: 1.2rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('customer-search');
    const tableRows = document.querySelectorAll('#customer-table tbody tr');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        
        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
});
</script>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
