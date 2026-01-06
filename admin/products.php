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
    .product-section { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.04); border: 1px solid #eee; }
    .product-table thead th { 
        background-color: #f8f9fa; 
        color: #6c757d; 
        font-weight: 600; 
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: none;
        padding: 15px;
    }
    .product-table tbody td { 
        padding: 15px; 
        font-size: 14px; 
        color: #2c3e50;
        border-bottom: 1px solid #f1f1f1;
        vertical-align: middle;
    }
    .product-img { width: 50px; height: 50px; object-fit: contain; border-radius: 8px; background: #fff; padding: 2px; }
    .product-name-link { transition: color 0.2s; }
    .product-name-link:hover { color: #0d6efd !important; }
    .table-hover tbody tr:hover { background-color: #fcfcfc; }
    .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: all 0.2s; }
    .btn-action:hover { transform: translateY(-2px); }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Sản phẩm</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-1">Quản Lý Sản Phẩm</h4>
                    <p class="text-muted small mb-0">Quản lý danh sách sản phẩm, giá cả và tồn kho của bạn.</p>
                </div>
                <a href="add_product.php" class="btn btn-primary px-4 shadow-sm rounded-3">
                    <i class="bi bi-plus-lg me-2"></i> Thêm sản phẩm mới
                </a>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <!-- Search & Filter -->
            <div class="card-header bg-white py-4 border-bottom">
                <form class="row g-3">
                    <div class="col-md-4">
                        <label class="filter-label small fw-bold text-muted mb-2">Tìm kiếm sản phẩm</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-0" placeholder="Tên sản phẩm, SKU...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label small fw-bold text-muted mb-2">Danh mục</label>
                        <select class="form-select bg-light border-0">
                            <option value="">Tất cả danh mục</option>
                            <?php
                            $categories = $pdo->query("SELECT * FROM categories")->fetchAll();
                            foreach ($categories as $cat) {
                                echo "<option value='{$cat['id']}'>{$cat['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="filter-label small fw-bold text-muted mb-2">Trạng thái kho</label>
                        <select class="form-select bg-light border-0">
                            <option value="">Tất cả trạng thái</option>
                            <option value="low">Sắp hết hàng</option>
                            <option value="out">Hết hàng</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-dark w-100 rounded-3">Lọc kết quả</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold text-uppercase">Sản phẩm</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase">Danh mục</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase">Giá bán</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase text-center">Tình trạng</th>
                            <th class="py-3 text-muted small fw-bold text-uppercase">Kho</th>
                            <th class="pe-4 py-3 text-muted small fw-bold text-uppercase text-end">Hành động</th>
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
                                        <img src="<?php echo htmlspecialchars($img); ?>" class="product-img me-3 border" onerror="this.src='https://placehold.co/100x100?text=No+Img'">
                                        <?php if ($p['sale_price'] && $p['sale_price'] < $p['price']): ?>
                                            <span class="position-absolute top-0 start-0 badge bg-danger rounded-circle p-1" style="font-size: 8px;"><i class="bi bi-lightning-fill"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="../product.php?id=<?php echo $p['id']; ?>" target="_blank" class="fw-bold text-dark text-decoration-none product-name-link d-block" style="max-width: 250px;"><?php echo htmlspecialchars($p['name']); ?></a>
                                        <div class="small text-muted" style="font-size: 11px;">SKU: <span class="text-dark fw-500"><?php echo htmlspecialchars($p['sku'] ?: 'N/A'); ?></span> | <?php echo htmlspecialchars($p['brand_name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge bg-light text-dark fw-normal border px-2 py-1"><?php echo htmlspecialchars($p['category_name']); ?></span></td>
                            <td>
                                <div class="fw-bold text-primary"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</div>
                                <?php if ($p['sale_price'] && $p['sale_price'] < $p['price']): ?>
                                    <div class="small text-muted text-decoration-line-through" style="font-size: 11px;"><?php echo number_format($p['sale_price'], 0, ',', '.'); ?>đ</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2">Ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="stock-badge <?php echo $stock_class; ?>">
                                    <?php echo $p['stock']; ?> sp
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="../product.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-action btn-outline-info border-0" title="Xem nhanh"><i class="bi bi-eye"></i></a>
                                    <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-action btn-outline-primary border-0" title="Sửa"><i class="bi bi-pencil"></i></a>
                                    <a href="delete_product.php?id=<?php echo $p['id']; ?>" class="btn btn-action btn-outline-danger border-0" title="Xóa" onclick="return confirm('Xóa sản phẩm này?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white py-3 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">Hiển thị <?php echo count($products); ?> sản phẩm</div>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-link rounded-start-3" href="#">Trước</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link rounded-end-3" href="#">Sau</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
