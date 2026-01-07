<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// Handle Voucher Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $code = strtoupper(trim($_POST['code']));
        $discount_type = $_POST['discount_type'];
        $discount_value = (float)$_POST['discount_value'];
        $min_spend = (float)$_POST['min_spend'];
        $max_discount = !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null;
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $usage_limit = !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO vouchers (code, discount_type, discount_value, min_spend, max_discount, start_date, end_date, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$code, $discount_type, $discount_value, $min_spend, $max_discount, $start_date, $end_date, $usage_limit, $is_active]);
                set_flash("success", "Đã thêm voucher mới.");
            } else {
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE vouchers SET code = ?, discount_type = ?, discount_value = ?, min_spend = ?, max_discount = ?, start_date = ?, end_date = ?, usage_limit = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$code, $discount_type, $discount_value, $min_spend, $max_discount, $start_date, $end_date, $usage_limit, $is_active, $id]);
                set_flash("success", "Đã cập nhật voucher.");
            }
        } catch (PDOException $e) {
            set_flash("danger", "Lỗi: " . $e->getMessage());
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM vouchers WHERE id = ?");
        $stmt->execute([$id]);
        set_flash("success", "Đã xóa voucher thành công.");
    }
    header("Location: vouchers.php");
    exit;
}

// Logic to check voucher status based on current time
$now = date('Y-m-d H:i:s');
$status_filter = $_GET['status'] ?? 'all';

$sql = "
    SELECT * FROM (
        SELECT *, 
        CASE 
            WHEN is_active = 0 THEN 'INACTIVE'
            WHEN usage_count >= usage_limit AND usage_limit IS NOT NULL THEN 'EXHAUSTED'
            WHEN '$now' < start_date THEN 'UPCOMING'
            WHEN '$now' > end_date THEN 'EXPIRED'
            ELSE 'ACTIVE'
        END as status_key
        FROM vouchers
    ) as v_table
";

if ($status_filter !== 'all') {
    $sql .= " WHERE status_key = " . $pdo->quote($status_filter);
}

