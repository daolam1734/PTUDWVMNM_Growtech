<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../functions.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identity = trim($_POST['identity'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($identity === '' || $password === '') {
        $errors[] = 'Vui lòng nhập email/tên đăng nhập và mật khẩu.';
    } else {
        $user = findUserByEmailOrUsername($identity);
        if (!$user) {
            $errors[] = 'Email hoặc mật khẩu không đúng.'; // generic message
        } else {
            if (isAccountLocked($user)) {
                $errors[] = 'Tài khoản tạm khóa do nhiều lần đăng nhập sai. Thử lại sau.';
            } elseif (!password_verify($password, $user['password'])) {
                incrementFailedLogin($user['id']);
                if ($user['failed_logins'] + 1 >= 5) lockAccount($user['id'], 15);
                $errors[] = 'Email hoặc mật khẩu không đúng.';
            } else {
                // success
                resetFailedLogins($user['id']);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                if ($user['role'] === 'admin') {
                    $_SESSION['admin_logged_in'] = $user['username'];
                }
                // remember me (simple)
                if ($remember) {
                    $token = bin2hex(random_bytes(24));
                    $hash = password_hash($token, PASSWORD_DEFAULT);
                    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                    $stmt = $pdo->prepare("INSERT INTO auth_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
                    $stmt->execute([$user['id'], $hash, $expires]);
                    setcookie('weblaptop_remember', $token, time() + 60*60*24*30, '/', '', false, true);
                }
                // success flash
                set_flash('success', 'Đăng nhập thành công');
                // redirect
                $next = !empty($_GET['next']) ? $_GET['next'] : '/weblaptop';
                header('Location: ' . $next);
                exit;
            }
        }
    }
    if (!empty($errors)) set_flash('error', implode('<br>', $errors));
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center my-5">
  <div class="col-md-6">
    <div class="card p-4">
      <h4>Đăng nhập tài khoản</h4>
      <form method="post">
        <div class="mb-2"><label class="form-label">Email hoặc tên đăng nhập</label><input class="form-control" name="identity" required></div>
        <div class="mb-2"><label class="form-label">Mật khẩu</label><input class="form-control" name="password" type="password" required></div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="form-check"><input class="form-check-input" type="checkbox" id="remember" name="remember"><label class="form-check-label" for="remember">Ghi nhớ</label></div>
          <a href="/weblaptop/auth/password_forgot.php">Quên mật khẩu?</a>
        </div>
        <button class="btn btn-primary" type="submit">Đăng nhập</button>
      </form>
      <div class="mt-2">Chưa có tài khoản? <a href="/weblaptop/auth/register.php">Đăng ký</a></div>
      <hr>
      <div>
        <a href="#" class="btn btn-outline-danger me-1">Đăng nhập với Google</a>
        <a href="#" class="btn btn-outline-primary">Đăng nhập với Facebook</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>