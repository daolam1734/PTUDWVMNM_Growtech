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
        color: var(--accent-color);
        background: #f8f9fa;
    }
    .sidebar-item:hover i {
        color: var(--accent-color);
    }
    .sidebar-item.active {
        color: var(--accent-color);
        background: #f8f9fa;
        font-weight: 600;
        border-left-color: var(--accent-color);
    }
    .sidebar-item.active i {
        color: var(--accent-color);
    }
    .sidebar-badge {
        margin-left: auto;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 10px;
    }
</style>

<div class="admin-sidebar">
    <div class="px-3 mb-3">
        <a href="../index.php" target="_blank" class="btn btn-light w-100 rounded-pill border shadow-sm py-2 small fw-bold">
            <i class="bi bi-box-arrow-up-right me-2"></i> Xem Website
        </a>
    </div>

    <a href="dashboard.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i> Tổng quan
    </a>

    <div class="sidebar-group-title">Kinh Doanh & Báo Cáo</div>
    <a href="orders.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
        <i class="bi bi-cart-check"></i> Quản Lý Đơn Hàng
        <?php
        $pending_count = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'dang_cho'")->fetchColumn();
        if ($pending_count > 0) echo "<span class='sidebar-badge bg-danger text-white'>$pending_count</span>";
        ?>
    </a>
    <a href="analytics.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
        <i class="bi bi-graph-up-arrow"></i> Phân Tích Doanh Thu
    </a>

    <div class="sidebar-group-title">Sản Phẩm & Kho</div>
    <a href="products.php" class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'edit_product.php', 'add_product.php']) ? 'active' : ''; ?>">
        <i class="bi bi-box-seam"></i> Danh Sách Sản Phẩm
    </a>
    <a href="categories.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
        <i class="bi bi-tags"></i> Danh Mục Ngành Hàng
    </a>
    <a href="brands.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>">
        <i class="bi bi-building"></i> Thương Hiệu Đối Tác
    </a>

    <div class="sidebar-group-title">Marketing & Khuyến Mãi</div>
    <a href="flash_sales.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'flash_sales.php' ? 'active' : ''; ?>">
        <i class="bi bi-lightning-charge"></i> Chương Trình Flash Sale
    </a>
    <a href="vouchers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'vouchers.php' ? 'active' : ''; ?>">
        <i class="bi bi-ticket-perforated"></i> Mã Giảm Giá (Voucher)
    </a>
    <a href="banners.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'banners.php' ? 'active' : ''; ?>">
        <i class="bi bi-image"></i> Banner Quảng Cáo
    </a>

    <div class="sidebar-group-title">Khách Hàng & Phản Hồi</div>
    <a href="customers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
        <i class="bi bi-people"></i> Danh Sách Khách Hàng
    </a>
    <a href="reviews.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>">
        <i class="bi bi-chat-left-text"></i> Đánh Giá Từ Khách
        <?php
        $pending_reviews = $pdo->query("SELECT COUNT(*) FROM reviews WHERE status = 'dang_cho'")->fetchColumn();
        if ($pending_reviews > 0) echo "<span class='sidebar-badge bg-warning text-dark'>$pending_reviews</span>";
        ?>
    </a>
    
    <div class="sidebar-group-title">Hệ Thống & Cấu Hình</div>
    <a href="settings.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
        <i class="bi bi-gear"></i> Cài Đặt Website
    </a>
    <div class="mt-4 px-4 mb-4">
        <a href="logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill">
            <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
        </a>
    </div>
</div>
