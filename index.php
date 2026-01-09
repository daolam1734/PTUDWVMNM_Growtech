<?php
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/functions.php";

$q = $_GET["q"] ?? "";
$category_slug = $_GET["category"] ?? "";
$selected_brands = isset($_GET['brands']) ? (array)$_GET['brands'] : [];
$min_price = $_GET['min_price'] ?? "";
$max_price = $_GET['max_price'] ?? "";

// Fetch all categories for the homepage sections
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// If searching or filtering, use the old grid view
$is_filtered = ($q || $category_slug || !empty($selected_brands) || $min_price !== "" || $max_price !== "");

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
        $sql .= " AND EXISTS (SELECT 1 FROM product_categories pc JOIN categories c_filter ON pc.category_id = c_filter.id WHERE pc.product_id = p.id AND c_filter.slug = ?)";
        $params[] = $category_slug;
    }
    if (!empty($selected_brands)) {
        $placeholders = implode(',', array_fill(0, count($selected_brands), '?'));
        $sql .= " AND p.brand_id IN (SELECT id FROM brands WHERE name IN ($placeholders))";
        foreach ($selected_brands as $b) {
            $params[] = $b;
        }
    }
    if ($min_price !== "") {
        $sql .= " AND p.price >= ?";
        $params[] = (float)$min_price;
    }
    if ($max_price !== "") {
        $sql .= " AND p.price <= ?";
        $params[] = (float)$max_price;
    }

    $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
}
?>

