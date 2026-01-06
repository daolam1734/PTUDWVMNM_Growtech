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
    :root {
        --accent-color: #2c3e50;
        --shopee-orange: #ee4d2d;
    }
    .product-section { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.04); border: 1px solid #eee; }
    
    .filter-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 2px 12px rgba(0,0,0,.04);
        margin-bottom: 24px;
        overflow: hidden;
    }
    
    .table-modern thead th { 
        background: #f8f9fa; 
        border-bottom: none; 
        font-size: 11px; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        color: #6c757d; 
        padding: 15px; 
    }
    .table-modern tbody td { 
        padding: 15px; 
        vertical-align: middle; 
        font-size: 14px; 
        border-bottom: 1px solid #f8f9fa; 
        color: #2c3e50;
    }
    
    .product-img { width: 44px; height: 44px; object-fit: contain; border-radius: 10px; background: #fff; padding: 2px; }
    .product-name-link { transition: color 0.1s; font-weight: 600; color: #2c3e50; text-decoration: none; }
    .product-name-link:hover { color: var(--shopee-orange); }
    
    .status-badge { 
        padding: 5px 10px; 
        border-radius: 20px; 
        font-size: 11px; 
        font-weight: 600; 
        display: inline-block; 
    }
    
    .stock-badge { 
        padding: 4px 8px; 
        border-radius: 4px; 
        font-size: 12px; 
        font-weight: 600; 
    }
    .stock-good { background: #e8f5e9; color: #2e7d32; }
    .stock-low { background: #fff3e0; color: #ef6c00; }
    .stock-out { background: #ffebee; color: #c62828; }

    .btn-action { 
        width: 34px; 
        height: 34px; 
        padding: 0; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 10px; 
        background: #f8f9fa;
        color: #4b5563;
        border: 1px solid #e5e7eb;
        transition: all 0.2s; 
    }
    .btn-action:hover { 
        background: #fff;
        color: var(--shopee-orange);
        border-color: var(--shopee-orange);
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    .btn-action.btn-delete:hover {
        color: #dc3545;
        border-color: #dc3545;
    }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Quản Lý Sản Phẩm</h4>
                <p class="text-muted small mb-0">Quản lý danh mục hàng hóa, giá bán và cấp nhật tồn kho.</p>
            </div>
            <a href="add_product.php" class="btn btn-primary px-4 py-2 shadow-sm rounded-pill fw-bold">
                <i class="bi bi-plus-lg me-2"></i> Thêm sản phẩm
            </a>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <div class="p-4">
                <form class="row g-3 align-items-end" method="GET">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-2">Tìm kiếm sản phẩm</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control bg-light border-0" placeholder="Tên sản phẩm, SKU..." value="<?php echo $_GET['search'] ?? ''; ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-2">Danh mục</label>
                        <select name="category" class="form-select bg-light border-0">
                            <option value="">Tất cả danh mục</option>
                            <?php
                            $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
                            foreach ($categories as $cat) {
                                $selected = (isset($_GET['category']) && $_GET['category'] == $cat['id']) ? 'selected' : '';
                                echo "<option value='{$cat['id']}' $selected>{$cat['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-2">Tình trạng kho</label>
                        <select name="stock" class="form-select bg-light border-0">
                            <option value="">Tất cả</option>
                            <option value="low" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'low') ? 'selected' : ''; ?>>Sắp hết hàng (< 10)</option>
                            <option value="out" <?php echo (isset($_GET['stock']) && $_GET['stock'] == 'out') ? 'selected' : ''; ?>>Đã hết hàng</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-dark w-100 rounded-pill py-2 fw-bold">Tìm kiếm</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="dashboard-section p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Sản phẩm</th>
                            <th>Danh mục / Thương hiệu</th>
                            <th>Giá bán</th>
                            <th>Tình trạng</th>
                            <th>Kho</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): 
                            $img = getProductImage($p['id']);
                            $stock_class = 'stock-good';
                            if ($p['stock'] <= 0) $stock_class = 'stock-out';
                            elseif ($p['stock'] < 10) $stock_class = 'stock-low';
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative">
                                        <img src="<?php echo htmlspecialchars($img); ?>" class="product-img me-3 border" onerror="this.src='../assets/images/no-image.png'">
                                        <?php if ($p['sale_price'] && $p['sale_price'] < $p['price']): ?>
                                            <span class="position-absolute top-0 start-0 badge bg-danger rounded-circle p-1" style="transform: translate(-30%, -30%);"><i class="bi bi-lightning-fill" style="font-size: 8px;"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="../product.php?id=<?php echo $p['id']; ?>" target="_blank" class="product-name-link d-block text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($p['name']); ?></a>
                                        <div class="small text-muted" style="font-size: 11px;">Mã SP: <span class="fw-bold"><?php echo htmlspecialchars($p['sku'] ?: 'N/A'); ?></span></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><?php echo htmlspecialchars($p['category_name']); ?></div>
                                <div class="small text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($p['brand_name']); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?php echo number_format($p['price']); ?>đ</div>
                                <?php if ($p['sale_price'] && $p['sale_price'] < $p['price']): ?>
                                    <div class="small text-muted text-decoration-line-through" style="font-size: 11px;"><?php echo number_format($p['sale_price']); ?>đ</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2">Tạm ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="stock-badge <?php echo $stock_class; ?>">
                                    <?php echo $p['stock']; ?>
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn-action" title="Sửa sản phẩm"><i class="bi bi-pencil-square"></i></a>
                                    <a href="delete_product.php?id=<?php echo $p['id']; ?>" class="btn-action btn-delete" title="Xóa" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">Hiển thị <b><?php echo count($products); ?></b> sản phẩm</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link rounded-pill" href="#">Trước</a></li>
                            <li class="page-item active"><a class="page-link mx-1 rounded-pill" href="#">1</a></li>
                            <li class="page-item"><a class="page-link rounded-pill" href="#">Sau</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
