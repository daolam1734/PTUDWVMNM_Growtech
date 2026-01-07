<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../functions.php";

// Fetch specific brands for menu
$allowed_brands = ['Dell', 'Lenovo', 'Acer', 'HP', 'Asus', 'Apple'];
$placeholders = implode(',', array_fill(0, count($allowed_brands), '?'));
$stmt_menu_brands = $pdo->prepare("SELECT * FROM brands WHERE name IN ($placeholders) ORDER BY FIELD(name, $placeholders)");
$stmt_execute_params = array_merge($allowed_brands, $allowed_brands);
$stmt_menu_brands->execute($stmt_execute_params);
$menu_brands = $stmt_menu_brands->fetchAll();
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>GrowTech - Chuẩn công nghệ – vững niềm tin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <link href="/weblaptop/assets/css/style.css" rel="stylesheet">
  <style>
    body { margin: 0 !important; padding: 0 !important; }
    :root { 
      --tet-red: #C62222; 
      --tet-gold: #D4AF37;
      --tet-dark-red: #8B0000;
      --tet-light-gold: #F9E79F;
      --tet-soft-bg: #FEF9E7;
    }
    .tet-header { 
      background: linear-gradient(135deg, #c62828, #8e0000); 
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"), linear-gradient(135deg, #c62828, #8e0000);
      color: #fff; 
      border-bottom: 4px solid var(--tet-gold);
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
      transition: all 0.3s ease;
    }
    .header-spacer {
      height: 170px;
    }
    @media (max-width: 991px) {
      .header-spacer { height: 110px; }
    }
    .tet-header.shrink {
      padding-bottom: 12px !important;
      box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    }
    .tet-header.shrink .nav-top {
      display: none !important;
    }
    .tet-header.shrink .main-menu-nav {
      display: none !important;
    }
    .tet-header a { color: #fff; text-decoration: none; font-size: 13px; transition: color 0.2s; }
    .tet-header a:hover { color: var(--tet-gold); }
    .search-bar-container { 
      background: #fff; 
      border-radius: 4px; 
      padding: 0; 
      display: flex; 
      flex-grow: 1; 
      margin: 0 60px; 
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      border: 2px solid var(--tet-gold);
      overflow: hidden;
      height: 45px;
    }
    .search-input { border: none; flex-grow: 1; padding: 0 20px; outline: none; color: #333; font-size: 15px; height: 100%; }
    .search-input::placeholder { color: #999; }
    .search-btn { 
      background: var(--tet-red); 
      color: #fff; 
      border: none; 
      padding: 0 30px; 
      border-radius: 0; 
      transition: all 0.2s; 
      font-size: 14px; 
      font-weight: 700;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .search-btn:hover { background: #b71c1c; }
    .cart-icon { font-size: 26px; position: relative; margin-left: 15px; color: #fff !important; transition: transform 0.2s; }
    .cart-icon:hover { transform: scale(1.05); }
    .cart-badge { 
      position: absolute; 
      top: -8px; 
      right: -12px; 
      background: #fff; 
      color: var(--tet-red); 
      border-radius: 12px; 
      padding: 1px 7px; 
      font-size: 12px; 
      font-weight: 700; 
      border: 2px solid var(--tet-red);
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
      line-height: 1;
    }

    /* Cart Dropdown */
    #header-cart-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      width: 400px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
      display: none;
      z-index: 1500;
      padding: 0;
      border: none;
      border-top: 3px solid var(--tet-gold);
      margin-top: 10px;
      animation: fadeIn 0.2s ease-out;
    }
    #header-cart-dropdown.show { display: block; }
    .cart-dropdown-header { padding: 15px; color: #000; font-weight: 700; font-size: 15px; border-bottom: 1px solid #f5f5f5; }
    .cart-dropdown-body { max-height: 350px; overflow-y: auto; }
    .cart-dropdown-item { 
      display: flex; 
      padding: 12px 15px; 
      text-decoration: none; 
      color: #333 !important; 
      transition: background 0.2s;
      border-bottom: 1px solid #fafafa;
    }
    .cart-dropdown-item:hover { background: rgba(211, 47, 47, 0.03); }
    .cart-dropdown-item img { width: 50px; height: 50px; object-fit: cover; border: 1px solid #eee; margin-right: 12px; border-radius: 4px; }
    .cart-dropdown-info { flex: 1; min-width: 0; }
    .cart-dropdown-name { font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 4px; color: #333; font-weight: 500; }
    .cart-dropdown-price { color: var(--tet-red); font-weight: 700; font-size: 14px; }
    .cart-dropdown-footer { padding: 12px; background: #fdfdfd; text-align: right; border-top: 1px solid #f5f5f5; }
    .btn-view-cart { background: var(--tet-red); color: #fff !important; padding: 8px 15px; border-radius: 2px; font-size: 13px; text-decoration: none; display: inline-block; }
    .btn-view-cart:hover { background: #ee4d2d; }
    .nav-top { font-size: 13px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.15); }
    .logo-text { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; letter-spacing: 1px; text-shadow: 2px 2px 4px rgba(0,0,0,0.4); }
    .slogan { font-size: 0.85rem; color: var(--tet-gold); font-style: italic; margin-top: -5px; font-weight: 500; }
    .tet-decoration { position: absolute; pointer-events: none; opacity: 0.4; font-size: 2.5rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); z-index: 10; }
    .tet-2026-badge {
      background: linear-gradient(45deg, var(--tet-gold), #fff);
      color: var(--tet-red);
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 800;
      text-transform: uppercase;
      margin-left: 10px;
      box-shadow: 0 3px 6px rgba(0,0,0,0.3);
      border: 1px solid #fff;
      animation: badge-glow 2s infinite alternate;
    }
    @keyframes badge-glow {
      from { box-shadow: 0 0 5px var(--tet-gold); }
      to { box-shadow: 0 0 15px var(--tet-gold); }
    }
    .hotline-box {
      background: rgba(255,255,255,0.1);
      padding: 4px 12px;
      border-radius: 50px;
      border: 1px solid rgba(255,255,255,0.2);
      font-weight: 600;
    }
    .hotline-box i { color: var(--tet-gold); }
    
    #search-suggestions {
      position: absolute; z-index: 1200; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #ddd; border-radius: 0 0 15px 15px; display: none; max-height: 450px; overflow: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.15); margin-top: 5px;
    }
    #search-suggestions.show { display: block; }
    .suggestion-item { text-decoration: none; color: #000; border-bottom: 1px solid #f0f0f0; transition: background 0.2s; }
    .suggestion-item:hover, .suggestion-item.active { background: #fff8f8; color: var(--tet-red); }
    .suggestion-item img { border-radius: 4px; border: 1px solid #eee; }

    /* Falling blossoms effect */
    .blossom {
      position: fixed;
      top: -50px;
      pointer-events: none;
      z-index: 9999;
      user-select: none;
      animation: fall linear infinite;
    }
    @keyframes fall {
      0% { 
        transform: translateY(0) translateX(0) rotate(0deg); 
        opacity: 0; 
      }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { 
        transform: translateY(105vh) translateX(100px) rotate(360deg); 
        opacity: 0; 
      }
    }

    /* Main Menu Styling */
    .main-menu-nav {
      background: rgba(0, 0, 0, 0.15);
      border-radius: 8px;
      margin-top: 12px;
      padding: 0;
      border: 1px solid rgba(255,255,255,0.1);
      backdrop-filter: blur(5px);
    }
    .main-menu-nav .nav-link {
      color: #fff !important;
      font-weight: 600;
      padding: 12px 20px !important;
      text-transform: uppercase;
      font-size: 13px;
      transition: all 0.3s;
      letter-spacing: 0.5px;
      position: relative;
    }
    .main-menu-nav .nav-link::after {
      content: '';
      position: absolute;
      bottom: 8px;
      left: 50%;
      width: 0;
      height: 2px;
      background: var(--tet-gold);
      transition: all 0.3s;
      transform: translateX(-50%);
    }
    .main-menu-nav .nav-link:hover::after {
      width: 30px;
    }
    .main-menu-nav .nav-link:hover {
      color: var(--tet-gold) !important;
    }
    
    /* Better Megamenu styling */
    .nav-item.dropdown.megamenu-parent {
      position: static !important;
    }
    .megamenu-parent .dropdown-menu {
      width: 90vw;
      max-width: 1100px;
      left: 50% !important;
      right: auto !important;
      transform: translateX(-50%) translateY(20px) !important;
      padding: 30px;
      border: none;
      border-radius: 15px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.15);
      border-top: 5px solid var(--tet-gold);
      opacity: 0;
      visibility: hidden;
      display: block;
      transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .megamenu-parent:hover .dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateX(-50%) translateY(0) !important;
    }
    .megamenu-title {
      font-size: 15px;
      font-weight: 800;
      color: var(--tet-red);
      text-transform: uppercase;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      letter-spacing: 0.5px;
    }
    .megamenu-title::after {
      content: "";
      flex: 1;
      height: 2px;
      background: #eee;
      margin-left: 15px;
    }
    .megamenu-link {
      color: #333 !important;
      font-weight: 600;
      padding: 10px 15px !important;
      border-radius: 10px;
      transition: all 0.2s;
      background: #fff;
      border: 1px solid #eee;
      display: flex;
      align-items: center;
      margin-bottom: 12px;
      font-size: 14px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    }
    .megamenu-link:hover {
      background: var(--tet-soft-bg);
      color: var(--tet-red) !important;
      border-color: var(--tet-gold);
      transform: translateX(8px);
    }
    .megamenu-link i {
      font-size: 20px;
      margin-right: 12px;
      color: var(--tet-red);
      background: #fff5f5;
      width: 35px;
      height: 35px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
    }
    .megamenu-brand-item {
      text-align: center;
      padding: 15px;
      border: 1px solid #eee;
      border-radius: 10px;
      transition: all 0.3s;
      display: block;
      text-decoration: none;
      height: 100%;
      background: #fff;
    }
    .megamenu-brand-item:hover {
      border-color: var(--tet-red);
      box-shadow: 0 5px 15px rgba(211, 47, 47, 0.1);
      transform: translateY(-3px);
    }
    .megamenu-brand-item img {
      width: 40px;
      height: 40px;
      object-fit: contain;
      margin-bottom: 8px;
    }
    .megamenu-brand-item span {
      display: block;
      font-size: 12px;
      font-weight: bold;
      color: #555;
    }
    .tet-badge {
      background: var(--tet-red);
      color: white;
      font-size: 10px;
      padding: 2px 6px;
      border-radius: 4px;
      margin-left: 5px;
      vertical-align: middle;
    }
    
    /* Show dropdown on hover for desktop */
    @media (min-width: 992px) {
      .nav-item.dropdown:hover > .dropdown-menu {
        display: block;
        opacity: 1;
        visibility: visible;
        margin-top: 0;
      }
      .nav-item.dropdown > .dropdown-menu {
        display: block;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        margin-top: 20px;
      }
      .nav-item.dropdown:hover > .nav-link {
        color: var(--tet-gold) !important;
      }
      .nav-item.dropdown:hover > .nav-link::after {
        width: 30px;
      }
    }

    /* General Dropdown Styling */
    .dropdown-menu {
      border: none;
      border-radius: 12px;
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
      padding: 8px;
      border-top: 3px solid var(--tet-gold);
      animation: fadeIn 0.2s ease-out;
      background-color: #ffffff;
      z-index: 2000;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .dropdown-item {
      border-radius: 8px;
      padding: 10px 15px;
      font-weight: 500;
      color: #333 !important;
      transition: all 0.2s;
      font-size: 14px;
      display: flex;
      align-items: center;
    }
    .dropdown-item i {
      font-size: 1.1rem;
      margin-right: 10px;
      color: var(--tet-red);
      width: 20px;
      text-align: center;
    }
    .dropdown-item:hover {
      background: rgba(211, 47, 47, 0.05);
      color: var(--tet-red) !important;
      transform: translateX(5px);
    }
    .dropdown-divider {
      margin: 8px 0;
      border-top: 1px solid #f0f0f0;
    }
    .dropdown-item.text-danger {
      color: #d32f2f !important;
      font-weight: 700;
    }
    .dropdown-item.text-danger:hover {
      background: rgba(211, 47, 47, 0.1);
    }
    
    /* Pulse animation for Lì xì */
    @keyframes pulse-gold {
      0% { transform: scale(1); text-shadow: 0 0 0 rgba(255, 193, 7, 0); }
      50% { transform: scale(1.05); text-shadow: 0 0 10px rgba(255, 193, 7, 0.5); }
      100% { transform: scale(1); text-shadow: 0 0 0 rgba(255, 193, 7, 0); }
    }
    .nav-link.text-warning {
      animation: pulse-gold 2s infinite ease-in-out;
    }

    /* Tet Footer Styling */
    .tet-footer {
      background: linear-gradient(135deg, var(--tet-dark-red), var(--tet-red));
      background-image: 
        url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"), 
        linear-gradient(135deg, var(--tet-dark-red), var(--tet-red));
      color: #fff;
      border-top: 5px solid var(--tet-gold);
      position: relative;
      overflow: hidden;
    }
    .tet-footer::before {
      content: '🏮';
      position: absolute;
      top: 20px;
      right: 30px;
      font-size: 3rem;
      opacity: 0.2;
    }
    .tet-footer::after {
      content: '🏮';
      position: absolute;
      bottom: 20px;
      left: 30px;
      font-size: 3rem;
      opacity: 0.2;
    }
    .footer-title {
      color: var(--tet-gold);
      font-weight: 800;
      letter-spacing: 1px;
      margin-bottom: 25px;
      position: relative;
      display: inline-block;
      text-transform: uppercase;
    }
    .footer-title::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 40px;
      height: 2px;
      background: var(--tet-gold);
    }
    .footer-links li {
      margin-bottom: 12px;
      transition: all 0.3s;
    }
    .footer-links a {
      color: rgba(255, 255, 255, 0.8) !important;
      text-decoration: none;
      font-size: 14px;
      transition: all 0.3s;
      display: flex;
      align-items: center;
    }
    .footer-links a:hover {
      color: var(--tet-gold) !important;
      transform: translateX(8px);
    }
    .social-btn {
      width: 36px;
      height: 36px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff !important;
      transition: all 0.3s;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .social-btn:hover {
      background: var(--tet-gold);
      color: var(--tet-red) !important;
      transform: translateY(-3px);
      border-color: var(--tet-gold);
    }
    .payment-methods img {
      background: #fff;
      padding: 3px;
      border-radius: 4px;
      filter: grayscale(0.2);
      transition: all 0.3s;
    }
    .payment-methods img:hover {
      filter: grayscale(0);
      transform: scale(1.1);
    }
  </style>
</head>
<body>

<header class="tet-header pb-3">
  <!-- Decorative elements -->
  <div class="tet-decoration" style="top: 10px; left: 2%;">🏮</div>
  <div class="tet-decoration" style="top: 60px; left: 4%; font-size: 1.5rem;">🌸</div>
  <div class="tet-decoration" style="top: 10px; right: 2%;">🏮</div>
  <div class="tet-decoration" style="top: 60px; right: 4%; font-size: 1.5rem;">🌼</div>
  <div class="tet-decoration" style="bottom: 10px; left: 10%; font-size: 1.2rem;">🧧</div>
  <div class="tet-decoration" style="bottom: 10px; right: 10%; font-size: 1.2rem;">✨</div>
  <div class="tet-decoration" style="top: 40%; left: 1%; font-size: 1.8rem; opacity: 0.3;">🐎</div>
  <div class="tet-decoration" style="top: 40%; right: 1%; font-size: 1.8rem; opacity: 0.3;">🐎</div>

  <div class="container">
    <!-- Top Nav -->
    <div class="d-flex justify-content-between nav-top align-items-center">
      <div class="d-flex gap-3 align-items-center">
        <a href="#"><i class="bi bi-phone me-1"></i> Tải ứng dụng</a>
        <div style="width: 1px; height: 12px; background: rgba(255,255,255,.3);"></div>
        <a href="#">Kết nối <i class="bi bi-facebook ms-1"></i> <i class="bi bi-instagram ms-1"></i></a>
        <div style="width: 1px; height: 12px; background: rgba(255,255,255,.3);"></div>
        <div class="hotline-box">
          <a href="tel:19001234"><i class="bi bi-telephone-fill me-1"></i> Hotline: 1900 1234</a>
        </div>
      </div>
      <div class="d-flex gap-3 align-items-center">
        <a href="/weblaptop/notifications.php" class="position-relative">
          <i class="bi bi-bell me-1"></i> Thông Báo
          <?php if (!empty($_SESSION['user_id'])): 
            $notif_count = getUnreadNotificationCount($_SESSION['user_id']);
            if ($notif_count > 0): ?>
              <span class="badge rounded-pill bg-warning text-dark px-1 ms-1" style="font-size: 10px;"><?php echo $notif_count; ?></span>
            <?php endif; 
          endif; ?>
        </a>
        <a href="/weblaptop/orders.php"><i class="bi bi-truck me-1"></i> Tra cứu đơn hàng</a>
        <a href="/weblaptop/contact.php"><i class="bi bi-question-circle me-1"></i> Hỗ Trợ</a>
        <?php if (!empty($_SESSION["user_id"])): ?>
          <div class="dropdown">
            <a href="#" class="dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
              <i class="bi bi-person-circle me-2"></i>
              <?php echo htmlspecialchars($_SESSION["user_name"] ?? "Tài khoản"); ?>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li><a class="dropdown-item text-danger fw-bold" href="/weblaptop/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Quản trị hệ thống</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="/weblaptop/account.php"><i class="bi bi-person me-2"></i> Hồ sơ</a></li>
              <li><a class="dropdown-item" href="/weblaptop/orders.php"><i class="bi bi-bag-check me-2"></i> Đơn mua</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/weblaptop/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Đăng xuất</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="/weblaptop/auth/register.php" class="fw-bold">Đăng Ký</a>
          <div style="width: 1px; height: 13px; background: rgba(255,255,255,.4);"></div>
          <a href="/weblaptop/auth/login.php" class="fw-bold">Đăng Nhập</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Main Header -->
    <div class="d-flex align-items-center mt-3">
      <a href="/weblaptop" class="d-flex flex-column text-decoration-none">
        <div class="fs-2 fw-bold d-flex align-items-center logo-text text-white">
          <span class="sparkle-effect me-2 text-warning"></span> GrowTech
          <span class="tet-2026-badge">Xuân Bính Ngọ 2026</span>
        </div>
        <span class="slogan">Mã Đáo Thành Công – Vững Niềm Tin Công Nghệ</span>
      </a>
      
      <div class="search-bar-container" id="header-search">
        <form action="/weblaptop/search.php" method="get" class="d-flex w-100 h-100">
          <input type="text" name="q" id="header-search-input" class="search-input" placeholder="Bạn cần tìm Laptop gì hôm nay?">
          <button type="submit" class="search-btn">
            <i class="bi bi-search me-2"></i> TÌM KIẾM
          </button>
        </form>
        <div id="search-suggestions"></div>
      </div>

      <div class="position-relative">
        <a href="/weblaptop/cart.php" class="cart-icon" id="header-cart-btn">
          <i class="bi bi-cart3"></i>
          <span class="cart-badge"><?php echo isset($_SESSION["cart"]) ? array_sum($_SESSION["cart"]) : 0; ?></span>
        </a>
        
        <!-- Cart Dropdown -->
        <div id="header-cart-dropdown">
          <div class="cart-dropdown-header">Sản phẩm mới thêm</div>
          <div class="cart-dropdown-body">
            <?php if (!empty($_SESSION["cart"])): ?>
              <?php 
              $cart_ids = array_keys($_SESSION["cart"]);
              $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
              $stmt_cart = $pdo->prepare("SELECT p.*, pi.url as image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 WHERE p.id IN ($placeholders)");
              $stmt_cart->execute($cart_ids);
              $cart_items = $stmt_cart->fetchAll();
              foreach ($cart_items as $item):
                $img = $item["image_url"] ?: 'https://placehold.co/45x45?text=No+Image';
              ?>
                <a href="/weblaptop/product.php?id=<?php echo $item['id']; ?>" class="cart-dropdown-item">
                  <img src="<?php echo htmlspecialchars($img); ?>" alt="">
                  <div class="cart-dropdown-info">
                    <div class="cart-dropdown-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="cart-dropdown-price"><?php echo number_format($item['price'], 0, ',', '.'); ?> đ</div>
                  </div>
                  <div class="small text-muted ms-2">x<?php echo $_SESSION["cart"][$item['id']]; ?></div>
                </a>
              <?php endforeach; ?>
              <div class="dropdown-divider m-0"></div>
              <div class="p-2 text-center" style="background: #f9f9f9;">
                <div class="small mb-2" style="color: #000;"><?php echo count($_SESSION['cart']); ?> sản phẩm mới thêm</div>
                <a href="/weblaptop/cart.php" class="btn btn-danger w-100 py-2" style="background-color: var(--tet-red); border: none; font-weight: 600; border-radius: 2px;">Xem Giỏ Hàng</a>
              </div>
            <?php else: ?>
              <div class="p-5 text-center">
                <img src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/assets/a60759ad1dabe909c46a817ecbf71878.png" width="100" class="mb-3" style="opacity: 0.8;">
                <div class="text-muted" style="font-size: 14px;">Chưa có sản phẩm</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Menu -->
    <nav class="navbar navbar-expand-lg main-menu-nav p-0">
      <div class="container-fluid p-0">
        <button class="navbar-toggler border-white text-white" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
          <i class="bi bi-list"></i>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
          <ul class="navbar-nav w-100 justify-content-center gap-3">
            <li class="nav-item">
              <a class="nav-link" href="/weblaptop/index.php"><i class="bi bi-house-door me-1"></i> Trang chủ</a>
            </li>
            
            <li class="nav-item dropdown megamenu-parent">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Sản phẩm</a>
              <div class="dropdown-menu">
                <div class="container-fluid">
                  <div class="row">
                    <!-- Categories Column -->
                    <div class="col-md-5 border-end">
                      <div class="megamenu-title">Theo nhu cầu</div>
                      <div class="row">
                        <div class="col-6">
                          <a href="/weblaptop/search.php?category=laptop-van-phong" class="megamenu-link">
                            <i class="bi bi-laptop"></i> Văn Phòng
                          </a>
                          <a href="/weblaptop/search.php?category=laptop-gaming" class="megamenu-link">
                            <i class="bi bi-controller"></i> Gaming <span class="tet-badge">Hot</span>
                          </a>
                          <a href="/weblaptop/search.php?category=laptop-doanh-nhan" class="megamenu-link">
                            <i class="bi bi-briefcase"></i> Doanh Nhân
                          </a>
                        </div>
                        <div class="col-6">
                          <a href="/weblaptop/search.php?category=laptop-hoc-tap" class="megamenu-link">
                            <i class="bi bi-mortarboard"></i> Sinh Viên
                          </a>
                          <a href="/weblaptop/search.php?category=laptop-cao-cap" class="megamenu-link">
                            <i class="bi bi-gem"></i> Cao Cấp
                          </a>
                          <a href="/weblaptop/search.php?category=laptop-2-trong-1" class="megamenu-link">
                            <i class="bi bi-unfold-vertical"></i> 2-in-1
                          </a>
                        </div>
                      </div>
                      
                      <div class="mt-4 p-3 rounded-4 text-center border" style="background: var(--tet-soft-bg); border-color: var(--tet-light-gold) !important;">
                        <p class="small mb-2 fw-bold" style="color: var(--tet-dark-red);">Hỗ trợ chọn mua Laptop Tết?</p>
                        <a href="/weblaptop/contact.php" class="btn btn-sm btn-danger rounded-pill px-3" style="background: var(--tet-red); border: none;">Liên hệ ngay</a>
                      </div>
                    </div>

                    <!-- Brands Column -->
                    <div class="col-md-7">
                      <div class="megamenu-title">Thương hiệu chính hãng</div>
                      <div class="row g-3">
                        <?php foreach ($menu_brands as $b): ?>
                          <div class="col-4 col-lg-3">
                            <a class="megamenu-brand-item" href="/weblaptop/search.php?brand=<?php echo urlencode($b['name']); ?>">
                              <?php if ($b['logo']): ?>
                                <img src="<?php echo htmlspecialchars($b['logo']); ?>" alt="<?php echo htmlspecialchars($b['name']); ?>">
                              <?php else: ?>
                                <div class="mb-2" style="color: var(--tet-red);"><i class="bi bi-tag-fill" style="font-size: 24px;"></i></div>
                              <?php endif; ?>
                              <span><?php echo htmlspecialchars($b['name']); ?></span>
                            </a>
                          </div>
                        <?php endforeach; ?>
                        
                        <div class="col-4 col-lg-3">
                          <a class="megamenu-brand-item d-flex flex-column justify-content-center align-items-center border-0" href="/weblaptop/search.php" style="background: var(--tet-red); color: white;">
                            <i class="bi bi-plus-circle mb-1" style="font-size: 20px;"></i>
                            <span class="text-white">Xem tất cả</span>
                          </a>
                        </div>
                      </div>

                      <div class="row mt-4">
                        <div class="col-12">
                          <div class="alert py-2 mb-0 border-0 rounded-3 d-flex align-items-center" style="background: linear-gradient(90deg, #fff3e0, #fff); border-left: 4px solid var(--tet-gold) !important;">
                            <i class="bi bi-gift-fill me-2" style="color: var(--tet-red);"></i>
                            <span class="small fw-bold" style="color: var(--tet-dark-red);">Mua Laptop đón Tết - Nhận Lì Xì tới 2.026.000đ!</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="/weblaptop/promotions.php"><i class="bi bi-percent me-1"></i> Khuyến mãi</a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="/weblaptop/news.php"><i class="bi bi-journal-text me-1"></i> Tin tức</a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="/weblaptop/contact.php"><i class="bi bi-envelope me-1"></i> Liên hệ</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </div>
</header>
<div class="header-spacer"></div>

<?php if (function_exists("display_flash")) display_flash(); ?>

<div class="container mt-4">