<style>
    .product-grid-item { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid #f0f0f0; background: #fff; height: 100%; display: flex; flex-direction: column; border-radius: 12px; overflow: hidden; position: relative; }
    .product-grid-item:hover { transform: translateY(-8px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); border-color: #d32f2f; }
    .product-grid-img { aspect-ratio: 1/1; object-fit: contain; width: 100%; padding: 10px; background: #fff; transition: transform 0.5s; }
    .product-grid-item:hover .product-grid-img { transform: scale(1.08); }
    .product-grid-info { padding: 15px; flex-grow: 1; display: flex; flex-direction: column; background: #fff; }
    .product-grid-name { font-size: 14px; line-height: 1.4; height: 40px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; color: #2c3e50; margin-bottom: 8px; text-decoration: none; font-weight: 600; }
    .product-grid-price { color: #d32f2f; font-size: 18px; font-weight: 800; }
    .product-grid-sold { font-size: 12px; color: #adb5bd; }
    
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
        flex-wrap: nowrap;
        overflow-x: auto;
        gap: 15px;
        padding: 10px 5px 20px 5px;
        scrollbar-width: none;
        -ms-overflow-style: none;
        scroll-behavior: smooth;
    }
    .scroll-container::-webkit-scrollbar { display: none; }
    .scroll-item { 
        flex: 0 0 calc(25% - 12px); /* Show exactly 4 items (15px gap * 3 / 4 = 11.25px) */
        min-width: 200px;
        user-select: none;
    }

    @media (max-width: 1200px) {
        .scroll-item { flex: 0 0 calc(33.333% - 10px); } /* 3 items */
    }
    @media (max-width: 768px) {
        .scroll-item { flex: 0 0 calc(50% - 8px); } /* 2 items */
    }
    @media (max-width: 480px) {
        .scroll-item { flex: 0 0 calc(100% - 0px); } /* 1 item */
    }

    .scroll-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid #eee;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100;
        cursor: pointer;
        transition: all 0.2s;
        color: var(--tet-red);
        opacity: 0; /* Hidden by default, show on hover */
        user-select: none;
    }
    .scroll-wrapper:hover .scroll-btn {
        opacity: 1;
    }
    .scroll-btn:hover { 
        background: var(--tet-red); 
        color: #fff; 
        transform: translateY(-50%) scale(1.1);
        opacity: 1;
        box-shadow: 0 4px 15px rgba(198, 40, 40, 0.4);
    }
    .scroll-btn-left { left: -15px; }
    .scroll-btn-right { right: -15px; }

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
        border-radius: 12px;
        padding: 15px;
        color: #333;
        text-align: center;
        transition: all 0.3s;
        border: 1px solid #f0f0f0;
        height: 100%;
    }
    .flash-sale-item:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.06); border-color: #d32f2f; }
    .flash-sale-price { color: #d32f2f; font-weight: 800; font-size: 18px; }
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
            <?php
            $banners = $pdo->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY position ASC")->fetchAll();
            ?>
            <div id="homeCarousel" class="carousel slide home-carousel mb-4 shadow-sm rounded overflow-hidden" data-bs-ride="carousel">
                <div class="carousel-indicators">
                    <?php foreach ($banners as $index => $b): ?>
                        <button type="button" data-bs-target="#homeCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                    <?php endforeach; ?>
                </div>
                <div class="carousel-inner">
                    <?php foreach ($banners as $index => $b): ?>
                        <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo htmlspecialchars($b['image_url']); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($b['title']); ?>">
                            <div class="carousel-caption <?php echo $index % 3 == 0 ? 'text-start' : ($index % 3 == 1 ? '' : 'text-end'); ?>">
                                <h2 class="fw-bold <?php echo $index % 2 == 0 ? 'text-warning' : 'text-danger'; ?>"><?php echo htmlspecialchars($b['title']); ?></h2>
                                <p><?php echo htmlspecialchars($b['description']); ?></p>
                                <?php if ($b['link_url']): ?>
                                    <a href="<?php echo htmlspecialchars($b['link_url']); ?>" class="btn <?php echo $index % 2 == 0 ? 'btn-warning' : 'btn-danger'; ?> fw-bold">Xem chi tiết</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($banners)): ?>
                        <div class="carousel-item active">
                            <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=1200&q=80" class="d-block w-100" alt="Default">
                            <div class="carousel-caption text-start">
                                <h2 class="fw-bold text-warning">Chào mừng đến với GrowTech</h2>
                                <p>Hệ thống bán lẻ Laptop uy tín hàng đầu Việt Nam.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>

            <!-- Flash Sale Section -->
            <?php
            $flash_sale_products = getFlashSaleProducts(20);
            // Fetch flash sale end time from settings
            $flash_sale_end = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'flash_sale_end'")->fetchColumn() ?: date('Y-m-d 23:59:59');
            ?>
            <div class="flash-sale-container shadow-sm" data-end-time="<?php echo $flash_sale_end; ?>">
                <div class="flash-sale-header">
                    <h3 class="flash-sale-title"><i class="bi bi-lightning-fill"></i> FLASH SALE</h3>
                    <div class="flash-sale-timer">
                        <span class="small me-1">Kết thúc sau:</span>
                        <div class="timer-box" id="timer-h">00</div>
                        <span>:</span>
                        <div class="timer-box" id="timer-m">00</div>
                        <span>:</span>
                        <div class="timer-box" id="timer-s">00</div>
                    </div>
                    <a href="promotions.php" class="ms-auto text-white text-decoration-none small">Xem tất cả <i class="bi bi-chevron-right"></i></a>
                </div>
                
                <div class="scroll-wrapper">
                    <div class="scroll-btn scroll-btn-left"><i class="bi bi-chevron-left"></i></div>
                    <div class="scroll-btn scroll-btn-right"><i class="bi bi-chevron-right"></i></div>
                    <div class="scroll-container">
                        <?php foreach ($flash_sale_products as $p): 
                            $img = getProductImage($p['id']);
                            $discount = round((1 - $p['sale_price'] / $p['price']) * 100);
                            $sold_count = rand(10, 50); 
                            $percent_sold = min(100, round(($sold_count / 60) * 100));
                        ?>
                        <div class="scroll-item">
                            <a href="product.php?id=<?php echo $p['id']; ?>" class="text-decoration-none">
                                <div class="flash-sale-item">
                                    <div class="position-relative mb-2">
                                        <img src="<?php echo htmlspecialchars($img); ?>" class="img-fluid rounded" style="aspect-ratio:1/1; object-fit:cover;" alt="<?php echo htmlspecialchars($p['name']); ?>">
                                        <div class="product-badge">-<?php echo $discount; ?>%</div>
                                    </div>
                                    <div class="text-truncate small mb-1 fw-bold text-start"><?php echo htmlspecialchars($p['name']); ?></div>
                                    <div class="flash-sale-price text-start"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</div>
                                    <div class="flash-sale-old-price text-start"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</div>
                                    <div class="flash-sale-progress">
                                        <div class="flash-sale-progress-bar" style="width: <?php echo $percent_sold; ?>%"></div>
                                        <div class="flash-sale-progress-text">ĐÃ BÁN <?php echo $sold_count; ?></div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Vouchers Section -->
            <?php
            $home_vouchers = $pdo->query("SELECT * FROM vouchers WHERE is_active = 1 AND (start_date <= NOW() OR start_date IS NULL) AND (end_date >= NOW() OR end_date IS NULL) AND (usage_limit IS NULL OR usage_count < usage_limit) ORDER BY created_at DESC LIMIT 4")->fetchAll();
            if (!empty($home_vouchers)):
            ?>
            <div class="mb-5">
                <div class="section-header">
                    <h4 class="section-title"><i class="bi bi-ticket-perforated-fill me-2"></i> Mã Giảm Giá GrowTech</h4>
                    <a href="/weblaptop/cart.php" class="view-more">Xem thêm <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="row g-3">
                    <?php foreach ($home_vouchers as $v): ?>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fff 70%, #fff5f5 100%); border-left: 5px solid var(--tet-red) !important;">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-danger"><?php echo $v['code']; ?></span>
                                    <span class="small text-muted">HSD: <?php echo date('d/m', strtotime($v['end_date'])); ?></span>
                                </div>
                                <h6 class="fw-bold mb-1">Giảm <?php echo $v['discount_type'] == 'percentage' ? $v['discount_value'].'%' : number_format($v['discount_value'], 0, ',', '.').'đ'; ?></h6>
                                <p class="small text-muted mb-2">Đơn tối thiểu <?php echo number_format($v['min_spend'], 0, ',', '.'); ?>đ</p>
                                <button class="btn btn-sm btn-outline-danger w-100 py-1" onclick="copyVoucher('<?php echo $v['code']; ?>')">Sao chép mã</button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <script>
            function copyVoucher(code) {
                navigator.clipboard.writeText(code).then(() => {
                    alert('Đã sao chép mã: ' + code);
                });
            }
            </script>
            <?php endif; ?>

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
                        $stmt_feat = $pdo->query("SELECT p.id, p.name, p.price FROM products p WHERE p.is_active = 1 GROUP BY p.id ORDER BY p.created_at DESC LIMIT 20");
                        while ($p = $stmt_feat->fetch()):
                            $img = getProductImage($p['id']);
                        ?>
                            <div class="scroll-item">
                                <a href="product.php?id=<?php echo $p["id"]; ?>" class="text-decoration-none">
                                    <div class="flash-sale-item">
                                        <div class="position-relative">
                                            <img src="<?php echo htmlspecialchars($img); ?>" class="img-fluid rounded mb-2" style="aspect-ratio:1/1; object-fit:cover;" alt="">
                                            <div class="product-badge" style="top:0; left:0;">Mới</div>
                                        </div>
                                        <div class="product-grid-name text-start mb-1"><?php echo htmlspecialchars($p["name"]); ?></div>
                                        <div class="flash-sale-price text-start"><?php echo number_format($p["price"], 0, ",", "."); ?> đ</div>
                                        <div class="product-grid-sold text-start mt-1">Đã bán <?php echo rand(50, 200); ?>+</div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Category Sliders -->
            <?php
            // Get top 3 categories that have products
            $stmt_cat_sliders = $pdo->query("SELECT c.* FROM categories c WHERE EXISTS (SELECT 1 FROM product_categories pc JOIN products p2 ON pc.product_id = p2.id WHERE pc.category_id = c.id AND p2.is_active = 1) LIMIT 3");
            while ($cat = $stmt_cat_sliders->fetch()):
            ?>
                <div class="mb-5">
                    <div class="section-header">
                        <h4 class="section-title"><?php echo htmlspecialchars($cat["name"]); ?></h4>
                        <a href="/weblaptop/search.php?category=<?php echo $cat["slug"]; ?>" class="view-more">Xem tất cả <i class="bi bi-chevron-right"></i></a>
                    </div>
                    <div class="scroll-wrapper">
                        <div class="scroll-btn scroll-btn-left"><i class="bi bi-chevron-left"></i></div>
                        <div class="scroll-btn scroll-btn-right"><i class="bi bi-chevron-right"></i></div>
                        <div class="scroll-container">
                            <?php
                            $stmt_cat_p = $pdo->prepare("SELECT p.id, p.name, p.price FROM products p JOIN product_categories pc ON p.id = pc.product_id WHERE pc.category_id = ? AND p.is_active = 1 GROUP BY p.id LIMIT 15");
                            $stmt_cat_p->execute([$cat["id"]]);
                            while ($p = $stmt_cat_p->fetch()):
                                $img = getProductImage($p['id']);
                            ?>
                                <div class="scroll-item">
                                    <a href="product.php?id=<?php echo $p["id"]; ?>" class="text-decoration-none">
                                        <div class="flash-sale-item">
                                            <div class="position-relative">
                                                <img src="<?php echo htmlspecialchars($img); ?>" class="img-fluid rounded mb-2" style="aspect-ratio:1/1; object-fit:cover;" alt="">
                                            </div>
                                            <div class="product-grid-name text-start mb-1"><?php echo htmlspecialchars($p["name"]); ?></div>
                                            <div class="flash-sale-price text-start"><?php echo number_format($p["price"], 0, ",", "."); ?> đ</div>
                                            <div class="product-grid-sold text-start mt-1">Đã bán <?php echo rand(10, 100); ?>+</div>
                                        </div>
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Today's Suggestions (Gợi ý hôm nay) -->
            <div class="mt-5 mb-4">
                <div class="section-header">
                    <h4 class="section-title"><span class="sparkle-effect"></span> Gợi ý hôm nay</h4>
                    <a href="/weblaptop/search.php" class="view-more">Xem tất cả <i class="bi bi-chevron-right"></i></a>
                </div>
                <div class="scroll-wrapper">
                    <div class="scroll-btn scroll-btn-left"><i class="bi bi-chevron-left"></i></div>
                    <div class="scroll-btn scroll-btn-right"><i class="bi bi-chevron-right"></i></div>
                    <div class="scroll-container" id="suggestion-container">
                        <?php
                        // Initial load of 30 products for the scroll
                        $stmt_sug = $pdo->query("SELECT p.*, pi.url as image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 WHERE p.is_active = 1 GROUP BY p.id ORDER BY p.created_at DESC LIMIT 30");
                        while ($p = $stmt_sug->fetch()):
                            $img = getProductImage($p['id']);
                        ?>
                            <div class="scroll-item">
                                <a href="product.php?id=<?php echo $p["id"]; ?>" class="text-decoration-none">
                                    <div class="flash-sale-item">
                                        <div class="position-relative">
                                            <img src="<?php echo htmlspecialchars($img); ?>" class="img-fluid rounded mb-2" style="aspect-ratio:1/1; object-fit:cover;" alt="">
                                        </div>
                                        <div class="product-grid-name text-start mb-1"><?php echo htmlspecialchars($p["name"]); ?></div>
                                        <div class="flash-sale-price text-start"><?php echo number_format($p["price"], 0, ",", "."); ?> đ</div>
                                        <div class="product-grid-sold text-start mt-1">Đã bán <?php echo rand(100, 999); ?>+</div>
                                    </div>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Filtered Results Grid -->
            <div class="mb-3 text-muted">Kết quả tìm kiếm cho: <strong><?php echo htmlspecialchars($q ?: ($category_slug ?: $brand)); ?></strong></div>
            <div class="row g-2">
                <?php foreach ($products as $p): 
                    $img = getProductImage($p['id']);
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
