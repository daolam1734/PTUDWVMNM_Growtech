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
    :root {
        --flash-orange: #fd7e14;
        --flash-soft-orange: rgba(253, 126, 20, 0.1);
    }
    .bg-soft-primary { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .bg-soft-warning { background-color: var(--flash-soft-orange); color: var(--flash-orange); }
    .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
    
    .table-modern thead th { 
        background: #f8fafc; 
        border-bottom: 2px solid #f1f5f9; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 0.05em; 
        color: #64748b; 
        padding: 1.25rem 1rem; 
    }
    .table-modern tbody td { 
        padding: 1.25rem 1rem; 
        vertical-align: middle; 
        font-size: 0.9rem; 
        border-bottom: 1px solid #f1f5f9; 
        color: #334155;
    }
    
    .flash-card { background: #fff; border-radius: 1.25rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.05); }
    .flash-input { border-radius: 0.75rem; border: 1px solid #e2e8f0; transition: all 0.2s; }
    .flash-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    
    .product-img { width: 48px; height: 48px; object-fit: contain; border-radius: 12px; background: #fff; padding: 2px; border: 1px solid #f1f5f9; }
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="mb-4 d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item small"><a href="dashboard.php" class="text-decoration-none text-muted">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item small active" aria-current="page">Quản lý Flash Sale</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0">Chương trình Flash Sale</h4>
                <p class="text-muted small mb-0">Quản lý thời gian và các sản phẩm đang được giảm giá chớp nhoáng.</p>
            </div>
            <button type="button" class="btn btn-primary shadow-sm px-4 rounded-pill fw-bold" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-lg me-2"></i> Thêm Sản Phẩm Mới
            </button>
        </div>

        <div class="row g-4 mb-4">
            <!-- Flash Sale Settings: Shopee/Standard Style (Light UI) -->
            <div class="col-lg-12">
                <div class="flash-card p-4 border-0 shadow-sm border-start border-4 border-primary">
                    <div class="row align-items-center g-4">
                        <div class="col-md-auto">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle d-inline-flex">
                                <i class="bi bi-clock-history text-primary fs-3"></i>
                            </div>
                        </div>
                        <div class="col-md">
                            <h5 class="fw-bold text-dark mb-1">Thời gian hiệu lực Flash Sale</h5>
                            <p class="text-muted small mb-0">Chương trình sẽ tự động hiển thị trên Slider và Trang chủ trong khoảng thời gian này.</p>
                        </div>
                        <div class="col-md-4">
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="action" value="update_settings">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-calendar-event"></i></span>
                                    <input type="datetime-local" name="flash_sale_end" class="form-control border-0 bg-light py-2 fw-bold" 
                                           value="<?php echo date('Y-m-d\TH:i', strtotime($flash_sale_end)); ?>" 
                                           style="border-radius: 0 0.75rem 0.75rem 0;" required>
                                </div>
                                <button type="submit" class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm">
                                    Lưu
                                </button>
                            </form>
                        </div>
                        <div class="col-md-auto border-start ps-4">
                            <div id="status-container" class="text-center">
                                <?php 
                                    $now = time();
                                    $end = strtotime($flash_sale_end);
                                    $is_running = ($end > $now && count($flash_products) > 0);
                                ?>
                                <div class="x-small text-muted text-uppercase fw-bold mb-2">Hiện tại</div>
                                <?php if ($is_running): ?>
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold mb-1">
                                            <i class="bi bi-play-circle-fill me-1"></i> Đang Chạy
                                        </span>
                                        <div class="text-primary fw-bold" id="timer-display" style="font-family: monospace; font-size: 1.1rem;">--:--:--</div>
                                    </div>
                                <?php else: ?>
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3 py-2 fw-bold mb-1">Dừng / Kết thúc</span>
                                        <div class="text-muted small">Hết hạn giảm giá</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Stats Briefing: Clean Light Style -->
            <div class="col-md-4">
                <div class="flash-card p-4 border-start border-4 border-info">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-info bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-box-seam text-info"></i>
                        </div>
                        <div class="x-small text-uppercase fw-bold text-muted">Sản phẩm tham gia</div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-0"><?php echo count($flash_products); ?> <span class="small fw-normal text-muted">mục</span></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="flash-card p-4 border-start border-4 border-danger">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-danger bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-percent text-danger"></i>
                        </div>
                        <div class="x-small text-uppercase fw-bold text-muted">Mức giảm sâu nhất</div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-0">
                        <?php 
                            $max_disc = 0;
                            foreach($flash_products as $fp) {
                                $d = round((($fp['price'] - $fp['sale_price']) / $fp['price']) * 100);
                                if($d > $max_disc) $max_disc = $d;
                            }
                            echo $max_disc;
                        ?>%
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="flash-card p-4 border-start border-4 border-success">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-success bg-opacity-10 p-2 rounded-3 me-3">
                            <i class="bi bi-piggy-bank text-success"></i>
                        </div>
                        <div class="x-small text-uppercase fw-bold text-muted">Tiết kiệm tối đa</div>
                    </div>
                    <div class="h3 fw-bold text-dark mb-0">
                        <?php 
                            $total_save = 0;
                            foreach($flash_products as $fp) $total_save += ($fp['price'] - $fp['sale_price']);
                            echo number_format($total_save / 1000, 0);
                        ?>k <span class="small fw-normal text-muted">giảm thực tế</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Sale Products List -->
        <div class="flash-card overflow-hidden">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white">
                <h6 class="mb-0 fw-bold"><i class="bi bi-list-task me-2 text-warning"></i> Danh Sách Sản Phẩm Flash Sale</h6>
                <div class="d-flex gap-2">
                    <div class="input-group input-group-sm rounded-pill border overflow-hidden bg-light">
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" class="form-control border-0 bg-transparent pr-3" placeholder="Tìm trong danh sách..." onkeyup="filterFlashItems(this)">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0" id="flashProductsTable">
                    <thead>
                        <tr>
                            <th class="ps-4">Thông tin sản phẩm</th>
                            <th class="text-center">Giá Niêm Yết</th>
                            <th class="text-center" style="width: 200px;">Giá Flash Sale</th>
                            <th class="text-center">Mức Giảm</th>
                            <th class="text-end pe-4">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($flash_products)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="py-4">
                                        <i class="bi bi-lightning fs-1 text-muted opacity-25 d-block mb-3"></i>
                                        <p class="text-muted">Chưa có sản phẩm nào được thiết lập Flash Sale</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($flash_products as $p): 
                                $discount = round((($p['price'] - $p['sale_price']) / $p['price']) * 100);
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo getProductImage($p['id']); ?>" class="product-img me-3">
                                            <div>
                                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['name']); ?></div>
                                                <div class="text-muted x-small"><?php echo htmlspecialchars($p['sku']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center text-muted text-decoration-line-through small">
                                        <?php echo number_format($p['price'], 0, ',', '.'); ?>₫
                                    </td>
                                    <td class="text-center">
                                        <form method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <div class="input-group input-group-sm flash-input overflow-hidden bg-light">
                                                <input type="number" name="sale_price" class="form-control border-0 bg-transparent fw-bold text-danger text-center" value="<?php echo (int)$p['sale_price']; ?>">
                                                <button type="submit" class="btn btn-primary border-0 px-3"><i class="bi bi-check2"></i></button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-soft-danger rounded-pill px-3">-<?php echo $discount; ?>%</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" onsubmit="return confirm('Xác nhận xóa sản phẩm này khỏi Flash Sale?');">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button type="submit" class="btn btn-outline-danger btn-sm border-0 rounded-circle" title="Gỡ bỏ">
                                                <i class="bi bi-trash3 fs-6"></i>
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

<!-- Scripts for dynamic filtering -->
<script>
// Countdown Timer Logic
const endTime = new Date("<?php echo date('Y-m-d H:i:s', strtotime($flash_sale_end)); ?>").getTime();
const timerDisplay = document.getElementById('timer-display');

if (timerDisplay) {
    const x = setInterval(function() {
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            clearInterval(x);
            timerDisplay.innerHTML = "ĐÃ HẾT HẠN";
            timerDisplay.classList.replace('text-primary', 'text-danger');
            return;
        }

        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        timerDisplay.innerHTML = hours.toString().padStart(2, '0') + ":" + 
                                minutes.toString().padStart(2, '0') + ":" + 
                                seconds.toString().padStart(2, '0');
    }, 1000);
}

function filterFlashItems(input) {
    let filter = input.value.toLowerCase();
    let rows = document.querySelectorAll("#flashProductsTable tbody tr");
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}

function filterModalItems(input) {
    let filter = input.value.toLowerCase();
    let rows = document.querySelectorAll("#modalProductsTable tbody tr");
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}
</script>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom py-3">
                <h6 class="modal-title fw-bold">Thêm Sản Phẩm Vào Flash Sale</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="p-3 bg-light border-bottom">
                <div class="input-group search-input-group bg-white rounded-pill border px-3">
                    <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-0 shadow-none" placeholder="Tìm sản phẩm theo tên hoặc mã..." onkeyup="filterModalItems(this)">
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 500px;">
                    <table class="table table-modern align-middle mb-0" id="modalProductsTable">
                        <thead class="sticky-top shadow-sm" style="z-index: 10;">
                            <tr>
                                <th class="ps-4 bg-white">Thông tin sản phẩm</th>
                                <th class="text-center bg-white" style="width: 200px;">Giá Sale Dự Kiến</th>
                                <th class="text-end pe-4 bg-white">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($other_products)): ?>
                                <tr><td colspan="3" class="text-center py-4 text-muted small">Tất cả sản phẩm đều đã tham gia Flash Sale</td></tr>
                            <?php endif; ?>
                            <?php foreach ($other_products as $p): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo getProductImage($p['id']); ?>" class="product-img me-3 border-0" style="width: 40px; height: 40px;">
                                            <div>
                                                <div class="fw-bold text-dark small"><?php echo htmlspecialchars($p['name']); ?></div>
                                                <div class="text-muted x-small">Dòng: <?php echo number_format($p['price'],0,',','.'); ?>₫</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" id="form-add-<?php echo $p['id']; ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="number" name="sale_price" class="form-control form-control-sm text-center fw-bold text-danger flash-input" placeholder="Nhập giá sale..." required>
                                        </form>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button type="submit" form="form-add-<?php echo $p['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm fw-bold">
                                            Lấy Sale
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
