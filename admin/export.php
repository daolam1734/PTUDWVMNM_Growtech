<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

$type = $_GET['type'] ?? 'orders';
$filename = $type . "_report_" . date('Ymd_His') . ".csv";

// Prevent output before headers
ob_end_clean();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

if ($type === 'orders') {
    // Header
    fputcsv($output, ['Mã đơn hàng', 'Khách hàng', 'Số điện thoại', 'Ngày đặt', 'Tổng tiền', 'Trạng thái', 'Thanh toán', 'Voucher']);

    $query = "
        SELECT o.order_no, u.full_name as customer_name, ua.phone as customer_phone, 
               o.created_at, o.total, o.order_status, o.payment_status, v.code as voucher_code
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN user_addresses ua ON o.address_id = ua.id
        LEFT JOIN vouchers v ON o.voucher_id = v.id
        ORDER BY o.created_at DESC
    ";
    
    $status_labels = [
        'PENDING' => 'Chờ xác nhận',
        'CONFIRMED' => 'Đã xác nhận',
        'PROCESSING' => 'Đang xử lý',
        'SHIPPING' => 'Đang giao',
        'DELIVERED' => 'Đã giao',
        'COMPLETED' => 'Hoàn thành',
        'CANCELLED' => 'Đã hủy'
    ];

    $stmt = $pdo->query($query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['order_status'] = $status_labels[$row['order_status']] ?? $row['order_status'];
        $row['payment_status'] = $row['payment_status'] === 'PAID' ? 'Đã thanh toán' : 'Chưa thanh toán';
        $row['total'] = number_format($row['total'], 0, ',', '.') . 'đ';
        fputcsv($output, [
            $row['order_no'],
            $row['customer_name'] ?: 'Khách vãng lai',
            $row['customer_phone'] ?: '',
            $row['created_at'],
            $row['total'],
            $row['order_status'],
            $row['payment_status'],
            $row['voucher_code'] ?: ''
        ]);
    }

} elseif ($type === 'revenue') {
    fputcsv($output, ['Ngày', 'Doanh thu (VNĐ)', 'Số đơn hàng']);

    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, SUM(total) as daily_total, COUNT(*) as order_count
        FROM orders 
        WHERE order_status NOT IN ('CANCELLED')
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['date'],
            number_format($row['daily_total'], 0, ',', '.'),
            $row['order_count']
        ]);
    }

} elseif ($type === 'products') {
    fputcsv($output, ['Sản phẩm', 'Danh mục', 'Số lượng đã bán', 'Doanh thu dự kiến']);

    $stmt = $pdo->query("
        SELECT p.name, c.name as category_name, SUM(oi.quantity) as total_sold, SUM(oi.unit_price * oi.quantity) as total_revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN categories c ON p.category_id = c.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.order_status NOT IN ('CANCELLED')
        GROUP BY p.id
        ORDER BY total_sold DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['name'],
            $row['category_name'],
            $row['total_sold'],
            number_format($row['total_revenue'], 0, ',', '.') . 'đ'
        ]);
    }
} elseif ($type === 'customers') {
    fputcsv($output, ['ID', 'Họ tên', 'Email', 'Số điện thoại', 'Ngày đăng ký', 'Tổng đơn hàng', 'Tổng chi tiêu']);

    $stmt = $pdo->query("
        SELECT 
            u.id, u.full_name, u.email, u.phone, u.created_at,
            COUNT(o.id) as total_orders,
            SUM(CASE WHEN o.order_status NOT IN ('CANCELLED', 'RETURNED') THEN o.total ELSE 0 END) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.role = 'user'
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['full_name'],
            $row['email'],
            $row['phone'],
            $row['created_at'],
            $row['total_orders'],
            number_format($row['total_spent'] ?: 0, 0, ',', '.') . 'đ'
        ]);
    }
} elseif ($type === 'inventory') {
    fputcsv($output, ['ID', 'Tên sản phẩm', 'Danh mục', 'Thương hiệu', 'Giá gốc', 'Giá khuyến mãi', 'Tồn kho']);

    $stmt = $pdo->query("
        SELECT p.id, p.name, c.name as category_name, b.name as brand_name, p.price, p.sale_price, p.stock
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN brands b ON p.brand_id = b.id
        ORDER BY p.id DESC
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['category_name'],
            $row['brand_name'],
            number_format($row['price'], 0, ',', '.') . 'đ',
            number_format($row['sale_price'], 0, ',', '.') . 'đ',
            $row['stock']
        ]);
    }
}

fclose($output);
exit;
