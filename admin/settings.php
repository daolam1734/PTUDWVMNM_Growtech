<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE settings SET value = ? WHERE `key` = ?");
        $stmt->execute([$value, $key]);
    }
    set_flash("success", "Đã cập nhật cài đặt hệ thống.");
    header("Location: settings.php"); exit;
}

$settings = $pdo->query("SELECT * FROM settings ORDER BY `key` ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Cài đặt</li>
                </ol>
            </nav>
            <h4 class="fw-bold">Cài Đặt Hệ Thống</h4>
            <p class="text-muted small">Cấu hình các thông tin cơ bản của website.</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4">
                        <form method="POST">
                            <?php foreach ($settings as $s): ?>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-uppercase text-muted"><?php echo htmlspecialchars($s['description'] ?: $s['key']); ?></label>
                                    <?php if ($s['key'] === 'flash_sale_end'): ?>
                                        <input type="datetime-local" name="settings[<?php echo $s['key']; ?>]" class="form-control" value="<?php echo date('Y-m-d\TH:i', strtotime($s['value'])); ?>">
                                    <?php elseif (strpos($s['key'], 'description') !== false || $s['key'] === 'site_footer'): ?>
                                        <textarea name="settings[<?php echo $s['key']; ?>]" class="form-control" rows="3"><?php echo htmlspecialchars($s['value']); ?></textarea>
                                    <?php else: ?>
                                        <input type="text" name="settings[<?php echo $s['key']; ?>]" class="form-control" value="<?php echo htmlspecialchars($s['value']); ?>">
                                    <?php endif; ?>
                                    <div class="form-text x-small">Key: <code><?php echo $s['key']; ?></code></div>
                                </div>
                            <?php endforeach; ?>
                            
                            <hr class="my-4">
                            <button type="submit" name="update_settings" class="btn btn-primary px-5 fw-bold">
                                <i class="bi bi-save me-2"></i>Lưu thay đổi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 bg-primary text-white mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2"></i>Hướng dẫn</h6>
                        <p class="small mb-0 opacity-75">
                            Các thông tin này sẽ được hiển thị trên toàn bộ website. Hãy đảm bảo thông tin liên hệ là chính xác để khách hàng có thể kết nối với shop.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
