<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4" style="color: var(--tet-red);">Về GROW<span style="color: var(--tet-gold);">TECH</span></h1>
            <p class="lead text-muted mb-4">Hệ thống bán lẻ laptop uy tín hàng đầu Việt Nam, mang đến giải pháp công nghệ tối ưu cho mọi người.</p>
            <p>Được thành lập với sứ mệnh kết nối người dùng với những sản phẩm công nghệ tiên tiến nhất, GrowTech tự hào là đối tác chiến lược của nhiều thương hiệu laptop danh tiếng như Apple, Dell, HP, Asus, Lenovo, Acer và Microsoft.</p>
            <div class="row g-4 mt-2">
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-patch-check-fill fs-3 text-success"></i>
                        <div>
                            <h5 class="mb-0">Chính hãng</h5>
                            <small class="text-muted">Cam kết 100%</small>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-truck fs-3 text-primary"></i>
                        <div>
                            <h5 class="mb-0">Giao nhanh</h5>
                            <small class="text-muted">Toàn quốc</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mt-4 mt-lg-0">
            <img src="https://images.unsplash.com/photo-1531297484001-80022131f5a1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80" alt="GrowTech Office" class="img-fluid rounded-4 shadow-lg">
        </div>
    </div>

    <div class="row g-4 py-5 border-top">
        <div class="col-md-4 text-center">
            <div class="p-4 rounded-4 bg-light h-100">
                <i class="bi bi-eye-fill display-5 color-tet-gold mb-3"></i>
                <h3>Tầm nhìn</h3>
                <p>Trở thành đơn vị phân phối thiết bị công nghệ hàng đầu, dẫn đầu về chất lượng dịch vụ và trải nghiệm khách hàng tại khu vực.</p>
            </div>
        </div>
        <div class="col-md-4 text-center">
            <div class="p-4 rounded-4 bg-light h-100">
                <i class="bi bi-rocket-takeoff-fill display-5 color-tet-gold mb-3"></i>
                <h3>Sứ mệnh</h3>
                <p>Cung cấp những sản phẩm công nghệ chất lượng cao với giá thành hợp lý, đồng hành cùng sự phát triển của cá nhân và doanh nghiệp Việt.</p>
            </div>
        </div>
        <div class="col-md-4 text-center">
            <div class="p-4 rounded-4 bg-light h-100">
                <i class="bi bi-heart-fill display-5 color-tet-gold mb-3"></i>
                <h3>Giá trị cốt lõi</h3>
                <p>Tín - Tâm - Toàn: Luôn giữ chữ tín, làm việc tận tâm và nỗ lực mang tới sự vẹn toàn trong mỗi dịch vụ.</p>
            </div>
        </div>
    </div>
</div>

<style>
.color-tet-gold { color: #D4AF37; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
