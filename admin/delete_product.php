<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (session_status() == PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    try {
        // Check if product has orders
        $stmt_orders = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $stmt_orders->execute([$id]);
        $has_orders = $stmt_orders->fetchColumn() > 0;

        if ($has_orders) {
            // Soft delete (deactivate) if there are orders to preserve history
            $stmt_deactivate = $pdo->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $stmt_deactivate->execute([$id]);
            set_flash('warning', 'Sản phẩm đã có trong đơn hàng nên không thể xóa dữ liệu hoàn toàn (để giữ lịch sử giao dịch). Đã chuyển sang trạng thái tạm ngưng kinh doanh.');
        } else {
            // Hard delete if no orders
            $pdo->beginTransaction();

            // 1. Delete from cart_items first (FK is RESTRICT)
            $stmt_cart = $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?");
            $stmt_cart->execute([$id]);

            // 2. product_specifications, product_images, reviews, stock_movements 
            // have ON DELETE CASCADE in schema, so they will be deleted automatically.
            
            // 3. Delete the product
            $stmt_del = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt_del->execute([$id]);

            $pdo->commit();
            set_flash('success', 'Xóa sản phẩm thành công.');
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash('danger', 'Lỗi: ' . $e->getMessage());
    }
}

header('Location: products.php');
exit;
