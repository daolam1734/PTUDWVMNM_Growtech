<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Statistics for "Business Insights"
$today_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$today_revenue = $pdo->query("SELECT SUM(total) FROM orders WHERE DATE(created_at) = CURDATE() AND order_status NOT IN ('huy', 'tra_lai')")->fetchColumn() ?: 0;
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'dang_cho'")->fetchColumn();
$processed_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'da_xac_nhan'")->fetchColumn();
$shipped_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'da_gui'")->fetchColumn();
$flash_sale_count = $pdo->query("SELECT COUNT(*) FROM products WHERE sale_price IS NOT NULL AND sale_price < price")->fetchColumn();
$active_vouchers = $pdo->query("SELECT COUNT(*) FROM vouchers WHERE is_active = 1 AND (end_date >= NOW() OR end_date IS NULL)")->fetchColumn();

// Total counts
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total) FROM orders WHERE order_status NOT IN ('huy', 'tra_lai')")->fetchColumn() ?: 0;

// Revenue for last 7 days
$revenue_7days = $pdo->query("
    SELECT DATE(created_at) as date, SUM(total) as total 
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    AND order_status NOT IN ('huy', 'tra_lai')
    GROUP BY DATE(created_at)
    ORDER BY DATE(created_at) ASC
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fill missing days with 0
$chart_labels = [];
$chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d/m', strtotime($date));
    $chart_data[] = $revenue_7days[$date] ?? 0;
}

