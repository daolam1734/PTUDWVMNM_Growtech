<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config/database.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$brand = isset($_GET['brand']) ? trim($_GET['brand']) : '';
$price_range = isset($_GET['price']) ? trim($_GET['price']) : '';
$cpu_filter = isset($_GET['cpu']) ? trim($_GET['cpu']) : '';
$ram_filter = isset($_GET['ram']) ? trim($_GET['ram']) : '';
$storage_filter = isset($_GET['storage']) ? trim($_GET['storage']) : '';
$screen_filter = isset($_GET['screen']) ? trim($_GET['screen']) : '';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$products = [];
$total_products = 0;

$where = ["p.is_active = 1"];
$params = [];

if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR b.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
}

if ($category_slug !== '') {
    $where[] = "c.slug = ?";
    $params[] = $category_slug;
}

if ($brand !== '') {
    $where[] = "b.name = ?";
    $params[] = $brand;
}

// Price Range Handler
if ($price_range !== '') {
    if ($price_range === '50+') {
        $where[] = "p.price >= 50000000";
    } else {
        $parts = explode('-', $price_range);
        if (count($parts) === 2) {
            $min = (float)$parts[0] * 1000000;
            $max = (float)$parts[1] * 1000000;
            $where[] = "p.price BETWEEN ? AND ?";
            $params[] = $min;
            $params[] = $max;
        }
    }
}

// Spec Filters (using LIKE for flexibility)
if ($cpu_filter !== '') {
    $where[] = "ps.cpu LIKE ?";
    $params[] = "%$cpu_filter%";
}
if ($ram_filter !== '') {
    $where[] = "ps.ram LIKE ?";
    $params[] = "%$ram_filter%";
}
if ($storage_filter !== '') {
    $where[] = "ps.storage LIKE ?";
    $params[] = "%$storage_filter%";
}
if ($screen_filter !== '') {
    $where[] = "ps.screen LIKE ?";
    $params[] = "%$screen_filter%";
}

$where_sql = implode(" AND ", $where);

