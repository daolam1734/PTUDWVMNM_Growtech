<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['user_id'])) {
    set_flash('warning', 'Vui lòng đăng nhập để xem thông báo.');
    header('Location: /weblaptop/auth/login.php?next=/weblaptop/notifications.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Mock notifications for now
$notifications = [
    [
        'id' => 1,
        'title' => 'Chào mừng bạn đến với GrowTech!',
        'content' => 'Cảm ơn bạn đã đăng ký tài khoản. Chúc bạn có những trải nghiệm mua sắm tuyệt vời.',
        'type' => 'system',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'is_read' => 0
    ],
    [
        'id' => 2,
        'title' => 'Khuyến mãi Tết Giáp Thìn 2024',
        'content' => 'Nhập mã TET2024 để được giảm ngay 10% cho tất cả các dòng Laptop Gaming.',
        'type' => 'promo',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'is_read' => 1
    ],
    [
        'id' => 3,
        'title' => 'Cập nhật trạng thái đơn hàng #102',
        'content' => 'Đơn hàng của bạn đã được bàn giao cho đơn vị vận chuyển.',
        'type' => 'order',
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'is_read' => 1
    ]
];
?>

<div class="container my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="list-group mb-4">
                <a href="account.php" class="list-group-item list-group-item-action">Thông tin tài khoản</a>
                <a href="orders.php" class="list-group-item list-group-item-action">Đơn hàng của tôi</a>
                <a href="notifications.php" class="list-group-item list-group-item-action active">Thông báo</a>
                <a href="/weblaptop/auth/logout.php" class="list-group-item list-group-item-action text-danger">Đăng xuất</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Thông báo của tôi</h2>
                <button class="btn btn-outline-secondary btn-sm">Đánh dấu tất cả là đã đọc</button>
            </div>

            <div class="card shadow-sm border-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $n): ?>
                        <div class="list-group-item p-3 <?php echo $n['is_read'] ? '' : 'bg-light'; ?>">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <?php if ($n['type'] == 'system'): ?>
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-info-circle"></i>
                                        </div>
                                    <?php elseif ($n['type'] == 'promo'): ?>
                                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-megaphone"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-box-seam"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-1 <?php echo $n['is_read'] ? 'text-dark' : 'fw-bold'; ?>"><?php echo htmlspecialchars($n['title']); ?></h6>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0 text-muted small"><?php echo htmlspecialchars($n['content']); ?></p>
                                </div>
                                <?php if (!$n['is_read']): ?>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-danger rounded-circle p-1"><span class="visually-hidden">Chưa đọc</span></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button class="btn btn-light text-muted">Xem thêm thông báo cũ</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
