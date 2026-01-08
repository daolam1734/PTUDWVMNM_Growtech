<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/functions.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$id = (int)($_POST['id'] ?? 0);

if ($action === 'update') {
    $qty = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;
    
    if (isset($_SESSION['cart'][$id])) {
        // Check stock
        $stmtStock = $pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
        $stmtStock->execute([$id]);
        $product = $stmtStock->fetch(PDO::FETCH_ASSOC);
        
        if ($product && $qty > $product['stock']) {
            echo json_encode([
                'success' => false, 
                'message' => "Sản phẩm {$product['name']} chỉ còn {$product['stock']} sản phẩm trong kho.",
                'max_stock' => $product['stock']
            ]);
            exit;
        }

        $_SESSION['cart'][$id] = $qty;
        
        // Recalculate totals
        $subtotal = 0;
        $item_total = 0;
        
        if (!empty($_SESSION['cart'])) {
            $ids = implode(",", array_map("intval", array_keys($_SESSION['cart'])));
            $stmt = $pdo->query("SELECT id, price FROM products WHERE id IN ($ids)");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as $p) {
                $p_qty = $_SESSION['cart'][$p['id']];
                $p_subtotal = $p_qty * $p['price'];
                $subtotal += $p_subtotal;
                if ($p['id'] == $id) {
                    $item_total = $p_subtotal;
                }
            }
        }
        
        $discount = 0;
        $shipping_discount = 0;
        if (isset($_SESSION['voucher'])) {
            if ($_SESSION['voucher']['discount_type'] === 'shipping') {
                $shipping_discount = calculateDiscount($_SESSION['voucher'], $subtotal, 30000);
            } else {
                $discount = calculateDiscount($_SESSION['voucher'], $subtotal, 30000);
            }
            if ($discount == 0 && $shipping_discount == 0) unset($_SESSION['voucher']);
        }
        
        $total = $subtotal + 30000 - $discount - $shipping_discount;
        
        echo json_encode([
            'success' => true,
            'item_total' => number_format($item_total, 0, ',', '.') . ' đ',
            'subtotal' => number_format($subtotal, 0, ',', '.') . ' đ',
            'discount' => number_format($discount, 0, ',', '.') . ' đ',
            'shipping_discount' => number_format($shipping_discount, 0, ',', '.') . ' đ',
            'total' => number_format($total, 0, ',', '.') . ' đ',
            'cart_count' => array_sum($_SESSION['cart'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not in cart']);
    }
} elseif ($action === 'remove') {
    if (isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        
        $subtotal = 0;
        if (!empty($_SESSION['cart'])) {
            $ids = implode(",", array_map("intval", array_keys($_SESSION['cart'])));
            $stmt = $pdo->query("SELECT id, price FROM products WHERE id IN ($ids)");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $p) {
                $subtotal += $_SESSION['cart'][$p['id']] * $p['price'];
            }
        }
        
        $discount = 0;
        $shipping_discount = 0;
        if (isset($_SESSION['voucher'])) {
            if ($_SESSION['voucher']['discount_type'] === 'shipping') {
                $shipping_discount = calculateDiscount($_SESSION['voucher'], $subtotal, 30000);
            } else {
                $discount = calculateDiscount($_SESSION['voucher'], $subtotal, 30000);
            }
            if ($discount == 0 && $shipping_discount == 0) unset($_SESSION['voucher']);
        }
        
        $total = $subtotal + 30000 - $discount - $shipping_discount;
        
        echo json_encode([
            'success' => true,
            'subtotal' => number_format($subtotal, 0, ',', '.') . ' đ',
            'discount' => number_format($discount, 0, ',', '.') . ' đ',
            'shipping_discount' => number_format($shipping_discount, 0, ',', '.') . ' đ',
            'total' => number_format($total, 0, ',', '.') . ' đ',
            'cart_count' => !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0,
            'cart_empty' => empty($_SESSION['cart'])
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not in cart']);
    }
} elseif ($action === 'add') {
    $qty = (int)($_POST['qty'] ?? 1);
    if ($qty < 1) $qty = 1;

    // Check stock
    $stmtStock = $pdo->prepare("SELECT stock, name FROM products WHERE id = ?");
    $stmtStock->execute([$id]);
    $product = $stmtStock->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
        exit;
    }

    $current_qty = isset($_SESSION['cart'][$id]) ? $_SESSION['cart'][$id] : 0;
    $new_qty = $current_qty + $qty;

    if ($new_qty > $product['stock']) {
        echo json_encode([
            'success' => false,
            'message' => "Sản phẩm {$product['name']} chỉ còn {$product['stock']} sản phẩm trong kho. Bạn đã có {$current_qty} trong giỏ.",
            'max_stock' => $product['stock']
        ]);
        exit;
    }

    $_SESSION['cart'][$id] = $new_qty;

    // Get dropdown HTML for instant update
    $dropdown_html = '';
    if (!empty($_SESSION["cart"])) {
        $cart_ids = array_keys($_SESSION["cart"]);
        $placeholders = implode(',', array_fill(0, count($cart_ids), '?'));
        $stmt_cart = $pdo->prepare("SELECT p.*, pi.url as image_url FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.position = 0 WHERE p.id IN ($placeholders) GROUP BY p.id");
        $stmt_cart->execute($cart_ids);
        $cart_items = $stmt_cart->fetchAll();
        
        $items_html = '';
        foreach ($cart_items as $item) {
            $img = getProductImage($item['id']);
            $p_price = number_format($item['price'], 0, ',', '.') . ' đ';
            $p_qty = $_SESSION["cart"][$item['id']];
            $p_url = BASE_URL . "product.php?id=" . $item['id'];
            $p_name = htmlspecialchars($item['name']);
            
            $items_html .= "
                <a href=\"$p_url\" class=\"cart-dropdown-item\">
                  <img src=\"$img\" alt=\"\">
                  <div class=\"cart-dropdown-info\">
                    <div class=\"cart-dropdown-name\">$p_name</div>
                    <div class=\"cart-dropdown-price\">$p_price</div>
                  </div>
                  <div class=\"small text-muted ms-2\">x$p_qty</div>
                </a>";
        }
        
        $cart_count_text = count($_SESSION['cart']) . " sản phẩm mới thêm";
        $dropdown_html = "
            <div class=\"cart-dropdown-header\">Sản phẩm mới thêm</div>
            <div class=\"cart-dropdown-body\">
                $items_html
                <div class=\"dropdown-divider m-0\"></div>
                <div class=\"p-2 text-center\" style=\"background: #f9f9f9;\">
                    <div class=\"small mb-2\" style=\"color: #000;\">$cart_count_text</div>
                    <a href=\"/weblaptop/cart.php\" class=\"btn btn-danger w-100 py-2\" style=\"background-color: var(--tet-red); border: none; font-weight: 600; border-radius: 2px;\">Xem Giỏ Hàng</a>
                </div>
            </div>";
    }

    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm vào giỏ hàng thành công!',
        'cart_count' => array_sum($_SESSION['cart']),
        'dropdown_html' => $dropdown_html
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
