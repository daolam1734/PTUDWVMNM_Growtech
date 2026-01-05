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
    .analytics-card { background: #fff; padding: 24px; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,.04); margin-bottom: 24px; border: 1px solid #f0f0f0; }
    .card-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; color: #2c3e50; display: flex; align-items: center; }
    .card-title i { margin-right: 10px; color: #2c3e50; }
    
    .stat-box { background: #fff; padding: 24px; border-radius: 16px; text-align: center; border: 1px solid #f0f0f0; transition: all 0.3s; height: 100%; box-shadow: 0 2px 8px rgba(0,0,0,.02); }
    .stat-box:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,.05); }
    .stat-value { font-size: 24px; font-weight: 800; color: #2c3e50; margin-bottom: 4px; }
    .stat-label { font-size: 13px; color: #6c757d; font-weight: 500; margin-bottom: 8px; }
    .chart-container { position: relative; height: 320px; width: 100%; }
    
    .table-modern thead th { background: #f8f9fa; color: #6c757d; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border: none; padding: 15px; }
    .table-modern tbody td { padding: 15px; font-size: 14px; border-bottom: 1px solid #f8f9fa; vertical-align: middle; }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Phân tích</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Phân Tích Bán Hàng</h4>
                    <p class="text-muted small mb-0">Dữ liệu chi tiết về hiệu quả kinh doanh của shop.</p>
                </div>
                <div class="d-flex gap-2">
                    <div class="text-muted small align-self-center me-2 bg-white px-3 py-2 rounded-3 border shadow-sm">Cập nhật: <span class="fw-bold"><?php echo date('H:i d/m/Y'); ?></span></div>
                    <button class="btn btn-primary shadow-sm btn-sm px-3 rounded-3"><i class="bi bi-download me-2"></i>Tải báo cáo</button>
                </div>
            </div>
        </div>

        <!-- Overview Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Doanh thu (Tháng này)</div>
                    <div class="stat-value">
                        <?php 
                        $monthly_rev = $pdo->query("SELECT SUM(total) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND order_status NOT IN ('huy', 'tra_lai')")->fetchColumn();
                        echo number_format($monthly_rev ?: 0, 0, ',', '.'); 
                        ?>đ
                    </div>
                    <div class="small text-success fw-bold"><i class="bi bi-arrow-up-short fs-5"></i> 8.2%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Đơn hàng mới (Hôm nay)</div>
                    <div class="stat-value">
                        <?php echo $today_orders; ?>
                    </div>
                    <div class="small text-success fw-bold"><i class="bi bi-arrow-up-short fs-5"></i> 5.4%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Khách hàng mới (Tháng này)</div>
                    <div class="stat-value">
                        <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn(); ?>
                    </div>
                    <div class="small text-danger fw-bold"><i class="bi bi-arrow-down-short fs-5"></i> 1.2%</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-label">Tỷ lệ hủy đơn</div>
                    <div class="stat-value">
                        <?php 
                        $total = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                        $cancelled = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'huy'")->fetchColumn();
                        echo $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
                        ?>%
                    </div>
                    <div class="small text-success fw-bold"><i class="bi bi-check-circle me-1"></i> Ổn định</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Revenue Chart -->
                <div class="analytics-card">
                    <div class="card-title"><i class="bi bi-graph-up"></i> Biểu đồ doanh thu (7 ngày qua)</div>
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="analytics-card">
                    <div class="card-title"><i class="bi bi-star"></i> Sản phẩm bán chạy nhất</div>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle">
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-center">Đã bán</th>
                                    <th class="text-end">Doanh thu</th>
                                    <th class="text-end">Xu hướng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_products as $p): ?>
                                <tr>
                                    <td class="fw-bold text-dark text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($p['name']); ?></td>
                                    <td class="text-center"><span class="badge bg-light text-dark rounded-pill px-3"><?php echo $p['total_sold']; ?></span></td>
                                    <td class="text-end fw-bold text-primary"><?php echo number_format($p['total_revenue'], 0, ',', '.'); ?>đ</td>
                                    <td class="text-end text-success"><i class="bi bi-caret-up-fill"></i></td>
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
                    <div class="card-title"><i class="bi bi-pie-chart"></i> Trạng thái đơn hàng</div>
                    <div class="chart-container" style="height: 280px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- Category Performance -->
                <div class="analytics-card">
                    <div class="card-title"><i class="bi bi-tags"></i> Hiệu quả theo danh mục</div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($category_stats as $cat): ?>
                        <div class="list-group-item px-0 py-3 border-0 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-dark"><?php echo htmlspecialchars($cat['name']); ?></span>
                                <span class="small text-muted"><?php echo $cat['total_sold']; ?> đã bán</span>
                            </div>
                            <div class="progress rounded-pill" style="height: 6px;">
                                <?php 
                                $max_sold = isset($category_stats[0]['total_sold']) ? ($category_stats[0]['total_sold'] ?: 1) : 1;
                                $percent = ($cat['total_sold'] / $max_sold) * 100;
                                ?>
                                <div class="progress-bar bg-primary rounded-pill" style="width: <?php echo $percent; ?>%"></div>
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
