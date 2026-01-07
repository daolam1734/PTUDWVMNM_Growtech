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
    $new_status = $_POST['status'];
    
    // Fetch current order info
    $stmt_info = $pdo->prepare("SELECT order_no, order_status, user_id FROM orders WHERE id = ?");
    $stmt_info->execute([$order_id]);
    $order = $stmt_info->fetch();

    if ($order) {
        $old_status = $order['order_status'];
        $order_no = $order['order_no'];
        $user_id = $order['user_id'];

        // Logic check: Avoid redundant updates
        if ($old_status === $new_status) {
            header("Location: orders.php");
            exit;
        }

        try {
            $pdo->beginTransaction();

            // 1. Update status
            $stmt = $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);

            // 2. Logic for Stock Restoration if CANCELLED
            if ($new_status === 'CANCELLED' && $old_status !== 'CANCELLED') {
                $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                $stmtItems->execute([$order_id]);
                $items = $stmtItems->fetchAll();

                $stmtStock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $stmtMovement = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, type, reference, note) VALUES (?, ?, 'restock', ?, 'Admin hủy đơn hàng')");
                
                foreach ($items as $it) {
                    $stmtStock->execute([$it['quantity'], $it['product_id']]);
                    $stmtMovement->execute([$it['product_id'], $it['quantity'], $order_no]);
                }

                createNotification($user_id, "Đơn hàng $order_no đã bị hủy", "Admin đã hủy đơn hàng $order_no của bạn. Vui lòng liên hệ CSKH nếu có thắc mắc.", "order", "/weblaptop/orders.php");
            }

            // 3. Update payment status if COMPLETED
            if ($new_status === 'COMPLETED') {
                $pdo->prepare("UPDATE orders SET payment_status = 'PAID' WHERE id = ?")->execute([$order_id]);
                createNotification($user_id, "Đơn hàng $order_no hoàn tất", "Cảm ơn bạn đã tin dùng sản phẩm của GrowTech. Đơn hàng đã được giao thành công.", "order", "/weblaptop/orders.php");
            }

            // 4. Notification for shipping
            if ($new_status === 'SHIPPING') {
                createNotification($user_id, "Đơn hàng $order_no đang giao", "Đơn hàng của bạn đã được giao cho đơn vị vận chuyển.", "order", "/weblaptop/orders.php");
            }

            $pdo->commit();

            $status_labels = [
                'PENDING' => 'Chờ xác nhận',
                'CONFIRMED' => 'Đã xác nhận',
                'PROCESSING' => 'Đang xử lý',
                'SHIPPING' => 'Đang giao',
                'DELIVERED' => 'Đã giao',
                'COMPLETED' => 'Hoàn tất',
                'CANCELLED' => 'Đã hủy'
            ];
            $readable_status = $status_labels[$new_status] ?? $new_status;

            set_flash("success", "Cập nhật trạng thái đơn hàng $order_no sang <b>$readable_status</b> thành công.");
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash("error", "Lỗi log: " . $e->getMessage());
        }
    } else {
        set_flash("error", "Không tìm thấy đơn hàng.");
    }
    header("Location: orders.php");
    exit;
}

// Filter by status if provided
$status_filter = $_GET['status'] ?? 'all';
$search_q = $_GET['q'] ?? '';
$payment_filter = $_GET['payment'] ?? '';

$query = "
    SELECT o.*, u.full_name as customer_name, ua.phone as customer_phone, v.code as voucher_code
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    LEFT JOIN user_addresses ua ON o.address_id = ua.id
    LEFT JOIN vouchers v ON o.voucher_id = v.id
    WHERE 1=1
";

if ($status_filter !== 'all') {
    $query .= " AND o.order_status = " . $pdo->quote($status_filter);
}

if (!empty($search_q)) {
    $search_q_quoted = $pdo->quote("%$search_q%");
    $query .= " AND (o.order_no LIKE $search_q_quoted OR u.full_name LIKE $search_q_quoted OR ua.phone LIKE $search_q_quoted)";
}

