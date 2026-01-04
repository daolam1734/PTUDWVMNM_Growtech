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

<div class="container my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="list-group mb-4">
                <a href="account.php" class="list-group-item list-group-item-action active">Thông tin tài khoản</a>
                <a href="orders.php" class="list-group-item list-group-item-action">Đơn hàng của tôi</a>
                <a href="/weblaptop/auth/logout.php" class="list-group-item list-group-item-action text-danger">Đăng xuất</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <h2 class="mb-4">Tài khoản của tôi</h2>
            
            <!-- Profile Info -->
            <div class="card mb-4">
                <div class="card-header"><h5>Thông tin cá nhân</h5></div>
                <div class="card-body">
                    <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                </div>
            </div>

            <!-- Addresses -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4>Địa chỉ giao hàng</h4>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">Thêm địa chỉ mới</button>
            </div>

            <?php if (empty($addresses)): ?>
                <div class="alert alert-info">Bạn chưa có địa chỉ giao hàng nào.</div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($addresses as $addr): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 <?php echo $addr['is_default'] ? 'border-primary' : ''; ?>">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="card-title">
                                            <?php echo htmlspecialchars($addr['label']); ?>
                                            <?php if ($addr['is_default']): ?>
                                                <span class="badge bg-primary">Mặc định</span>
                                            <?php endif; ?>
                                        </h6>
                                        <a href="?delete_address=<?php echo $addr['id']; ?>" class="text-danger" onclick="return confirm('Xóa địa chỉ này?')"><span class="sparkle-effect"></span></a>
                                    </div>
                                    <p class="mb-1"><strong><?php echo htmlspecialchars($addr['recipient_name']); ?></strong></p>
                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($addr['phone']); ?></p>
                                    <p class="mb-0 small">
                                        <?php echo htmlspecialchars($addr['address_line1']); ?><br>
                                        <?php echo htmlspecialchars($addr['district']); ?>, <?php echo htmlspecialchars($addr['city']); ?>
                                    </p>
                                </div>
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
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm địa chỉ mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Tên người nhận</label>
                    <input type="text" name="recipient_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Địa chỉ chi tiết</label>
                    <input type="text" name="address_line1" class="form-control" placeholder="Số nhà, tên đường..." required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quận/Huyện</label>
                        <input type="text" name="district" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tỉnh/Thành phố</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Loại địa chỉ</label>
                    <select name="label" class="form-select">
                        <option value="Nhà riêng">Nhà riêng</option>
                        <option value="Văn phòng">Văn phòng</option>
                    </select>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_default" id="is_default" checked>
                    <label class="form-check-label" for="is_default">Đặt làm mặc định</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" name="add_address" class="btn btn-primary">Lưu địa chỉ</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
