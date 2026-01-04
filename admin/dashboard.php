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

// Recent orders
$recent_orders = $pdo->query("
    SELECT o.*, u.full_name as customer_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Low stock products
$low_stock = $pdo->query("SELECT * FROM products WHERE stock < 5 ORDER BY stock ASC LIMIT 5")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .dashboard-section { background: #fff; padding: 24px; border-radius: 4px; margin-bottom: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.05); position: relative; overflow: hidden; }
    .section-title { font-size: 18px; font-weight: 700; margin-bottom: 20px; color: rgba(0,0,0,.87); display: flex; align-items: center; }
    .section-subtitle { font-size: 14px; color: rgba(0,0,0,.54); margin-bottom: 20px; }
    
    /* To-do List Style */
    .todo-item { text-align: center; padding: 16px; text-decoration: none; color: inherit; transition: background .2s; border-radius: 4px; }
    .todo-item:hover { background: #f6f6f6; }
    .todo-count { font-size: 20px; font-weight: 700; color: var(--shopee-orange); margin-bottom: 4px; }
    .todo-label { font-size: 14px; color: rgba(0,0,0,.87); }

    /* Business Insights Style */
    .insight-card { border: 1px solid #e8e8e8; padding: 20px; border-radius: 4px; }
    .insight-label { font-size: 14px; color: rgba(0,0,0,.54); margin-bottom: 8px; }
    .insight-value { font-size: 20px; font-weight: 700; color: rgba(0,0,0,.87); }

    /* Tet Accents */
    .tet-bg-decoration { position: absolute; top: -20px; right: -20px; font-size: 80px; opacity: 0.05; pointer-events: none; }
    .tet-badge { background: var(--tet-red); color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 12px; margin-left: 8px; }
</style>

<div class="container-fluid">
    <!-- To-do List Section -->
    <div class="dashboard-section">
        <div class="tet-bg-decoration">🧧</div>
        <div class="section-title">Danh sách cần làm <span class="tet-badge">Tết Giáp Thìn 2024</span></div>
        <div class="section-subtitle">Những việc bạn cần xử lý ngay</div>
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
                    <div class="todo-count">0</div>
                    <div class="todo-label">Đã Xử Lý</div>
                </a>
            </div>
            <div class="col border-start">
                <a href="products.php?stock=low" class="todo-item d-block">
                    <div class="todo-count"><?php echo count($low_stock); ?></div>
                    <div class="todo-label">Sản Phẩm Hết Hàng</div>
                </a>
            </div>
            <div class="col border-start">
                <a href="#" class="todo-item d-block">
                    <div class="todo-count">0</div>
                    <div class="todo-label">Chương Trình Khuyến Mãi Chờ Duyệt</div>
                </a>
            </div>
        </div>
    </div>

    <!-- Business Insights Section -->
    <div class="dashboard-section">
        <div class="tet-bg-decoration">🌸</div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="section-title mb-0">Phân Tích Bán Hàng</div>
                <div class="section-subtitle mb-0">Tổng quan dữ liệu bán hàng hôm nay (<?php echo date('d/m/Y'); ?>)</div>
            </div>
            <a href="analytics.php" class="text-shopee text-decoration-none small fw-bold">Xem chi tiết <i class="bi bi-chevron-right"></i></a>
        </div>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="insight-card">
                    <div class="insight-label">Lượt Truy Cập</div>
                    <div class="insight-value">1.240</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="insight-card">
                    <div class="insight-label">Đơn Hàng</div>
                    <div class="insight-value"><?php echo number_format($today_orders); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="insight-card">
                    <div class="insight-label">Doanh Thu</div>
                    <div class="insight-value"><?php echo number_format($today_revenue, 0, ',', '.'); ?> đ</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="insight-card">
                    <div class="insight-label">Tỉ Lệ Chuyển Đổi</div>
                    <div class="insight-value">2.5%</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Orders -->
        <div class="col-md-8">
            <div class="dashboard-section h-100 mb-0">
                <div class="section-title">Đơn hàng gần đây</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $o): ?>
                                <tr>
                                    <td>#<?php echo $o['id']; ?></td>
                                    <td><?php echo htmlspecialchars($o['customer_name'] ?: 'Khách vãng lai'); ?></td>
                                    <td class="text-danger fw-bold"><?php echo number_format($o['total'], 0, ',', '.'); ?> đ</td>
                                    <td>
                                        <?php
                                        $status_class = 'secondary';
                                        $status_text = $o['order_status'];
                                        switch($o['order_status']) {
                                            case 'dang_cho': $status_class = 'warning'; $status_text = 'Chờ xác nhận'; break;
                                            case 'da_xac_nhan': $status_class = 'info'; $status_text = 'Đã xác nhận'; break;
                                            case 'dang_xu_ly': $status_class = 'primary'; $status_text = 'Đang xử lý'; break;
                                            case 'da_giao': $status_class = 'success'; $status_text = 'Đã giao'; break;
                                            case 'huy': $status_class = 'danger'; $status_text = 'Đã hủy'; break;
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                    </td>
                                    <td><a href="order_detail.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-link text-decoration-none">Chi tiết</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Low Stock Warning -->
        <div class="col-md-4">
            <div class="dashboard-section h-100 mb-0">
                <div class="section-title text-danger">Cảnh báo hết hàng</div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($low_stock as $p): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div class="text-truncate me-2" style="max-width: 200px;">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </div>
                            <span class="badge bg-danger rounded-pill"><?php echo $p['stock']; ?></span>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($low_stock)): ?>
                        <li class="list-group-item text-muted text-center py-3 px-0">Không có sản phẩm nào sắp hết hàng</li>
                    <?php endif; ?>
                </ul>
                <div class="mt-3 text-center">
                    <a href="products.php" class="btn btn-sm btn-outline-secondary w-100">Quản lý kho</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
