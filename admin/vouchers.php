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
        $stmt->execute([id]);
        set_flash("success", "Đã xóa voucher.");
    }
    header("Location: vouchers.php");
    exit;
}

$vouchers = $pdo->query("SELECT * FROM vouchers ORDER BY created_at DESC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
    
    <div class="admin-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">Quản Lý Voucher</h4>
                <p class="text-muted small mb-0">Tạo và quản lý các mã giảm giá để kích cầu mua sắm.</p>
            </div>
            <button class="btn btn-primary shadow-sm px-4" data-bs-toggle="modal" data-bs-target="#voucherModal" onclick="resetModal()">
                <i class="bi bi-plus-lg me-2"></i>Tạo Voucher Mới
            </button>
        </div>

        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-ticket-perforated me-2 text-primary"></i>Danh sách Voucher</h6>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm border-0 bg-light" style="width: 150px;">
                        <option>Tất cả trạng thái</option>
                        <option>Đang hoạt động</option>
                        <option>Đã kết thúc</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4 py-3 text-muted small fw-bold">Mã Voucher</th>
                            <th class="py-3 text-muted small fw-bold">Loại giảm giá</th>
                            <th class="py-3 text-muted small fw-bold">Giá trị</th>
                            <th class="py-3 text-muted small fw-bold">Đơn tối thiểu</th>
                            <th class="py-3 text-muted small fw-bold">Thời hạn</th>
                            <th class="py-3 text-muted small fw-bold">Lượt dùng</th>
                            <th class="py-3 text-muted small fw-bold">Trạng thái</th>
                            <th class="py-3 text-muted small fw-bold text-end pe-4">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $v): ?>
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="voucher-icon me-3 bg-primary bg-opacity-10 text-primary rounded-3 p-2">
                                        <i class="bi bi-ticket-perforated fs-5"></i>
                                    </div>
                                    <strong class="text-dark"><?php echo htmlspecialchars($v['code']); ?></strong>
                                </div>
                            </td>
                            <td>
                                <span class="small text-muted">
                                    <?php 
                                    if ($v['discount_type'] === 'percentage') echo 'Phần trăm (%)';
                                    elseif ($v['discount_type'] === 'fixed') echo 'Số tiền cố định';
                                    else echo 'Giảm giá vận chuyển';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">
                                    <?php 
                                    if ($v['discount_type'] === 'percentage') {
                                        echo (int)$v['discount_value'] . '%';
                                        if ($v['max_discount']) echo '<div class="x-small text-muted fw-normal">Tối đa ' . number_format($v['max_discount'], 0, ',', '.') . 'đ</div>';
                                    } else {
                                        echo number_format($v['discount_value'], 0, ',', '.') . 'đ';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td><span class="small"><?php echo number_format($v['min_spend'], 0, ',', '.'); ?>đ</span></td>
                            <td>
                                <div class="small">
                                    <div><i class="bi bi-calendar-check me-1 text-success"></i><?php echo date('d/m/Y', strtotime($v['start_date'])); ?></div>
                                    <div><i class="bi bi-calendar-x me-1 text-danger"></i><?php echo date('d/m/Y', strtotime($v['end_date'])); ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <span class="fw-bold text-dark"><?php echo $v['usage_count'] ?? 0; ?></span>
                                    <span class="text-muted">/ <?php echo $v['usage_limit'] ?: '∞'; ?></span>
                                </div>
                                <div class="progress mt-1" style="height: 4px; width: 60px;">
                                    <?php 
                                    $limit = $v['usage_limit'] ?: 100;
                                    $used = $v['usage_count'] ?? 0;
                                    $percent = min(($used / $limit) * 100, 100);
                                    ?>
                                    <div class="progress-bar bg-primary" style="width: <?php echo $percent; ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <?php if ($v['is_active'] && strtotime($v['end_date']) >= time()): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Đang chạy</span>
                                <?php elseif (!$v['is_active']): ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Tạm dừng</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Hết hạn</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-light border" onclick='editVoucher(<?php echo json_encode($v); ?>)' title="Chỉnh sửa">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Xác nhận xóa voucher này?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $v['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Xóa">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($vouchers)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-ticket-perforated fs-1 d-block mb-2"></i>
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
                            <input type="text" name="code" id="vCode" class="form-control form-control-lg fs-6 text-uppercase" placeholder="VD: GIAM20K" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Loại giảm giá</label>
                            <select name="discount_type" id="vType" class="form-select" required>
                                <option value="fixed">Số tiền cố định (đ)</option>
                                <option value="percentage">Phần trăm (%)</option>
                                <option value="shipping">Giảm giá vận chuyển</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Giá trị giảm</label>
                            <input type="number" name="discount_value" id="vValue" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Đơn tối thiểu</label>
                            <input type="number" name="min_spend" id="vMinSpend" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Giảm tối đa (cho %)</label>
                            <input type="number" name="max_discount" id="vMaxDiscount" class="form-control" placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Lượt sử dụng tối đa</label>
                            <input type="number" name="usage_limit" id="vLimit" class="form-control" placeholder="Để trống nếu không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ngày bắt đầu</label>
                            <input type="date" name="start_date" id="vStart" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ngày kết thúc</label>
                            <input type="date" name="end_date" id="vEnd" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="vActive" checked>
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
    document.getElementById('modalAction').value = 'add';
    document.getElementById('modalTitle').innerText = 'Tạo Voucher Mới';
    document.getElementById('voucherId').value = '';
    document.getElementById('vCode').value = '';
    document.getElementById('vValue').value = '';
    document.getElementById('vMinSpend').value = '0';
    document.getElementById('vMaxDiscount').value = '';
    document.getElementById('vLimit').value = '';
    document.getElementById('vStart').value = '';
    document.getElementById('vEnd').value = '';
    document.getElementById('vActive').checked = true;
}