// Count total
$stmt_count = $pdo->prepare("SELECT COUNT(DISTINCT p.id) 
    FROM products p 
    LEFT JOIN brands b ON b.id = p.brand_id
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_specifications ps ON ps.product_id = p.id
    WHERE $where_sql");
$stmt_count->execute($params);
$total_products = $stmt_count->fetchColumn();

// Fetch products
$sql = "SELECT p.*, b.name as brand_name, pi.url as image_url, ps.cpu, ps.ram, ps.storage, ps.screen
    FROM products p
    LEFT JOIN brands b ON b.id = p.brand_id
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN product_specifications ps ON ps.product_id = p.id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.position = 0
    WHERE $where_sql
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
foreach ($params as $i => $p) {
    $stmt->bindValue($i + 1, $p);
}
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$total_pages = ceil($total_products / $limit);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .search-header {
        background: #fff;
        padding: 20px;
        border-radius: 4px;
        margin-bottom: 20px;
        box-shadow: 0 1px 1px rgba(0,0,0,.05);
    }
    .filter-section {
        background: #fff;
        padding: 15px;
        border-radius: 4px;
        box-shadow: 0 1px 1px rgba(0,0,0,.05);
    }
    .filter-title {
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .product-grid-item {
        background: #fff;
        border: 1px solid transparent;
        transition: all 0.2s;
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    .product-grid-item:hover {
        border-color: var(--tet-red);
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        transform: translateY(-2px);
        z-index: 1;
    }
    .product-grid-img {
        width: 100%;
        aspect-ratio: 1/1;
        object-fit: cover;
    }
    .product-grid-info {
        padding: 8px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .product-grid-name {
        font-size: 12px;
        line-height: 14px;
        height: 28px;
        overflow: hidden;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        margin-bottom: 8px;
        color: rgba(0,0,0,.87);
    }
    .product-grid-price {
        color: var(--tet-red);
        font-size: 16px;
        font-weight: 500;
    }
    .product-grid-sold {
        font-size: 12px;
        color: rgba(0,0,0,.54);
    }
    .pagination .page-link {
        color: #555;
        border: none;
        margin: 0 5px;
        border-radius: 2px;
    }
    .pagination .page-item.active .page-link {
        background-color: var(--tet-red);
        color: #fff;
    }
    .pagination .page-link:hover {
        background-color: #f8f8f8;
        color: var(--tet-red);
    }
</style>

<div class="container my-4">
    <div class="row">
        <!-- Sidebar Filters -->
        <aside class="col-md-2 d-none d-md-block">
            <div class="filter-section mb-3">
                <div class="filter-title"><i class="bi bi-funnel"></i> BỘ LỌC TÌM KIẾM</div>
                
                <div class="mb-4">
                    <div class="small fw-bold mb-2">Theo Danh Mục</div>
                    <?php
                    $cats = $pdo->query("SELECT * FROM categories LIMIT 10")->fetchAll();
                    foreach ($cats as $c):
                    ?>
                        <div class="form-check small mb-1">
                            <a href="?category=<?php echo $c['slug']; ?>&q=<?php echo urlencode($q); ?>" class="text-decoration-none text-dark <?php echo $category_slug == $c['slug'] ? 'fw-bold text-danger' : ''; ?>">
                                <?php echo htmlspecialchars($c['name']); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mb-4">
                    <div class="small fw-bold mb-2">Khoảng Giá</div>
                    <form action="search.php" method="get">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_slug); ?>">
                        <div class="d-flex align-items-center gap-1 mb-2">
                            <input type="number" name="price_min" class="form-control form-control-sm" placeholder="₫ TỪ" value="<?php echo $price_min ?: ''; ?>">
                            <div style="width: 10px; height: 1px; background: #bdbdbd;"></div>
                            <input type="number" name="price_max" class="form-control form-control-sm" placeholder="₫ ĐẾN" value="<?php echo $price_max ?: ''; ?>">
                        </div>
                        <button type="submit" class="btn btn-danger btn-sm w-100" style="background-color: var(--tet-red);">ÁP DỤNG</button>
                    </form>
                </div>

                <button onclick="window.location.href='search.php'" class="btn btn-outline-secondary btn-sm w-100">XÓA TẤT CẢ</button>
            </div>
        </aside>

        <!-- Search Results -->
        <main class="col-md-10">
            <div class="search-header d-flex align-items-center justify-content-between">
                <div>
                    <?php if ($q !== ''): ?>
                        <span class="text-muted">Kết quả tìm kiếm cho '</span><span class="text-danger fw-bold"><?php echo htmlspecialchars($q); ?></span><span class="text-muted">'</span>
                    <?php else: ?>
                        <span class="fw-bold">Tất cả sản phẩm</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex align-items-center gap-3 small">
                    <span>Sắp xếp theo:</span>
                    <button class="btn btn-danger btn-sm" style="background-color: var(--tet-red);">Phổ biến</button>
                    <button class="btn btn-light btn-sm bg-white border">Mới nhất</button>
                    <button class="btn btn-light btn-sm bg-white border">Bán chạy</button>
                    <select class="form-select form-select-sm" style="width: 150px;">
                        <option>Giá</option>
                        <option>Giá: Thấp đến Cao</option>
                        <option>Giá: Cao đến Thấp</option>
                    </select>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="text-center py-5 bg-white rounded shadow-sm">
                    <img src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/assets/a60759ad1dabe909c46a817ecbf71878.png" alt="No results" style="width: 120px;" class="mb-3">
                    <p class="text-muted">Không tìm thấy sản phẩm nào phù hợp.</p>
                    <a href="search.php" class="btn btn-danger" style="background-color: var(--tet-red);">Xem tất cả sản phẩm</a>
                </div>
            <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($products as $p): 
                        $img = $p['image_url'];
                        if (!$img || (strpos($img, 'http') !== 0 && strpos($img, '/') !== 0)) {
                            $img = 'https://placehold.co/600x400?text=No+Image';
                        }
                    ?>
                        <div class="col-6 col-md-4 col-lg-3 col-xl-2-4" style="width: 20%;">
                            <a href="product.php?id=<?php echo $p['id']; ?>" class="text-decoration-none">
                                <div class="product-grid-item shadow-sm">
                                    <img src="<?php echo htmlspecialchars($img); ?>" class="product-grid-img" alt="">
                                    <div class="product-grid-info">
                                        <div class="product-grid-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                        <div class="mt-auto">
                                            <div class="product-grid-price"><?php echo number_format($p['price'], 0, ',', '.'); ?> đ</div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <div class="product-grid-sold">Đã bán <?php echo rand(10, 100); ?>+</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php 
                            $query_params = $_GET;
                            unset($query_params['page']);
                            $base_url = '?' . http_build_query($query_params) . '&page=';
                            ?>
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $base_url . ($page - 1); ?>"><i class="bi bi-chevron-left"></i></a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $base_url . ($page + 1); ?>"><i class="bi bi-chevron-right"></i></a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

