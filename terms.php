<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <h1 class="mb-4 text-center fw-bold">Điều khoản dịch vụ</h1>
            <div class="card border-0 shadow-sm p-4 p-md-5 rounded-4">
                <p class="text-muted italic">Cập nhật lần cuối: 09/01/2026</p>
                
                <h5 class="fw-bold mt-4">1. Chấp thuận điều khoản</h5>
                <p>Bằng cách truy cập và sử dụng website GrowTech, bạn đồng ý tuân thủ các điều khoản và điều kiện được quy định tại đây. Nếu bạn không đồng ý với bất kỳ phần nào, vui lòng ngừng sử dụng dịch vụ của chúng tôi.</p>

                <h5 class="fw-bold mt-4">2. Thông tin tài khoản</h5>
                <p>Khi đăng ký tài khoản, bạn có trách nhiệm cung cấp thông tin chính xác và bảo mật mật khẩu của mình. Mọi hoạt động diễn ra dưới tên tài khoản của bạn sẽ thuộc trách nhiệm của bạn.</p>

                <h5 class="fw-bold mt-4">3. Giá cả và Thanh toán</h5>
                <p>Giá sản phẩm được niêm yết trên website đã bao gồm thuế VAT (trừ trường hợp có ghi chú khác). Chúng tôi nỗ lực đảm bảo giá hiển thị là chính xác nhất, tuy nhiên sai sót có thể xảy ra. Trong trường hợp đó, chúng tôi sẽ liên hệ với bạn để xác nhận hoặc hủy đơn hàng.</p>

                <h5 class="fw-bold mt-4">4. Quyền sở hữu trí tuệ</h5>
                <p>Toàn bộ nội dung trên website bao gồm văn bản, hình ảnh, logo, mã nguồn đều thuộc sở hữu của GrowTech hoặc các đối tác cấp phép. Mọi hành vi sao chép không được sự đồng ý bằng văn bản đều vi phạm pháp luật.</p>

                <h5 class="fw-bold mt-4">5. Giới hạn trách nhiệm</h5>
                <p>GrowTech không chịu trách nhiệm cho bất kỳ thiệt hại gián tiếp nào phát sinh từ việc sử dụng hoặc không thể sử dụng website hoặc sản phẩm đã mua.</p>

                <div class="alert alert-danger mt-5">
                    Mọi thắc mắc về điều khoản dịch vụ, vui lòng liên hệ hotline <b>1900 1234</b> để được giải đáp.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
