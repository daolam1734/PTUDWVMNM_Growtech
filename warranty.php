<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold" style="color: var(--tet-red);"><i class="bi bi-shield-check me-2"></i>Tra cứu bảo hành</h1>
        <p class="text-muted">Nhập số Serial (S/N) hoặc Số điện thoại để kiểm tra thông tin bảo hành của thiết bị.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card border-0 shadow-lg rounded-4 p-4 mb-5">
                <form action="" method="GET">
                    <div class="input-group input-group-lg mb-3">
                        <input type="text" class="form-control border-end-0" placeholder="S/N hoặc Số điện thoại..." required>
                        <button class="btn btn-danger px-4" type="submit">Tìm kiếm</button>
                    </div>
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Số Serial thường được dán ở mặt dưới của Laptop.</small>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <h3 class="fw-bold mb-4">Chính sách bảo hành tại GrowTech</h3>
        <div class="col-md-4">
            <div class="d-flex gap-3">
                <div class="flex-shrink-0">
                    <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                         <i class="bi bi-laptop fs-4"></i>
                    </div>
                </div>
                <div>
                    <h5>Laptop chính hãng</h5>
                    <p class="small text-muted">Bảo hành 12-24 tháng theo tiêu chuẩn của nhà sản xuất (Dell, HP, Apple, Asus...)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex gap-3">
                <div class="flex-shrink-0">
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                         <i class="bi bi-arrow-repeat fs-4"></i>
                    </div>
                </div>
                <div>
                    <h5>Lỗi là đổi mới</h5>
                    <p class="small text-muted">1 đổi 1 trong vòng 30 ngày đầu nếu phát sinh lỗi phần cứng từ nhà sản xuất.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="d-flex gap-3">
                <div class="flex-shrink-0">
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                         <i class="bi bi-tools fs-4"></i>
                    </div>
                </div>
                <div>
                    <h5>Hỗ trợ trọn đời</h5>
                    <p class="small text-muted">Miễn phí vệ sinh máy và hỗ trợ cài đặt phần mềm trọn đời máy tại các chi nhánh.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-5 p-4 rounded-4 border border-dashed text-center">
        <h5 class="fw-bold">Bạn cần hỗ trợ kỹ thuật gấp?</h5>
        <p class="mb-3">Kỹ thuật viên của chúng tôi luôn sẵn sàng hỗ trợ bạn.</p>
        <a href="/weblaptop/contact.php" class="btn btn-outline-dark px-4 rounded-pill">Liên hệ Kỹ thuật</a>
    </div>
</div>

<style>
.border-dashed { border-style: dashed !important; border-width: 2px !important; border-color: #dee2e6 !important; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
