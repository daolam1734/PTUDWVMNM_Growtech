<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

$stmt = $pdo->query("
  SELECT p.*, c.name as category_name, b.name as brand_name 
  FROM products p 
  LEFT JOIN categories c ON p.category_id = c.id 
  LEFT JOIN brands b ON p.brand_id = b.id 
  ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .product-section { background: #fff; padding: 24px; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,.05); }
    .product-table thead th { 
        background-color: #f6f6f6; 
        color: rgba(0,0,0,.54); 
        font-weight: 500; 
        font-size: 14px;
        border-bottom: none;
        padding: 12px 16px;
    }
    .product-table tbody td { 
        padding: 16px; 
        font-size: 14px; 
        color: rgba(0,0,0,.87);
        border-bottom: 1px solid #f0f0f0;
    }
    .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 2px; border: 1px solid #e8e8e8; }
    .btn-shopee-primary { background-color: var(--shopee-orange); color: #fff; border: none; }
    .btn-shopee-primary:hover { background-color: #d73211; color: #fff; }
    .search-bar { background: #f6f6f6; border-radius: 4px; padding: 16px; margin-bottom: 20px; }
    .filter-label { font-size: 14px; color: rgba(0,0,0,.54); margin-right: 12px; }
</style>

<div class="container-fluid">
    <div class="product-section">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold">Tất cả sản phẩm</h4>
            <a href="add_product.php" class="btn btn-shopee-primary px-4">
                <i class="bi bi-plus-lg me-1"></i> Thêm 1 sản phẩm mới
            </a>
        </div>

        <!-- Search & Filter (Shopee Style) -->
        <div class="search-bar">
            <form class="row g-3 align-items-center">
                <div class="col-auto">
                    <span class="filter-label">Tên sản phẩm:</span>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm" placeholder="Nhập tên sản phẩm">
                </div>
                <div class="col-auto">
                    <span class="filter-label">Danh mục:</span>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-shopee-primary px-3">Tìm kiếm</button>
                    <button type="reset" class="btn btn-sm btn-outline-secondary px-3 ms-2">Nhập lại</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table product-table mb-0">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th width="80">Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá</th>
                        <th>Kho hàng</th>
                        <th>Trạng thái</th>
                        <th width="150">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): 
                        $img = getProductImage($p['id']);
                    ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><img src="<?php echo htmlspecialchars($img); ?>" class="product-img"></td>
                        <td>
                            <div class="fw-bold text-truncate" style="max-width: 300px;"><?php echo htmlspecialchars($p['name']); ?></div>
                            <div class="small text-muted">SKU: <?php echo htmlspecialchars($p['sku'] ?: 'N/A'); ?></div>
                        </td>
                        <td>
                            <?php if ($p['sale_price']): ?>
                                <div class="text-danger fw-bold"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?> đ</div>
                                <div class="text-muted small text-decoration-line-through"><?php echo number_format($p['price'], 0, ',', '.'); ?> đ</div>
                            <?php else: ?>
                                <div class="fw-bold"><?php echo number_format($p['price'], 0, ',', '.'); ?> đ</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?php echo $p['stock'] < 5 ? 'text-danger fw-bold' : ''; ?>">
                                <?php echo $p['stock']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($p['is_active']): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Đang hiển thị</span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25">Đã ẩn</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="text-primary text-decoration-none">Cập nhật</a>
                                <div class="vr"></div>
                                <a href="delete_product.php?id=<?php echo $p['id']; ?>" class="text-danger text-decoration-none" onclick="return confirm('Xóa sản phẩm này?')">Xóa</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
