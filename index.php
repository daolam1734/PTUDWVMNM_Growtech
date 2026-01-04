<?php
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/functions.php";

$q = $_GET["q"] ?? "";
$category_slug = $_GET["category"] ?? "";
$brand = $_GET["brand"] ?? "";

// Fetch all categories for the homepage sections
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// If searching or filtering, use the old grid view
$is_filtered = ($q || $category_slug || $brand);

if ($is_filtered) {
    $sql = "SELECT p.*, pi.url as image_url 
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0
            WHERE p.is_active = 1";
    $params = [];

    if ($q) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    if ($category_slug) {
        $sql .= " AND p.category_id IN (SELECT id FROM categories WHERE slug = ?)";
        $params[] = $category_slug;
    }
    if ($brand) {
        $sql .= " AND p.brand_id IN (SELECT id FROM brands WHERE name = ?)";
        $params[] = $brand;
    }

    $sql .= " ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
}
?>

<style>
    .product-grid-item { transition: transform 0.2s, box-shadow 0.2s; border: 1px solid transparent; background: #fff; height: 100%; display: flex; flex-direction: column; border-radius: 8px; overflow: hidden; position: relative; }
    .product-grid-item:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(211, 47, 47, 0.15); border-color: var(--tet-red, #d32f2f); }
    .product-grid-img { aspect-ratio: 1/1; object-fit: cover; width: 100%; transition: transform 0.3s; }
    .product-grid-item:hover .product-grid-img { transform: scale(1.05); }
    .product-grid-info { padding: 12px; flex-grow: 1; display: flex; flex-direction: column; }
    .product-grid-name { font-size: 13px; line-height: 18px; height: 36px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; color: #333; margin-bottom: 10px; text-decoration: none; font-weight: 500; }
    .product-grid-price { color: var(--tet-red, #d32f2f); font-size: 16px; font-weight: 700; }
    .product-grid-sold { font-size: 11px; color: #757575; }
    
    .product-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: linear-gradient(45deg, var(--tet-red), #ff4d4d);
        color: #fff;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 4px;
        font-weight: 600;
        z-index: 2;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-transform: uppercase;
    }

    /* Horizontal Scroll Styling */
    .scroll-wrapper { 
        position: relative; 
        padding: 0 10px; /* Add padding so buttons don't overlap content too much */
    }
    .scroll-container {
        display: flex;
        flex-wrap: nowrap; /* Ensure items stay in one row */
        overflow-x: auto;
        gap: 15px;
        padding: 10px 5px 20px 5px;
        scrollbar-width: none; /* Hide scrollbar for cleaner look */
        -ms-overflow-style: none;
        /* Remove scroll-behavior: smooth from here to allow auto-scroll to work properly */
    }
    .scroll-container::-webkit-scrollbar { display: none; }
    .scroll-item { flex: 0 0 200px; }

    .scroll-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #fff;
        border: 1px solid #eee;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--tet-red);
        opacity: 0.9;
    }
    .scroll-btn:hover { 
        background: var(--tet-red); 
        color: #fff; 
        transform: translateY(-50%) scale(1.1);
        opacity: 1;
        box-shadow: 0 4px 15px rgba(198, 40, 40, 0.4);
    }
    .scroll-btn.scroll-btn-left { left: -10px; }
    .scroll-btn.scroll-btn-right { right: -10px; }
    .scroll-btn-left { left: 0; }
    .scroll-btn-right { right: 0; }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 2px solid var(--tet-red);
        padding-bottom: 8px;
    }
    .section-title {
        font-weight: 700;
        color: var(--tet-red);
        text-transform: uppercase;
        margin-bottom: 0;
    }
    .view-more {
        font-size: 14px;
        color: var(--tet-red);
        text-decoration: none;
        font-weight: 500;
    }
    .view-more:hover { text-decoration: underline; color: var(--tet-dark-red); }

    .home-carousel .carousel-item { height: 400px; }
    .home-carousel img { object-fit: cover; height: 100%; width: 100%; }
    .carousel-caption {
        background: rgba(0,0,0,0.5);
        border-radius: 10px;
        padding: 20px;
        bottom: 10%;
    }

    /* Flash Sale Section */
    .flash-sale-container {
        background: linear-gradient(90deg, #ff4d4d, #d32f2f);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 30px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .flash-sale-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    .flash-sale-title {
        font-size: 24px;
        font-weight: 800;
        text-transform: uppercase;
        font-style: italic;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .flash-sale-timer {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .timer-box {
        background: #333;
        color: #fff;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 700;
        min-width: 35px;
        text-align: center;
    }
    .flash-sale-item {
        background: #fff;
        border-radius: 8px;
        padding: 10px;
        color: #333;
        text-align: center;
        transition: transform 0.2s;
    }
    .flash-sale-item:hover { transform: scale(1.03); }
    .flash-sale-price { color: var(--tet-red); font-weight: 700; font-size: 18px; }
    .flash-sale-old-price { text-decoration: line-through; color: #999; font-size: 13px; }
    .flash-sale-progress {
        height: 16px;
        background: #eee;
        border-radius: 10px;
        margin-top: 10px;
        position: relative;
        overflow: hidden;
    }
    .flash-sale-progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #ffc107, #ff9800);
        border-radius: 10px;
    }
    .flash-sale-progress-text {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        font-size: 10px;
        font-weight: 700;
        color: #333;
        line-height: 16px;
    }
    
    /* Load More Button */
    .btn-load-more {
        background: #fff;
        border: 1px solid rgba(0,0,0,.09);
        color: rgba(0,0,0,.87);
        padding: 10px 50px;
        font-size: 14px;
        transition: background .2s;
        border-radius: 2px;
        box-shadow: 0 1px 1px 0 rgba(0,0,0,.03);
    }
    .btn-load-more:hover {
        background: #f8f8f8;
        color: var(--tet-red);
        border-color: var(--tet-red);
    }
    
    /* Suggestion Grid */
    .suggestion-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 10px;
    }
    @media (max-width: 1200px) {
        .suggestion-grid { grid-template-columns: repeat(4, 1fr); }
    }
    @media (max-width: 992px) {
        .suggestion-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 576px) {
        .suggestion-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<div class="row">
    <aside class="col-md-2 d-none d-md-block">
        <?php include __DIR__ . "/includes/sidebar.php"; ?>
    </aside>
    <main class="col-md-10">
        
        <?php if (!$is_filtered): ?>
            <!-- Banner Carousel -->
            <div id="homeCarousel" class="carousel slide home-carousel mb-4 shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="0" class="active"></button>
                    <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="1"></button>
                    <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="2"></button>
                </div>
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=1200&q=80" class="d-block w-100" alt="Shop Intro">
                        <div class="carousel-caption text-start">
                            <h2 class="fw-bold text-warning">Chào mừng đến với GrowTech</h2>
                            <p>Hệ thống bán lẻ Laptop uy tín hàng đầu Việt Nam. Cam kết chất lượng, bảo hành tận tâm.</p>
                            <a href="/weblaptop/contact.php" class="btn btn-warning fw-bold">Tìm hiểu thêm</a>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="https://images.unsplash.com/photo-1593642632823-8f785ba67e45?auto=format&fit=crop&w=1200&q=80" class="d-block w-100" alt="Tet Event">
                        <div class="carousel-caption">
                            <h2 class="fw-bold text-danger">🧧 KHAI XUÂN NHƯ Ý 🧧</h2>
                            <p>Lì xì ngay 1.000.000đ cho đơn hàng Laptop Gaming từ 20 triệu.</p>
                            <a href="/weblaptop/search.php?q=gaming" class="btn btn-danger">Săn Deal Ngay</a>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?auto=format&fit=crop&w=1200&q=80" class="d-block w-100" alt="MacBook Pro">
                        <div class="carousel-caption text-end">
                            <h2 class="fw-bold text-info">MacBook Pro M3 Series</h2>
                            <p>Sức mạnh vượt trội cho mọi tác vụ đồ họa chuyên nghiệp. Trả góp 0%.</p>
                            <a href="/weblaptop/search.php?q=Apple" class="btn btn-info text-white">Xem chi tiết</a>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>

            <!-- Flash Sale Section -->
            <div class="flash-sale-container shadow-sm">
                <div class="flash-sale-header">
                    <h3 class="flash-sale-title">
                        <i class="bi bi-lightning-fill text-warning"></i> FLASH SALE
                    </h3>
                    <div class="flash-sale-timer" id="flashSaleTimer">
                        <span class="timer-box" id="timer-h">02</span> :
                        <span class="timer-box" id="timer-m">45</span> :
                        <span class="timer-box" id="timer-s">12</span>
                    </div>
                    <div class="ms-auto d-none d-md-block">
                        <a href="/weblaptop/search.php" class="text-white text-decoration-none small">Xem tất cả <i class="bi bi-chevron-right"></i></a>
                    </div>
                </div>
                <div class="scroll-wrapper">
                    <div class="scroll-btn scroll-btn-left"><i class="bi bi-chevron-left"></i></div>
                    <div class="scroll-btn scroll-btn-right"><i class="bi bi-chevron-right"></i></div>
                    <div class="scroll-container">
                        <?php
                        // Fetch some products for flash sale (mocking with random discount)
                        $stmt_flash = $pdo->query("SELECT p.*, pi.url as image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 WHERE p.is_active = 1 LIMIT 6");
                        while ($p = $stmt_flash->fetch()):
                            $img = $p["image_url"];
                            if (!$img || (strpos($img, 'http') !== 0 && strpos($img, '/') !== 0)) {
                                $img = 'https://placehold.co/600x400?text=No+Image';
                            }
                            $discount = rand(10, 30);
                            $old_price = $p["price"] * (1 + $discount/100);
                            $sold_percent = rand(40, 90);
                        ?>
                            <div class="scroll-item">
                                <a href="product.php?id=<?php echo $p["id"]; ?>" class="text-decoration-none">
                                    <div class="flash-sale-item">
                                        <div class="position-relative">
                                            <img src="<?php echo htmlspecialchars($img); ?>" class="img-fluid rounded mb-2" style="aspect-ratio:1/1; object-fit:cover;" alt="">
                                            <div class="product-badge" style="top:0; left:0;">-<?php echo $discount; ?>%</div>
                                        </div>
                                        <div class="product-grid-name text-start mb-1"><?php echo htmlspecialchars($p["name"]); ?></div>
                                        <div class="flash-sale-price text-start"><?php echo number_format($p["price"], 0, ",", "."); ?> đ</div>
                                        <div class="flash-sale-old-price text-start"><?php echo number_format($old_price, 0, ",", "."); ?> đ</div>
                                        <div class="flash-sale-progress">
                                            <div class="flash-sale-progress-bar" style="width: <?php echo $sold_percent; ?>%;"></div>
                                            <div class="flash-sale-progress-text">ĐÃ BÁN <?php echo $sold_percent; ?>%</div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Featured Products Section -->
            <div class="mb-5">
                <div class="section-header">
                    <h4 class="section-title"><span class="sparkle-effect"></span> Sản phẩm nổi bật</h4>
                    <a href="/weblaptop/search.php" class="view-more">Xem tất cả <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="scroll-wrapper">
                    <div class="scroll-btn scroll-btn-left"><i class="bi bi-chevron-left"></i></div>
                    <div class="scroll-btn scroll-btn-right"><i class="bi bi-chevron-right"></i></div>
                    <div class="scroll-container">
                        <?php
                        $stmt_feat = $pdo->query("SELECT p.*, pi.url as image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 10");
                        while ($p = $stmt_feat->fetch()):
                            $img = $p["image_url"];
                            if (!$img || (strpos($img, 'http') !== 0 && strpos($img, '/') !== 0)) {
                                $img = 'https://placehold.co/600x400?text=No+Image';
                            }
                        ?>
                            <div class="scroll-item">
                                <a href="product.php?id=<?php echo $p["id"]; ?>" class="text-decoration-none">
                                    <div class="product-grid-item shadow-sm">
                                        <div class="product-badge">Mới</div>
                                        <img src="<?php echo htmlspecialchars($img); ?>" class="product-grid-img" alt="">
                                        <div class="product-grid-info">
                                            <div class="product-grid-name"><?php echo htmlspecialchars($p["name"]); ?></div>
                                            <div class="mt-auto">
                                                <div class="product-grid-price"><?php echo number_format($p["price"], 0, ",", "."); ?> đ</div>
                                                <div class="product-grid-sold mt-1">Đã bán 50+</div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Category Sections -->
            <?php foreach ($categories as $cat): ?>
                <div class="mb-5">
                    <div class="section-header">
                        <h4 class="section-title"><span class="sparkle-effect"></span> <?php echo htmlspecialchars($cat['name']); ?></h4>
                        <a href="/weblaptop/search.php?category=<?php echo $cat['slug']; ?>" class="view-more">Xem thêm <i class="bi bi-chevron-right"></i></a>
                    </div>
                    <div class="scroll-wrapper">
                        <div class="scroll-btn scroll-btn-left"><i class="bi bi-chevron-left"></i></div>
                        <div class="scroll-btn scroll-btn-right"><i class="bi bi-chevron-right"></i></div>
                        <div class="scroll-container">
                            <?php
                            $stmt_cat = $pdo->prepare("SELECT p.*, pi.url as image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 WHERE p.category_id = ? AND p.is_active = 1 LIMIT 8");
                            $stmt_cat->execute([$cat['id']]);
                            $cat_products = $stmt_cat->fetchAll();
                            
                            if (empty($cat_products)): ?>
                                <div class="text-muted small p-3">Đang cập nhật sản phẩm...</div>
                            <?php else:
                                foreach ($cat_products as $p):
                                    $img = $p["image_url"];
                                    if (!$img || (strpos($img, 'http') !== 0 && strpos($img, '/') !== 0)) {
                                        $img = 'https://placehold.co/600x400?text=No+Image';
                                    }
                            ?>
                                <div class="scroll-item">
                                    <a href="product.php?id=<?php echo $p["id"]; ?>" class="text-decoration-none">
                                        <div class="product-grid-item shadow-sm">
                                            <div class="product-badge">Hot</div>
                                            <img src="<?php echo htmlspecialchars($img); ?>" class="product-grid-img" alt="">
                                            <div class="product-grid-info">
                                                <div class="product-grid-name"><?php echo htmlspecialchars($p["name"]); ?></div>
                                                <div class="mt-auto">
                                                    <div class="product-grid-price"><?php echo number_format($p["price"], 0, ",", "."); ?> đ</div>
                                                    <div class="product-grid-sold mt-1">Đã bán 20+</div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Today's Suggestions (Gợi ý hôm nay) -->
            <div class="mt-5 mb-4">
                <div class="section-header" style="border-bottom: 4px solid var(--tet-red);">
                    <h4 class="section-title py-2 px-4 text-white" style="background: var(--tet-red); margin-bottom: -4px;">GỢI Ý HÔM NAY</h4>
                </div>
                <div class="suggestion-grid mt-3" id="suggestion-container">
                    <?php
                    // Initial load of 10 products
                    $stmt_sug = $pdo->query("SELECT p.*, pi.url as image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 WHERE p.is_active = 1 ORDER BY p.created_at DESC LIMIT 10");
                    while ($p = $stmt_sug->fetch()):
                        $img = $p["image_url"];
                        if (!$img || (strpos($img, 'http') !== 0 && strpos($img, '/') !== 0)) {
                            $img = 'https://placehold.co/600x400?text=No+Image';
                        }
                    ?>
                        <div class="suggestion-item">
                            <a href="product.php?id=<?php echo $p["id"]; ?>" class="text-decoration-none">
                                <div class="product-grid-item shadow-sm h-100">
                                    <img src="<?php echo htmlspecialchars($img); ?>" class="product-grid-img" alt="">
                                    <div class="product-grid-info">
                                        <div class="product-grid-name"><?php echo htmlspecialchars($p["name"]); ?></div>
                                        <div class="mt-auto">
                                            <div class="product-grid-price"><?php echo number_format($p["price"], 0, ",", "."); ?> đ</div>
                                            <div class="product-grid-sold mt-1">Đã bán <?php echo rand(100, 999); ?>+</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="text-center mt-4">
                    <button id="btn-load-more" class="btn btn-load-more" data-page="1">Xem thêm</button>
                </div>
            </div>

        <?php else: ?>
            <!-- Filtered Results Grid -->
            <div class="mb-3 text-muted">Kết quả tìm kiếm cho: <strong><?php echo htmlspecialchars($q ?: ($category_slug ?: $brand)); ?></strong></div>
            <div class="row g-2">
                <?php foreach ($products as $p): 
                    $img = $p["image_url"];
                    if (!$img || (strpos($img, 'http') !== 0 && strpos($img, '/') !== 0)) {
                        $img = 'https://placehold.co/600x400?text=No+Image';
                    }
                ?>
                    <div class="col-6 col-md-4 col-lg-2-4 mb-2" style="width: 20%;">
                        <a href="product.php?id=<?php echo $p["id"]; ?>" class="text-decoration-none">
                            <div class="product-grid-item">
                                <img src="<?php echo htmlspecialchars($img); ?>" class="product-grid-img" alt="">
                                <div class="product-grid-info">
                                    <div class="product-grid-name"><?php echo htmlspecialchars($p["name"]); ?></div>
                                    <div class="mt-auto">
                                        <div class="product-grid-price"><?php echo number_format($p["price"], 0, ",", "."); ?> đ</div>
                                        <div class="product-grid-sold mt-1">Đã bán 100+</div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <div class="col-12 text-center py-5">
                        <img src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/assets/a60759ad1dabe909c46a817ecbf71878.png" style="width: 100px;" class="mb-3">
                        <p class="text-muted">Không tìm thấy sản phẩm nào phù hợp.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
