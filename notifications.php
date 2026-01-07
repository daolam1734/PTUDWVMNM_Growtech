<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

if (empty($_SESSION['user_id'])) {
    set_flash('warning', 'Vui lòng đăng nhập để xem thông báo.');
    header('Location: /weblaptop/auth/login.php?next=/weblaptop/notifications.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Actions
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_all_read') {
        markAllNotificationsAsRead($user_id);
        set_flash('success', 'Đã đánh dấu tất cả thông báo là đã đọc.');
    } elseif ($_POST['action'] === 'read' && isset($_POST['notif_id'])) {
        markNotificationAsRead((int)$_POST['notif_id'], $user_id);
    }
    header('Location: notifications.php');
    exit;
}

$notifications = getUserNotifications($user_id);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --tet-red: #d32f2f;
        --tet-gold: #ffc107;
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
    
    .notification-item {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border-left: 5px solid transparent;
        cursor: pointer;
    }
    .notification-item.unread {
        background-color: #fff1f2;
        border-left-color: var(--tet-red);
    }
    .notification-item:hover {
        background-color: #f8fafc;
        transform: scale(1.002);
    }
    .icon-box {
        width: 50px;
        height: 50px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
</style>

<div class="container py-5">
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
                    <a href="orders.php" class="nav-link-modern">
                        <i class="bi bi-box-seam fs-5"></i> Đơn hàng đã mua
                    </a>
                    <a href="notifications.php" class="nav-link-modern active">
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
            <div class="d-flex justify-content-between align-items-center mb-4 px-2">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Thông báo kiến thức</h3>
                    <p class="text-muted small mb-0">Cập nhật những tin tức mới nhất từ GrowTech</p>
                </div>
                <?php if (!empty($notifications)): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-4 shadow-sm border-0 bg-white">
                            <i class="bi bi-check2-all me-1"></i> Đọc tất cả
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                <div class="list-group list-group-flush">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <div class="mb-3 opacity-25">
                                <i class="bi bi-bell-slash fs-1"></i>
                            </div>
                            <h5 class="fw-bold">Hòm thư trống</h5>
                            <p class="text-muted">Bạn chưa có thông báo nào vào lúc này.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <div class="list-group-item p-4 notification-item <?php echo $n['is_read'] ? '' : 'unread'; ?> border-bottom border-light" 
                                 onclick="markRead(<?php echo $n['id']; ?>, '<?php echo $n['link'] ?? ''; ?>')">
                                <div class="d-flex gap-4">
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
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0 fs-5 <?php echo $n['is_read'] ? 'text-dark fw-bold' : 'fw-bold text-danger'; ?>">
                                                <?php echo htmlspecialchars($n['title']); ?>
                                            </h6>
                                            <small class="text-muted fw-medium"><?php echo date('d/m/Y H:i', strtotime($n['created_at'])); ?></small>
                                        </div>
                                        <p class="text-muted small mb-0 lh-base"><?php echo htmlspecialchars($n['content']); ?></p>
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
            
            <form id="readForm" method="POST" style="display:none;">
                <input type="hidden" name="action" value="read">
                <input type="hidden" name="notif_id" id="notif_id_input">
            </form>

            <script>
            function markRead(id, link) {
                document.getElementById('notif_id_input').value = id;
                const form = document.getElementById('readForm');
                form.submit();
            }
            </script>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
