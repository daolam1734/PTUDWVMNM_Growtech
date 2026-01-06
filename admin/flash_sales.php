<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// Handle adding/removing from Flash Sale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($_POST['action'] === 'remove') {
            $stmt = $pdo->prepare("UPDATE products SET sale_price = NULL WHERE id = ?");
            $stmt->execute([$product_id]);
            set_flash("success", "Đã xóa sản phẩm khỏi Flash Sale.");
        } elseif ($_POST['action'] === 'update') {
            $sale_price = (float)$_POST['sale_price'];
            $stmt = $pdo->prepare("UPDATE products SET sale_price = ? WHERE id = ?");
            $stmt->execute([$sale_price, $product_id]);
            set_flash("success", "Đã cập nhật giá Flash Sale.");
        } elseif ($_POST['action'] === 'update_settings') {
            $end_time = $_POST['flash_sale_end'];
            $stmt = $pdo->prepare("UPDATE settings SET `value` = ? WHERE `key` = 'flash_sale_end'");
            $stmt->execute([$end_time]);
            set_flash("success", "Đã cập nhật thời gian kết thúc Flash Sale.");
        }
        header("Location: flash_sales.php");
        exit;
    }
}

// Fetch Flash Sale settings
$flash_sale_end = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'flash_sale_end'")->fetchColumn() ?: date('Y-m-d 23:59:59');

// Fetch current Flash Sale products
$flash_products = $pdo->query("
    SELECT p.*, pi.url as image_url 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0
    WHERE p.sale_price IS NOT NULL AND p.sale_price < p.price
    ORDER BY p.updated_at DESC
")->fetchAll();

// Fetch other products to add to Flash Sale
$other_products = $pdo->query("
    SELECT p.*, pi.url as image_url 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0
    WHERE p.sale_price IS NULL OR p.sale_price >= p.price
    ORDER BY p.name ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    .bg-soft-primary { background-color: rgba(13, 110, 253, 0.1); }
    .bg-soft-warning { background-color: rgba(255, 193, 7, 0.1); }
    .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }
    
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
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none text-muted">Dashboard</a></li>
                    <li class="breadcrumb-item active text-primary">Flash Sale</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="fw-bold mb-0">Quản lý Flash Sale</h4>
                    <p class="text-muted small mb-0">Tạo các chương trình giảm giá chớp nhoáng theo thời gian</p>
                </div>
                <button type="button" class="btn btn-primary shadow-sm px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="bi bi-plus-lg me-2"></i>Thêm sản phẩm
                </button>
            </div>
        </div>

        <!-- Flash Sale Settings -->
        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="mb-0 fw-bold"><i class="bi bi-gear me-2 text-primary"></i>Cấu hình thời gian</h6>
            </div>
            <div class="card-body p-4 bg-soft-primary">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="update_settings">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-primary">Thời gian kết thúc</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-calendar-event text-primary"></i></span>
                            <input type="datetime-local" name="flash_sale_end" class="form-control border-start-0" value="<?php echo date('Y-m-d\TH:i', strtotime($flash_sale_end)); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm">Cập nhật</button>
                    </div>
                    <div class="col-md-6">
                        <div class="bg-white rounded-3 p-3 d-flex align-items-center shadow-sm">
                            <i class="bi bi-info-circle-fill text-primary fs-5 me-3"></i>
                            <span class="small text-muted">Thời gian này sẽ được dùng để hiển thị đồng hồ đếm ngược trên trang chủ. Chương trình sẽ tự động dừng khi hết thời gian.</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Flash Sale Products -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-warning"></i>Sản phẩm đang Flash Sale</h6>
                <span class="badge bg-soft-warning text-warning rounded-pill px-3 border border-warning"><?php echo count($flash_products); ?> sản phẩm</span>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Sản phẩm</th>
                            <th class="text-center">Giá gốc</th>
                            <th class="text-center">Giá Flash Sale</th>
                            <th class="text-center">Giảm giá</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($flash_products)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Chưa có sản phẩm nào trong chương trình Flash Sale.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($flash_products as $p): 
                                $discount = round((($p['price'] - $p['sale_price']) / $p['price']) * 100);
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo getProductImage($p['id']); ?>" class="rounded shadow-sm me-3" style="width: 48px; height: 48px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['name']); ?></div>
                                                <div class="text-muted x-small">SKU: <?php echo htmlspecialchars($p['sku']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center text-muted text-decoration-line-through small">
                                        <?php echo number_format($p['price'], 0, ',', '.'); ?>đ
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" class="d-flex align-items-center justify-content-center">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <div class="input-group input-group-sm shadow-sm" style="width: 140px;">
                                                <input type="number" name="sale_price" class="form-control fw-bold text-danger border-end-0" value="<?php echo (int)$p['sale_price']; ?>">
                                                <button type="submit" class="btn btn-outline-primary border-start-0"><i class="bi bi-check-lg"></i></button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-soft-danger text-danger rounded-pill px-3 border border-danger">
                                            -<?php echo $discount; ?>%
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" onsubmit="return confirm('Xóa sản phẩm này khỏi Flash Sale?');">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button type="submit" class="btn btn-light btn-sm text-danger rounded-pill px-3 border">
                                                <i class="bi bi-trash me-1"></i> Gỡ bỏ
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold">Thêm sản phẩm vào Flash Sale</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 450px;">
                    <table class="table table-modern align-middle mb-0">
                        <thead class="sticky-top">
                            <tr>
                                <th class="ps-4">Sản phẩm</th>
                                <th class="text-center">Giá gốc</th>
                                <th class="text-center" style="width: 180px;">Giá khuyến mãi</th>
                                <th class="text-end pe-4">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($other_products as $p): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo getProductImage($p['id']); ?>" class="rounded shadow-sm me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold text-dark small"><?php echo htmlspecialchars($p['name']); ?></div>
                                                <div class="text-muted x-small"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center text-muted small">
                                        <?php echo number_format($p['price'], 0, ',', '.'); ?>đ
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" id="form-add-<?php echo $p['id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="number" name="sale_price" class="form-control form-control-sm text-center fw-bold text-danger" placeholder="Nhập giá sale..." required>
                                        </form>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="submit" form="form-add-<?php echo $p['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-plus-lg me-1"></i> Thêm
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-top p-3">
                <button type="button" class="btn btn-light fw-bold rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($p['image_url'] ?: 'https://placehold.co/50'); ?>" class="rounded me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                            <div class="small fw-bold text-dark text-truncate" style="max-width: 200px;"><?php echo htmlspecialchars($p['name']); ?></div>
                                        </div>
                                    </td>
                                    <td class="text-center small"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</td>
                                    <td>
                                        <form method="POST" id="form-add-<?php echo $p['id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="sale_price" class="form-control" placeholder="Nhập giá..." required>
                                                <span class="input-group-text">đ</span>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <button type="submit" form="form-add-<?php echo $p['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                                            Thêm
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
