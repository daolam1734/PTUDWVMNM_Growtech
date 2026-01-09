<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// Filter logic
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$stock_status = $_GET['stock'] ?? '';
$brand = $_GET['brand'] ?? '';

$sql = "
  SELECT p.*, GROUP_CONCAT(c.name SEPARATOR ', ') as category_name, b.name as brand_name 
  FROM products p 
  LEFT JOIN product_categories pc ON p.id = pc.product_id
  LEFT JOIN categories c ON pc.category_id = c.id 
  LEFT JOIN brands b ON p.brand_id = b.id 
  WHERE 1=1
";

$params = [];

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $sql .= " AND EXISTS (SELECT 1 FROM product_categories pc2 WHERE pc2.product_id = p.id AND pc2.category_id = ?)";
    $params[] = $category;
}

if ($brand) {
    $sql .= " AND p.brand_id = ?";
    $params[] = $brand;
}

if ($stock_status === 'out') {
    $sql .= " AND p.stock <= 0";
} elseif ($stock_status === 'low') {
    $sql .= " AND p.stock > 0 AND p.stock < 10";
}

$sql .= " GROUP BY p.id";

// Pagination setup
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $items_per_page;

// Count total items for pagination
$count_sql = "SELECT COUNT(*) FROM (" . $sql . ") as t";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);

