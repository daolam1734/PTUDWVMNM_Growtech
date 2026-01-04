<style>
    .admin-sidebar {
        width: var(--sidebar-width);
        background: #fff;
        border-right: 1px solid #e8e8e8;
        padding-top: 16px;
        flex-shrink: 0;
    }
    .sidebar-group-title {
        padding: 8px 24px;
        font-size: 14px;
        font-weight: 700;
        color: rgba(0,0,0,.87);
        margin-top: 8px;
    }
    .sidebar-item {
        display: flex;
        align-items: center;
        padding: 10px 24px;
        color: rgba(0,0,0,.65);
        text-decoration: none;
        font-size: 14px;
        transition: all .2s;
    }
    .sidebar-item:hover {
        color: var(--shopee-orange);
        background: #f6f6f6;
    }
    .sidebar-item.active {
        color: var(--shopee-orange);
        font-weight: 500;
    }
    .sidebar-item .sparkle-effect {
        width: 16px;
        height: 16px;
        margin-right: 12px;
    }
</style>

<div class="admin-sidebar">
    <div class="sidebar-group-title">Quản Lý Đơn Hàng</div>
    <a href="orders.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
        <span class="sparkle-effect"></span> Tất Cả
    </a>

    <div class="sidebar-group-title">Quản Lý Sản Phẩm</div>
    <a href="products.php" class="sidebar-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['products.php', 'add_product.php', 'edit_product.php']) ? 'active' : ''; ?>">
        <span class="sparkle-effect"></span> Tất Cả Sản Phẩm
    </a>
    <a href="add_product.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'add_product.php' ? 'active' : ''; ?>">
        <span class="sparkle-effect"></span> Thêm Sản Phẩm
    </a>

    <div class="sidebar-group-title">Quản Lý Shop</div>
    <a href="categories.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
        <span class="sparkle-effect"></span> Danh Mục Shop
    </a>
    <a href="brands.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>">
        <span class="sparkle-effect"></span> Thương Hiệu
    </a>

    <div class="sidebar-group-title">Dữ Liệu</div>
    <a href="analytics.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
        <span class="sparkle-effect"></span> Phân Tích Bán Hàng
    </a>
    <a href="customers.php" class="sidebar-item <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>">
        <span class="sparkle-effect"></span> Dữ Liệu Khách Hàng
    </a>
</div>
