# 🧧 GrowTech - Chuẩn Công Nghệ, Vững Niềm Tin (Tết Edition 2026)

**GrowTech** là một nền tảng thương mại điện tử chuyên doanh Laptop và linh kiện máy tính, được thiết kế đặc biệt với chủ đề **Xuân Bính Ngọ 2026**. Dự án sử dụng ngôn ngữ PHP thuần kết hợp với kiến trúc hiện đại, mang lại trải nghiệm mua sắm mượt mà và giao diện đậm chất truyền thống Việt Nam.

---

## ✨ Tính Năng Nổi Bật

### 🎨 Giao Diện & Trải Nghiệm (UI/UX)
- **Chủ đề Tết Bính Ngọ 2026**: Tông màu Đỏ (Tet Red) và Vàng (Tet Gold) chủ đạo, kết hợp hiệu ứng hoa mai, hoa đào rơi và các họa tiết trang trí Tết tinh tế.
- **Header Thông Minh**: Thanh điều hướng cố định (Fixed Header) với hiệu ứng co giãn (Shrink) khi cuộn trang, tích hợp Megamenu chuyên nghiệp.
- **Responsive Design**: Tương thích hoàn hảo trên mọi thiết bị từ Desktop, Tablet đến Mobile.

### 🛒 Chức Năng Mua Sắm
- **Bộ Lọc Nâng Cao**: Tìm kiếm sản phẩm theo danh mục, thương hiệu và khoảng giá linh hoạt ngay tại trang chủ.
- **Tìm Kiếm Thông Minh (AJAX)**: Gợi ý sản phẩm ngay khi người dùng nhập từ khóa, giúp tìm kiếm nhanh chóng.
- **Giỏ Hàng & Thanh Toán**: Quy trình thêm sản phẩm, cập nhật số lượng và đặt hàng tối ưu hóa trải nghiệm người dùng.
- **Quản Lý Đơn Hàng**: Theo dõi trạng thái đơn hàng, lịch sử mua hàng và thông báo khuyến mãi.

### 🔐 Quản Trị Hệ Thống (Admin Panel)
- **Dashboard Toàn Diện**: Thống kê doanh thu, đơn hàng và khách hàng bằng biểu đồ trực quan.
- **Quản Lý Sản Phẩm**: Hỗ trợ thêm/sửa/xóa sản phẩm với nhiều hình ảnh, thông số kỹ thuật chi tiết.
- **Quản Lý Khuyến Mãi**: Tạo mã giảm giá (Voucher), chương trình Flash Sale và Banner quảng cáo Tết.

---

## 🛠 Công Nghệ Sử Dụng

- **Backend**: PHP 8.x (Pure PHP), PDO (PHP Data Objects) cho bảo mật SQL Injection.
- **Database**: MySQL 8.0.
- **Frontend**: Bootstrap 5, CSS3 (Variables, Animations), JavaScript (ES6+, AJAX).
- **Icons**: Bootstrap Icons, FontAwesome.

---

## 📂 Cấu Trúc Thư Mục Chính

```text
├── admin/              # Trang quản trị (Products, Orders, Analytics...)
├── assets/             # Tài nguyên tĩnh (CSS, JS, Images)
├── auth/               # Xử lý đăng nhập, đăng ký, quên mật khẩu
├── config/             # Cấu hình hệ thống & Kết nối Database
├── includes/           # Các thành phần giao diện dùng chung (Header, Footer, Sidebar)
├── functions.php       # Thư viện hàm tiện ích hệ thống
├── index.php           # Trang chủ & Bộ lọc sản phẩm
└── database.sql        # Cấu trúc cơ sở dữ liệu & Dữ liệu mẫu
```

---

## 🚀 Hướng Dẫn Cài Đặt (XAMPP)

1. **Clone Project**: Tải mã nguồn và giải nén vào thư mục `C:\xampp\htdocs\weblaptop`.
2. **Khởi Động XAMPP**: Mở XAMPP Control Panel và Start **Apache** & **MySQL**.
3. **Khởi Tạo Database**:
   - Truy cập: `http://localhost/weblaptop/config/create_db.php`.
   - Hệ thống sẽ tự động tạo Database `weblaptop`, các bảng cần thiết và nạp dữ liệu mẫu.
4. **Trải Nghiệm**:
   - Trang chủ: `http://localhost/weblaptop/index.php`.
   - Trang quản trị: `http://localhost/weblaptop/admin/login.php`.

---

## 🔑 Tài Khoản Demo

| Vai trò | Username | Password |
| :--- | :--- | :--- |
| **Quản trị viên** | `admin` | `admin123` |
| **Người dùng** | `user` | `user123` |

---

## 📝 Ghi Chú Phát Triển
- Dự án tuân thủ các tiêu chuẩn bảo mật cơ bản (Prepared Statements).
- Giao diện được tối ưu hóa cho sự kiện Tết 2026 với các hiệu ứng CSS nhẹ nhàng, không gây ảnh hưởng đến hiệu năng.
- Mọi đóng góp hoặc báo lỗi vui lòng liên hệ qua Issue của Repository.

---
**GrowTech** - *Chuẩn công nghệ – vững niềm tin. Chúc mừng năm mới Bính Ngọ 2026!* 🧧✨