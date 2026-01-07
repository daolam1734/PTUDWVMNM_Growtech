<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../functions.php';

$errors = [];
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $agree = isset($_POST['agree']);

    // basic validation
    if ($full_name === '') $errors[] = 'Họ và tên bắt buộc.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (!preg_match('/^0[0-9]{9,10}$/', $phone)) $errors[] = 'Số điện thoại không hợp lệ.';
    if (strlen($password) < 8 || !preg_match('/[0-9]/', $password) || !preg_match('/[A-Za-z]/', $password)) $errors[] = 'Mật khẩu cần ít nhất 8 ký tự, bao gồm chữ và số.';
    if ($password !== $confirm) $errors[] = 'Mật khẩu xác nhận không khớp.';
    if (!$agree) $errors[] = 'Bạn cần đồng ý Điều khoản & Chính sách.';

    // existing email
    if (findUserByEmailOrUsername($email)) $errors[] = 'Email đã được sử dụng.';

    if (empty($errors)) {
        $userdata = [
            'username' => $username ?: strstr($email, '@', true),
            'email' => $email,
            'password' => $password,
            'full_name' => $full_name,
            'phone' => $phone
        ];
        $uid = createUser($userdata);
        if ($uid) {
            // create verification token
            $token = bin2hex(random_bytes(16));
            $expires = date('Y-m-d H:i:s', strtotime('+2 day'));
            setEmailVerificationToken($uid, $token, $expires);
            $link = sendVerificationEmailSimulated($email, $token);

            // Welcome Notification
            createNotification(
                $uid, 
                "Chào mừng bạn đến với Growtech", 
                "Cảm ơn bạn đã tin tưởng và đăng ký tài khoản. Hãy bắt đầu mua sắm ngay hôm nay!", 
                'system'
            );

            set_flash('success', "Đăng ký thành công. Vui lòng kiểm tra email để xác thực tài khoản. (Link test: <a href='".htmlspecialchars($link)."' target='_blank'>Xác thực</a>)");
            header('Location: login.php');
            exit;
        } else {
            $errors[] = 'Đăng ký thất bại, thử lại sau.';
        }
    }
    if (!empty($errors)) set_flash('error', implode('<br>', $errors));
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center my-5">
  <div class="col-md-6">
    <div class="card p-4">
      <h4>Tạo tài khoản mới</h4>

      <form method="post">
        <div class="mb-2"><label class="form-label">Họ và tên</label><input class="form-control" name="full_name" required></div>
        <div class="mb-2"><label class="form-label">Email</label><input class="form-control" name="email" type="email" required></div>
        <div class="mb-2"><label class="form-label">Số điện thoại</label><input class="form-control" name="phone" required></div>
        <div class="mb-2"><label class="form-label">Tên đăng nhập (tuỳ chọn)</label><input class="form-control" name="username"></div>
        <div class="mb-2"><label class="form-label">Mật khẩu</label><input class="form-control" name="password" type="password" required></div>
        <div class="mb-2"><label class="form-label">Xác nhận mật khẩu</label><input class="form-control" name="confirm_password" type="password" required></div>
        <div class="mb-3 form-check"><input class="form-check-input" id="agree" name="agree" type="checkbox"><label class="form-check-label" for="agree">Tôi đồng ý <a href="/weblaptop/terms.php">Điều khoản & Chính sách</a></label></div>
        <button class="btn btn-primary" type="submit">Đăng ký</button>
      </form>
      <div class="mt-2">Đã có tài khoản? <a href="/weblaptop/auth/login.php">Đăng nhập</a></div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>