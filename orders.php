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
            // Send notification
            createNotification(
                $user_id, 
                "Đơn hàng #$order_id đã hủy", 
                "Bạn vừa hủy đơn hàng #$order_id thành công. Tiền (nếu đã thanh toán) sẽ được xử lý hoàn lại theo chính sách.", 
                'order',
                "/weblaptop/orders.php"
            );
            set_flash('success', 'Đã hủy đơn hàng #' . $order_id . ' thành công.');
        } else {
            set_flash('danger', 'Không thể hủy đơn hàng này.');
        }
    } elseif ($action === 'edit' && $order_id > 0) {
        // Fetch items and voucher before cancelling
        $stmt_order = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt_order->execute([$order_id, $user_id]);
        $order_data = $stmt_order->fetch();

        if (!$order_data || $order_data['order_status'] !== 'PENDING') {
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
                                    <div class="text-muted x-small fw-bold">ID</div>
                                    <div class="text-dark fw-bold small">#<?php echo $o['id']; ?></div>
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
                                $stmt_img = $pdo->prepare("SELECT url FROM product_images WHERE product_id = ? AND position = 0 LIMIT 1");
                                $stmt_img->execute([$it['product_id']]);
                                $img_row = $stmt_img->fetch();
                                $img = $img_row ? $img_row['url'] : 'https://placehold.co/150?text=No+Image';
                                if ($img && strpos($img, 'http') !== 0 && strpos($img, '/') !== 0) {
                                    $img = 'https://placehold.co/' . $img;
                                }
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

