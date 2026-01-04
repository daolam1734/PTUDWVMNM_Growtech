<?php
require_once __DIR__ . '/config/database.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = $page * $limit;

$stmt = $pdo->prepare("
    SELECT p.*, pi.url as image_url 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 
    WHERE p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute([$limit, $offset]);
$products = $stmt->fetchAll();

if (empty($products)) {
    echo "";
    exit;
}

foreach ($products as $p):
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
<?php endforeach; ?>
