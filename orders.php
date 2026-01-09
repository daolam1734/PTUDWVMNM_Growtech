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

    // Fetch order first to get order_no for feedback
    $stmt_check = $pdo->prepare("SELECT order_no, order_status FROM orders WHERE id = ? AND user_id = ?");
    $stmt_check->execute([$order_id, $user_id]);
    $order_info = $stmt_check->fetch();
    $order_no = $order_info ? $order_info['order_no'] : '';

    if ($action === 'cancel' && $order_id > 0 && $order_no) {
        if (cancelOrder($order_id, $user_id)) {
            // Send notification
            createNotification(
                $user_id, 
                "Đơn hàng $order_no đã hủy", 
                "Bạn vừa hủy đơn hàng $order_no thành công. Tiền (nếu đã thanh toán) sẽ được xử lý hoàn lại theo chính sách.", 
                'order',
                "/weblaptop/orders.php"
            );
            set_flash('success', 'Đã hủy đơn hàng ' . $order_no . ' thành công.');
        } else {
            set_flash('danger', 'Không thể hủy đơn hàng này.');
        }
    } elseif ($action === 'edit' && $order_id > 0 && $order_no) {
        if (!$order_info || $order_info['order_status'] !== 'PENDING') {
            set_flash('danger', 'Không thể chỉnh sửa đơn hàng này.');
            header('Location: orders.php');
            exit;
        }

        $stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmt_items->execute([$order_id]);
        $items = $stmt_items->fetchAll();

        // Check if there's a voucher
        $stmt_order_full = $pdo->prepare("SELECT voucher_id FROM orders WHERE id = ?");
        $stmt_order_full->execute([$order_id]);
        $full_order = $stmt_order_full->fetch();
        
        $voucher_data = null;
        if (!empty($full_order['voucher_id'])) {
            $stmt_v = $pdo->prepare("SELECT * FROM vouchers WHERE id = ?");
            $stmt_v->execute([$full_order['voucher_id']]);
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
            
            // Restore voucher if it was a single voucher (legacy support)
            if ($voucher_data) {
                $_SESSION['vouchers']['product'] = [
                    'code' => $voucher_data['code'],
                    'value' => $voucher_data['discount_value'],
                    'type' => $voucher_data['discount_type']
                ];
            }

            set_flash('success', 'Đơn hàng ' . $order_no . ' đã được hủy và các sản phẩm đã được đưa lại vào giỏ hàng để bạn chỉnh sửa.');
            header('Location: cart.php');
            exit;
        } else {
            set_flash('danger', 'Không thể chỉnh sửa đơn hàng này.');
        }
    }
    header('Location: orders.php');
    exit;
}

// Fetch user orders with filtering
$status_filter = $_GET['status'] ?? 'all';
$query = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];