$sql .= " ORDER BY created_at DESC";
$vouchers = $pdo->query($sql)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --primary-dark: #1e293b;
        --accent-blue: #3b82f6;
        --text-main: #334155;
        --text-light: #64748b;
        --bg-light: #f8fafc;
    }

    .card-modern {
        border-radius: 1.25rem;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
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
    
    .voucher-tag {
        background: #fdf2f2;
        border: 1px dashed #ef4444;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        color: #ef4444;
        font-family: 'Courier New', Courier, monospace;
        font-weight: 800;
        display: inline-block;
        letter-spacing: 1px;
    }

    .status-badge { 
        padding: 0.4rem 0.8rem; 
        border-radius: 9999px; 
        font-size: 0.75rem; 
        font-weight: 700; 
        border: 1px solid transparent;
    }
    .status-running { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
    .status-ended { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
    .status-paused { background: #f1f5f9; color: #475569; border-color: #e2e8f0; }

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
</style>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item small"><a href="dashboard.php" class="text-decoration-none text-muted">Bảng điều khiển</a></li>
                        <li class="breadcrumb-item small active" aria-current="page">Marketing</li>
                    </ol>
                </nav>
                <h4 class="fw-bold mb-0">Quản Lý Mã Giảm Giá</h4>
            </div>
            <button class="btn btn-primary shadow-sm rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#voucherModal" onclick="resetModal()">
                <i class="bi bi-plus-lg me-2"></i>Tạo Voucher Mới
            </button>
        </div>

        <div class="card card-modern border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom border-secondary border-opacity-10 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-ticket-perforated me-2 text-primary"></i>Danh sách mã giảm giá</h6>
                <div class="d-flex gap-2">
                    <form method="GET" class="d-flex gap-2">
                        <select name="status" class="form-select form-select-sm border-0 bg-light rounded-pill px-4 shadow-none" style="width: 200px;" onchange="this.form.submit()">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tất cả trạng thái</option>
                            <option value="ACTIVE" <?php echo $status_filter === 'ACTIVE' ? 'selected' : ''; ?>>Đang hoạt động</option>
                            <option value="UPCOMING" <?php echo $status_filter === 'UPCOMING' ? 'selected' : ''; ?>>Sắp diễn ra</option>
                            <option value="EXPIRED" <?php echo $status_filter === 'EXPIRED' ? 'selected' : ''; ?>>Đã hết hạn</option>
                            <option value="EXHAUSTED" <?php echo $status_filter === 'EXHAUSTED' ? 'selected' : ''; ?>>Đã hết lượt dùng</option>
                            <option value="INACTIVE" <?php echo $status_filter === 'INACTIVE' ? 'selected' : ''; ?>>Đang tạm dừng</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Mã Voucher</th>
                            <th>Chi tiết giảm giá</th>
                            <th>Điều kiện đơn</th>
                            <th>Thời hạn & Lượt dùng</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $v): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="voucher-tag">
                                    <?php echo htmlspecialchars($v['code']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary mb-1">
                                    <?php 
                                    if ($v['discount_type'] === 'percentage') {
                                        echo 'Giảm ' . (int)$v['discount_value'] . '%';
                                        if ($v['max_discount']) echo '<div class="text-muted x-small fw-normal">Tối đa ' . number_format($v['max_discount']) . '₫</div>';
                                    } else {
                                        echo 'Giảm ' . number_format($v['discount_value']) . '₫';
                                    }
                                    ?>
                                </div>
                                <div class="x-small text-muted text-uppercase fw-bold" style="letter-spacing: 0.05em;">
                                    <?php 
                                    if ($v['discount_type'] === 'percentage') echo 'Phần trăm';
                                    elseif ($v['discount_type'] === 'fixed') echo 'Cố định';
                                    else echo 'Vận chuyển';
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-bold text-dark mb-1">Min: <?php echo number_format($v['min_spend']); ?>₫</div>
                                <div class="progress rounded-pill shadow-none" style="height: 6px; width: 100px; background: #f1f5f9;">
                                    <div class="progress-bar bg-primary rounded-pill" style="width: <?php echo min(100, ($v['usage_count'] / ($v['usage_limit'] ?: 1)) * 100); ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <div class="small mb-1">
                                    <span class="text-muted">Hạn:</span> 
                                    <span class="fw-bold text-dark"><?php echo date('d/m/Y', strtotime($v['end_date'])); ?></span>
                                </div>
                                <div class="x-small text-muted">
                                    Sử dụng: <span class="text-primary fw-bold"><?php echo $v['usage_count'] ?? 0; ?></span> / <?php echo $v['usage_limit'] ?: '∞'; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $status_map = [
                                        'ACTIVE' => ['label' => 'Đang chạy', 'class' => 'status-running'],
                                        'UPCOMING' => ['label' => 'Sắp tới', 'class' => 'bg-info bg-opacity-10 text-info'],
                                        'EXPIRED' => ['label' => 'Hết hạn', 'class' => 'status-ended'],
                                        'EXHAUSTED' => ['label' => 'Hết lượt', 'class' => 'bg-warning bg-opacity-10 text-warning'],
                                        'INACTIVE' => ['label' => 'Tạm dừng', 'class' => 'status-paused']
                                    ];
                                    $s = $status_map[$v['status_key']] ?? $status_map['INACTIVE'];
                                ?>
                                <span class="status-badge <?php echo $s['class']; ?> rounded-pill px-3 py-1 fw-bold border-0" style="font-size: 0.7rem;">
                                    <?php echo $s['label']; ?>
                                </span>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn-action" onclick='editVoucher(<?php echo json_encode($v); ?>)' title="Chỉnh sửa">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Xác nhận xóa voucher này?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                        <button type="submit" class="btn-action btn-delete" title="Xóa">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($vouchers)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted bg-light">
                                <i class="bi bi-ticket-perforated fs-1 d-block mb-3 opacity-25"></i>
                                Chưa có voucher nào được tạo.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



<!-- Voucher Modal -->
<div class="modal fade" id="voucherModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST">
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="voucherId">
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold" id="modalTitle">Tạo Voucher Mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Mã Voucher</label>
                            <input type="text" name="code" id="voucherCode" class="form-control form-control-lg fs-6 text-uppercase" placeholder="VD: GIAM20K" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Loại giảm giá</label>
                            <select name="discount_type" id="voucherType" class="form-select" required>
                                <option value="fixed">Số tiền cố định (đ)</option>
                                <option value="percentage">Phần trăm (%)</option>
                                <option value="shipping">Giảm giá vận chuyển</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Giá trị giảm</label>
                            <input type="number" name="discount_value" id="voucherValue" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Đơn tối thiểu</label>
                            <input type="number" name="min_spend" id="voucherMin" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Giảm tối đa (cho %)</label>
                            <input type="number" name="max_discount" id="voucherMax" class="form-control" placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Lượt sử dụng tối đa</label>
                            <input type="number" name="usage_limit" id="voucherLimit" class="form-control" placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ngày bắt đầu</label>
                            <input type="datetime-local" name="start_date" id="voucherStart" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ngày kết thúc</label>
                            <input type="datetime-local" name="end_date" id="voucherEnd" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="voucherActive" checked>
                                <label class="form-check-label fw-bold small">Kích hoạt Voucher ngay</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top py-3">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Lưu Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetModal() {
    document.getElementById('modalTitle').innerText = 'Tạo Voucher Mới';
    document.getElementById('modalAction').value = 'add';
    document.getElementById('voucherId').value = '';
    document.getElementById('voucherCode').value = '';
    document.getElementById('voucherType').value = 'fixed';
    document.getElementById('voucherValue').value = '';
    document.getElementById('voucherMax').value = '';
    document.getElementById('voucherMin').value = '0';
    document.getElementById('voucherLimit').value = '';
    document.getElementById('voucherStart').value = '<?php echo date('Y-m-d\TH:i'); ?>';
    document.getElementById('voucherEnd').value = '<?php echo date('Y-m-d\TH:i', strtotime('+1 month')); ?>';
    document.getElementById('voucherActive').checked = true;
}

function editVoucher(v) {
    document.getElementById('modalTitle').innerText = 'Sửa Voucher';
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('voucherId').value = v.id;
    document.getElementById('voucherCode').value = v.code;
    document.getElementById('voucherType').value = v.discount_type;
    document.getElementById('voucherValue').value = v.discount_value;
    document.getElementById('voucherMax').value = v.max_discount || '';
    document.getElementById('voucherMin').value = v.min_spend;
    document.getElementById('voucherLimit').value = v.usage_limit || '';
    document.getElementById('voucherStart').value = v.start_date.replace(' ', 'T').substring(0, 16);
    document.getElementById('voucherEnd').value = v.end_date.replace(' ', 'T').substring(0, 16);
    document.getElementById('voucherActive').checked = v.is_active == 1;
    
    var modal = new bootstrap.Modal(document.getElementById('voucherModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
