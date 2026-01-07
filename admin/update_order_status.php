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

        // fetch current status
        $stmt_curr = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt_curr->execute([$order_id]);
        $curr_order = $stmt_curr->fetch();

        if (!$curr_order) throw new Exception("Không tìm thấy đơn hàng.");

        $payment_status = $curr_order['payment_status'];
        $shipping_status = $curr_order['shipping_status'];

        // Logic side-effects
        if ($status === 'COMPLETED') {
            $payment_status = 'PAID';
            $shipping_status = 'DELIVERED';
        } elseif ($status === 'DELIVERED') {
            $shipping_status = 'DELIVERED';
        } elseif ($status === 'SHIPPING') {
            $shipping_status = 'SHIPPING';
        } elseif ($status === 'CANCELLED') {
            $shipping_status = 'CANCELLED';
        }

        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET 
            order_status = ?, 
            payment_status = ?,
            shipping_status = ?,
            updated_at = NOW() 
            WHERE id = ?");
        $stmt->execute([$status, $payment_status, $shipping_status, $order_id]);

        // Create notification for user
        $status_label = get_status_label($status);
        $notif_title = "Cập nhật đơn hàng #$order_id";
        $notif_content = "Đơn hàng của bạn đã chuyển sang trạng thái: $status_label";
        
        // Detailed message based on status
        if ($status === 'SHIPPING') {
            $notif_content = "Đơn hàng #$order_id đang được giao đến bạn. Vui lòng chú ý điện thoại.";
        } elseif ($status === 'DELIVERED') {
            $notif_content = "Đơn hàng #$order_id đã giao thành công. Đừng quên đánh giá sản phẩm nhé!";
        } elseif ($status === 'CANCELLED') {
            $notif_content = "Đơn hàng #$order_id của bạn đã bị hủy.";
        }

        createNotification($curr_order['user_id'], $notif_title, $notif_content, 'order', "/weblaptop/orders.php");

        // Notification for payment status changed to PAID
        if ($payment_status === 'PAID' && $curr_order['payment_status'] !== 'PAID') {
            createNotification(
                $curr_order['user_id'], 
                "Xác nhận thanh toán", 
                "Chúng tôi đã nhận được thanh toán cho đơn hàng #$order_id. Cảm ơn bạn!", 
                'order', 
                "/weblaptop/orders.php"
            );
        }

        $pdo->commit();
        set_flash("success", "Cập nhật trạng thái đơn hàng #$order_id thành công.");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        set_flash("error", "Có lỗi xảy ra: " . $e->getMessage());
    }

    header("Location: order_detail.php?id=$order_id");
    exit;
}

header("Location: orders.php");
exit;
