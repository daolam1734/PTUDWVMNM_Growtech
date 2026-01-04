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

// Fetch user orders
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="list-group mb-4">
                <a href="account.php" class="list-group-item list-group-item-action">Thông tin tài khoản</a>
                <a href="orders.php" class="list-group-item list-group-item-action active">Đơn hàng của tôi</a>
                <a href="/weblaptop/auth/logout.php" class="list-group-item list-group-item-action text-danger">Đăng xuất</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <h2 class="mb-4">Đơn hàng của tôi</h2>

            <?php if (empty($orders)): ?>
                <div class="card text-center py-5 shadow-sm">
                    <div class="card-body">
                        <span class="sparkle-effect display-1 text-muted mb-3"></span>
                        <p class="text-muted">Bạn chưa có đơn hàng nào.</p>
                        <a href="/weblaptop" class="btn btn-primary">Mua sắm ngay</a>
                    </div>
                </div>
<?php else: ?>
                <?php foreach ($orders as $o): ?>
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <div>
                                <span class="fw-bold">Mã đơn hàng: #<?php echo $o['id']; ?></span>
                                <span class="text-muted ms-2">| <?php echo date('d/m/Y', strtotime($o['created_at'])); ?></span>
                            </div>
                            <?php
                            $status_text = 'Chờ xử lý';
                            $status_class = 'text-warning';
                            switch($o['order_status']) {
                                case 'da_xac_nhan': $status_text = 'Đã xác nhận'; $status_class = 'text-info'; break;
                                case 'dang_xu_ly': $status_text = 'Đang xử lý'; $status_class = 'text-primary'; break;
                                case 'da_gui': $status_text = 'Đang giao hàng'; $status_class = 'text-primary'; break;
                                case 'da_giao': $status_text = 'Đã giao hàng'; $status_class = 'text-success'; break;
                                case 'hoan_thanh': $status_text = 'Hoàn thành'; $status_class = 'text-success'; break;
                                case 'huy': $status_text = 'Đã hủy'; $status_class = 'text-danger'; break;
                                case 'tra_lai': $status_text = 'Trả hàng'; $status_class = 'text-secondary'; break;
                            }
                            ?>
                            <span class="fw-bold <?php echo $status_class; ?> text-uppercase small">
                                <span class="sparkle-effect me-1"></span> <?php echo $status_text; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php
                            // Fetch items for this order
                            $stmt_items = $pdo->prepare("
                                SELECT oi.*
                                FROM order_items oi 
                                WHERE oi.order_id = ?
                            ");
                            $stmt_items->execute([$o['id']]);
                            $items = $stmt_items->fetchAll();
                            
                            foreach ($items as $it):
                                // Get product image from product_images table
                                $stmt_img = $pdo->prepare("SELECT url FROM product_images WHERE product_id = ? AND position = 0 LIMIT 1");
                                $stmt_img->execute([$it['product_id']]);
                                $img_row = $stmt_img->fetch();
                                $img = $img_row ? $img_row['url'] : 'https://placehold.co/150?text=No+Image';
                                if ($img && strpos($img, 'http') !== 0 && strpos($img, '/') !== 0) {
                                    $img = 'https://placehold.co/' . $img;
                                }
                            ?>
                                <div class="d-flex align-items-center mb-3">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="" style="width: 80px; height: 80px; object-fit: cover;" class="border rounded me-3">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($it['product_name']); ?></h6>
                                        <small class="text-muted">Số lượng: x<?php echo $it['quantity']; ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="text-danger"><?php echo number_format($it['unit_price'], 0, ',', '.'); ?> đ</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer bg-light d-flex justify-content-between align-items-center py-3">
                            <div>
                                <small class="text-muted">Phương thức thanh toán: <?php echo str_replace('_', ' ', strtoupper($o['payment_method'])); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="me-2">Tổng số tiền:</span>
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