$sql .= " LIMIT $items_per_page OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get stats for cards
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$active_products = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$low_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock < 10")->fetchColumn();
$out_of_stock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 0")->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --primary-dark: #1e293b;
        --accent-blue: #3b82f6;
        --text-main: #334155;
        --text-light: #64748b;
        --bg-light: #f8fafc;
        --shopee-orange: #ee4d2d;
    }
    .product-section { background: #fff; padding: 24px; border-radius: 1.25rem; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.05); }
    
    .filter-card {
        background: #fff;
        border-radius: 1.25rem;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        margin-bottom: 24px;
        overflow: hidden;
    }
    
    .table-modern thead th { 
        background: var(--bg-light); 
        border-bottom: 2px solid #f1f5f9; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
        color: var(--text-light); 
        padding: 1rem 1.5rem; 
    }
    .table-modern tbody td { 
        padding: 1.25rem 1.5rem; 
        vertical-align: middle; 
        font-size: 0.9rem; 
        border-bottom: 1px solid #f1f5f9; 
        color: var(--text-main);
    }
    
    .product-img { width: 48px; height: 48px; object-fit: contain; border-radius: 12px; background: #fff; padding: 2px; transition: transform 0.2s; }
    .product-img:hover { transform: scale(1.1); }
    .product-name-link { transition: color 0.1s; font-weight: 700; color: var(--primary-dark); text-decoration: none; }
    .product-name-link:hover { color: var(--accent-blue); }
    
    .status-badge { 
        padding: 0.4rem 0.8rem; 
        border-radius: 9999px; 
        font-size: 0.7rem; 
        font-weight: 700; 
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    
    .stock-badge { 
        padding: 0.3rem 0.6rem; 
        border-radius: 8px; 
        font-size: 0.75rem; 
        font-weight: 700; 
    }
    .stock-good { background: #dcfce7; color: #166534; }
    .stock-low { background: #fef3c7; color: #92400e; }
    .stock-out { background: #fee2e2; color: #991b1b; }

    .btn-action { 
        width: 38px; 
        height: 38px; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: 12px; 
        background: #fff;
        color: var(--text-main);
        border: 1px solid #e2e8f0;
        transition: all 0.2s; 
    }
    .btn-action:hover { 
        background: var(--bg-light);
        color: var(--accent-blue);
        border-color: var(--accent-blue);
        transform: translateY(-2px);
    }
    
    .btn-action.btn-delete:hover {
        color: #ef4444;
        border-color: #fee2e2;
        background: #fff5f5;
    }

    .stat-card {
        background: #fff;
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        height: 100%;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }

    .search-input-group {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        transition: all 0.3s;
    }
    .search-input-group:focus-within {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1 text-dark">Quản Lý Sản Phẩm</h4>
                <p class="text-muted small mb-0">Quản lý danh mục hàng hóa, giá bán và cập nhật tồn kho GrowTech.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="export.php?type=inventory" class="btn btn-white border px-4 py-2 shadow-sm rounded-pill fw-bold">
                    <i class="bi bi-file-earmark-excel me-2"></i> Xuất Báo Cáo
                </a>
                <a href="add_product.php" class="btn btn-primary px-4 py-2 shadow-sm rounded-pill fw-bold">
                    <i class="bi bi-plus-lg me-2"></i> Thêm sản phẩm
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-box-seam"></i></div>
                    <div class="text-muted small fw-bold text-uppercase mb-1">Tổng sản phẩm</div>
                    <div class="h3 mb-0 fw-bold"><?php echo number_format($total_products); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-eye"></i></div>
                    <div class="text-muted small fw-bold text-uppercase mb-1">Đang hiển thị</div>
                    <div class="h3 mb-0 fw-bold"><?php echo number_format($active_products); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="text-muted small fw-bold text-uppercase mb-1">Sắp hết hàng</div>
                    <div class="h3 mb-0 fw-bold text-warning"><?php echo number_format($low_stock); ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-octagon"></i></div>
                    <div class="text-muted small fw-bold text-uppercase mb-1">Hết hàng</div>
                    <div class="h3 mb-0 fw-bold text-danger"><?php echo number_format($out_of_stock); ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card border-0 mb-4">
            <div class="p-4 bg-white">
                <form class="row g-3 align-items-end" method="GET">
                    <div class="col-lg-4 col-md-6">
                        <label class="form-label x-small fw-bold text-uppercase text-muted mb-2">Tìm kiếm nhanh</label>
                        <div class="input-group search-input-group shadow-none">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" name="search" id="product-search" class="form-control border-0 shadow-none ps-0" placeholder="Tên sản phẩm, SKU..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label x-small fw-bold text-uppercase text-muted mb-2">Danh mục</label>
                        <select name="category" class="form-select border shadow-none rounded-3">
                            <option value="">Tất cả</option>
                            <?php
                            $categories_list = $pdo->query("SELECT * FROM categories")->fetchAll();
                            foreach ($categories_list as $cat) {
                                $sel = ($category == $cat['id']) ? 'selected' : '';
                                echo "<option value='{$cat['id']}' $sel>{$cat['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label x-small fw-bold text-uppercase text-muted mb-2">Thương hiệu</label>
                        <select name="brand" class="form-select border shadow-none rounded-3">
                            <option value="">Tất cả</option>
                            <?php
                            $brands_list = $pdo->query("SELECT * FROM brands")->fetchAll();
                            foreach ($brands_list as $b) {
                                $sel = ($brand == $b['id']) ? 'selected' : '';
                                echo "<option value='{$b['id']}' $sel>{$b['name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <label class="form-label x-small fw-bold text-uppercase text-muted mb-2">Kho hàng</label>
                        <select name="stock" class="form-select border shadow-none rounded-3">
                            <option value="">Tất cả</option>
                            <option value="low" <?php echo ($stock_status == 'low') ? 'selected' : ''; ?>>Sắp hết hàng</option>
                            <option value="out" <?php echo ($stock_status == 'out') ? 'selected' : ''; ?>>Hết hàng</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-3">
                        <button type="submit" class="btn btn-dark w-100 rounded-3 py-2 fw-bold">
                            <i class="bi bi-funnel me-1"></i> Lọc dữ liệu
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="product-section p-0 overflow-hidden">
            <div class="table-responsive" style="overflow: visible;">
                <table id="product-table" class="table table-modern align-middle mb-0">
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
                            $stock_text = 'Còn hàng';
                            if ($p['stock'] <= 0) { $stock_class = 'stock-out'; $stock_text = 'Hết hàng'; }
                            elseif ($p['stock'] < 10) { $stock_class = 'stock-low'; $stock_text = 'Sắp hết'; }
                        ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative me-3">
                                        <div class="bg-light rounded-3 p-1 border">
                                            <img src="<?php echo htmlspecialchars($img); ?>" class="product-img" onerror="this.src='https://placehold.co/100x100?text=No+Image'">
                                        </div>
                                        <?php if ($p['sale_price'] && $p['sale_price'] < $p['price']): ?>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-circle bg-danger p-1 border border-white" style="width: 18px; height: 18px;"><i class="bi bi-lightning-fill" style="font-size: 10px;"></i></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <a href="../product.php?id=<?php echo $p['id']; ?>" target="_blank" class="product-name-link d-block text-truncate" style="max-width: 250px;"><?php echo htmlspecialchars($p['name']); ?></a>
                                        <div class="small text-muted" style="font-size: 11px;">SKU: <span class="fw-bold"><?php echo htmlspecialchars($p['sku'] ?: 'N/A'); ?></span></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-0" style="font-size: 0.85rem;"><?php echo htmlspecialchars($p['category_name']); ?></div>
                                <div class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($p['brand_name']); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary mb-0"><?php echo number_format($p['price']); ?>₫</div>
                                <?php if ($p['sale_price'] && $p['sale_price'] < $p['price']): ?>
                                    <div class="text-muted text-decoration-line-through" style="font-size: 11px;"><?php echo number_format($p['sale_price']); ?>₫</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($p['is_active']) && $p['is_active']): ?>
                                    <span class="status-badge bg-light text-dark border">
                                        <i class="bi bi-circle-fill me-1 text-success" style="font-size: 6px;"></i> Hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge bg-light text-dark border">
                                        <i class="bi bi-circle-fill me-1 text-secondary" style="font-size: 6px;"></i> Tạm ẩn
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="stock-badge <?php echo $stock_class; ?> d-inline-block">
                                    <?php echo $p['stock']; ?> <span class="x-small fw-normal opacity-75 ms-1"><?php echo $stock_text; ?></span>
                                </div>
                            </td>
                            <td class="pe-4 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn-action" title="Sửa sản phẩm"><i class="bi bi-pencil-square"></i></a>
                                    <div class="dropdown">
                                        <button class="btn-action border-0 shadow-none no-caret" data-bs-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-2">
                                            <li><a class="dropdown-item rounded-3 py-2 small" href="../product.php?id=<?php echo $p['id']; ?>" target="_blank"><i class="bi bi-box-arrow-up-right me-2"></i>Xem trên web</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item rounded-3 py-2 small text-danger" href="delete_product.php?id=<?php echo $p['id']; ?>" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')"><i class="bi bi-trash me-2"></i>Xóa vĩnh viễn</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-top">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Hiển thị <b><?php echo count($products); ?></b> / <b><?php echo $total_items; ?></b> sản phẩm
                    </div>
                    <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <!-- Nút Trước -->
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page - 1])); ?>">Trước</a>
                            </li>

                            <!-- Các số trang -->
                            <?php 
                            $start_p = max(1, $current_page - 2);
                            $end_p = min($total_pages, $current_page + 2);
                            
                            for ($i = $start_p; $i <= $end_p; $i++): 
                            ?>
                                <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                                    <a class="page-link mx-1 rounded-pill" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Nút Sau -->
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link rounded-pill px-3" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $current_page + 1])); ?>">Sau</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('product-search');
    const tableBody = document.querySelector('#product-table tbody');
    const rows = tableBody.querySelectorAll('tr');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        let visibleCount = 0;

        rows.forEach(row => {
            const text = row.textContent.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
            if (text.includes(query)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // No results feedback
        let noResult = document.getElementById('no-product-result');
        if (visibleCount === 0 && query !== '') {
            if (!noResult) {
                noResult = document.createElement('tr');
                noResult.id = 'no-product-result';
                noResult.innerHTML = `<td colspan="6" class="text-center py-5 text-muted">Không tìm thấy sản phẩm nào khớp với "${this.value}"</td>`;
                tableBody.appendChild(noResult);
            }
        } else if (noResult) {
            noResult.remove();
        }
    });

    // Auto-submit filters on change
    document.querySelectorAll('.filter-card select').forEach(select => {
        select.addEventListener('change', () => {
            select.closest('form').submit();
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
