<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold" style="color: var(--tet-red);">Cơ hội nghề nghiệp tại GROW<span style="color: var(--tet-gold);">TECH</span></h1>
        <p class="text-muted">Gia nhập đội ngũ năng động để cùng nhau kiến tạo tương lai công nghệ.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <h3 class="mb-4">Vị trí đang tuyển dụng</h3>
            
            <div class="card mb-3 border-0 shadow-sm hover-shadow">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0 fw-bold">Nhân viên Tư vấn Bán hàng (Laptop)</h5>
                        <span class="badge bg-success">Full-time</span>
                    </div>
                    <p class="small text-muted mb-3"><i class="bi bi-geo-alt me-1"></i> Vĩnh Long / Cần Thơ</p>
                    <p class="mb-3">Tư vấn cấu hình laptop phù hợp với nhu cầu của khách hàng, hỗ trợ trải nghiệm sản phẩm tại showroom.</p>
                    <a href="#" class="btn btn-outline-danger btn-sm">Xem chi tiết & Ứng tuyển</a>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm hover-shadow">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0 fw-bold">Kỹ thuật viên Sửa chữa Laptop</h5>
                        <span class="badge bg-success">Full-time</span>
                    </div>
                    <p class="small text-muted mb-3"><i class="bi bi-geo-alt me-1"></i> Vĩnh Long</p>
                    <p class="mb-3">Kiểm tra, khắc phục các sự cố phần cứng/phần mềm laptop. Vệ sinh và bảo trì máy định kỳ cho khách.</p>
                    <a href="#" class="btn btn-outline-danger btn-sm">Xem chi tiết & Ứng tuyển</a>
                </div>
            </div>

            <div class="card mb-3 border-0 shadow-sm hover-shadow">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0 fw-bold">Thực tập sinh Marketing / Content</h5>
                        <span class="badge bg-info">Internship</span>
                    </div>
                    <p class="small text-muted mb-3"><i class="bi bi-geo-alt me-1"></i> Remote / Hybrid</p>
                    <p class="mb-3">Hỗ trợ xây dựng nội dung fanpage, viết bài tin tức công nghệ và đánh giá laptop.</p>
                    <a href="#" class="btn btn-outline-danger btn-sm">Xem chi tiết & Ứng tuyển</a>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 bg-light shadow-sm p-4 rounded-4 mb-4">
                <h4>Tại sao chọn GrowTech?</h4>
                <ul class="list-unstyled mt-3">
                    <li class="mb-3"><i class="bi bi-check2-circle text-danger me-2"></i> Môi trường làm việc trẻ trung, sáng tạo</li>
                    <li class="mb-3"><i class="bi bi-check2-circle text-danger me-2"></i> Lương thưởng cạnh tranh & lộ trình thăng tiến rõ ràng</li>
                    <li class="mb-3"><i class="bi bi-check2-circle text-danger me-2"></i> Đào tạo bài bản về kiến thức công nghệ</li>
                    <li class="mb-3"><i class="bi bi-check2-circle text-danger me-2"></i> Ưu đãi đặc quyền khi mua sắm sản phẩm</li>
                </ul>
            </div>
            
            <div class="alert alert-warning rounded-4 border-0">
                <h5>Bạn không tìm thấy vị trí phù hợp?</h5>
                <p class="small mb-0">Đừng ngần ngại gửi CV của bạn về email <b class="text-dark">hr@growtech.com</b>. Chúng tôi luôn tìm kiếm những nhân tài mới!</p>
            </div>
        </div>
    </div>
</div>

<style>
.hover-shadow:hover { 
    transform: translateY(-5px);
    transition: all 0.3s ease;
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
