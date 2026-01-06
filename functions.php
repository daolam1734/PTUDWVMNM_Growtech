<?php
require_once __DIR__ . '/config/database.php';

if (session_status() == PHP_SESSION_NONE) session_start();

// Auto-login via Remember Me cookie
if (empty($_SESSION['user_id']) && !empty($_COOKIE['weblaptop_remember'])) {
    $token = $_COOKIE['weblaptop_remember'];
    $stmt = $pdo->prepare("SELECT t.*, u.full_name, u.username, u.role FROM auth_tokens t JOIN users u ON t.user_id = u.id WHERE t.expires_at > NOW()");
    $stmt->execute();
    $tokens = $stmt->fetchAll();
    foreach ($tokens as $t) {
        if (password_verify($token, $t['token_hash'])) {
            $_SESSION['user_id'] = $t['user_id'];
            $_SESSION['user_name'] = $t['full_name'];
            $_SESSION['user_role'] = $t['role'];
            if ($t['role'] === 'admin') {
                $_SESSION['admin_logged_in'] = $t['username'];
            }
            break;
        }
    }
}

function getProduct($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getProducts($limit = null) {
    global $pdo;
    $sql = "SELECT p.*, pi.url as image_url 
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0
            WHERE p.is_active = 1 
            ORDER BY p.created_at DESC";
    if ($limit) $sql .= " LIMIT " . (int)$limit;
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getFlashSaleProducts($limit = 20) {
    global $pdo;
    $sql = "SELECT p.*, pi.url as image_url 
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0
            WHERE p.is_active = 1 AND p.sale_price IS NOT NULL AND p.sale_price < p.price
            ORDER BY (p.price - p.sale_price) / p.price DESC
            LIMIT " . (int)$limit;
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getProductImage($product_id) {
    global $pdo;
    // Simple cache for the same request
    static $img_cache = [];
    if (isset($img_cache[$product_id])) return $img_cache[$product_id];

    $stmt = $pdo->prepare("SELECT url FROM product_images WHERE product_id = ? ORDER BY position ASC LIMIT 1");
    $stmt->execute([$product_id]);
    $img = $stmt->fetchColumn();
    
    // Fix broken placeholder URLs if they exist in DB
    if ($img && !preg_match('/^https?:\/\//', $img) && !preg_match('/^\//', $img)) {
        if (preg_match('/^\d+x\d+/', $img)) {
            $img = 'https://placehold.co/' . $img;
        }
    }
    
    // Double check for common placeholder patterns that might have been stripped
    if ($img && strpos($img, 'http') !== 0 && strpos($img, '/') !== 0) {
        if (strpos($img, 'text=') !== false || preg_match('/^\d+x\d+/', $img)) {
            $img = 'https://placehold.co/' . $img;
        }
    }

    $img_cache[$product_id] = $img ?: 'https://placehold.co/600x400?text=No+Image';
    return $img_cache[$product_id];
}

function getProductImages($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY position ASC");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getProductSpecs($product_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM product_specifications WHERE product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Voucher Functions
 */
function getVoucherByCode($code) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM vouchers WHERE code = ? AND is_active = 1 AND (start_date <= NOW() OR start_date IS NULL) AND (end_date >= NOW() OR end_date IS NULL)");
    $stmt->execute([$code]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function calculateDiscount($voucher, $subtotal, $shipping_fee = 0) {
    if (!$voucher) return 0;
    if ($subtotal < $voucher['min_spend']) return 0;
    if ($voucher['usage_limit'] !== null && $voucher['usage_count'] >= $voucher['usage_limit']) return 0;

    $discount = 0;
    if ($voucher['discount_type'] === 'fixed') {
        $discount = $voucher['discount_value'];
        return min($discount, $subtotal);
    } elseif ($voucher['discount_type'] === 'percentage') {
        $discount = $subtotal * ($voucher['discount_value'] / 100);
        if ($voucher['max_discount'] !== null) {
            $discount = min($discount, $voucher['max_discount']);
        }
        return min($discount, $subtotal);
    } elseif ($voucher['discount_type'] === 'shipping') {
        $discount = $voucher['discount_value'];
        return min($discount, $shipping_fee);
    }
    
    return 0;
}
function isAdmin() {
    return !empty($_SESSION['admin_logged_in']);
}

/** AUTH HELPERS **/
function findUserByEmailOrUsername($identity) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$identity, $identity]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function findUserById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createUser($data) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO users (username,email,password,full_name,phone,role,created_at) VALUES (?,?,?,?,?,? ,NOW())");
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $role = isset($data['role']) ? $data['role'] : 'user';
    $stmt->execute([$data['username'],$data['email'],$hash,$data['full_name'],$data['phone'],$role]);
    return $pdo->lastInsertId();
}

function setEmailVerificationToken($user_id, $token, $expires_at) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, verification_expires = ?, email_verified = 0 WHERE id = ?");
    return $stmt->execute([$token, $expires_at, $user_id]);
}

function sendVerificationEmailSimulated($email, $token) {
    // For local/dev: return the verification URL so dev can view it.
    $link = sprintf('%s/weblaptop/auth/verify_email.php?token=%s', rtrim((isset($_SERVER['HTTP_HOST'])? 'http://'.$_SERVER['HTTP_HOST'] : ''), '/'), urlencode($token));
    // In real setup, use mailer to send. Here we return the link for testing.
    return $link;
}

function createPasswordResetToken($user_id, $token, $expires_at) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    return $stmt->execute([$user_id, $token, $expires_at]);
}

function verifyPasswordResetToken($token) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at >= NOW() LIMIT 1");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function markPasswordResetUsed($id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

function resetUserPassword($user_id, $new_password) {
    global $pdo;
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
    return $stmt->execute([$hash, $user_id]);
}

function incrementFailedLogin($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET failed_logins = failed_logins + 1 WHERE id = ?");
    $stmt->execute([$user_id]);
}

function resetFailedLogins($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$user_id]);
}

