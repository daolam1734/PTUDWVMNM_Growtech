<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/database.php';

if (session_status() == PHP_SESSION_NONE) session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND role = 'admin' LIMIT 1");
    $stmt->execute([$u, $u]);
    $user = $stmt->fetch();
    if ($user && password_verify($p, $user['password'])) {
        $_SESSION['admin_logged_in'] = $user['username'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        header('Location: dashboard.php'); exit;
    } else {
      $error = 'Sai tên đăng nhập hoặc mật khẩu quản trị';
    }
}
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-danger text-white text-center py-4">
          <h3 class="mb-0">ADMIN LOGIN</h3>
          <p class="mb-0 small opacity-75">Hệ thống quản trị GrowTech</p>
        </div>
        <div class="card-body p-4">
          <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
          <form method="post">
            <div class="mb-3">
                <label class="form-label fw-bold">Tên đăng nhập hoặc Email</label>
                <input class="form-control form-control-lg" name="username" required placeholder="admin@example.com">
            </div>
            <div class="mb-4">
                <label class="form-label fw-bold">Mật khẩu</label>
                <input type="password" class="form-control form-control-lg" name="password" required placeholder="••••••••">
            </div>
            <button class="btn btn-danger btn-lg w-100 shadow-sm">Đăng nhập hệ thống</button>
          </form>
        </div>
        <div class="card-footer bg-light text-center py-3">
          <a href="/weblaptop" class="text-decoration-none small text-muted">Quay lại trang chủ</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>