<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="mb-4 text-center fw-bold">Chính sách bảo mật</h1>
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4">
                <p class="text-muted italic">Cập nhật lần cuối: 09/01/2026</p>
                
                <h5 class="fw-bold mt-4">1. Thu thập thông tin</h5>
                <p>Chúng tôi thu thập các thông tin cần thiết để xử lý đơn hàng và cải thiện trải nghiệm người dùng, bao gồm: Họ tên, số điện thoại, email, địa chỉ giao hàng và lịch sử mua sắm.</p>

                <h5 class="fw-bold mt-4">2. Sử dụng thông tin</h5>
                <p>Thông tin của bạn được sử dụng để:</p>
                <ul>
                    <li>Xử lý và giao đơn hàng.</li>
                    <li>Gửi thông báo về các chương trình khuyến mãi (nếu bạn đăng ký).</li>
                    <li>Hỗ trợ kỹ thuật và giải quyết khiếu nại.</li>
                    <li>Phân tích thị trường để cải thiện dịch vụ.</li>
                </ul>

                <h5 class="fw-bold mt-4">3. Bảo mật dữ liệu</h5>
                <p>GrowTech cam kết sử dụng các công nghệ bảo mật tiên tiến (SSL/TLS) để bảo vệ dữ liệu của khách hàng. Thông tin nhạy cảm như thông tin thanh toán sẽ được xử lý bởi các đối tác cổng thanh toán uy tín và không lưu trữ trên máy chủ của chúng tôi.</p>

                <h5 class="fw-bold mt-4">4. Chia sẻ thông tin</h5>
                <p>Chúng tôi cam kết không bán hoặc chia sẻ thông tin cá nhân của bạn cho bên thứ ba, ngoại trừ các đơn vị vận chuyển cần thiết để hoàn tất đơn hàng.</p>

                <h5 class="fw-bold mt-4">5. Quyền của người dùng</h5>
                <p>Bạn có quyền yêu cầu truy cập, chỉnh sửa hoặc xóa thông tin cá nhân của mình bất kỳ lúc nào bằng cách đăng nhập vào tài khoản hoặc liên hệ với bộ phận chăm sóc khách hàng.</p>
                
                <div class="mt-5 p-4 bg-light rounded-4">
                    <p class="mb-0 fs-7">Nếu có bất kỳ câu hỏi nào về chính sách này, vui lòng gửi email về: <b>privacy@growtech.com</b></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
