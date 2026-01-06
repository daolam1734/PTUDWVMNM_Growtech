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
        'content' => 'Cảm ơn bạn đã đăng ký tài khoản. Chúc bạn có những trải nghiệm mua sắm tuyệt vời trong năm 2026.',
        'type' => 'system',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'is_read' => 0
    ],
    [
        'id' => 2,
        'title' => 'Siêu Khuyến Mãi Tết 2026',
        'content' => 'Nhập mã TET2026 để được giảm ngay 15% cho tất cả các dòng Laptop Gaming và Phụ kiện.',
        'type' => 'promo',
        'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours')),
        'is_read' => 0
    ],
    [
        'id' => 3,
        'title' => 'Cập nhật trạng thái đơn hàng #WL-20260105-A1B2',
        'content' => 'Đơn hàng của bạn đã được bàn giao cho đơn vị vận chuyển và dự kiến giao trong 2 ngày tới.',
        'type' => 'order',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'is_read' => 1
    ],
    [
        'id' => 4,
        'title' => 'Voucher Freeship đã sẵn sàng!',
        'content' => 'Bạn có 1 mã miễn phí vận chuyển mới trong ví voucher. Sử dụng ngay trước khi hết hạn.',
        'type' => 'promo',
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'is_read' => 1
    ]
];
?>

<style>
    :root {
        --tet-red: #d32f2f;
        --tet-gold: #ffc107;
    }
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
</style>

<div class="container my-5">
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
                    <a href="orders.php" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <i class="bi bi-bag-check me-2"></i> Đơn hàng của tôi
                    </a>
                    <a href="notifications.php" class="list-group-item list-group-item-action py-3 px-4 border-0 active sidebar-link">
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
                <h3 class="fw-bold mb-0">Thông báo của tôi</h3>
                <?php if (!empty($notifications)): ?>
                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3">
                        <i class="bi bi-check2-all me-1"></i> Đánh dấu tất cả là đã đọc
                    </button>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                <div class="list-group list-group-flush">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <img src="https://cdn-icons-png.flaticon.com/512/3602/3602145.png" alt="No notifications" style="width: 120px; opacity: 0.5;">
                            <p class="text-muted mt-3">Bạn chưa có thông báo nào.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="list-group-item p-4 notification-item <?php echo $n['is_read'] ? '' : 'unread'; ?> border-bottom">
                                <div class="d-flex gap-3">
                                    <div class="flex-shrink-0">
                                        <?php if ($n['type'] == 'system'): ?>
                                            <div class="icon-box bg-primary-subtle text-primary">
                                                <i class="bi bi-info-circle-fill"></i>
                                            </div>
                                        <?php elseif ($n['type'] == 'promo'): ?>
                                            <div class="icon-box bg-danger-subtle text-danger">
                                                <i class="bi bi-megaphone-fill"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="icon-box bg-success-subtle text-success">
                                                <i class="bi bi-box-seam-fill"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <h6 class="mb-0 <?php echo $n['is_read'] ? 'text-dark' : 'fw-bold text-danger'; ?>">
                                                <?php echo htmlspecialchars($n['title']); ?>
                                            </h6>
                                            <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-0 text-muted small lh-base"><?php echo htmlspecialchars($n['content']); ?></p>
                                    </div>
                                    <?php if (!$n['is_read']): ?>
                                        <div class="flex-shrink-0 align-self-center">
                                            <div class="bg-danger rounded-circle" style="width: 10px; height: 10px;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($notifications)): ?>
                <div class="text-center mt-4">
                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-4">Xem thêm thông báo cũ</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