if (!empty($payment_filter)) {
    $query .= " AND o.payment_method = " . $pdo->quote($payment_filter);
}

$query .= " ORDER BY o.created_at DESC";
$orders = $pdo->query($query)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --primary-dark: #1e293b;
        --accent-blue: #3b82f6;
        --text-main: #334155;
        --text-light: #64748b;
        --bg-light: #f8fafc;
    }

    .dashboard-section { 
        background: #fff; 
        border-radius: 1.25rem; 
        box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
        border: 1px solid rgba(0,0,0,0.05); 
        overflow: hidden; 
    }

    .order-tabs { 
        display: flex; 
        border-bottom: 2px solid #f1f5f9; 
        background: #fff; 
        padding: 0 1rem; 
        overflow-x: auto;
        gap: 0.5rem;
    }
    .order-tab { 
        padding: 1.25rem 1.5rem; 
        cursor: pointer; 
        color: var(--text-light); 
        text-decoration: none; 
        border-bottom: 3px solid transparent; 
        font-size: 0.95rem; 
        white-space: nowrap; 
        font-weight: 600; 
        transition: all 0.3s; 
    }
    .order-tab:hover { color: var(--primary-dark); }
    .order-tab.active { 
        color: var(--accent-blue); 
        border-bottom-color: var(--accent-blue); 
    }
    
    .table-modern thead th { 
        background: var(--bg-light); 
        border-bottom: 2px solid #f1f5f9; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
        color: var(--text-light); 
        padding: 1rem 1.5rem; 
    }
    .table-modern tbody td { 
        padding: 1.25rem 1.5rem; 
        vertical-align: middle; 
        font-size: 0.9rem; 
        border-bottom: 1px solid #f1f5f9; 
        color: var(--text-main);
    }
    
    .status-badge { 
        padding: 0.4rem 0.8rem; 
        border-radius: 9999px; 
        font-size: 0.75rem; 
        font-weight: 700; 
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .status-badge::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-PENDING { background: #fef3c7; color: #92400e; }
    .status-CONFIRMED { background: #e0f2fe; color: #075985; }
    .status-PROCESSING { background: #f3e8ff; color: #6b21a8; }
    .status-SHIPPING { background: #e0e7ff; color: #3730a3; }
    .status-DELIVERED { background: #dcfce7; color: #166534; }
    .status-COMPLETED { background: #dcfce7; color: #166534; }
    .status-CANCELLED { background: #fee2e2; color: #991b1b; }
    
    .customer-avatar { 
        width: 40px; 
        height: 40px; 
        background: var(--bg-light); 
        border-radius: 12px; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        margin-right: 12px; 
        font-weight: 700; 
        color: var(--primary-dark); 
        font-size: 0.9rem;
        border: 1px solid #f1f5f9;
        text-transform: uppercase;
    }

    .btn-action { 
        width: 38px; 
        height: 38px; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 12px; 
        background: #fff;
        color: var(--text-main);
        border: 1px solid #e2e8f0;
        transition: all 0.2s; 
    }
    .btn-action:hover { 
        background: var(--bg-light);
        color: var(--accent-blue);
        border-color: var(--accent-blue);
        transform: translateY(-2px);
    }

    .no-caret::after { display: none !important; }
    
    .table-responsive { 
        overflow: visible !important; 
        padding-bottom: 60px; /* Thêm không gian cho dropdown tại dòng cuối */
        margin-bottom: -60px;
    }

    .search-input-group {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 9999px;
        padding-left: 1rem;
        transition: all 0.3s;
    }
    .search-input-group:focus-within {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">Quản Lý Đơn Hàng</h4>
                <p class="text-muted small mb-0">Theo dõi và cập nhật trạng thái đơn hàng của hệ thống GrowTech.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="export.php?type=orders" class="btn btn-white border border-secondary border-opacity-10 shadow-sm px-4 rounded-pill fw-bold btn-sm">
                    <i class="bi bi-file-earmark-excel me-2"></i> Xuất Báo Cáo
                </a>
            </div>
        </div>

        <div class="dashboard-section">
            <!-- Order Tabs -->
            <div class="order-tabs">
                <a href="orders.php?status=all" class="order-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">Tất cả</a>
                <a href="orders.php?status=PENDING" class="order-tab <?php echo $status_filter === 'PENDING' ? 'active' : ''; ?>">Chờ xác nhận</a>
                <a href="orders.php?status=CONFIRMED" class="order-tab <?php echo $status_filter === 'CONFIRMED' ? 'active' : ''; ?>">Đã xác nhận</a>
                <a href="orders.php?status=PROCESSING" class="order-tab <?php echo $status_filter === 'PROCESSING' ? 'active' : ''; ?>">Đang xử lý</a>
                <a href="orders.php?status=SHIPPING" class="order-tab <?php echo $status_filter === 'SHIPPING' ? 'active' : ''; ?>">Đang giao</a>
                <a href="orders.php?status=COMPLETED" class="order-tab <?php echo $status_filter === 'COMPLETED' ? 'active' : ''; ?>">Hoàn tất</a>
                <a href="orders.php?status=CANCELLED" class="order-tab <?php echo $status_filter === 'CANCELLED' ? 'active' : ''; ?>">Đã hủy</a>
            </div>

            <div class="p-4 bg-light bg-opacity-30 border-bottom border-secondary border-opacity-10">
                <form method="GET">
                    <div class="input-group search-input-group shadow-none">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="order-search" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" class="form-control border-0 shadow-none bg-transparent" placeholder="Tìm kiếm theo mã đơn, khách hàng hoặc SĐT...">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table id="order-table" class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Thời gian</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Thanh toán</th>
                            <th class="text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                        <i class="bi bi-cart-x fs-1 text-secondary opacity-50"></i>
                                    </div>
                                    <h6 class="text-dark fw-bold">Không có đơn hàng nào</h6>
                                    <p class="text-muted small">Hiện chưa có đơn hàng nào thuộc danh mục này.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($o['order_no']); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="customer-avatar"><?php echo strtoupper(substr($o['customer_name'] ?: 'K', 0, 1)); ?></div>
                                    <div>
                                        <div class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($o['customer_name'] ?: 'Khách vãng lai'); ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($o['customer_phone'] ?: 'Không có SĐT'); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark mb-0"><?php echo date('d/m, Y', strtotime($o['created_at'])); ?></div>
                                <div class="text-muted small" style="font-size: 0.75rem;"><i class="bi bi-clock me-1"></i><?php echo date('H:i', strtotime($o['created_at'])); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary mb-0"><?php echo number_format($o['total']); ?>₫</div>
                                <?php if ($o['voucher_code']): ?>
                                    <div class="text-success fw-medium" style="font-size: 0.75rem;"><i class="bi bi-ticket-perforated me-1"></i><?php echo $o['voucher_code']; ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $o['order_status']; ?>">
                                    <?php 
                                    $status_labels = [
                                        'PENDING' => 'Chờ xác nhận',
                                        'CONFIRMED' => 'Đã xác nhận',
                                        'PROCESSING' => 'Đang xử lý',
                                        'SHIPPING' => 'Đang giao',
                                        'DELIVERED' => 'Đã giao',
                                        'COMPLETED' => 'Hoàn tất',
                                        'CANCELLED' => 'Đã hủy'
                                    ];
                                    echo $status_labels[$o['order_status']] ?? $o['order_status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border border-secondary border-opacity-10 px-2 py-1 fw-bold" style="font-size: 0.65rem;">
                                    <?php echo strtoupper($o['payment_method'] ?: 'COD'); ?>
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="order_detail.php?id=<?php echo $o['id']; ?>" class="btn-action" title="Chi tiết"><i class="bi bi-eye"></i></a>
                                    <div class="dropdown">
                                        <button class="btn-action dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2">
                                            <li class="dropdown-header small text-muted text-uppercase fw-bold pb-2 pt-1">Hành động</li>
                                            <li><a class="dropdown-item rounded-3 small py-2" href="order_detail.php?id=<?php echo $o['id']; ?>"><i class="bi bi-eye me-2"></i>Xem chi tiết</a></li>
                                            
                                            <?php
                                            $current = $o['order_status'];
                                            $next_options = [];
                                            
                                            switch($current) {
                                                case 'PENDING':
                                                    $next_options = [
                                                        'CONFIRMED' => ['Xác nhận đơn', 'bi-check-circle'],
                                                        'CANCELLED' => ['Hủy đơn', 'bi-x-circle']
                                                    ];
                                                    break;
                                                case 'CONFIRMED':
                                                    $next_options = [
                                                        'PROCESSING' => ['Đóng gói/Xử lý', 'bi-box-seam'],
                                                        'CANCELLED' => ['Hủy đơn', 'bi-x-circle']
                                                    ];
                                                    break;
                                                case 'PROCESSING':
                                                    $next_options = [
                                                        'SHIPPING' => ['Giao đơn vị vận chuyển', 'bi-truck'],
                                                        'CANCELLED' => ['Hủy đơn', 'bi-x-circle']
                                                    ];
                                                    break;
                                                case 'SHIPPING':
                                                    $next_options = [
                                                        'DELIVERED' => ['Đã giao hàng', 'bi-house-check'],
                                                        'CANCELLED' => ['Hủy đơn', 'bi-x-circle']
                                                    ];
                                                    break;
                                                case 'DELIVERED':
                                                    $next_options = [
                                                        'COMPLETED' => ['Hoàn tất đơn', 'bi-shield-check']
                                                    ];
                                                    break;
                                            }

                                            if (!empty($next_options)):
                                                echo '<div class="dropdown-divider"></div>';
                                                echo '<li class="dropdown-header small text-muted text-uppercase fw-bold pb-1 pt-1">Chuyển trạng thái</li>';
                                                foreach ($next_options as $val => $data):
                                                    $label = $data[0];
                                                    $icon = $data[1];
                                            ?>
                                            <li>
                                                <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn chuyển sang trạng thái <?php echo $label; ?>?')" class="m-0">
                                                    <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $val; ?>">
                                                    <button type="submit" class="dropdown-item rounded-3 small py-2 <?php echo $val === 'CANCELLED' ? 'text-danger' : 'text-primary'; ?>">
                                                        <i class="bi <?php echo $icon; ?> me-2"></i> <?php echo $label; ?>
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endforeach; endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-top border-secondary border-opacity-10">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">Tổng cộng <b><?php echo count($orders); ?></b> đơn hàng</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link rounded-pill border-0 bg-light" href="#">Trước</a></li>
                            <li class="page-item active"><a class="page-link mx-1 rounded-pill border-0 shadow-sm" href="#">1</a></li>
                            <li class="page-item"><a class="page-link rounded-pill border-0 bg-light" href="#">Sau</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('order-search');
    const tableBody = document.querySelector('#order-table tbody');
    const rows = tableBody.querySelectorAll('tr');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        let visibleCount = 0;

        rows.forEach(row => {
            // Chỉ lọc những dòng có dữ liệu (không phải dòng "Không có đơn hàng")
            if (row.cells.length > 1) {
                const text = row.textContent.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                if (text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            }
        });

        // Nếu không có kết quả, hiển thị dòng thông báo
        let noResultRow = document.getElementById('no-result-row');
        if (visibleCount === 0 && query !== '') {
            if (!noResultRow) {
                noResultRow = document.createElement('tr');
                noResultRow.id = 'no-result-row';
                noResultRow.innerHTML = `<td colspan="7" class="text-center py-4 text-muted">Không tìm thấy đơn hàng phù hợp với "${this.value}"</td>`;
                tableBody.appendChild(noResultRow);
            }
        } else if (noResultRow) {
            noResultRow.remove();
        }
    });
});
</script>
