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
  <title>Admin Dashboard - GrowTech</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link href="/weblaptop/assets/css/style.css" rel="stylesheet">
  <style>
    :root {
      --shopee-orange: #ee4d2d;
      --tet-red: #d32f2f;
      --tet-gold: #ffc107;
      --admin-bg: #f5f5f5;
      --sidebar-width: 250px;
    }
    body { background-color: var(--admin-bg); font-family: -apple-system, Helvetica Neue, Helvetica, Roboto, Droid Sans, Arial, sans-serif; }
    .admin-wrapper { display: flex; min-height: 100vh; }
    .admin-content { flex-grow: 1; padding: 24px; background-color: var(--admin-bg); }
    
    /* Global Shopee Utilities */
    .btn-shopee-primary { background-color: var(--shopee-orange); color: #fff; border: none; }
    .btn-shopee-primary:hover { background-color: #d73211; color: #fff; }
    .card-shopee { background: #fff; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,.05); border: none; }
    .text-shopee { color: var(--shopee-orange); }
    
    /* Shopee Style Navbar */
    .admin-navbar {
      background: #fff;
      height: 60px;
      display: flex;
      align-items: center;
      padding: 0 24px;
      box-shadow: 0 1px 4px rgba(0,0,0,.05);
      position: sticky;
      top: 0;
      z-index: 1000;
      border-bottom: 2px solid var(--tet-red);
    }
    .admin-navbar .brand {
      font-size: 20px;
      font-weight: 700;
      color: var(--shopee-orange);
      text-decoration: none;
      display: flex;
      align-items: center;
    }
    .admin-navbar .brand .tet-icon { margin-right: 8px; font-size: 24px; }

    /* Tet Decorations */
    .tet-corner {
      position: fixed;
      top: 0;
      right: 0;
      width: 150px;
      pointer-events: none;
      z-index: 1001;
      opacity: 0.8;
    }
    .tet-corner-left {
      position: fixed;
      top: 0;
      left: 0;
      width: 150px;
      pointer-events: none;
      z-index: 1001;
      opacity: 0.8;
      transform: scaleX(-1);
    }
  </style>
</head>
<body>

<div class="admin-navbar">
  <a href="dashboard.php" class="brand">
    <span class="tet-icon">🧧</span> GROWTECH <span class="ms-2 text-dark fw-normal" style="font-size: 16px;">Kênh Người Bán</span>
  </a>
  <div class="ms-auto d-flex align-items-center">
    <a href="/weblaptop" class="btn btn-sm btn-outline-secondary me-3">Xem Shop</a>
    <div class="dropdown">
      <a href="#" class="text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
        <span class="sparkle-effect me-1"></span> Admin
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
        <li><a class="dropdown-item" href="logout.php">Đăng xuất</a></li>
      </ul>
    </div>
  </div>
</div>

<div class="tet-corner">🌸</div>
<div class="tet-corner-left">🌸</div>

<div class="admin-wrapper">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="admin-content">
        <?php display_flash(); ?>
