<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../functions.php";
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
    :root { 
      --tet-red: #d32f2f; 
      --tet-gold: #ffc107;
      --tet-dark-red: #a51d1d;
      --tet-light-gold: #ffecb3;
    }
    .tet-header { 
      background: linear-gradient(135deg, #c62828, #8e0000); 
      color: #fff; 
      border-bottom: 4px solid var(--tet-gold);
      position: relative;
      box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    }
    .tet-header a { color: #fff; text-decoration: none; font-size: 13px; transition: color 0.2s; }
    .tet-header a:hover { color: var(--tet-gold); }
    .search-bar-container { 
      background: #fff; 
      border-radius: 2px; 
      padding: 2px; 
      display: flex; 
      flex-grow: 1; 
      margin: 0 40px; 
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      border: 2px solid var(--tet-red); /* Add a red border to make it pop */
    }
    .search-input { border: none; flex-grow: 1; padding: 8px 15px; outline: none; color: #333; font-size: 14px; }
    .search-input::placeholder { color: #bbb; }
    .search-btn { background: var(--tet-red); color: #fff; border: none; padding: 0 25px; border-radius: 0; transition: all 0.2s; font-size: 18px; }
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
      border-radius: 4px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.15);
      display: none;
      z-index: 1500;
      padding: 0;
      border: 1px solid #eee;
    }
    #header-cart-dropdown.show { display: block; }
    .cart-dropdown-header { padding: 12px; color: #999; font-size: 14px; border-bottom: 1px solid #f5f5f5; }
    .cart-dropdown-body { max-height: 350px; overflow-y: auto; }
    .cart-dropdown-item { 
      display: flex; 
      padding: 10px; 
      text-decoration: none; 
      color: #333 !important; 
      transition: background 0.2s;
      border-bottom: 1px solid #fafafa;
    }
    .cart-dropdown-item:hover { background: #f8f8f8; }
    .cart-dropdown-item img { width: 45px; height: 45px; object-fit: cover; border: 1px solid #eee; margin-right: 10px; }
    .cart-dropdown-info { flex: 1; min-width: 0; }
    .cart-dropdown-name { font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; color: #333; }
    .cart-dropdown-price { color: var(--tet-red); font-weight: 600; font-size: 13px; }
    .cart-dropdown-footer { padding: 12px; background: #fdfdfd; text-align: right; border-top: 1px solid #f5f5f5; }
    .btn-view-cart { background: var(--tet-red); color: #fff !important; padding: 8px 15px; border-radius: 2px; font-size: 13px; text-decoration: none; display: inline-block; }
    .btn-view-cart:hover { background: #ee4d2d; }
    .nav-top { font-size: 13px; padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.15); }
    .logo-text { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; letter-spacing: 1px; text-shadow: 2px 2px 4px rgba(0,0,0,0.4); }
    .slogan { font-size: 0.85rem; color: var(--tet-gold); font-style: italic; margin-top: -5px; font-weight: 500; }
    .tet-decoration { position: absolute; pointer-events: none; opacity: 0.25; font-size: 2.2rem; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); }
    
    #search-suggestions {
      position: absolute; z-index: 1200; left: 0; right: 0; top: 100%; background: #fff; border: 1px solid #ddd; border-radius: 0 0 15px 15px; display: none; max-height: 450px; overflow: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.15); margin-top: 5px;
    }
    #search-suggestions.show { display: block; }
    .suggestion-item { text-decoration: none; color: #333; border-bottom: 1px solid #f0f0f0; transition: background 0.2s; }
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
      margin-top: 15px;
      padding: 0 10px;
      border: 1px solid rgba(255,255,255,0.1);
    }
    .main-menu-nav .nav-link {
      color: #fff !important;
      font-weight: 600;
      padding: 12px 18px !important;
      text-transform: uppercase;
      font-size: 13px;
      transition: all 0.3s;
      letter-spacing: 0.5px;
    }
    .main-menu-nav .nav-link:hover {
      color: var(--tet-gold) !important;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 4px;
    }
    .dropdown-menu {
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      border-radius: 8px;
      padding: 10px 0;
    }
    .dropdown-item {
      font-size: 14px;
      padding: 8px 20px;
      transition: all 0.2s;
      color: #333; /* Ensure black text */
    }
    .dropdown-item:hover {
      background-color: #f8f9fa;
      color: var(--tet-red);
      padding-left: 22px;
    }
    .dropdown-submenu {
      position: relative;
    }
    .dropdown-submenu .dropdown-menu {
      top: 0;
      left: 100%;
      margin-top: -1px;
    }
    .megamenu {
      position: static !important;
    }
    .megamenu .dropdown-menu {
      width: 100%;
      left: 0;
      right: 0;
      top: auto;
      padding: 20px;
    }
    .megamenu-title {
      font-weight: 800;
      color: var(--tet-red);
      margin-bottom: 12px;
      text-transform: uppercase;
      font-size: 13px;
      border-bottom: 2px solid #f0f0f0;
      padding-bottom: 8px;
      letter-spacing: 0.5px;
    }
  </style>
</head>
<body>

<header class="tet-header pb-3">
  <!-- Decorative elements -->
  <div class="tet-decoration" style="top: 10px; left: 5%;">🌸</div>
  <div class="tet-decoration" style="bottom: 10px; right: 5%;">🧧</div>

  <div class="container">
    <!-- Top Nav -->
    <div class="d-flex justify-content-between nav-top">
      <div class="d-flex gap-3">
        <a href="/weblaptop/admin/dashboard.php"><span class="sparkle-effect"></span> Quản trị</a>
        <a href="#"><span class="sparkle-effect"></span> Tải ứng dụng</a>
        <a href="#">Kết nối <span class="sparkle-effect"></span> <span class="sparkle-effect"></span></a>
      </div>
      <div class="d-flex gap-3 align-items-center">
        <a href="/weblaptop/notifications.php"><span class="sparkle-effect"></span> Thông Báo</a>
        <a href="/weblaptop/contact.php"><span class="sparkle-effect"></span> Hỗ Trợ</a>
        <?php if (!empty($_SESSION["user_id"])): ?>
          <div class="dropdown">
            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown"><?php echo htmlspecialchars($_SESSION["user_name"] ?? "Tài khoản"); ?></a>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <li><a class="dropdown-item text-danger fw-bold" href="/weblaptop/admin/dashboard.php">Quản trị hệ thống</a></li>
                <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="/weblaptop/account.php">Hồ sơ</a></li>
              <li><a class="dropdown-item" href="/weblaptop/orders.php">Đơn mua</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/weblaptop/auth/logout.php">Đăng xuất</a></li>
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
        </div>
        <span class="slogan">Chuẩn công nghệ – vững niềm tin</span>
      </a>
      
      <div class="search-bar-container" id="header-search">
        <form action="/weblaptop/search.php" method="get" class="d-flex w-100">
          <input type="text" name="q" id="header-search-input" class="search-input" placeholder="Tìm kiếm sản phẩm công nghệ đón Tết...">
          <button type="submit" class="search-btn"><i class="bi bi-search"></i></button>
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
                <div class="small text-muted mb-2"><?php echo count($_SESSION['cart']); ?> sản phẩm mới thêm</div>
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
          <ul class="navbar-nav w-100 justify-content-between">
            <li class="nav-item">
              <a class="nav-link" href="/weblaptop/index.php"><i class="bi bi-house-door me-1"></i> Trang chủ</a>
            </li>
            
            <li class="nav-item dropdown megamenu">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Sản phẩm</a>
              <div class="dropdown-menu">
                <div class="container">
                  <div class="row">
                    <div class="col-md-4">
                      <div class="megamenu-title">Theo hãng</div>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=Dell">Dell</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=HP">HP</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=Lenovo">Lenovo</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=ASUS">ASUS</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=Acer">Acer</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=Apple">Apple (MacBook)</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=MSI">MSI</a>
                    </div>
                    <div class="col-md-4">
                      <div class="megamenu-title">Theo nhu cầu</div>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=van+phong">Laptop văn phòng</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=sinh+vien">Laptop học sinh – sinh viên</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=do+hoa">Laptop đồ họa – kỹ thuật</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=gaming">Laptop gaming</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?q=cao+cap">Laptop mỏng nhẹ – cao cấp</a>
                    </div>
                    <div class="col-md-4">
                      <div class="megamenu-title">Theo mức giá</div>
                      <a class="dropdown-item" href="/weblaptop/search.php?price_max=10000000">Dưới 10 triệu</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?price_min=10000000&price_max=15000000">10 – 15 triệu</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?price_min=15000000&price_max=20000000">15 – 20 triệu</a>
                      <a class="dropdown-item" href="/weblaptop/search.php?price_min=20000000">Trên 20 triệu</a>
                    </div>
                  </div>
                </div>
              </div>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Phụ kiện</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/weblaptop/search.php?q=chuot">Chuột</a></li>
                <li><a class="dropdown-item" href="/weblaptop/search.php?q=ban+phim">Bàn phím</a></li>
                <li><a class="dropdown-item" href="/weblaptop/search.php?q=tai+nghe">Tai nghe</a></li>
                <li><a class="dropdown-item" href="/weblaptop/search.php?q=balo">Balo / túi laptop</a></li>
                <li><a class="dropdown-item" href="/weblaptop/search.php?q=o+cung">Ổ cứng, RAM</a></li>
              </ul>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Khuyến mãi</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Flash sale</a></li>
                <li><a class="dropdown-item" href="#">Giảm giá theo hãng</a></li>
                <li><a class="dropdown-item" href="#">Combo laptop + phụ kiện</a></li>
              </ul>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Tin tức – Blog</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Tư vấn chọn laptop</a></li>
                <li><a class="dropdown-item" href="#">So sánh cấu hình</a></li>
                <li><a class="dropdown-item" href="#">Đánh giá sản phẩm</a></li>
                <li><a class="dropdown-item" href="#">Tin công nghệ</a></li>
              </ul>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Hỗ trợ</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/weblaptop/shipping.php">Hướng dẫn mua hàng</a></li>
                <li><a class="dropdown-item" href="#">Chính sách bảo hành</a></li>
                <li><a class="dropdown-item" href="/weblaptop/returns.php">Chính sách đổi trả</a></li>
                <li><a class="dropdown-item" href="#">Câu hỏi thường gặp (FAQ)</a></li>
              </ul>
            </li>

            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Giới thiệu</a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Về cửa hàng</a></li>
                <li><a class="dropdown-item" href="#">Cam kết chất lượng</a></li>
                <li><a class="dropdown-item" href="#">Hệ thống cửa hàng</a></li>
              </ul>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="/weblaptop/contact.php">Liên hệ</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </div>
</header>

<?php if (function_exists("display_flash")) display_flash(); ?>

<div class="container mt-4">
