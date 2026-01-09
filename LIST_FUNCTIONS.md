# Danh sách các hàm trong hệ thống WebLaptop

Tài liệu này liệt kê các hàm (functions) chính được sử dụng trong hệ thống, bao gồm cả backend PHP và frontend JavaScript.

---

## 1. Core Backend Functions ([functions.php](functions.php))

Đây là tệp tin chứa các hàm xử lý logic nghiệp vụ cốt lõi của hệ thống.

### Sản phẩm (Products)
- `getProduct($id)` - [functions.php#L25](functions.php#L25): Lấy thông tin chi tiết của một sản phẩm theo ID.
- `getProducts($limit)` - [functions.php#L32](functions.php#L32): Lấy danh sách sản phẩm mới nhất (có hỗ trợ giới hạn số lượng).
- `getFlashSaleProducts($limit)` - [functions.php#L44](functions.php#L44): Lấy danh sách sản phẩm đang trong chương trình Flash Sale.
- `getProductImage($product_id)` - [functions.php#L56](functions.php#L56): Lấy URL ảnh đại diện (thumbnail) của sản phẩm.
- `getProductImages($product_id)` - [functions.php#L83](functions.php#L83): Lấy tất cả ảnh liên quan đến sản phẩm.
- `getProductSpecs($product_id)` - [functions.php#L102](functions.php#L102): Lấy thông số kỹ thuật chi tiết của sản phẩm.

### Voucher & Khuyến mãi
- `getVoucherByCode($code)` - [functions.php#L112](functions.php#L112): Kiểm tra và lấy thông tin voucher theo mã code.
- `calculateDiscount($voucher, $subtotal, $shipping_fee)` - [functions.php#L119](functions.php#L119): Tính toán số tiền được giảm dựa trên loại voucher và tổng đơn hàng.

### Xác thực & Người dùng (Authentication)
- `isAdmin()` - [functions.php#L141](functions.php#L141): Kiểm tra xem người dùng hiện tại có phải là admin hay không.
- `findUserByEmailOrUsername($identity)` - [functions.php#L146](functions.php#L146): Tìm kiếm người dùng bằng email hoặc tên đăng nhập.
- `findUserById($id)` - [functions.php#L153](functions.php#L153): Tìm kiếm người dùng theo ID.
- `createUser($data)` - [functions.php#L160](functions.php#L160): Đăng ký người dùng mới.
- `setEmailVerificationToken(...)` - [functions.php#L169](functions.php#L169): Lưu token xác thực email.
- `sendVerificationEmailSimulated(...)` - [functions.php#L175](functions.php#L175): Mô phỏng gửi email xác thực (trả về link).
- `createPasswordResetToken(...)` - [functions.php#L182](functions.php#L182): Tạo token để khôi phục mật khẩu.
- `verifyPasswordResetToken($token)` - [functions.php#L188](functions.php#L188): Xác thực token khôi phục mật khẩu.
- `markPasswordResetUsed($id)` - [functions.php#L195](functions.php#L195): Đánh dấu token khôi phục mật khẩu đã được sử dụng.
- `resetUserPassword(...)` - [functions.php#L201](functions.php#L201): Cập nhật mật khẩu mới cho người dùng.
- `incrementFailedLogin($user_id)` - [functions.php#L208](functions.php#L208): Tăng số lần đăng nhập sai.
- `resetFailedLogins($user_id)` - [functions.php#L214](functions.php#L214): Đặt lại số lần đăng nhập sai khi thành công.
- `lockAccount($user_id, $minutes)` - [functions.php#L220](functions.php#L220): Khóa tài khoản trong một khoảng thời gian.
- `isAccountLocked($user)` - [functions.php#L353](functions.php#L353): Kiểm tra trạng thái khóa của tài khoản.

### Đơn hàng (Orders)
- `createOrder($data)` - [functions.php#L242](functions.php#L242): Xử lý tạo đơn hàng mới, bao gồm trừ tồn kho và ghi log biến động.
- `cancelOrder($order_id, $user_id)` - [functions.php#L307](functions.php#L307): Hủy đơn hàng và hoàn trả lại số lượng tồn kho.
- `getUserAddresses($user_id)` - [functions.php#L227](functions.php#L227): Lấy danh sách địa chỉ nhận hàng của người dùng.
- `getAddressById($id)` - [functions.php#L234](functions.php#L234): Lấy thông tin chi tiết một địa chỉ cụ thể.

### Thông báo (Notifications)
- `getUserNotifications($user_id, $limit)` - [functions.php#L482](functions.php#L482): Lấy danh sách thông báo của người dùng.
- `getUnreadNotificationCount($user_id)` - [functions.php#L489](functions.php#L489): Đếm số lượng thông báo chưa đọc.
- `markNotificationAsRead(...)` - [functions.php#L496](functions.php#L496): Đánh dấu một thông báo là đã đọc.
- `markAllNotificationsAsRead($user_id)` - [functions.php#L502](functions.php#L502): Đánh dấu tất cả thông báo là đã đọc.
- `createNotification(...)` - [functions.php#L508](functions.php#L508): Tạo một thông báo mới trong hệ thống.

### UI & Flash Messages
- `set_flash($type, $message)` - [functions.php#L360](functions.php#L360): Đặt thông báo tạm thời (toast message).
- `get_flash()` - [functions.php#L366](functions.php#L366): Lấy danh sách các thông báo tạm thời.
- `display_flash()` - [functions.php#L374](functions.php#L374): Hiển thị các thông báo toast ra màn hình.
- `slugify($text)` - [functions.php#L427](functions.php#L427): Chuyển đổi văn bản thành dạng slug (URL friendly).
- `get_order_status_badge($status)` - [functions.php#L439](functions.php#L439): Trả về mã HTML badge trạng thái đơn hàng.
- `get_status_label($status)` - [functions.php#L454](functions.php#L454): Trả về nhãn tiếng Việt của trạng thái.
- `get_payment_status_badge($status)` - [functions.php#L468](functions.php#L468): Trả về mã HTML badge trạng thái thanh toán.

---

## 2. Các hàm trong các tệp PHP khác

### [cart.php](cart.php) (JS)
- `calculateTotals()` - [cart.php#L399](cart.php#L399): Tính toán lại tổng tiền giỏ hàng sau khi thay đổi số lượng.
- `ajaxUpdate(id, delta)` - [cart.php#L473](cart.php#L473): Gửi yêu cầu AJAX cập nhật số lượng sản phẩm.
- `ajaxRemove(id)` - [cart.php#L504](cart.php#L504): Gửi yêu cầu AJAX xóa sản phẩm khỏi giỏ hàng.
- `ajaxClear()` - [cart.php#L520](cart.php#L520): Xóa toàn bộ sản phẩm trong giỏ hàng.

### [product.php](product.php) (JS)
- `updateGallery(src, thumb)` - [product.php#L350](product.php#L350): Cập nhật hình ảnh chính khi click vào ảnh thu nhỏ (thumbnail).
- `updateQty(delta)` - [product.php#L356](product.php#L356): Xử lý tăng/giảm nút số lượng sản phẩm.
- `handleAddToCart(redirect)` - [product.php#L364](product.php#L364): Gửi yêu cầu thêm vào giỏ hàng qua AJAX.

### [promotions.php](promotions.php) (JS)
- `copyCode(code)` - [promotions.php#L241](promotions.php#L241): Sao chép nhanh mã khuyến mãi vào bộ nhớ tạm.
- `updateCountdown()` - [promotions.php#L247](promotions.php#L247): Xử lý bộ đếm ngược cho các ưu đãi có thời gian.

---

## 3. JavaScript Functions ([assets/js/main.js](assets/js/main.js))

Tệp tin này điều khiển các hiệu ứng và tương tác phía client.

- `initBlossoms()` - [assets/js/main.js#L2](assets/js/main.js#L2): Tạo hiệu ứng hoa mai/đào rơi (chỉ hiển thị ở trang chủ).
- `debounce(fn, delay)` - [assets/js/main.js#L57](assets/js/main.js#L57): Hàm bổ trợ trì hoãn thực thi (dùng cho fetch search).
- `fetchSuggestions(q)` - [assets/js/main.js#L70](assets/js/main.js#L70): Xử lý tìm kiếm sản phẩm bằng AJAX khi người dùng nhập từ khóa.
- `updateSelection(items)` - [assets/js/main.js#L95](assets/js/main.js#L95): Cập nhật trạng thái chọn trong danh sách gợi ý tìm kiếm.
- `renderSuggestions(list)` - [assets/js/main.js#L100](assets/js/main.js#L100): Hiển thị danh sách gợi ý tìm kiếm ra giao diện.
- `copyVoucher(code)` - [index.php#L418](index.php#L418): Hàm copy voucher tại trang chủ.
- `markRead(id, link)` - [notifications.php#L199](notifications.php#L199): Đánh dấu thông báo đã đọc.

---

*Tài liệu này được tạo tự động dựa trên cấu trúc source code hiện tại.* 
*Lần cập nhật cuối: 2024-05-23*
