<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/header.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, we would save this to DB or send email
    $success = true;
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Liên hệ với chúng tôi</h2>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success text-center">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-md-5">
                            <div class="mb-4">
                                <h5><i class="bi bi-geo-alt-fill text-danger me-2"></i> Địa chỉ</h5>
                                <p class="text-muted">123 Đường ABC, Quận 1, TP. Hồ Chí Minh</p>
                            </div>
                            <div class="mb-4">
                                <h5><i class="bi bi-telephone-fill text-danger me-2"></i> Điện thoại</h5>
                                <p class="text-muted">1900 1234 (8:00 - 21:00)</p>
                            </div>
                            <div class="mb-4">
                                <h5><i class="bi bi-envelope-fill text-danger me-2"></i> Email</h5>
                                <p class="text-muted">support@growtech.vn</p>
                            </div>
                            <div class="mt-5">
                                <h5>Theo dõi chúng tôi</h5>
                                <div class="d-flex gap-3 mt-3">
                                    <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-facebook"></i></a>
                                    <a href="#" class="btn btn-outline-info btn-sm rounded-circle"><i class="bi bi-twitter"></i></a>
                                    <a href="#" class="btn btn-outline-danger btn-sm rounded-circle"><i class="bi bi-instagram"></i></a>
                                    <a href="#" class="btn btn-outline-primary btn-sm rounded-circle"><i class="bi bi-linkedin"></i></a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <form action="" method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Họ và tên</label>
                                    <input type="text" class="form-control" placeholder="Nhập họ tên của bạn" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" placeholder="Nhập email của bạn" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Chủ đề</label>
                                    <select class="form-select">
                                        <option value="1">Tư vấn mua hàng</option>
                                        <option value="2">Hỗ trợ kỹ thuật</option>
                                        <option value="3">Khiếu nại dịch vụ</option>
                                        <option value="4">Khác</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nội dung</label>
                                    <textarea class="form-control" rows="5" placeholder="Nhập nội dung tin nhắn" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger w-100 py-2">Gửi tin nhắn</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-5">
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.424129007858!2d106.69823431474898!3d10.7756586923221!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f385570472f%3A0x118ad941f1d660b7!2zRGluaCDEkOG7mWMgTOG6rXA!5e0!3m2!1svi!2s!4v1625560000000!5m2!1svi!2s" width="100%" height="400" style="border:0; border-radius: 15px;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
