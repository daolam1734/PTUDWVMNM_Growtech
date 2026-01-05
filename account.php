<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

// 1. Check login
if (empty($_SESSION['user_id'])) {
    set_flash('warning', 'Vui lòng đăng nhập để xem thông tin tài khoản.');
    header('Location: /weblaptop/auth/login.php?next=/weblaptop/account.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user = findUserById($user_id);

// 2. Handle Add Address
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_address'])) {
    $recipient_name = trim($_POST['recipient_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address_line1 = trim($_POST['address_line1'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $label = trim($_POST['label'] ?? 'Nhà riêng');
    $is_default = isset($_POST['is_default']) ? 1 : 0;

    $errors = [];
    if (!$recipient_name) $errors[] = 'Tên người nhận không được để trống.';
    if (!$phone) $errors[] = 'Số điện thoại không được để trống.';
    if (!$address_line1) $errors[] = 'Địa chỉ không được để trống.';

    if (empty($errors)) {
        global $pdo;
        
        // If this is set as default, unset other defaults
        if ($is_default) {
            $stmt = $pdo->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }

        $stmt = $pdo->prepare("INSERT INTO user_addresses (user_id, label, recipient_name, phone, address_line1, city, district, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $label, $recipient_name, $phone, $address_line1, $city, $district, $is_default])) {
            set_flash('success', 'Thêm địa chỉ mới thành công.');
            header('Location: account.php');
            exit;
        } else {
            $errors[] = 'Có lỗi xảy ra, vui lòng thử lại.';
        }
    }
    if (!empty($errors)) set_flash('error', implode('<br>', $errors));
}

// 3. Handle Delete Address
if (isset($_GET['delete_address'])) {
    $addr_id = (int)$_GET['delete_address'];
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM user_addresses WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$addr_id, $user_id])) {
        set_flash('success', 'Đã xóa địa chỉ.');
    }
    header('Location: account.php');
    exit;
}

$addresses = getUserAddresses($user_id);

require_once __DIR__ . '/includes/header.php';
?>