function lockAccount($user_id, $minutes = 15) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?");
    $stmt->execute([$minutes, $user_id]);
}

/** ADDRESS HELPERS **/
function getUserAddresses($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAddressById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM user_addresses WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/** ORDER HELPERS **/
function createOrder($data) {
    global $pdo;
    try {
        $pdo->beginTransaction();

        // 1. Insert into orders
        $stmt = $pdo->prepare("INSERT INTO orders (order_no, user_id, address_id, voucher_id, subtotal, shipping_fee, shipping_discount, discount, discount_amount, total, order_status, payment_method, payment_status, notes, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'dang_cho', ?, 'dang_cho', ?, NOW())");
        
        $order_no = 'WL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $stmt->execute([
            $order_no,
            $data['user_id'],
            $data['address_id'],
            $data['voucher_id'] ?? null,
            $data['subtotal'],
            $data['shipping_fee'],
            $data['shipping_discount'] ?? 0,
            $data['discount'],
            $data['discount_amount'] ?? $data['discount'],
            $data['total'],
            $data['payment_method'],
            $data['notes']
        ]);
        $order_id = $pdo->lastInsertId();

        // 2. Update voucher usage count if applicable
        if (!empty($data['voucher_id'])) {
            $stmtVoucher = $pdo->prepare("UPDATE vouchers SET usage_count = usage_count + 1 WHERE id = ?");
            $stmtVoucher->execute([$data['voucher_id']]);
        }

        // 3. Insert into order_items and update stock
        $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, sku, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmtMovement = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, type, reference, note) VALUES (?, ?, 'sale', ?, 'Khách đặt hàng')");

        foreach ($data['items'] as $item) {
            $itemSubtotal = $item['price'] * $item['quantity'];
            $stmtItem->execute([
                $order_id,
                $item['id'],
                $item['name'],
                $item['sku'],
                $item['quantity'],
                $item['price'],
                $itemSubtotal
            ]);

            // Update stock
            $stmtStock->execute([$item['quantity'], $item['id']]);
            
            // Record movement
            $stmtMovement->execute([$item['id'], -$item['quantity'], $order_no]);
        }

        $pdo->commit();
        return $order_id;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order creation failed: " . $e->getMessage());
        return false;
    }
}

function cancelOrder($order_id, $user_id) {
    global $pdo;
    try {
        $pdo->beginTransaction();

        // 1. Fetch order and check ownership/status
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch();

        if (!$order || $order['order_status'] !== 'dang_cho') {
            throw new Exception("Đơn hàng không thể hủy.");
        }

        // 2. Update order status
        $stmtUpdate = $pdo->prepare("UPDATE orders SET order_status = 'huy' WHERE id = ?");
        $stmtUpdate->execute([$order_id]);

        // 3. Restore stock and record movement
        $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$order_id]);
        $items = $stmtItems->fetchAll();

        $stmtStock = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        $stmtMovement = $pdo->prepare("INSERT INTO stock_movements (product_id, change_qty, type, reference, note) VALUES (?, ?, 'return', ?, 'Khách hủy đơn hàng')");

        foreach ($items as $item) {
            $stmtStock->execute([$item['quantity'], $item['product_id']]);
            $stmtMovement->execute([$item['product_id'], $item['quantity'], $order['order_no']]);
        }

        // 4. Restore voucher usage if applicable
        if (!empty($order['voucher_id'])) {
            $stmtVoucher = $pdo->prepare("UPDATE vouchers SET usage_count = GREATEST(0, usage_count - 1) WHERE id = ?");
            $stmtVoucher->execute([$order['voucher_id']]);
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order cancellation failed: " . $e->getMessage());
        return false;
    }
}

function isAccountLocked($user) {
    if (!$user) return false;
    if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) return true;
    return false;
}

/** Flash messages (UI) **/
function set_flash($type, $message) {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flash() {
    if (session_status() == PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['flash'])) return [];
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

function display_flash() {
    $items = get_flash();
    if (empty($items)) return;
    foreach ($items as $it) {
        $type = $it['type'];
        $msg = $it['message'];
        $cls = 'info';
        if ($type === 'error' || $type === 'danger') $cls = 'danger';
        if ($type === 'success') $cls = 'success';
        if ($type === 'warning') $cls = 'warning';
        echo '<div class="container mt-2">';
        echo '<div class="alert alert-' . $cls . ' flash-alert" role="alert">' . $msg . '</div>';
        echo '</div>';
    }
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) return 'n-a';
    return $text;
}

