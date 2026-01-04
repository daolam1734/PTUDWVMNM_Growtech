<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config/database.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$products = [];
$total_products = 0;

$where = ["p.is_active = 1"];
$params = [];

if ($q !== '') {
    $where[] = "(p.name LIKE ? OR p.sku LIKE ? OR b.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

if ($price_min > 0) {
    $where[] = "p.price >= ?";
    $params[] = $price_min;
}

if ($price_max > 0) {
    $where[] = "p.price <= ?";
    $params[] = $price_max;
}

$where_sql = implode(" AND ", $where);

// Count total for pagination
$stmt_count = $pdo->prepare("SELECT COUNT(DISTINCT p.id) 
    FROM products p 
    LEFT JOIN brands b ON b.id = p.brand_id
    WHERE $where_sql");
$stmt_count->execute($params);
$total_products = $stmt_count->fetchColumn();

// Fetch products
$sql = "SELECT p.*, b.name as brand_name, pi.url as image_url
    FROM products p
    LEFT JOIN brands b ON b.id = p.brand_id
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

$total_pages = ceil($total_products / $limit);

require_once __DIR__ . '/includes/header.php';
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/weblaptop">Trang chủ</a></li>
            <li class="breadcrumb-item active" aria-current="page">Tìm kiếm</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">
            <?php if ($q !== ''): ?>
                Kết quả tìm kiếm cho: <span class="text-danger">"<?php echo htmlspecialchars($q); ?>"</span>
            <?php elseif ($price_min > 0 || $price_max > 0): ?>
                Sản phẩm theo mức giá: 
                <span class="text-danger">
                    <?php 
                    if ($price_min > 0 && $price_max > 0) echo number_format($price_min, 0, ',', '.') . ' - ' . number_format($price_max, 0, ',', '.') . ' đ';
                    elseif ($price_min > 0) echo 'Trên ' . number_format($price_min, 0, ',', '.') . ' đ';
                    else echo 'Dưới ' . number_format($price_max, 0, ',', '.') . ' đ';
                    ?>
                </span>
            <?php else: ?>
                Tất cả sản phẩm
            <?php endif; ?>
            <small class="text-muted ms-2">(<?php echo $total_products; ?> sản phẩm)</small>
        </h4>
    </div>

    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <img src="https://deo.shopeemobile.com/shopee/shopee-pcmall-live-sg/assets/a60759ad1dabe909c46a817ecbf71878.png" alt="No results" style="width: 150px;" class="mb-3">
            <p class="text-muted">Không tìm thấy sản phẩm nào phù hợp với tìm kiếm của bạn.</p>
            <a href="/weblaptop" class="btn btn-primary">Quay lại trang chủ</a>
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-6 g-3">
            <?php foreach ($products as $p): 
                $img = $p['image_url'];
                if (!$img || (strpos($img, 'http') !== 0 && strpos($img, '/') !== 0)) {
                    if ($img && (preg_match('/^\d+x\d+/', $img) || strpos($img, 'text=') !== false)) {
                        $img = 'https://placehold.co/' . $img;
                    } else {
                        $img = 'https://placehold.co/150?text=No+Image';
                    }
                }
            ?>
                <div class="col">
                    <div class="card h-100 product-card border-0 shadow-sm">
                        <a href="product.php?id=<?php echo $p['id']; ?>" class="text-decoration-none text-dark">
                            <div class="position-relative">
                                <img src="<?php echo htmlspecialchars($img); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($p['name']); ?>" style="height: 180px; object-fit: cover;">
                                <?php if ($p['stock_quantity'] <= 0): ?>
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0,0,0,0.5);">
                                        <span class="badge bg-dark">Hết hàng</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1 text-truncate-2" style="height: 40px; font-size: 0.9rem;"><?php echo htmlspecialchars($p['name']); ?></h6>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="text-danger fw-bold"><?php echo number_format($p['price'], 0, ',', '.'); ?> đ</span>
                                    <small class="text-muted" style="font-size: 0.75rem;">Đã bán 0</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?php echo urlencode($q); ?>&page=<?php echo $page - 1; ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?q=<?php echo urlencode($q); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?q=<?php echo urlencode($q); ?>&page=<?php echo $page + 1; ?>">Sau</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
