<?php
require_once __DIR__ . '/../includes/header.php';
$token = $_GET['token'] ?? '';
$msg = null;
if ($token) {
    $stmt = $pdo->prepare('SELECT id, verification_expires FROM users WHERE verification_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if ($u && (!$u['verification_expires'] || strtotime($u['verification_expires']) >= time())) {
        $stmt2 = $pdo->prepare('UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ?');
        $stmt2->execute([$u['id']]);

        // Send confirmation notification
        createNotification(
            $u['id'], 
            "Xác thực tài khoản thành công", 
            "Chúc mừng! Email của bạn đã được xác thực thành công. Bây giờ bạn có thể trải nghiệm đầy đủ các tính năng của Growtech.", 
            'system'
        );

        set_flash('success', 'Xác thực email thành công. Bạn có thể đăng nhập ngay.');
    } else {
        set_flash('error', 'Liên kết xác thực không hợp lệ hoặc đã hết hạn.');
    }
} else {
    set_flash('error', 'Thiếu mã xác thực.');
}
?>
<div class="row justify-content-center my-5">
  <div class="col-md-6">
    <div class="card p-4">
      <p>Trạng thái xác thực được hiển thị ở thông báo phía trên. Bạn có thể quay lại <a href="/weblaptop/auth/login.php">Đăng nhập</a>.</p>
      <a href="/weblaptop/auth/login.php" class="btn btn-primary">Đăng nhập</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>