function editVoucher(v) {
    document.getElementById('modalAction').value = 'edit';
    document.getElementById('modalTitle').innerText = 'Chỉnh Sửa Voucher';
    document.getElementById('voucherId').value = v.id;
    document.getElementById('vCode').value = v.code;
    document.getElementById('vType').value = v.discount_type;
    document.getElementById('vValue').value = v.discount_value;
    document.getElementById('vMinSpend').value = v.min_spend;
    document.getElementById('vMaxDiscount').value = v.max_discount || '';
    document.getElementById('vLimit').value = v.usage_limit || '';
    document.getElementById('vStart').value = v.start_date;
    document.getElementById('vEnd').value = v.end_date;
    document.getElementById('vActive').checked = v.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('voucherModal')).show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
                <input type="hidden" name="action" id="modalAction" value="add">
                <input type="hidden" name="id" id="voucherId">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Mã Voucher</label>
                        <input type="text" name="code" id="voucherCode" class="form-control" placeholder="VD: TET2026" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Loại giảm giá</label>
                        <select name="discount_type" id="voucherType" class="form-select">
                            <option value="fixed">Số tiền cố định (đ)</option>
                            <option value="percentage">Phần trăm (%)</option>
                            <option value="shipping">Giảm giá vận chuyển (đ)</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Giá trị giảm</label>
                        <input type="number" name="discount_value" id="voucherValue" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Giảm tối đa (chỉ cho %)</label>
                        <input type="number" name="max_discount" id="voucherMax" class="form-control" placeholder="Để trống nếu không giới hạn">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Đơn hàng tối thiểu</label>
                        <input type="number" name="min_spend" id="voucherMin" class="form-control" value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Giới hạn lượt dùng</label>
                        <input type="number" name="usage_limit" id="voucherLimit" class="form-control" placeholder="Để trống nếu không giới hạn">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Ngày bắt đầu</label>
                        <input type="datetime-local" name="start_date" id="voucherStart" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Ngày kết thúc</label>
                        <input type="datetime-local" name="end_date" id="voucherEnd" class="form-control" required>
                    </div>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" id="voucherActive" checked>
                    <label class="form-check-label">Kích hoạt voucher này</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu Voucher</button>
            </div>
        </form>
    </div>
</div>

<script>
function resetModal() {
    document.getElementById('modalTitle').innerText = 'Thêm Voucher';
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
