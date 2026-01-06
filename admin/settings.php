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
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item small"><a href="dashboard.php" class="text-decoration-none text-muted">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item small active" aria-current="page">Cấu hình hệ thống</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0">Cài Đặt Tổng Quan</h4>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm bg-white" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i> Làm mới
                </button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-gear me-2 text-primary"></i>Thông tin website</h6>
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <?php foreach ($settings as $s): ?>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold text-dark mb-1">
                                        <?php echo htmlspecialchars($s['description'] ?: $s['key']); ?>
                                    </label>
                                    <?php if ($s['key'] === 'flash_sale_end'): ?>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-clock"></i></span>
                                            <input type="datetime-local" name="settings[<?php echo $s['key']; ?>]" class="form-control bg-light border-start-0" value="<?php echo date('Y-m-d\TH:i', strtotime($s['value'])); ?>">
                                        </div>
                                    <?php elseif (strpos($s['key'], 'description') !== false || $s['key'] === 'site_footer'): ?>
                                        <textarea name="settings[<?php echo $s['key']; ?>]" class="form-control bg-light" rows="3" placeholder="Nhập nội dung..."><?php echo htmlspecialchars($s['value']); ?></textarea>
                                    <?php else: ?>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-pencil-square"></i></span>
                                            <input type="text" name="settings[<?php echo $s['key']; ?>]" class="form-control bg-light border-start-0" value="<?php echo htmlspecialchars($s['value']); ?>">
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between mt-1">
                                        <div class="text-muted" style="font-size: 11px;">Mã khóa: <code><?php echo $s['key']; ?></code></div>
                                        <div class="text-primary" style="font-size: 11px;">Hệ thống</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="pt-3 border-top mt-4 d-flex justify-content-end">
                                <button type="submit" name="update_settings" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                    <i class="bi bi-check2-all me-2"></i>Cập nhật tất cả thay đổi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 bg-dark text-white mb-4 overflow-hidden">
                    <div class="card-body p-4 position-relative" style="z-index: 1;">
                        <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock me-2"></i>Lưu ý quan trọng</h6>
                        <p class="small mb-3 opacity-75">
                            Các thông tin cấu hình này ảnh hưởng trực tiếp đến giao diện người dùng và hoạt động của hệ thống.
                        </p>
                        <ul class="small opacity-75 ps-3 mb-0">
                            <li class="mb-2">Kiểm tra kỹ định dạng Email/Số điện thoại.</li>
                            <li class="mb-2">Cấu hình SEO giúp website lên hạng tìm kiếm.</li>
                            <li>Thông tin này hiển thị ở chân trang (Footer).</li>
                        </ul>
                        <i class="bi bi-gear-fill position-absolute" style="right: -20px; bottom: -20px; font-size: 100px; opacity: 0.1;"></i>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3"><i class="bi bi-hdd-network me-2"></i>Trạng thái máy chủ</h6>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1 small fw-bold">Kết nối Database</div>
                            <span class="badge bg-soft-success text-success rounded-pill px-3 border border-success">Hoạt động</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-grow-1 small fw-bold">PHP Version</div>
                            <span class="badge bg-soft-primary text-primary rounded-pill px-3 border border-primary"><?php echo PHP_VERSION; ?></span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1 small fw-bold">Môi trường</div>
                            <span class="badge bg-soft-info text-info rounded-pill px-3 border border-info">Production</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-soft-success { background-color: rgba(25, 135, 84, 0.1); }
.bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
.bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
.input-group-text { border-color: #dee2e6; color: #6c757d; }
.form-control:focus { box-shadow: none; border-color: var(--accent-color); }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
