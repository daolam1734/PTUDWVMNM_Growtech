<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $note = trim($_POST['note']);

    try {
        $pdo->beginTransaction();

        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $order_id]);

        // Add to order history/notes if needed (optional, but good for UX)
        // For now, we just update the main status.

        $pdo->commit();
        set_flash("success", "Cập nhật trạng thái đơn hàng #$order_id thành công.");
    } catch (Exception $e) {
        $pdo->rollBack();
        set_flash("error", "Có lỗi xảy ra: " . $e->getMessage());
    }

    header("Location: order_detail.php?id=$order_id");
    exit;
}

header("Location: orders.php");
exit;
