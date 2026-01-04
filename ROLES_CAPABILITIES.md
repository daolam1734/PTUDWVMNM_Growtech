# Phân Quyền và Chức Năng Hệ Thống (User Roles & Capabilities)

Tài liệu này liệt kê chi tiết các quyền hạn và chức năng tương ứng với từng nhóm người dùng trong hệ thống website **GrowTech**.

---

## 1. Khách (Người chưa đăng nhập)
Đây là nhóm người dùng vãng lai truy cập vào website.
- **Xem sản phẩm:** Truy cập trang chủ, xem danh sách sản phẩm và chi tiết từng sản phẩm.
- **Tìm kiếm:** Sử dụng thanh tìm kiếm Shopee-style với tính năng gợi ý sản phẩm thông minh.
- **Giỏ hàng:** 
    - Thêm sản phẩm vào giỏ hàng (lưu tạm thời trong Session).
    - Xem và cập nhật số lượng sản phẩm trong giỏ hàng.
- **Trang thông tin:** Xem các trang hướng dẫn mua hàng, chính sách đổi trả, liên hệ.
- **Tài khoản:**
    - Đăng ký tài khoản mới.
    - Đăng nhập vào hệ thống.
    - Khôi phục mật khẩu qua email (nếu có cấu hình).

---

## 2. Người dùng (Đã đăng nhập)
Bao gồm tất cả các quyền của **Khách**, cộng thêm các tính năng cá nhân hóa:
- **Quản lý tài khoản:** 
    - Xem và chỉnh sửa thông tin cá nhân (Hồ sơ).
    - Đổi mật khẩu.
- **Mua hàng:**
    - Tiến hành thanh toán (Checkout) cho các sản phẩm trong giỏ hàng.
    - Nhập thông tin giao hàng và chọn phương thức thanh toán.
- **Quản lý đơn hàng:**
    - Xem danh sách lịch sử đơn hàng đã mua.
    - Theo dõi trạng thái đơn hàng (Đang chờ, Đã xác nhận, Đang giao, v.v.).
- **Tương tác:**
    - Nhận thông báo hệ thống.
    - Đăng xuất khỏi hệ thống an toàn.

---

## 3. Quản trị viên (Admin)
Bao gồm tất cả các quyền của **Người dùng**, cộng thêm quyền kiểm soát toàn bộ hệ thống:
- **Bảng điều khiển (Dashboard):**
    - Xem tổng quan kinh doanh: Doanh thu hôm nay, số đơn hàng mới.
    - Theo dõi các sản phẩm sắp hết hàng (Low stock).
- **Quản lý sản phẩm:**
    - Xem danh sách toàn bộ sản phẩm trong kho.
    - Thêm sản phẩm mới (Tên, mô tả, giá, hình ảnh, số lượng).
    - Chỉnh sửa thông tin sản phẩm hiện có.
    - Xóa sản phẩm khỏi hệ thống.
- **Quản lý đơn hàng:**
    - Xem danh sách tất cả đơn hàng từ mọi khách hàng.
    - Xem chi tiết từng đơn hàng (Thông tin khách, danh sách món đồ).
    - Cập nhật trạng thái đơn hàng (Xác nhận đơn, Đã giao hàng, Hủy đơn).
- **Hệ thống:**
    - Truy cập nhanh vào giao diện quản trị từ thanh điều hướng (Header).
    - Quyền quản lý cao nhất đối với cơ sở dữ liệu.

---

*Tài liệu này được cập nhật dựa trên cấu trúc mã nguồn hiện tại của dự án GrowTech.*
