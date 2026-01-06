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
    SELECT o.*, u.full_name as customer_name, ua.phone as customer_phone, v.code as voucher_code
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN user_addresses ua ON o.address_id = ua.id
    LEFT JOIN vouchers v ON o.voucher_id = v.id
";
if ($status_filter !== 'all') {
    $query .= " WHERE o.order_status = " . $pdo->quote($status_filter);
}
$query .= " ORDER BY o.created_at DESC";
$orders = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --accent-color: #2c3e50;
        --shopee-orange: #ee4d2d;
    }
    .order-section { background: #fff; padding: 0; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.04); border: 1px solid #eee; overflow: hidden; }
    .order-tabs { display: flex; border-bottom: 1px solid #eee; background: #fff; padding: 0 10px; overflow-x: auto; }
    .order-tab { 
        padding: 18px 20px; 
        cursor: pointer; 
        color: #6c757d; 
        text-decoration: none; 
        border-bottom: 3px solid transparent; 
        font-size: 14px; 
        white-space: nowrap; 
        font-weight: 600; 
        transition: all 0.2s; 
    }
    .order-tab:hover { color: var(--accent-color); }
    .order-tab.active { color: var(--accent-color); border-bottom-color: var(--accent-color); }
    
    .table-modern thead th { 
        background: #f8f9fa; 
        border-bottom: none; 
        font-size: 11px; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        color: #6c757d; 
        padding: 15px; 
    }
    .table-modern tbody td { 
        padding: 15px; 
        vertical-align: middle; 
        font-size: 14px; 
        border-bottom: 1px solid #f8f9fa; 
        color: #2c3e50;
    }
    
    .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
    .status-dang_cho { background: #fff4e5; color: #ff9800; }
    .status-da_xac_nhan { background: #e3f2fd; color: #2196f3; }
    .status-da_gui { background: #f0f5ff; color: #2f54eb; }
    .status-da_giao { background: #e8f5e9; color: #4caf50; }
    .status-huy { background: #ffebee; color: #f44336; }
    
    .customer-avatar { 
        width: 36px; 
        height: 36px; 
        background: #f0f2f5; 
        border-radius: 10px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        margin-right: 12px; 
        font-weight: bold; 
        color: #2c3e50; 
        font-size: 14px; 
    }

    .btn-action { 
        width: 34px; 
        height: 34px; 
        padding: 0; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 10px; 
        background: #f8f9fa;
        color: #4b5563;
        border: 1px solid #e5e7eb;
        transition: all 0.2s; 
    }
    .btn-action:hover { 
        background: #fff;
        color: var(--accent-color);
        border-color: var(--accent-color);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Quản Lý Đơn Hàng</h4>
                <p class="text-muted small mb-0">Theo dõi, cập nhật trạng thái và xử lý các đơn hàng của khách.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-white border shadow-sm px-3 rounded-pill btn-sm fw-bold"><i class="bi bi-download me-2"></i> Xuất Excel</button>
            </div>
        </div>

        <div class="dashboard-section p-0">
            <!-- Order Tabs -->
            <div class="order-tabs px-3">
                <a href="orders.php?status=all" class="order-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">Tất cả</a>
                <a href="orders.php?status=dang_cho" class="order-tab <?php echo $status_filter === 'dang_cho' ? 'active' : ''; ?>">Chờ xác nhận</a>
                <a href="orders.php?status=da_xac_nhan" class="order-tab <?php echo $status_filter === 'da_xac_nhan' ? 'active' : ''; ?>">Chờ lấy hàng</a>
                <a href="orders.php?status=da_gui" class="order-tab <?php echo $status_filter === 'da_gui' ? 'active' : ''; ?>">Đang giao</a>
                <a href="orders.php?status=da_giao" class="order-tab <?php echo $status_filter === 'da_giao' ? 'active' : ''; ?>">Đã giao</a>
                <a href="orders.php?status=huy" class="order-tab <?php echo $status_filter === 'huy' ? 'active' : ''; ?>">Đơn hủy</a>
            </div>

            <!-- Search Bar -->
            <div class="p-4 bg-light bg-opacity-10 border-bottom">
                <form class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 shadow-sm"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-0 shadow-sm" placeholder="Tìm theo Mã đơn, tên khách, số điện thoại...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select border-0 shadow-sm">
                            <option value="">Tất cả phương thức thanh toán</option>
                            <option value="cod">COD - Khi nhận hàng</option>
                            <option value="vnpay">VNPAY Online</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100 rounded-pill fw-bold">Tìm kiếm</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Hình thức</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-cart-x fs-1 text-muted mb-3 d-block"></i>
                                <p class="text-muted mb-0">Không tìm thấy đơn hàng nào khớp với yêu cầu.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="ps-4 fw-bold">#<?php echo $o['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="customer-avatar"><?php echo strtoupper(substr($o['customer_name'] ?: 'K', 0, 1)); ?></div>
                                    <div>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($o['customer_name'] ?: 'Khách vãng lai'); ?></div>
                                        <div class="small text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($o['customer_phone'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small text-dark fw-medium"><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></div>
                                <div class="text-muted small" style="font-size: 11px;"><?php echo date('H:i', strtotime($o['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?php echo number_format($o['total']); ?>đ</div>
                                <?php if ($o['voucher_code']): ?>
                                    <div class="small text-success" style="font-size: 11px;"><i class="bi bi-ticket-perforated me-1"></i><?php echo $o['voucher_code']; ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $o['order_status']; ?>">
                                    <?php 
                                    $status_map = [
                                        'dang_cho' => 'Chờ xác nhận',
                                        'da_xac_nhan' => 'Đã xác nhận',
                                        'da_gui' => 'Đang giao',
                                        'da_giao' => 'Đã giao',
                                        'huy' => 'Đã hủy'
                                    ];
                                    echo $status_map[$o['order_status']] ?? $o['order_status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark fw-normal border px-2 py-1" style="font-size: 10px;"><?php echo strtoupper($o['payment_method'] ?? 'COD'); ?></span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="order_detail.php?id=<?php echo $o['id']; ?>" class="btn-action" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    <div class="dropdown">
                                        <button class="btn-action dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2">
                                            <li><div class="dropdown-header small text-muted text-uppercase fw-bold pb-2">Đổi trạng thái</div></li>
                                            <?php
                                            $statuses = [
                                                'dang_cho' => 'Chờ xác nhận',
                                                'da_xac_nhan' => 'Đã xác nhận',
                                                'da_gui' => 'Đang giao',
                                                'da_giao' => 'Đã giao',
                                                'huy' => 'Hủy đơn'
                                            ];
                                            foreach ($statuses as $val => $label): if ($val == $o['order_status']) continue;
                                            ?>
                                            <li>
                                                <form method="POST">
                                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $val; ?>">
                                                    <button type="submit" class="dropdown-item rounded-3 small py-2"><?php echo $label; ?></button>
                                                </form>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">Hiển thị <b><?php echo count($orders); ?></b> đơn hàng</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link rounded-pill" href="#">Trước</a></li>
                            <li class="page-item active"><a class="page-link mx-1 rounded-pill" href="#">1</a></li>
                            <li class="page-item"><a class="page-link rounded-pill" href="#">Sau</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>
                        <select class="form-select form-select-sm">
                            <option value="">Tất cả phương thức thanh toán</option>
                            <option value="cod">Thanh toán khi nhận hàng (COD)</option>
                            <option value="vnpay">VNPAY</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-dark px-4">Tìm kiếm</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table order-table mb-0">
                    <thead>
                        <tr>
                            <th width="120">Mã đơn hàng</th>
                            <th>Khách hàng</th>
                            <th>Ngày đặt</th>
                            <th>Tổng thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Thanh toán</th>
                            <th width="100">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">Không tìm thấy đơn hàng nào.</td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="fw-bold">#<?php echo $o['id']; ?></td>
                            <td>
                                <div class="customer-info">
                                    <div class="customer-avatar"><?php echo strtoupper(substr($o['customer_name'] ?: 'K', 0, 1)); ?></div>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($o['customer_name'] ?: 'Khách vãng lai'); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($o['customer_phone']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small"><?php echo date('d/m/Y', strtotime($o['created_at'])); ?></div>
                                <div class="text-muted small" style="font-size: 11px;"><?php echo date('H:i', strtotime($o['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-shopee"><?php echo number_format($o['total']); ?>đ</div>
                                <?php if ($o['voucher_code']): ?>
                                    <div class="small text-success" style="font-size: 11px;"><i class="bi bi-ticket-perforated me-1"></i><?php echo $o['voucher_code']; ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $o['order_status']; ?>">
                                    <?php 
                                    $status_map = [
                                        'dang_cho' => 'Chờ xác nhận',
                                        'da_xac_nhan' => 'Đã xác nhận',
                                        'da_gui' => 'Đang giao',
                                        'da_giao' => 'Đã giao',
                                        'huy' => 'Đã hủy'
                                    ];
                                    echo $status_map[$o['order_status']] ?? $o['order_status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark fw-normal border"><?php echo strtoupper($o['payment_method']); ?></span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="order_detail.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-light border" title="Chi tiết"><i class="bi bi-eye"></i></a>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-gear"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                            <li>
                                                <form method="POST" class="px-3 py-1">
                                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                                    <label class="small text-muted mb-1">Đổi trạng thái:</label>
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="dang_cho" <?php echo $o['order_status'] == 'dang_cho' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                                        <option value="da_xac_nhan" <?php echo $o['order_status'] == 'da_xac_nhan' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                                        <option value="da_gui" <?php echo $o['order_status'] == 'da_gui' ? 'selected' : ''; ?>>Đang giao</option>
                                                        <option value="da_giao" <?php echo $o['order_status'] == 'da_giao' ? 'selected' : ''; ?>>Đã giao</option>
                                                        <option value="huy" <?php echo $o['order_status'] == 'huy' ? 'selected' : ''; ?>>Hủy đơn</option>
                                                    </select>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-4 border-top">
                <div class="text-muted small">Hiển thị <?php echo count($orders); ?> đơn hàng</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Trước</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">Sau</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