// Recent orders
$recent_orders = $pdo->query("
    SELECT o.*, u.full_name as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 8
")->fetchAll();

// Low stock products
$low_stock = $pdo->query("SELECT * FROM products WHERE stock < 10 ORDER BY stock ASC LIMIT 5")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --accent-color: #2c3e50;
        --shopee-orange: #ee4d2d;
    }
    .dashboard-section { 
        background: #fff; 
        padding: 24px; 
        border-radius: 16px; 
        margin-bottom: 24px; 
        box-shadow: 0 2px 12px rgba(0,0,0,.04); 
        border: 1px solid #f0f0f0; 
        position: relative; 
        overflow: hidden; 
    }
    .section-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #2c3e50; display: flex; align-items: center; justify-content: space-between; }
    
    /* Stats Cards */
    .stat-card { border: none; border-radius: 16px; padding: 20px; height: 100%; transition: all 0.3s; background: #fff; border: 1px solid #f0f0f0; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,.05); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 15px; }
    .stat-value { font-size: 24px; font-weight: 800; color: #2c3e50; margin-bottom: 4px; }
    .stat-label { font-size: 13px; color: #6c757d; font-weight: 500; }
    .stat-trend { font-size: 12px; margin-top: 8px; display: flex; align-items: center; }

    /* To-do List Style */
    .todo-item { text-align: center; padding: 20px; text-decoration: none; color: inherit; transition: all .2s; border-radius: 12px; }
    .todo-item:hover { background: #f8f9fa; }
    .todo-count { font-size: 24px; font-weight: 800; color: var(--shopee-orange); margin-bottom: 4px; }
    .todo-label { font-size: 13px; color: #495057; font-weight: 500; }

    /* Table Style */
    .table-modern thead th { background: #f8f9fa; border-bottom: none; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; padding: 15px; }
    .table-modern tbody td { padding: 15px; vertical-align: middle; font-size: 14px; border-bottom: 1px solid #f8f9fa; }
    
    .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
    .status-dang_cho { background: #fff4e5; color: #ff9800; }
    .status-da_xac_nhan { background: #e3f2fd; color: #2196f3; }
    .status-da_gui { background: #e8f5e9; color: #4caf50; }
    .status-huy { background: #ffebee; color: #f44336; }

    .tet-badge { background: #d32f2f; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; margin-left: 10px; font-weight: 600; letter-spacing: 0.5px; }
    
    .chart-container { position: relative; height: 320px; width: 100%; }
    
    .insight-card { padding: 15px; border-radius: 12px; background: #f8f9fa; border: 1px solid #eee; height: 100%; }
    .insight-label { font-size: 12px; color: #6c757d; margin-bottom: 5px; }
    .insight-value { font-size: 18px; font-weight: 700; color: #2c3e50; }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Bảng Điều Khiển</h4>
                <p class="text-muted small mb-0">Chào mừng trở lại! Đây là tổng quan hoạt động kinh doanh của bạn.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-white border shadow-sm btn-sm px-3 rounded-3"><i class="bi bi-download me-2"></i>Xuất báo cáo</button>
                <a href="add_product.php" class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm"><i class="bi bi-plus-lg me-2"></i>Thêm sản phẩm</a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-currency-dollar"></i></div>
                    <div class="stat-label">Doanh thu hôm nay</div>
                    <div class="stat-value"><?php echo number_format($today_revenue); ?>đ</div>
                    <div class="stat-trend text-success"><i class="bi bi-arrow-up-short fs-5"></i>12.5% <span class="text-muted ms-1">so với hôm qua</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-cart-check"></i></div>
                    <div class="stat-label">Đơn hàng mới</div>
                    <div class="stat-value"><?php echo $today_orders; ?></div>
                    <div class="stat-trend text-success"><i class="bi bi-arrow-up-short fs-5"></i>5.2% <span class="text-muted ms-1">so với hôm qua</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-people"></i></div>
                    <div class="stat-label">Khách hàng mới</div>
                    <div class="stat-value"><?php echo $total_customers; ?></div>
                    <div class="stat-trend text-danger"><i class="bi bi-arrow-down-short fs-5"></i>2.1% <span class="text-muted ms-1">so với tuần trước</span></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-info bg-opacity-10 text-info"><i class="bi bi-box-seam"></i></div>
                    <div class="stat-label">Tổng sản phẩm</div>
                    <div class="stat-value"><?php echo $total_products; ?></div>
                    <div class="stat-trend text-muted">Đang kinh doanh</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="dashboard-section">
                    <div class="section-title">
                        <span>Danh sách cần làm <span class="tet-badge">Tết Bính Ngọ 2026</span></span>
                    </div>
                    <div class="row g-0">
                        <div class="col">
                            <a href="orders.php?status=dang_cho" class="todo-item d-block">
                                <div class="todo-count"><?php echo $pending_orders; ?></div>
                                <div class="todo-label">Chờ Xác Nhận</div>
                            </a>
                        </div>
                        <div class="col border-start">
                            <a href="orders.php?status=da_xac_nhan" class="todo-item d-block">
                                <div class="todo-count"><?php echo $processed_orders; ?></div>
                                <div class="todo-label">Chờ Lấy Hàng</div>
                            </a>
                        </div>
                        <div class="col border-start">
                            <a href="orders.php?status=da_gui" class="todo-item d-block">
                                <div class="todo-count"><?php echo $shipped_orders; ?></div>
                                <div class="todo-label">Đang Giao</div>
                            </a>
                        </div>
                        <div class="col border-start">
                            <a href="flash_sales.php" class="todo-item d-block">
                                <div class="todo-count"><?php echo $flash_sale_count; ?></div>
                                <div class="todo-label">Flash Sale</div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="dashboard-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="section-title mb-0">Phân Tích Doanh Thu</div>
                        <select class="form-select form-select-sm w-auto border-0 bg-light">
                            <option>7 ngày qua</option>
                            <option>30 ngày qua</option>
                        </select>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="dashboard-section">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="section-title mb-0">Đơn Hàng Gần Đây</div>
                        <a href="orders.php" class="btn btn-link btn-sm text-primary text-decoration-none fw-bold">Xem tất cả</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Mã Đơn</th>
                                    <th>Khách Hàng</th>
                                    <th>Ngày Đặt</th>
                                    <th>Tổng Tiền</th>
                                    <th>Trạng Thái</th>
                                    <th class="text-end">#</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td class="fw-bold text-dark">#<?php echo $order['id']; ?></td>
                                    <td>
                                        <div class="fw-medium"><?php echo htmlspecialchars($order['customer_name'] ?: 'Khách vãng lai'); ?></div>
                                    </td>
                                    <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td class="fw-bold text-primary"><?php echo number_format($order['total']); ?>đ</td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php
                                            switch($order['order_status']) {
                                                case 'dang_cho': echo 'Chờ xác nhận'; break;
                                                case 'da_xac_nhan': echo 'Đã xác nhận'; break;
                                                case 'da_gui': echo 'Đang giao'; break;
                                                case 'da_giao': echo 'Đã giao'; break;
                                                case 'huy': echo 'Đã hủy'; break;
                                                default: echo $order['order_status'];
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-light rounded-3 border-0"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Business Insights -->
                <div class="dashboard-section">
                    <div class="section-title">Chỉ Số Quan Trọng</div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="insight-card text-center">
                                <div class="insight-label">Tỉ lệ hủy</div>
                                <div class="insight-value text-danger">2.4%</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="insight-card text-center">
                                <div class="insight-label">Tỉ lệ phản hồi</div>
                                <div class="insight-value text-success">98%</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="insight-card text-center">
                                <div class="insight-label">Voucher active</div>
                                <div class="insight-value text-primary"><?php echo $active_vouchers; ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="insight-card text-center">
                                <div class="insight-label">Đánh giá shop</div>
                                <div class="insight-value text-warning">4.9 <i class="bi bi-star-fill ms-1"></i></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="dashboard-section">
                    <div class="section-title text-danger"><span><i class="bi bi-exclamation-triangle me-2"></i>Sắp Hết Hàng</span></div>
                    <?php if (empty($low_stock)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-check2-circle fs-1 text-success mb-2 d-block"></i>
                            <p class="text-muted small mb-0">Kho hàng của bạn đang rất tốt!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($low_stock as $item): 
                                $img = getProductImage($item['id']);
                            ?>
                            <div class="list-group-item px-0 py-3 border-0 border-bottom">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($img); ?>" class="rounded-3 me-3" style="width: 44px; height: 44px; object-fit: cover;" onerror="this.src='https://placehold.co/400x400?text=No+Image'">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-bold small text-truncate mb-1"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="text-danger small fw-bold">Còn lại: <?php echo $item['stock']; ?></div>
                                    </div>
                                    <a href="edit_product.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size: 11px;">Nhập</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="products.php" class="text-decoration-none small fw-bold">Xem tất cả kho hàng <i class="bi bi-arrow-right ms-1"></i></a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- System Status -->
                <div class="dashboard-section bg-light border-0">
                    <div class="section-title small mb-3">Trạng Thái Hệ Thống</div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="spinner-grow spinner-grow-sm text-success me-3" role="status" style="width: 10px; height: 10px;"></div>
                        <span class="small fw-medium">Máy chủ: Hoạt động tốt</span>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-database-check text-success me-3 fs-6"></i>
                        <span class="small fw-medium">Cơ sở dữ liệu: Đã kết nối</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-clock text-muted me-3 fs-6"></i>
                        <span class="small text-muted">Cập nhật: <?php echo date('H:i:s'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(44, 62, 80, 0.1)');
    gradient.addColorStop(1, 'rgba(44, 62, 80, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Doanh thu',
                data: <?php echo json_encode($chart_data); ?>,
                borderColor: '#2c3e50',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
                pointHoverBackgroundColor: '#2c3e50',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    backgroundColor: '#2c3e50',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return ' ' + context.parsed.y.toLocaleString('vi-VN') + 'đ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [5, 5], color: '#f0f0f0', drawBorder: false },
                    ticks: {
                        font: { size: 11 },
                        color: '#999',
                        callback: function(value) {
                            if (value >= 1000000) return (value / 1000000) + 'M';
                            if (value >= 1000) return (value / 1000) + 'K';
                            return value;
                        }
                    }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { size: 11 }, color: '#999' }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
