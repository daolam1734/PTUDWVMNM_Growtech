<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// --- DATA FETCHING ---

// 1. Revenue for the last 7 days (Filling missing days with 0)
$revenue_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $revenue_data[$date] = 0;
}

$stmt = $pdo->query("
    SELECT DATE(created_at) as date, SUM(total) as total 
    FROM orders 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    AND order_status NOT IN ('huy', 'tra_lai')
    GROUP BY DATE(created_at)
");
while ($row = $stmt->fetch()) {
    if (isset($revenue_data[$row['date']])) {
        $revenue_data[$row['date']] = (float)$row['total'];
    }
}

$dates = [];
$revenues = [];
foreach ($revenue_data as $date => $total) {
    $dates[] = date('d/m', strtotime($date));
    $revenues[] = $total;
}

// 2. Top 5 Selling Products
$top_products = $pdo->query("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.unit_price * oi.quantity) as total_revenue
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status NOT IN ('huy', 'tra_lai')
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

// 3. Order Status Distribution (With Vietnamese Labels)
$status_map = [
    'dang_cho' => 'Chờ xác nhận',
    'da_xac_nhan' => 'Chờ lấy hàng',
    'dang_xu_ly' => 'Đang xử lý',
    'da_gui' => 'Đang giao',
    'da_giao' => 'Đã giao',
    'hoan_thanh' => 'Hoàn thành',
    'huy' => 'Đã hủy',
    'tra_lai' => 'Trả hàng'
];

$order_stats_raw = $pdo->query("
    SELECT order_status, COUNT(*) as count 
    FROM orders 
    GROUP BY order_status
")->fetchAll();

$status_labels = [];
$status_counts = [];
foreach ($order_stats_raw as $row) {
    $status_labels[] = $status_map[$row['order_status']] ?? $row['order_status'];
    $status_counts[] = (int)$row['count'];
}

// 4. Category Performance
$category_stats = $pdo->query("
    SELECT c.name, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    JOIN categories c ON p.category_id = c.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.order_status NOT IN ('huy', 'tra_lai')
    GROUP BY c.id
    ORDER BY total_sold DESC
")->fetchAll();

// 5. Today's Orders Count
$today_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn() ?: 0;

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .analytics-card { background: #fff; padding: 24px; border-radius: 1.25rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.04); margin-bottom: 24px; border: 1px solid rgba(0,0,0,0.05); }
    .card-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 1.5rem; color: #1e293b; display: flex; align-items: center; }
    .card-title i { margin-right: 0.75rem; font-size: 1.25rem; }
    
    .stat-box { background: #fff; padding: 24px; border-radius: 1.25rem; text-align: left; border: 1px solid rgba(0,0,0,0.05); transition: all 0.3s; height: 100%; position: relative; overflow: hidden; }
    .stat-box:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,.05); }
    .stat-value { font-size: 1.75rem; font-weight: 800; color: #1e293b; margin-bottom: 0.25rem; line-height: 1.2; }
    .stat-label { font-size: 0.875rem; color: #64748b; font-weight: 600; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.025em; }
    .chart-container { position: relative; height: 320px; width: 100%; }
    
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1) !important; color: #0d6efd !important; }
    .bg-soft-success { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
    .bg-soft-warning { background-color: rgba(255, 193, 7, 0.1) !important; color: #ffc107 !important; }
    .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1) !important; color: #dc3545 !important; }
    .bg-soft-info { background-color: rgba(13, 202, 240, 0.1) !important; color: #0dcaf0 !important; }

    .stat-icon-bg { position: absolute; right: -10px; bottom: -10px; font-size: 80px; opacity: 0.05; transform: rotate(-15deg); }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item small"><a href="dashboard.php" class="text-decoration-none text-muted">Bảng điều khiển</a></li>
                    <li class="breadcrumb-item small active" aria-current="page">Phân tích dữ liệu</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-0">Trung Tâm Phân Tích</h4>
                    <p class="text-muted small mb-0">Dữ liệu chi tiết về hiệu quả kinh doanh của shop trong năm 2026.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm bg-white" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                    </button>
                    <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                        <i class="bi bi-cloud-download me-1"></i> Xuất Excel
                    </button>
                </div>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Doanh thu tháng này</div>
                    <div class="stat-value">
                        <?php 
                        $monthly_rev = $pdo->query("SELECT SUM(total) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND order_status NOT IN ('huy', 'tra_lai')")->fetchColumn();
                        echo number_format($monthly_rev ?: 0, 0, ',', '.'); 
                        ?>đ
                    </div>
                    <div class="small fw-bold text-success"><i class="bi bi-graph-up-arrow me-1"></i> +8.2% <span class="fw-normal text-muted ms-1">so với t.trước</span></div>
                    <i class="bi bi-currency-dollar stat-icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Đơn hàng mới hôm nay</div>
                    <div class="stat-value">
                        <?php echo $today_orders; ?>
                    </div>
                    <div class="small fw-bold text-primary"><i class="bi bi-cart-check me-1"></i> +5.4% <span class="fw-normal text-muted ms-1">so với hôm qua</span></div>
                    <i class="bi bi-cart3 stat-icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box border-soft-danger">
                    <div class="stat-label">Khách hàng mới t.này</div>
                    <div class="stat-value">
                        <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn(); ?>
                    </div>
                    <div class="small fw-bold text-danger"><i class="bi bi-graph-down-arrow me-1"></i> -1.2% <span class="fw-normal text-muted ms-1">so với t.trước</span></div>
                    <i class="bi bi-people stat-icon-bg"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Tỷ lệ hủy đơn hàng</div>
                    <div class="stat-value">
                        <?php 
                        $total = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                        $cancelled = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'huy'")->fetchColumn();
                        echo $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
                        ?>%
                    </div>
                    <div class="small fw-bold text-info"><i class="bi bi-info-circle me-1"></i> Mức an toàn <span class="fw-normal text-muted ms-1">(Dưới 3%)</span></div>
                    <i class="bi bi-x-circle stat-icon-bg"></i>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Revenue Chart -->
                <div class="analytics-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="card-title mb-0"><i class="bi bi-activity text-primary"></i> Biểu đồ doanh thu 7 ngày</div>
                        <div class="badge bg-soft-primary px-3 py-2 rounded-pill">Theo ngày</div>
                    </div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="analytics-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="card-title mb-0"><i class="bi bi-lightning-charge text-warning"></i> Sản phẩm "Bùng Nổ" nhất</div>
                        <a href="products.php" class="small text-decoration-none">Xem tất cả</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-0">Tên sản phẩm</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-end">Doanh thu tạm tính</th>
                                    <th class="text-end pe-0">Hiệu suất</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $p): ?>
                                <tr>
                                    <td class="ps-0 fw-bold text-dark text-truncate" style="max-width: 280px;"><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td class="text-center"><span class="badge bg-soft-primary rounded-pill px-3"><?php echo $p['total_sold']; ?> sp</span></td>
                                    <td class="text-end fw-bold text-primary"><?php echo number_format($p['total_revenue'], 0, ',', '.'); ?>đ</td>
                                    <td class="text-end pe-0">
                                        <div class="d-flex align-items-center justify-content-end text-success fw-bold small">
                                            <i class="bi bi-arrow-up-right me-1"></i> Tốt
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Status Distribution -->
                <div class="analytics-card">
                    <div class="card-title mb-4"><i class="bi bi-funnel text-info"></i> Luồng đơn hàng</div>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Category Performance -->
                <div class="analytics-card">
                    <div class="card-title mb-3"><i class="bi bi-grid-1x2 text-success"></i> Tỷ trọng ngành hàng</div>
                    <div class="list-group list-group-flush mt-2">
                        <?php foreach ($category_stats as $cat): ?>
                        <div class="list-group-item px-0 py-3 border-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span>
                                <span class="small text-muted"><?php echo $cat['total_sold']; ?> đã bán</span>
                            </div>
                            <div class="progress rounded-pill bg-light" style="height: 8px;">
                                <?php 
                                $max_sold = isset($category_stats[0]['total_sold']) ? ($category_stats[0]['total_sold'] ?: 1) : 1;
                                $percent = ($cat['total_sold'] / $max_sold) * 100;
                                ?>
                                <div class="progress-bar bg-primary rounded-pill shadow-sm" style="width: <?php echo $percent; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    const gradient = revCtx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(44, 62, 80, 0.1)');
    gradient.addColorStop(1, 'rgba(44, 62, 80, 0)');

    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Doanh thu',
                data: <?php echo json_encode($revenues); ?>,
                borderColor: '#2c3e50',
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                borderWidth: 3,
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
                    backgroundColor: '#2c3e50',
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
                        callback: value => value.toLocaleString() + ' đ' 
                    }
                },
                x: { 
                    grid: { display: false, drawBorder: false },
                    ticks: { font: { size: 11 }, color: '#999' }
                }
            }
        }
    });

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_counts); ?>,
                backgroundColor: ['#ff8a00', '#00b0ff', '#00bfa5', '#ff4d4f', '#2f54eb', '#722ed1', '#eb2f96', '#faad14'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'bottom', 
                    labels: { 
                        boxWidth: 8, 
                        usePointStyle: true,
                        padding: 15,
                        font: { size: 11 } 
                    } 
                }
            },
            cutout: '75%'
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