if ($status_filter !== 'all') {
    if ($status_filter === 'PROCESSING_GROUP') {
        $query .= " AND order_status IN ('CONFIRMED', 'PROCESSING')";
    } else {
        $query .= " AND order_status = ?";
        $params[] = $status_filter;
    }
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --tet-red: #d32f2f;
        --tet-gold: #ffc107;
        --tet-bg: #fdfdfd;
        --accent-blue: #3b82f6;
    }
    body { background-color: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    
    /* Sidebar Modern */
    .sidebar-modern {
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    }
    .avatar-wrapper {
        background: linear-gradient(135deg, #fff 0%, #f1f5f9 100%);
        padding: 2rem 1.5rem;
    }
    .nav-link-modern {
        padding: 12px 20px;
        border-radius: 12px;
        margin: 4px 12px;
        color: #64748b;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .nav-link-modern:hover {
        background: #f1f5f9;
        color: var(--tet-red);
    }
    .nav-link-modern.active {
        background: #fff1f2;
        color: var(--tet-red);
    }

    /* Order Card Modern */
    .order-card {
        background: #fff;
        border-radius: 20px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        border: 1px solid rgba(226, 232, 240, 0.8);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .order-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); }
    
    .order-header {
        padding: 20px 24px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .order-footer {
        padding: 20px 24px;
        border-top: 1px solid #f1f5f9;
        background: #fcfcfd;
        border-radius: 0 0 20px 20px;
    }

    .product-img {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 12px;
        background: #f8fafc;
    }

    /* Modern Badges for Web */
    .status-badge-web {
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .status-badge-web .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    
    .status-pending { background: #fef9c3; color: #854d0e; }
    .status-pending .dot { background: #eab308; }
    
    .status-confirmed, .status-processing { background: #dbeafe; color: #1e40af; }
    .status-confirmed .dot, .status-processing .dot { background: #3b82f6; }
    
    .status-shipping { background: #f1f5f9; color: #475569; }
    .status-shipping .dot { background: #94a3b8; }
    
    .status-delivered, .status-completed { background: #dcfce7; color: #166534; }
    .status-delivered .dot, .status-completed .dot { background: #22c55e; }
    
    .status-cancelled, .status-returned { background: #fee2e2; color: #991b1b; }
    .status-cancelled .dot, .status-returned .dot { background: #ef4444; }

    .pay-badge {
        font-size: 0.7rem;
        padding: 2px 8px;
        border-radius: 4px;
        background: #f1f5f9;
        color: #64748b;
        text-transform: uppercase;
        font-weight: 700;
    }

    /* Order Status Tabs */
    .order-status-tabs {
        display: flex;
        background: #fff;
        border-radius: 16px;
        padding: 8px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        overflow-x: auto;
        gap: 5px;
        scrollbar-width: none; /* Firefox */
    }
    .order-status-tabs::-webkit-scrollbar { display: none; } /* Chrome/Safari */

    .status-tab {
        flex: 1;
        text-align: center;
        padding: 12px 15px;
        border-radius: 12px;
        color: #64748b;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.3s;
        white-space: nowrap;
        border: 2px solid transparent;
    }
    .status-tab:hover {
        background: #f8fafc;
        color: var(--tet-red);
    }
    .status-tab.active {
        background: #fff1f2;
        color: var(--tet-red);
        border-color: #fee2e2;
    }
    
    .border-dashed { border-style: dashed !important; border-width: 1px !important; }
    .x-small { font-size: 0.75rem; }
</style>

<div class="container py-5">
    <?php display_flash(); ?>
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 sidebar-modern overflow-hidden">
                <div class="avatar-wrapper text-center">
                    <div class="position-relative d-inline-block mb-3">
                        <div class="bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center mx-auto" style="width: 90px; height: 90px;">
                            <i class="bi bi-person-fill fs-1 text-danger opacity-75"></i>
                        </div>
                        <span class="position-absolute bottom-0 end-0 bg-success border border-white border-3 rounded-circle" style="width: 14px; height: 14px;"></span>
                    </div>
                    <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Khách hàng'); ?></h6>
                    <span class="badge bg-light text-muted fw-normal rounded-pill px-3 py-2">Thành viên hạng Đồng</span>
                </div>
                <div class="py-3 bg-white">
                    <a href="account.php" class="nav-link-modern">
                        <i class="bi bi-person-circle fs-5"></i> Hồ sơ của tôi
                    </a>
                    <a href="orders.php" class="nav-link-modern active">
                        <i class="bi bi-box-seam fs-5"></i> Đơn hàng đã mua
                    </a>
                    <a href="notifications.php" class="nav-link-modern">
                        <i class="bi bi-bell fs-5"></i> Thông báo
                    </a>
                    <hr class="mx-4 my-2 opacity-10">
                    <a href="/weblaptop/auth/logout.php" class="nav-link-modern text-danger">
                        <i class="bi bi-box-arrow-right fs-5"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-end mb-4 px-2">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Đơn hàng của tôi</h3>
                    <p class="text-muted small mb-0">Quản lý và theo dõi hành trình đơn hàng của bạn</p>
                </div>
                <div class="text-end">
                    <span class="text-dark fw-bold h5 mb-0"><?php echo count($orders); ?></span>
                    <div class="text-muted x-small">TỔNG ĐƠN</div>
                </div>
            </div>

            <!-- Status Tabs Navigation -->
            <div class="order-status-tabs">
                <a href="?status=all" class="status-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">Tất cả</a>
                <a href="?status=PENDING" class="status-tab <?php echo $status_filter === 'PENDING' ? 'active' : ''; ?>">Chờ xác nhận</a>
                <a href="?status=PROCESSING_GROUP" class="status-tab <?php echo $status_filter === 'PROCESSING_GROUP' ? 'active' : ''; ?>">Đang xử lý</a>
                <a href="?status=SHIPPING" class="status-tab <?php echo $status_filter === 'SHIPPING' ? 'active' : ''; ?>">Đang giao</a>
                <a href="?status=COMPLETED" class="status-tab <?php echo $status_filter === 'COMPLETED' ? 'active' : ''; ?>">Hoàn thành</a>
                <a href="?status=CANCELLED" class="status-tab <?php echo $status_filter === 'CANCELLED' ? 'active' : ''; ?>">Đã hủy</a>
            </div>

            <?php if (empty($orders)): ?>
                <div class="card border-0 shadow-sm rounded-5 py-5 text-center bg-white">
                    <div class="py-5">
                        <div class="mb-4">
                            <i class="bi bi-bag-x fs-1 text-muted opacity-25"></i>
                        </div>
                        <h5 class="fw-bold">Chưa có đơn hàng nào</h5>
                        <p class="text-muted mb-4">Hãy bắt đầu hành trình mua sắm của bạn cùng GrowTech!</p>
                        <a href="/weblaptop" class="btn btn-danger rounded-pill px-5 py-2 shadow-sm">Mua sắm ngay</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="order-card p-0">
                        <div class="order-header">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-light rounded-3 p-2 text-center" style="min-width: 60px;">
                                    <div class="text-muted x-small fw-bold">MÃ ĐƠN</div>
                                    <div class="text-dark fw-bold small">#<?php echo htmlspecialchars($o['order_no']); ?></div>
                                </div>
                                <div>
                                    <div class="text-muted x-small fw-bold text-uppercase">Ngày đặt</div>
                                    <div class="text-dark small fw-bold"><?php echo date('d/m/Y H:i', strtotime($o['created_at'])); ?></div>
                                </div>
                            </div>
                            <?php echo get_order_status_badge($o['order_status']); ?>
                        </div>
                        
                        <div class="px-4 py-3">
                            <?php
                            $stmt_items = $pdo->prepare("SELECT oi.* FROM order_items oi WHERE oi.order_id = ?");
                            $stmt_items->execute([$o['id']]);
                            $items = $stmt_items->fetchAll();
                            
                            foreach ($items as $it):
                                $img = getProductImage($it['product_id']);
                            ?>
                                <div class="d-flex align-items-center py-3 border-bottom border-dashed border-secondary border-opacity-10">
                                    <img src="<?php echo htmlspecialchars($img); ?>" class="product-img me-3 border">
                                    <div class="flex-grow-1">
                                        <div class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($it['product_name']); ?></div>
                                        <div class="d-flex gap-3 align-items-center">
                                            <span class="text-muted small">x<?php echo $it['quantity']; ?></span>
                                            <span class="text-danger fw-bold small"><?php echo number_format($it['unit_price'], 0, ',', '.'); ?> ₫</span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-dark fw-bold"><?php echo number_format($it['unit_price'] * $it['quantity'], 0, ',', '.'); ?> ₫</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-footer">
                            <div class="row align-items-center g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="pay-badge d-flex align-items-center gap-1">
                                            <i class="bi bi-credit-card"></i>
                                            <?php echo strtoupper($o['payment_method']); ?>
                                        </span>
                                        <span class="small text-muted border-start ps-3">
                                            Phí vận chuyển: <?php echo number_format($o['shipping_fee'], 0, ',', '.'); ?> ₫
                                        </span>
                                    </div>

                                    <?php if ($o['payment_method'] === 'banking' && ($o['payment_status'] ?? 'UNPAID') === 'UNPAID' && $o['order_status'] !== 'CANCELLED'): ?>
                                        <div class="mt-3 p-3 rounded-3 bg-light border border-dashed">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <small class="text-danger fw-bold"><i class="bi bi-info-circle-fill me-1"></i> Vui lòng chuyển khoản:</small>
                                                <button class="btn btn-sm btn-link p-0 text-decoration-none small" type="button" data-bs-toggle="collapse" data-bs-target="#bank-<?php echo $o['id']; ?>">
                                                    Xem chi tiết
                                                </button>
                                            </div>
                                            <div class="collapse show" id="bank-<?php echo $o['id']; ?>">
                                                <div class="small bg-white p-2 rounded shadow-sm border">
                                                    <p class="mb-1">Ngân hàng: <b><?php echo BANK_NAME; ?></b></p>
                                                    <p class="mb-1">Chủ TK: <b><?php echo BANK_ACCOUNT_NAME; ?></b></p>
                                                    <p class="mb-1">Số TK: <b class="text-danger"><?php echo BANK_ACCOUNT_NUMBER; ?></b></p>
                                                    <p class="mb-0">Nội dung: <b class="text-primary"><?php echo $o['order_no']; ?></b></p>
                                                    <div class="text-center mt-2 pt-2 border-top">
                                                        <?php 
                                                        $vietqr_url_order = "https://img.vietqr.io/image/" . BANK_ID . "-" . BANK_ACCOUNT_NUMBER . "-compact.png?amount=" . $o['total'] . "&addInfo=" . urlencode($o['order_no']) . "&accountName=" . urlencode(BANK_ACCOUNT_NAME);
                                                        ?>
                                                        <img src="<?php echo $vietqr_url_order; ?>" alt="QR VietQR" width="150" class="img-thumbnail">
                                                        <div class="mt-1 xsmall text-muted">Quét VietQR để chuyển nhanh</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($o['payment_method'] === 'momo' && ($o['payment_status'] ?? 'UNPAID') === 'UNPAID' && $o['order_status'] !== 'CANCELLED'): ?>
                                        <div class="mt-3 p-3 rounded-3 bg-light border border-dashed" style="border-color: #a50064 !important;">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="fw-bold" style="color: #a50064;"><i class="bi bi-info-circle-fill me-1"></i> Thanh toán qua MoMo:</small>
                                            </div>
                                            <div class="small bg-white p-2 rounded shadow-sm border text-center">
                                                <p class="mb-1">Số điện thoại: <b class="text-danger"><?php echo MOMO_PHONE; ?></b></p>
                                                <p class="mb-2">Chủ TK: <b><?php echo MOMO_NAME; ?></b></p>
                                                <?php 
                                                $momo_qr_url_order = "https://api.vietqr.io/image/970422-" . MOMO_PHONE . "-compact.png?amount=" . $o['total'] . "&addInfo=" . urlencode($o['order_no']) . "&accountName=" . urlencode(MOMO_NAME);
                                                ?>
                                                <img src="<?php echo $momo_qr_url_order; ?>" alt="QR MoMo" width="150" class="img-thumbnail">
                                                <div class="mt-1 xsmall text-muted">Quét mã MoMo để chuyển tiền</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($o['order_status'] === 'PENDING'): ?>
                                        <div class="mt-3 d-flex gap-2">
                                            <form method="POST" onsubmit="return confirm('Hủy đơn hàng này?')">
                                                <input type="hidden" name="action" value="cancel">
                                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3 shadow-none">Hủy đơn</button>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Đưa sản phẩm vào giỏ hàng để chỉnh sửa?')">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-none">Sửa đơn</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 text-md-end text-center">
                                    <div class="text-muted small mb-1">Thành tiền (Đã bao gồm VAT)</div>
                                    <div class="text-danger fw-bold fs-4"><?php echo number_format($o['total'], 0, ',', '.'); ?> ₫</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>

