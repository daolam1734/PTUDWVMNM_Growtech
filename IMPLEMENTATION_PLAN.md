# Kế hoạch triển khai các tính năng còn thiếu - Website Bán Laptop

Dưới đây là thứ tự ưu tiên thực hiện các file và tính năng để hoàn thiện dự án theo luồng nghiệp vụ từ cơ bản đến nâng cao.

## Giai đoạn 1: Hoàn thiện luồng mua hàng cơ bản (Ưu tiên cao nhất)
1.  **`checkout.php`**: Xây dựng trang thanh toán.
    *   Lấy thông tin từ giỏ hàng.
    *   Form nhập địa chỉ giao hàng (hoặc chọn từ `user_addresses`).
    *   Chọn phương thức thanh toán (COD, Chuyển khoản).
    *   Lưu dữ liệu vào bảng `orders` và `order_items`.
2.  **`admin/orders.php`**: Quản lý đơn hàng cho Admin.
    *   Liệt kê danh sách đơn hàng mới.
    *   Xem chi tiết đơn hàng.
    *   Cập nhật trạng thái (Xác nhận, Đang giao, Hoàn thành, Hủy).
3.  **Cập nhật `cart.php`**: Thêm nút "Tiến hành thanh toán" dẫn đến `checkout.php`.

## Giai đoạn 2: Quản lý tài khoản và Thông tin hỗ trợ
1.  **`account.php`**: Trang cá nhân của người dùng.
    *   Hiển thị thông tin cá nhân.
    *   **`order_history.php`**: Xem danh sách đơn hàng đã mua và trạng thái.
2.  **`shipping.php` & `returns.php`**: Các trang chính sách.
    *   Nội dung tĩnh về quy định giao hàng và đổi trả (để fix lỗi link chết ở Header).
3.  **`auth/password_forgot.php` & `auth/password_reset.php`**: Hoàn thiện logic khôi phục mật khẩu.

## Giai đoạn 3: Nâng cao trải nghiệm sản phẩm và Admin
1.  **`product.php` (Cập nhật)**:
    *   Hiển thị thông số kỹ thuật chi tiết từ bảng `product_specifications`.
    *   Hiển thị danh sách đánh giá từ bảng `reviews`.
    *   Thêm form gửi đánh giá (chỉ cho người dùng đã mua hàng).
2.  **`admin/categories.php` & `admin/brands.php`**:
    *   Trang quản lý danh mục và thương hiệu (Thêm, Sửa, Xóa).
3.  **`index.php` (Cập nhật)**:
    *   Hoàn thiện bộ lọc ở Sidebar (Lọc theo giá, thương hiệu, loại laptop).

## Giai đoạn 4: Tính năng bổ trợ và Tối ưu
1.  **`notifications.php`**: Hệ thống thông báo cho người dùng khi đơn hàng thay đổi trạng thái.
2.  **`admin/dashboard.php`**: Trang thống kê.
    *   Tổng doanh thu, số đơn hàng trong tháng.
    *   Sản phẩm bán chạy nhất.
    *   Cảnh báo sản phẩm sắp hết hàng (`stock < 5`).
3.  **`search_suggest.php` (Cập nhật)**: Tối ưu tìm kiếm gợi ý bằng AJAX để hiển thị ảnh và giá sản phẩm ngay khi gõ.

## Giai đoạn 5: Tích hợp hệ thống bên ngoài
1.  **Tích hợp Email**: Sử dụng thư viện PHPMailer để gửi email xác nhận đơn hàng thật thay vì chỉ mô phỏng.
2.  **Thanh toán Online**: Tích hợp API của VNPay hoặc Momo vào luồng thanh toán.
3.  **SEO & Friendly URL**: Cấu hình `.htaccess` để sử dụng đường dẫn dạng `domain.com/san-pham/dell-xps-13` thay vì `product.php?id=1`.
