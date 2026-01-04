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

require_once __DIR__ . '/includes/header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
    .analytics-card { background: #fff; padding: 24px; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,.05); margin-bottom: 24px; }
    .card-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; color: rgba(0,0,0,.87); }
    .stat-box { border: 1px solid #e8e8e8; padding: 16px; border-radius: 4px; text-align: center; }
    .stat-value { font-size: 24px; font-weight: 700; color: var(--shopee-orange); }
    .stat-label { font-size: 14px; color: rgba(0,0,0,.54); }
    .chart-container { position: relative; height: 300px; width: 100%; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Phân Tích Bán Hàng</h4>
        <div class="text-muted small">Cập nhật lần cuối: <?php echo date('H:i d/m/Y'); ?></div>
    </div>

    <!-- Overview Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="analytics-card mb-0">
                <div class="stat-label">Tổng doanh thu (Tháng này)</div>
                <div class="stat-value">
                    <?php 
                    $monthly_rev = $pdo->query("SELECT SUM(total) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND order_status NOT IN ('huy', 'tra_lai')")->fetchColumn();
                    echo number_format($monthly_rev ?: 0, 0, ',', '.'); 
                    ?> đ
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card mb-0">
                <div class="stat-label">Đơn hàng mới (Hôm nay)</div>
                <div class="stat-value">
                    <?php echo $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn(); ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card mb-0">
                <div class="stat-label">Khách hàng mới (Tháng này)</div>
                <div class="stat-value">
                    <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn(); ?>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card mb-0">
                <div class="stat-label">Tỷ lệ hủy đơn</div>
                <div class="stat-value">
                    <?php 
                    $total = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
                    $cancelled = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'huy'")->fetchColumn();
                    echo $total > 0 ? round(($cancelled / $total) * 100, 1) : 0;
                    ?>%
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Revenue Chart -->
        <div class="col-md-8">
            <div class="analytics-card">
                <div class="card-title">Biểu đồ doanh thu (7 ngày qua)</div>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Order Status Distribution -->
        <div class="col-md-4">
            <div class="analytics-card">
                <div class="card-title">Trạng thái đơn hàng</div>
                <div class="chart-container">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-md-6">
            <div class="analytics-card">
                <div class="card-title">Top 5 sản phẩm bán chạy</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sản phẩm</th>
                                <th class="text-center">Đã bán</th>
                                <th class="text-end">Doanh thu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $p): ?>
                            <tr>
                                <td class="text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($p['name']); ?></td>
                                <td class="text-center"><?php echo $p['total_sold']; ?></td>
                                <td class="text-end fw-bold"><?php echo number_format($p['total_revenue'], 0, ',', '.'); ?> đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Category Performance -->
        <div class="col-md-6">
            <div class="analytics-card">
                <div class="card-title">Hiệu suất theo danh mục</div>
                <div class="chart-container" style="height: 200px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Revenue Chart
    const revCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($dates); ?>,
            datasets: [{
                label: 'Doanh thu (đ)',
                data: <?php echo json_encode($revenues); ?>,
                borderColor: '#ee4d2d',
                backgroundColor: 'rgba(238, 77, 45, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: value => value.toLocaleString() + ' đ' } }
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
                backgroundColor: ['#ff8a00', '#00b0ff', '#00bfa5', '#ff4d4f', '#2f54eb', '#722ed1', '#eb2f96', '#faad14']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Category Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(catCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_map(fn($c) => $c['name'], $category_stats)); ?>,
            datasets: [{
                label: 'Số lượng bán',
                data: <?php echo json_encode(array_map(fn($c) => (int)$c['total_sold'], $category_stats)); ?>,
                backgroundColor: '#ffc107'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
