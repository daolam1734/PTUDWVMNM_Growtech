<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/header.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, we would save this to DB or send email
    $success = true;
}
?>

<style>
    :root {
        --tet-red: #d32f2f;
        --tet-gold: #ffc107;
        --tet-bg: #f8f9fa;
    }
    body { background-color: var(--tet-bg); }
    
    .contact-card {
        background: #fff;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        border: none;
    }
    .contact-info-side {
        background: var(--tet-red);
        color: #fff;
        padding: 50px 40px;
        height: 100%;
    }
    .contact-form-side {
        padding: 50px 40px;
    }
    .info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 30px;
    }
    .info-item i {
        font-size: 1.2rem;
        margin-right: 20px;
        margin-top: 5px;
        color: var(--tet-gold);
    }
    .social-links a {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,0.1);
        color: #fff;
        border-radius: 50%;
        margin-right: 10px;
        transition: all 0.3s;
        text-decoration: none;
    }
    .social-links a:hover {
        background: var(--tet-gold);
        color: var(--tet-red);
        transform: translateY(-3px);
    }
    .form-control, .form-select {
        border-radius: 10px;
        padding: 12px 15px;
        border: 1px solid #eee;
        background: #f9f9f9;
    }
    .form-control:focus {
        border-color: var(--tet-red);
        box-shadow: 0 0 0 0.25rem rgba(211, 47, 47, 0.1);
        background: #fff;
    }
    .btn-send {
        background: var(--tet-red);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s;
    }
    .btn-send:hover {
        background: #b71c1c;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="contact-card">
                <div class="row g-0">
                    <div class="col-md-5">
                        <div class="contact-info-side">
                            <h3 class="fw-bold mb-4">Thông Tin Liên Hệ</h3>
                            <p class="mb-5 opacity-75">Hãy để lại lời nhắn cho chúng tôi, đội ngũ GrowTech sẽ phản hồi bạn trong vòng 24h làm việc.</p>
                            
                            <div class="info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <div class="fw-bold">Địa chỉ</div>
                                    <div class="opacity-75">123 Đường ABC, Quận 1, TP. Hồ Chí Minh</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-phone-alt"></i>
                                <div>
                                    <div class="fw-bold">Điện thoại</div>
                                    <div class="opacity-75">1900 1234 (8:00 - 21:00)</div>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="fas fa-envelope"></i>
                                <div>
                                    <div class="fw-bold">Email</div>
                                    <div class="opacity-75">support@growtech.vn</div>
                                </div>
                            </div>
                            
                            <div class="mt-5 pt-4">
                                <h6 class="fw-bold mb-3">Theo dõi chúng tôi</h6>
                                <div class="social-links">
                                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                                    <a href="#"><i class="fab fa-instagram"></i></a>
                                    <a href="#"><i class="fab fa-youtube"></i></a>
                                    <a href="#"><i class="fab fa-tiktok"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="contact-form-side">
                            <h3 class="fw-bold mb-4 text-dark">Gửi Lời Nhắn</h3>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success border-0 rounded-3 mb-4">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.
                                </div>
                            <?php endif; ?>

                            <form action="" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small fw-bold">Họ và tên</label>
                                        <input type="text" class="form-control" placeholder="Nguyễn Văn A" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small fw-bold">Email</label>
                                        <input type="email" class="form-control" placeholder="example@gmail.com" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Chủ đề</label>
                                    <select class="form-select">
                                        <option value="1">Tư vấn mua hàng</option>
                                        <option value="2">Hỗ trợ kỹ thuật</option>
                                        <option value="3">Khiếu nại dịch vụ</option>
                                        <option value="4">Khác</option>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Nội dung</label>
                                    <textarea class="form-control" rows="5" placeholder="Bạn cần chúng tôi giúp gì?" required></textarea>
                                </div>
                                <button type="submit" class="btn-send w-100">Gửi Tin Nhắn Ngay</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-5">
                <div class="rounded-4 overflow-hidden shadow-sm">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.424129007858!2d106.69823431474898!3d10.7756586923221!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f385570472f%3A0x118ad941f1d660b7!2zRGluaCDEkOG7mWMgTOG6rXA!5e0!3m2!1svi!2s!4v1625560000000!5m2!1svi!2s" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

