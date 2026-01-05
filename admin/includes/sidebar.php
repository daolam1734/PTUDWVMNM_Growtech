<style>
    .admin-sidebar {
        width: var(--sidebar-width);
        background: #fff;
        border-right: 1px solid #eee;
        padding-top: 10px;
        flex-shrink: 0;
        height: calc(100vh - 70px);
        position: sticky;
        top: 70px;
        overflow-y: auto;
    }
    .sidebar-group-title {
        padding: 15px 24px 8px;
        font-size: 11px;
        font-weight: 700;
        color: #adb5bd;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .sidebar-item {
        display: flex;
        align-items: center;
        padding: 12px 24px;
        color: #495057;
        text-decoration: none;
        font-size: 14px;
        transition: all .2s;
        border-left: 3px solid transparent;
    }
    .sidebar-item i {
        font-size: 18px;
        margin-right: 12px;
        width: 20px;
        text-align: center;
        color: #adb5bd;
    }
    .sidebar-item:hover {
        color: var(--shopee-orange);
        background: #fff5f2;
    }
    .sidebar-item:hover i {
        color: var(--shopee-orange);
    }
    .sidebar-item.active {
        color: var(--shopee-orange);
        background: #fff5f2;
        font-weight: 600;
        border-left-color: var(--shopee-orange);
    }
    .sidebar-item.active i {
        color: var(--shopee-orange);
    }
    .sidebar-badge {
        margin-left: auto;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
    }
</style>

<div class="admin-sidebar">
    <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i> Tổng quan
    </a>

    <div class="sidebar-group-title">Quản Lý Đơn Hàng</div>
    <a href="orders.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
        <i class="bi bi-cart-check"></i> Tất Cả Đơn Hàng
        <?php
        $pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'dang_cho'")->fetchColumn();
        if ($pending_count > 0) echo "<span class='sidebar-badge bg-danger text-white'>$pending_count</span>";
        ?>
    </a>

    <div class="sidebar-group-title">Quản Lý Sản Phẩm</div>
    <a href="products.php" class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'edit_product.php']) ? 'active' : ''; ?>">
        <i class="bi bi-box-seam"></i> Tất Cả Sản Phẩm
    </a>
    <a href="add_product.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'add_product.php' ? 'active' : ''; ?>">
        <i class="bi bi-plus-circle"></i> Thêm Sản Phẩm
    </a>
    <a href="categories.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
        <i class="bi bi-tags"></i> Danh Mục
    </a>
    <a href="brands.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>">
        <i class="bi bi-building"></i> Thương Hiệu
    </a>

    <div class="sidebar-group-title">Marketing & Khuyến Mãi</div>
    <a href="flash_sales.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'flash_sales.php' ? 'active' : ''; ?>">
        <i class="bi bi-lightning-charge"></i> Flash Sale
    </a>
    <a href="vouchers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'vouchers.php' ? 'active' : ''; ?>">
        <i class="bi bi-ticket-perforated"></i> Mã Giảm Giá
    </a>
    <a href="banners.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'banners.php' ? 'active' : ''; ?>">
        <i class="bi bi-image"></i> Quản Lý Banner
    </a>

    <div class="sidebar-group-title">Khách Hàng & Phản Hồi</div>
    <a href="customers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
        <i class="bi bi-people"></i> Khách Hàng
    </a>
    <a href="reviews.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
        <i class="bi bi-chat-left-text"></i> Đánh Giá
        <?php
        $pending_reviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'dang_cho'")->fetchColumn();
        if ($pending_reviews > 0) echo "<span class='sidebar-badge bg-warning text-dark'>$pending_reviews</span>";
        ?>
    </a>
    
    <div class="sidebar-group-title">Báo Cáo & Hệ Thống</div>
    <a href="analytics.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
        <i class="bi bi-graph-up-arrow"></i> Phân Tích Bán Hàng
    </a>
    <a href="settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i class="bi bi-gear"></i> Cài Đặt Shop
    </a>
    <div class="mt-4 px-4 mb-4">
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
            <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
        </a>
    </div>
</div>
