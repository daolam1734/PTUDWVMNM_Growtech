# Hoàn thành dự án GrowTech - Tết Edition!

Dự án đã được nâng cấp toàn diện với thương hiệu **GrowTech** và chủ đề **Tết đến xuân về**.

### Các thay đổi chính:
1. **Thương hiệu**: Đổi tên thành **GrowTech** với slogan "Chuẩn công nghệ – vững niềm tin".
2. **Giao diện Tết**: 
   - Header & Footer tông màu Đỏ - Vàng sang trọng.
   - Hiệu ứng hoa mai, hoa đào, bao lì xì rơi (Blossom Effect).
   - Banner Tết rực rỡ tại trang chủ.
3. **Sửa lỗi & Tối ưu**:
   - Sửa lỗi ảnh không hiển thị (`ERR_NAME_NOT_RESOLVED`) bằng cách chuẩn hóa URL ảnh trong DB.
   - Refactor Admin Panel để hỗ trợ bảng `product_images` (thay vì cột `image` cũ).
   - Cập nhật hàm `getProductImage()` để xử lý ảnh linh hoạt và có fallback.
4. **Tính năng**: Tìm kiếm gợi ý (AJAX), Giỏ hàng, Thanh toán, Quản lý đơn hàng.

### Cách chạy:
- Khởi động XAMPP (Apache & MySQL).
- Truy cập `http://localhost/weblaptop/config/create_db.php` để khởi tạo lại dữ liệu (nếu cần).
- Truy cập `http://localhost/weblaptop` để trải nghiệm không khí Tết tại GrowTech!

Admin: `admin` / `admin123` tại `/admin/login.php`.
