<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['user_id'])) {
    set_flash('warning', 'Vui lòng đăng nhập để xem lịch sử đơn hàng.');
    header('Location: /weblaptop/auth/login.php?next=/weblaptop/orders.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $order_id = (int)($_POST['order_id'] ?? 0);

    if ($action === 'cancel' && $order_id > 0) {
        if (cancelOrder($order_id, $user_id)) {
            set_flash('success', 'Đã hủy đơn hàng #' . $order_id . ' thành công.');
        } else {
            set_flash('danger', 'Không thể hủy đơn hàng này.');
        }
    } elseif ($action === 'edit' && $order_id > 0) {
        // Fetch items and voucher before cancelling
        $stmt_order = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt_order->execute([$order_id, $user_id]);
        $order_data = $stmt_order->fetch();

        if (!$order_data || $order_data['order_status'] !== 'dang_cho') {
            set_flash('danger', 'Không thể chỉnh sửa đơn hàng này.');
            header('Location: orders.php');
            exit;
        }

        $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt_items->execute([$order_id]);
        $items = $stmt_items->fetchAll();

        $voucher_data = null;
        if (!empty($order_data['voucher_id'])) {
            $stmt_v = $pdo->prepare("SELECT * FROM vouchers WHERE id = ?");
            $stmt_v->execute([$order_data['voucher_id']]);
            $voucher_data = $stmt_v->fetch();
        }

        if (cancelOrder($order_id, $user_id)) {
            // Put items back to cart
            if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
            foreach ($items as $it) {
                $pid = $it['product_id'];
                $qty = $it['quantity'];
                if (isset($_SESSION['cart'][$pid])) {
                    $_SESSION['cart'][$pid] += $qty;
                } else {
                    $_SESSION['cart'][$pid] = $qty;
                }
            }
            
            // Restore voucher to session if it existed
            if ($voucher_data) {
                $_SESSION['voucher'] = $voucher_data;
            }

            set_flash('success', 'Đơn hàng đã được hủy và các sản phẩm đã được đưa lại vào giỏ hàng để bạn chỉnh sửa.');
            header('Location: cart.php');
            exit;
        } else {
            set_flash('danger', 'Không thể chỉnh sửa đơn hàng này.');
        }
    }
    header('Location: orders.php');
    exit;
}

// Fetch user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --tet-red: #d32f2f;
        --tet-gold: #ffc107;
        --tet-bg: #f8f9fa;
    }
    body { background-color: var(--tet-bg); }
    
    /* Sidebar Styling */
    .notification-item {
        transition: all 0.2s;
        border-left: 4px solid transparent;
    }
    .notification-item.unread {
        background-color: #fff9f9;
        border-left-color: var(--tet-red);
    }
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    .icon-box {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .sidebar-link.active {
        background-color: var(--tet-red) !important;
        border-color: var(--tet-red) !important;
    }

    /* Order Card Styling */
    .order-card {
        background: #fff;
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .order-header {
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #fafafa;
    }
    .order-body { padding: 20px; }
    .order-footer {
        padding: 15px 20px;
        border-top: 1px solid #f0f0f0;
        background: #fffdf5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .product-item {
        display: flex;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px dashed #eee;
    }
    .product-item:last-child { border-bottom: none; }
    .product-img {
        width: 70px;
        height: 70px;
        object-fit: cover;
        border-radius: 8px;
        margin-right: 15px;
        border: 1px solid #eee;
    }
    .product-info { flex: 1; }
    .product-name { font-weight: 500; color: #333; margin-bottom: 4px; }
    .product-price { color: var(--tet-red); font-weight: 600; }

    .status-badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-confirmed { background: #d1ecf1; color: #0c5460; }
    .status-processing { background: #cce5ff; color: #004085; }
    .status-shipping { background: #e2e3e5; color: #383d41; }
    .status-delivered { background: #d4edda; color: #155724; }
    .status-cancelled { background: #f8d7da; color: #721c24; }
</style>

<div class="container py-5">
    <?php display_flash(); ?>
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 text-center border-bottom bg-light">
                    <div class="avatar-placeholder mb-3 mx-auto d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 80px; height: 80px;">
                        <i class="bi bi-person-circle fs-1 text-danger"></i>
                    </div>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Khách hàng'); ?></h6>
                    <small class="text-muted">Thành viên GrowTech</small>
                </div>
                <div class="list-group list-group-flush">
                    <a href="account.php" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <i class="bi bi-person-circle me-2"></i> Thông tin tài khoản
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action py-3 px-4 border-0 active sidebar-link">
                        <i class="bi bi-bag-check me-2"></i> Đơn hàng của tôi
                    </a>
                    <a href="notifications.php" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <i class="bi bi-bell me-2"></i> Thông báo
                    </a>
                    <a href="/weblaptop/auth/logout.php" class="list-group-item list-group-item-action py-3 px-4 border-0 text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Lịch Sử Đơn Hàng</h4>
                <div class="text-muted small">Tổng số: <?php echo count($orders); ?> đơn hàng</div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="order-card text-center py-5">
                    <div class="py-4">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <p class="text-muted">Bạn chưa có đơn hàng nào.</p>
                        <a href="/weblaptop" class="btn btn-danger rounded-pill px-4">Mua sắm ngay</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <span class="fw-bold text-dark">Mã đơn: #<?php echo $o['id']; ?></span>
                                <span class="text-muted ms-2 small">| <?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></span>
                            </div>
                            <?php
                            $status_text = 'Chờ xử lý';
                            $status_class = 'status-pending';
                            switch($o['order_status']) {
                                case 'da_xac_nhan': $status_text = 'Đã xác nhận'; $status_class = 'status-confirmed'; break;
                                case 'dang_xu_ly': $status_text = 'Đang xử lý'; $status_class = 'status-processing'; break;
                                case 'da_gui': $status_text = 'Đang giao hàng'; $status_class = 'status-shipping'; break;
                                case 'da_giao': $status_text = 'Đã giao hàng'; $status_class = 'status-delivered'; break;
                                case 'hoan_thanh': $status_text = 'Hoàn thành'; $status_class = 'status-delivered'; break;
                                case 'huy': $status_text = 'Đã hủy'; $status_class = 'status-cancelled'; break;
                                case 'tra_lai': $status_text = 'Trả hàng'; $status_class = 'status-cancelled'; break;
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        <div class="order-body">
                            <?php
                            $stmt_items = $pdo->prepare("SELECT oi.* FROM order_items oi WHERE oi.order_id = ?");
                            $stmt_items->execute([$o['id']]);
                            $items = $stmt_items->fetchAll();
                            
                            foreach ($items as $it):
                                $stmt_img = $pdo->prepare("SELECT url FROM product_images WHERE product_id = ? AND position = 0 LIMIT 1");
                                $stmt_img->execute([$it['product_id']]);
                                $img_row = $stmt_img->fetch();
                                $img = $img_row ? $img_row['url'] : 'https://placehold.co/150?text=No+Image';
                                if ($img && strpos($img, 'http') !== 0 && strpos($img, '/') !== 0) {
                                    $img = 'https://placehold.co/' . $img;
                                }
                            ?>
                                <div class="product-item">
                                    <img src="<?php echo htmlspecialchars($img); ?>" class="product-img">
                                    <div class="product-info">
                                        <div class="product-name"><?php echo htmlspecialchars($it['product_name']); ?></div>
                                        <div class="small text-muted">Số lượng: x<?php echo $it['quantity']; ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="product-price"><?php echo number_format($it['unit_price'], 0, ',', '.'); ?> đ</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="order-footer">
                            <div class="d-flex gap-2">
                                <?php if ($o['order_status'] === 'dang_cho'): ?>
                                    <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">Hủy đơn</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Hệ thống sẽ hủy đơn hàng hiện tại và đưa sản phẩm vào giỏ hàng để bạn chỉnh sửa/bổ sung. Tiếp tục?')">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-3">Sửa/Bổ sung</button>
                                    </form>
                                <?php endif; ?>
                                <div class="small text-muted d-flex align-items-center ms-2">
                                    <i class="fas fa-credit-card me-1"></i> <?php echo str_replace('_', ' ', strtoupper($o['payment_method'])); ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="text-muted me-2">Thành tiền:</span>
                                <span class="fs-5 fw-bold text-danger"><?php echo number_format($o['total'], 0, ',', '.'); ?> đ</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

