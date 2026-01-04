<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
    if ($stmt->execute([$status, $order_id])) {
        set_flash("success", "Cập nhật trạng thái đơn hàng #$order_id thành công.");
    } else {
        set_flash("error", "Lỗi khi cập nhật trạng thái.");
    }
    header("Location: orders.php");
    exit;
}

// Filter by status if provided
$status_filter = $_GET['status'] ?? 'all';
$query = "
    SELECT o.*, u.full_name as customer_name, ua.phone as customer_phone
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN user_addresses ua ON o.address_id = ua.id
";
if ($status_filter !== 'all') {
    $query .= " WHERE o.order_status = " . $pdo->quote($status_filter);
}
$query .= " ORDER BY o.created_at DESC";
$orders = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .order-section { background: #fff; padding: 0; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .order-tabs { display: flex; border-bottom: 1px solid #e8e8e8; background: #fff; padding: 0 20px; overflow-x: auto; }
    .order-tab { padding: 16px 20px; cursor: pointer; color: rgba(0,0,0,.87); text-decoration: none; border-bottom: 2px solid transparent; font-size: 14px; white-space: nowrap; }
    .order-tab:hover { color: var(--shopee-orange); }
    .order-tab.active { color: var(--shopee-orange); border-bottom-color: var(--shopee-orange); font-weight: 500; }
    
    .order-search { padding: 20px; background: #fff; border-bottom: 1px solid #f0f0f0; }
    .order-table thead th { background: #f6f6f6; color: rgba(0,0,0,.54); font-weight: 500; font-size: 14px; border: none; padding: 12px 16px; }
    .order-table tbody td { padding: 16px; font-size: 14px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
    
    .status-badge { padding: 4px 8px; border-radius: 2px; font-size: 12px; font-weight: 500; }
    .status-dang_cho { background: #fff4e5; color: #ff8a00; }
    .status-da_xac_nhan { background: #e5f9ff; color: #00b0ff; }
    .status-da_giao { background: #e5fffa; color: #00bfa5; }
    .status-huy { background: #fff0f0; color: #ff4d4f; }
    .status-da_gui { background: #f0f5ff; color: #2f54eb; }
</style>

<div class="container-fluid">
    <div class="order-section">
        <!-- Order Tabs -->
        <div class="order-tabs">
            <a href="orders.php?status=all" class="order-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">Tất cả</a>
            <a href="orders.php?status=dang_cho" class="order-tab <?php echo $status_filter === 'dang_cho' ? 'active' : ''; ?>">Chờ xác nhận</a>
            <a href="orders.php?status=da_xac_nhan" class="order-tab <?php echo $status_filter === 'da_xac_nhan' ? 'active' : ''; ?>">Chờ lấy hàng</a>
            <a href="orders.php?status=da_gui" class="order-tab <?php echo $status_filter === 'da_gui' ? 'active' : ''; ?>">Đang giao</a>
            <a href="orders.php?status=da_giao" class="order-tab <?php echo $status_filter === 'da_giao' ? 'active' : ''; ?>">Đã giao</a>
            <a href="orders.php?status=huy" class="order-tab <?php echo $status_filter === 'huy' ? 'active' : ''; ?>">Đơn hủy</a>
        </div>

        <!-- Search Bar -->
        <div class="order-search">
            <form class="row g-3">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" placeholder="Tìm đơn hàng theo Mã đơn hàng hoặc Tên khách hàng">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-shopee-primary px-4">Tìm kiếm</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table order-table mb-0">
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Khách hàng</th>
                        <th>Tổng thanh toán</th>
                        <th>Trạng thái</th>
                        <th>Vận chuyển</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>
                            <div class="fw-bold">#<?php echo $o['id']; ?></div>
                            <div class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></div>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($o['customer_name'] ?: 'Khách vãng lai'); ?></div>
                            <div class="small text-muted"><?php echo htmlspecialchars($o['customer_phone']); ?></div>
                        </td>
                        <td>
                            <div class="text-danger fw-bold"><?php echo number_format($o['total'], 0, ',', '.'); ?> đ</div>
                            <div class="small text-muted"><?php echo str_replace('_', ' ', strtoupper($o['payment_method'])); ?></div>
                        </td>
                        <td>
                            <?php
                            $status_text = 'Chờ xử lý';
                            switch($o['order_status']) {
                                case 'dang_cho': $status_text = 'Chờ xác nhận'; break;
                                case 'da_xac_nhan': $status_text = 'Chờ lấy hàng'; break;
                                case 'dang_xu_ly': $status_text = 'Đang xử lý'; break;
                                case 'da_gui': $status_text = 'Đang giao'; break;
                                case 'da_giao': $status_text = 'Đã giao'; break;
                                case 'hoan_thanh': $status_text = 'Hoàn thành'; break;
                                case 'huy': $status_text = 'Đã hủy'; break;
                            }
                            ?>
                            <span class="status-badge status-<?php echo $o['order_status']; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td>
                            <div class="small">Nhanh</div>
                            <div class="small text-muted">Shopee Express</div>
                        </td>
                        <td>
                            <div class="dropdown">
                                <a href="#" class="text-primary text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">Xử lý</a>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                    <li><a class="dropdown-item py-2" href="order_detail.php?id=<?php echo $o['id']; ?>">Xem chi tiết</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" class="px-3 py-1">
                                            <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                            <select name="status" class="form-select form-select-sm mb-2" onchange="this.form.submit()">
                                                <option value="">-- Đổi trạng thái --</option>
                                                <option value="dang_cho" <?php echo $o['order_status'] == 'dang_cho' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                                <option value="da_xac_nhan" <?php echo $o['order_status'] == 'da_xac_nhan' ? 'selected' : ''; ?>>Chờ lấy hàng</option>
                                                <option value="da_gui" <?php echo $o['order_status'] == 'da_gui' ? 'selected' : ''; ?>>Đang giao</option>
                                                <option value="da_giao" <?php echo $o['order_status'] == 'da_giao' ? 'selected' : ''; ?>>Đã giao</option>
                                                <option value="huy" <?php echo $o['order_status'] == 'huy' ? 'selected' : ''; ?>>Hủy đơn</option>
                                            </select>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Không tìm thấy đơn hàng nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