<style>
    :root {
        --tet-red: #d32f2f;
        --tet-gold: #ffc107;
        --tet-bg: #f8f9fa;
    }
    body { background-color: var(--tet-bg); }
    
    /* Sidebar Styling */
    .notification-item {
        transition: all 0.2s;
        border-left: 4px solid transparent;
    }
    .notification-item.unread {
        background-color: #fff9f9;
        border-left-color: var(--tet-red);
    }
    .notification-item:hover {
        background-color: #f8f9fa;
    }
    .icon-box {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .sidebar-link.active {
        background-color: var(--tet-red) !important;
        border-color: var(--tet-red) !important;
    }

    /* Card Styling */
    .account-card {
        background: #fff;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .account-card-header {
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
        background: #fafafa;
        font-weight: 700;
        color: #333;
    }
    .account-card-body { padding: 20px; }

    .address-card {
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 15px;
        height: 100%;
        transition: all 0.3s;
        position: relative;
    }
    .address-card:hover {
        border-color: var(--tet-gold);
        box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .address-card.default {
        border-color: var(--tet-red);
        background: #fff5f5;
    }
    
    .btn-add-address {
        background: var(--tet-red);
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 8px 20px;
        font-weight: 600;
        transition: all 0.3s;
    }
    .btn-add-address:hover {
        background: #b71c1c;
        color: #fff;
        transform: translateY(-1px);
    }

    .modal-content {
        border-radius: 15px;
        border: none;
    }
    .modal-header {
        background: var(--tet-red);
        color: #fff;
        border-radius: 15px 15px 0 0;
    }
    .modal-header .btn-close { filter: brightness(0) invert(1); }
</style>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="p-4 text-center border-bottom bg-light">
                    <div class="avatar-placeholder mb-3 mx-auto d-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm" style="width: 80px; height: 80px;">
                        <i class="bi bi-person-circle fs-1 text-danger"></i>
                    </div>
                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h6>
                    <small class="text-muted">Thành viên GrowTech</small>
                </div>
                <div class="list-group list-group-flush">
                    <a href="account.php" class="list-group-item list-group-item-action py-3 px-4 border-0 active sidebar-link">
                        <i class="bi bi-person-circle me-2"></i> Thông tin tài khoản
                    </a>
                    <a href="orders.php" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <i class="bi bi-bag-check me-2"></i> Đơn hàng của tôi
                    </a>
                    <a href="notifications.php" class="list-group-item list-group-item-action py-3 px-4 border-0">
                        <i class="bi bi-bell me-2"></i> Thông báo
                    </a>
                    <a href="/weblaptop/auth/logout.php" class="list-group-item list-group-item-action py-3 px-4 border-0 text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Hồ Sơ Của Tôi</h4>
                <div class="text-muted small">Quản lý thông tin hồ sơ để bảo mật tài khoản</div>
            </div>
            
            <!-- Profile Info -->
            <div class="account-card">
                <div class="account-card-header">
                    <i class="fas fa-info-circle me-2 text-primary"></i>Thông tin cá nhân
                </div>
                <div class="account-card-body">
                    <div class="row mb-3">
                        <div class="col-sm-3 text-muted">Họ và tên</div>
                        <div class="col-sm-9 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-3 text-muted">Email</div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-sm-3 text-muted">Số điện thoại</div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($user['phone'] ?: 'Chưa cập nhật'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Addresses -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Địa chỉ giao hàng</h5>
                <button class="btn-add-address" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                    <i class="fas fa-plus me-2"></i>Thêm địa chỉ mới
                </button>
            </div>

            <?php if (empty($addresses)): ?>
                <div class="account-card p-5 text-center">
                    <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Bạn chưa có địa chỉ giao hàng nào.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="col-md-6 mb-4">
                            <div class="address-card <?php echo $addr['is_default'] ? 'default' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge <?php echo $addr['is_default'] ? 'bg-danger' : 'bg-secondary'; ?> small">
                                        <?php echo htmlspecialchars($addr['label']); ?>
                                    </span>
                                    <div class="dropdown">
                                        <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item text-danger" href="?delete_address=<?php echo $addr['id']; ?>" onclick="return confirm('Xóa địa chỉ này?')">Xóa địa chỉ</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="fw-bold mb-1"><?php echo htmlspecialchars($addr['recipient_name']); ?></div>
                                <div class="text-muted small mb-2"><?php echo htmlspecialchars($addr['phone']); ?></div>
                                <div class="small text-dark">
                                    <?php echo htmlspecialchars($addr['address_line1']); ?><br>
                                    <?php echo htmlspecialchars($addr['district']); ?>, <?php echo htmlspecialchars($addr['city']); ?>
                                </div>
                                <?php if ($addr['is_default']): ?>
                                    <div class="mt-2 text-danger small fw-bold">
                                        <i class="fas fa-check-circle me-1"></i>Địa chỉ mặc định
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Thêm Địa Chỉ Mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Tên người nhận</label>
                        <input type="text" name="recipient_name" class="form-control" placeholder="Nhập họ tên" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" placeholder="Nhập số điện thoại" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Địa chỉ chi tiết</label>
                    <input type="text" name="address_line1" class="form-control" placeholder="Số nhà, tên đường..." required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Quận/Huyện</label>
                        <input type="text" name="district" class="form-control" placeholder="Nhập quận/huyện">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Tỉnh/Thành phố</label>
                        <input type="text" name="city" class="form-control" placeholder="Nhập tỉnh/thành phố">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Loại địa chỉ</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="label" id="labelHome" value="Nhà riêng" checked>
                            <label class="form-check-label" for="labelHome">Nhà riêng</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="label" id="labelOffice" value="Văn phòng">
                            <label class="form-check-label" for="labelOffice">Văn phòng</label>
                        </div>
                    </div>
                </div>
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_default" id="is_default" checked>
                    <label class="form-check-label small" for="is_default">Đặt làm địa chỉ mặc định</label>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-toggle="modal">Trở lại</button>
                <button type="submit" name="add_address" class="btn btn-danger rounded-pill px-4">Hoàn thành</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

