<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

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
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Đăng nhập Quản trị - GrowTech</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <style>
    :root {
      --shopee-orange: #ee4d2d;
      --tet-red: #d32f2f;
      --tet-gold: #ffc107;
    }
    body {
      background: #f5f5f5;
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', -apple-system, sans-serif;
    }
    .login-card {
      width: 100%;
      max-width: 400px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.05);
      overflow: hidden;
    }
    .login-header {
      background: var(--tet-red);
      padding: 40px 20px;
      text-align: center;
      color: #fff;
    }
    .login-header .brand {
      font-size: 28px;
      font-weight: 800;
      letter-spacing: -1px;
      margin-bottom: 5px;
    }
    .login-body {
      padding: 40px;
    }
    .form-control {
      padding: 12px 15px;
      border-radius: 8px;
      border: 1px solid #ddd;
    }
    .form-control:focus {
      border-color: var(--tet-red);
      box-shadow: 0 0 0 0.25rem rgba(211, 47, 47, 0.1);
    }
    .btn-login {
      background: var(--tet-red);
      border: none;
      padding: 12px;
      border-radius: 8px;
      font-weight: 700;
      color: #fff;
      transition: all 0.2s;
    }
    .btn-login:hover {
      background: #b71c1c;
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
    }
    .tet-icon {
      margin-bottom: 15px;
    }
  </style>
</head>
<body>

<div class="login-card">
  <div class="login-header">
    <div class="tet-icon">
      <svg viewBox="0 0 24 24" width="48" height="48">
        <rect x="5" y="3" width="14" height="18" rx="2" fill="#fff"/>
        <path d="M12,10 L19,3 L5,3 Z" fill="#ffc107"/>
        <circle cx="12" cy="12" r="2" fill="#d32f2f"/>
      </svg>
    </div>
    <div class="brand">GROWTECH</div>
    <div class="small opacity-75">Seller Center - Admin Portal</div>
  </div>
  
  <div class="login-body">
    <?php if ($error): ?>
      <div class="alert alert-danger border-0 small mb-4">
        <i class="bi bi-exclamation-circle me-2"></i><?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label small fw-bold text-muted">Tên đăng nhập hoặc Email</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
          <input type="text" name="username" class="form-control border-start-0" placeholder="admin@growtech.vn" required autofocus>
        </div>
      </div>
      
      <div class="mb-4">
        <label class="form-label small fw-bold text-muted">Mật khẩu</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
          <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
        </div>
      </div>

      <button type="submit" class="btn btn-login w-100 mb-3">ĐĂNG NHẬP</button>
      
      <div class="text-center">
        <a href="/weblaptop" class="text-decoration-none small text-muted">
          <i class="bi bi-arrow-left me-1"></i> Quay lại trang chủ
        </a>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>