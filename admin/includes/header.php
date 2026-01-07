<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../functions.php";
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Quản Trị Hệ Thống - GrowTech</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link href="/weblaptop/assets/css/style.css" rel="stylesheet">
  <style>
    :root {
      --admin-bg: #f5f5f5;
      --sidebar-width: 260px;
      --primary-color: #2c3e50;
      --accent-color: #3498db;
    }
    body { background-color: var(--admin-bg); font-family: 'Inter', -apple-system, sans-serif; }
    .admin-wrapper { display: flex; min-height: 100vh; }
    .admin-content { flex-grow: 1; padding: 24px; background-color: var(--admin-bg); width: calc(100% - var(--sidebar-width)); }
    
    /* Professional UI Components */
    .card-stats { border: none; border-radius: 12px; transition: transform 0.2s; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    .card-stats:hover { transform: translateY(-5px); }
    .icon-shape { width: 48px; height: 48px; background-position: center; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
    
    /* Shopee Style Navbar */
    .admin-navbar {
      background: #fff;
      height: 70px;
      display: flex;
      align-items: center;
      padding: 0 24px;
      box-shadow: 0 2px 4px rgba(0,0,0,.04);
      position: sticky;
      top: 0;
      z-index: 1000;
      border-bottom: 1px solid #eee;
    }
    .admin-navbar .brand {
      font-size: 22px;
      font-weight: 800;
      color: #2c3e50;
      text-decoration: none;
      display: flex;
      align-items: center;
      letter-spacing: -0.5px;
    }
    .admin-navbar .brand .brand-icon { margin-right: 10px; font-size: 28px; }

    /* Search Bar */
    .nav-search { max-width: 400px; width: 100%; margin-left: 40px; }
    .nav-search .form-control { background: #f0f2f5; border: none; border-radius: 20px; padding: 8px 20px; font-size: 14px; }
    
    /* Notifications */
    .nav-link-icon { position: relative; padding: 8px; color: #65676b; font-size: 20px; transition: all 0.2s; }
    .nav-link-icon:hover { color: #3498db; background: #f0f2f5; border-radius: 50%; }
    .badge-notify { position: absolute; top: 5px; right: 5px; font-size: 10px; padding: 3px 5px; border-radius: 50%; border: 2px solid #fff; }
    
    .hover-bg:hover { background: rgba(0,0,0,0.05); }
  </style>
</head>
<body>

<div class="admin-navbar">
  <a href="dashboard.php" class="brand">
    <span class="brand-icon me-2">
      <i class="bi bi-laptop text-primary"></i>
    </span> GROWTECH <span class="ms-2 text-dark fw-light d-none d-md-inline" style="font-size: 15px; border-left: 1px solid #ddd; padding-left: 12px;">Seller Center 2026</span>
  </a>

  <div class="ms-auto d-flex align-items-center">
    <div class="d-flex me-3">
      <a href="/weblaptop" target="_blank" class="nav-link-icon" title="Truy cập Website"><i class="bi bi-box-arrow-up-right"></i></a>
    </div>
    
    <div class="dropdown">
      <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle px-2 py-1 rounded-pill hover-bg" data-bs-toggle="dropdown" style="transition: all 0.2s;">
        <div class="avatar me-2 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 14px;">AD</div>
        <div class="d-none d-sm-block me-1">
          <div class="fw-bold" style="font-size: 13px; line-height: 1;">Quản trị viên</div>
          <div class="text-success" style="font-size: 10px;"><i class="bi bi-circle-fill" style="font-size: 6px;"></i> Trực tuyến</div>
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2 rounded-3 p-2">
        <li><a class="dropdown-item py-2 px-3 rounded-2 small" href="settings.php"><i class="bi bi-gear me-2"></i>Cài đặt hệ thống</a></li>
        <li><hr class="dropdown-divider mx-2"></li>
        <li><a class="dropdown-item py-2 px-3 rounded-2 small text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất tài khoản</a></li>
      </ul>
    </div>
  </div>
</div>

<div class="admin-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="admin-content">
        <?php display_flash(); ?>
