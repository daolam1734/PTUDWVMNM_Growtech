-- Database
CREATE DATABASE IF NOT EXISTS weblaptop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE weblaptop;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL, -- hashed
  `full_name` VARCHAR(255),
  `phone` VARCHAR(50),
  `role` ENUM('admin','user') NOT NULL DEFAULT 'user',
  `email_verified` TINYINT(1) DEFAULT 0,
  `verification_token` VARCHAR(255),
  `verification_expires` DATETIME,
  `failed_logins` INT DEFAULT 0,
  `locked_until` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Auth tokens for "Remember Me"
CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Password resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- User addresses (multiple per user)
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `label` VARCHAR(100) DEFAULT 'Home', -- e.g., Home, Office
  `recipient_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50),
  `address_line1` VARCHAR(255) NOT NULL,
  `address_line2` VARCHAR(255),
  `city` VARCHAR(100),
  `district` VARCHAR(100),
  `postal_code` VARCHAR(20),
  `country` VARCHAR(100) DEFAULT 'VN',
  `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL UNIQUE,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Brands
CREATE TABLE IF NOT EXISTS `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL UNIQUE,
  `logo` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sku` VARCHAR(100) UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `brand_id` INT,
  `category_id` INT,
  `short_description` VARCHAR(512),
  `description` TEXT,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `sale_price` DECIMAL(12,2) DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`brand_id`) REFERENCES `brands`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  INDEX (`name`), INDEX (`sku`)
) ENGINE=InnoDB;

-- Product specifications (dạng cột có cấu trúc cho laptop)
CREATE TABLE IF NOT EXISTS `product_specifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL UNIQUE,
  `cpu` VARCHAR(255),
  `ram` VARCHAR(100),            -- e.g., "16GB DDR4"
  `storage` VARCHAR(100),        -- e.g., "512GB NVMe SSD"
  `gpu` VARCHAR(255),
  `screen` VARCHAR(255),         -- e.g., "15.6\" FHD 144Hz"
  `os` VARCHAR(100),
  `weight` VARCHAR(50),
  `battery` VARCHAR(100),
  `ports` VARCHAR(255),
  `other` TEXT,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Product images
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `url` VARCHAR(512),
  `alt` VARCHAR(255),
  `position` INT DEFAULT 0,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Stock movements (audit)
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `change_qty` INT NOT NULL,
  `type` ENUM('initial','restock','sale','return','adjustment') NOT NULL,
  `reference` VARCHAR(255), -- e.g., order id, purchase id
  `note` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Carts (persistent or session-based)
CREATE TABLE IF NOT EXISTS `carts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `session_token` VARCHAR(255) NULL, -- for guests
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cart_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2) NOT NULL, -- price snapshot when added
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`cart_id`) REFERENCES `carts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_no` VARCHAR(50) NOT NULL UNIQUE, -- e.g., WL-20251223-0001
  `user_id` INT NULL,
  `address_id` INT NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `shipping_fee` DECIMAL(12,2) DEFAULT 0.00,
  `discount` DECIMAL(12,2) DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL,
  `order_status` ENUM('dang_cho','da_xac_nhan','dang_xu_ly','da_gui','da_giao','hoan_thanh','huy','tra_lai') NOT NULL DEFAULT 'dang_cho',
  `payment_method` ENUM('tien_mat','chuyen_khoan','vi_dien_tu') NOT NULL,
  `payment_status` ENUM('dang_cho','da_thanh_toan','that_bai','da_hoan_tien') NOT NULL DEFAULT 'dang_cho',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`address_id`) REFERENCES `user_addresses`(`id`) ON DELETE RESTRICT,
  INDEX (`order_no`), INDEX (`order_status`)
) ENGINE=InnoDB;

-- Order items (snapshot product data)
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `sku` VARCHAR(100),
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `payment_method` ENUM('tien_mat','chuyen_khoan','vi_dien_tu') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `status` ENUM('dang_cho','da_thanh_toan','that_bai','da_hoan_tien') NOT NULL DEFAULT 'dang_cho',
  `transaction_id` VARCHAR(255),
  `provider_info` JSON NULL, -- optional raw data
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Shipments
CREATE TABLE IF NOT EXISTS `shipments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `carrier` VARCHAR(100), -- e.g., GHTK, GHN
  `tracking_number` VARCHAR(255),
  `shipment_status` ENUM('dang_cho','da_gui','dang_van_chuyen','da_giao','tra_lai') NOT NULL DEFAULT 'dang_cho',
  `shipped_at` TIMESTAMP NULL,
  `delivered_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Order status history
CREATE TABLE IF NOT EXISTS `order_status_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `old_status` VARCHAR(50),
  `new_status` VARCHAR(50),
  `changed_by` INT NULL, -- admin/user id
  `note` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Reviews / Comments
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` TINYINT NOT NULL, -- 1..5
  `title` VARCHAR(255),
  `comment` TEXT,
  `status` ENUM('dang_cho','duyet','tu_choi') DEFAULT 'dang_cho',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------
-- Sample / Seed Data
-- --------------------------------------------------

-- Categories
INSERT IGNORE INTO `categories` (`name`,`slug`,`description`) VALUES
('Mỏng nhẹ','mong-nhe','Laptop mỏng nhẹ, dễ mang theo'),
('Chơi game','choi-game','Laptop hiệu suất cao dành cho chơi game'),
('Văn phòng','van-phong','Laptop cho nhu cầu văn phòng và học tập');

-- Brands
INSERT IGNORE INTO `brands` (`name`,`logo`) VALUES



-- Products (idempotent using unique sku)
INSERT IGNORE INTO `products` (`sku`,`name`,`slug`,`brand_id`,`category_id`,`short_description`,`description`,`price`,`stock`,`is_active`)
VALUES
('ULTRA-001','Alpha Mỏng Nhẹ','alpha-mong-nhe',
  (SELECT id FROM categories WHERE name='Mỏng nhẹ'),
  '13.3" FHD, Intel Core i5, 8GB RAM, 256GB SSD','Mô tả chi tiết Alpha Mỏng Nhẹ',799.00,10,1),
('GAME-002','Quái Vật Chơi Game',' Quai-vat-choi-game',
  (SELECT id FROM categories WHERE name='Chơi game'),
  '15.6" 144Hz, Intel Core i7, 16GB RAM, RTX 3060, 512GB SSD','Mô tả chi tiết Quái Vật Chơi Game',1299.00,5,1),
  (SELECT id FROM categories WHERE name='Văn phòng'),
  '14" HD, AMD Ryzen 5, 8GB RAM, 256GB SSD','Mô tả chi tiết Văn Phòng Pro',599.00,15,1);

-- Specifications


-- Images


-- Users (admin created via create_db.php is recommended; we add a sample customer)
-- Note: Replace the password with a proper hash produced by PHP: php -r "echo password_hash('password', PASSWORD_DEFAULT).PHP_EOL;"
INSERT IGNORE INTO `users` (`username`,`email`,`password`,`full_name`,`phone`,`role`)
VALUES
('nguyen','nguyen.an@gmail.com',SHA2('password',256),'Nguyễn Văn An','0912345678','user'),
('khach','khach@example.com',SHA2('guest',256),'Khách Vãng Lai','0900000000','user');

-- Addresses for sample user
INSERT IGNORE INTO `user_addresses` (`user_id`,`label`,`recipient_name`,`phone`,`address_line1`,`city`,`district`,`postal_code`,`country`,`is_default`)
SELECT u.id,'Nhà','Nguyễn Văn An','0912345678','Số 12, Phố Hàng Bạc','Hà Nội','Hoàn Kiếm','100000','VN',1 FROM users u WHERE u.email='nguyen.an@gmail.com';

-- Create a cart and add one item for John
INSERT IGNORE INTO `carts` (`user_id`) SELECT id FROM users WHERE email='nguyen.an@gmail.com';

INSERT INTO `cart_items` (`cart_id`,`product_id`,`quantity`,`unit_price`)
SELECT c.id, p.id, 1, p.price FROM carts c JOIN users u ON c.user_id=u.id JOIN products p ON p.sku='ULTRA-001' WHERE u.email='nguyen.an@gmail.com' AND NOT EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id=c.id AND ci.product_id=p.id);

-- Create an order from John's cart
INSERT INTO `orders` (`order_no`,`user_id`,`address_id`,`subtotal`,`shipping_fee`,`discount`,`total`,`order_status`,`payment_method`,`payment_status`,`notes`)
SELECT
  CONCAT('WL-', DATE_FORMAT(NOW(),'%Y%m%d'), '-', LPAD(FLOOR(RAND()*10000),4,'0')) as order_no,
  u.id,
  a.id,
  SUM(ci.unit_price * ci.quantity) as subtotal,
  15.00 as shipping_fee,
  0.00 as discount,
  SUM(ci.unit_price * ci.quantity) + 15.00 as total,
  'dang_cho' as order_status,
  'chuyen_khoan' as payment_method,
  'dang_cho' as payment_status,
  'Đơn hàng được tạo từ giỏ hàng' as notes
FROM users u
JOIN user_addresses a ON a.user_id = u.id
JOIN carts c ON c.user_id = u.id
JOIN cart_items ci ON ci.cart_id = c.id
WHERE u.email='nguyen.an@gmail.com'
GROUP BY u.id, a.id
LIMIT 1; 

-- Link order items
INSERT INTO `order_items` (`order_id`,`product_id`,`product_name`,`sku`,`quantity`,`unit_price`,`subtotal`)
SELECT o.id, ci.product_id, p.name, p.sku, ci.quantity, ci.unit_price, (ci.quantity * ci.unit_price)
FROM orders o
JOIN users u ON u.id = o.user_id
JOIN carts c ON c.user_id = u.id
JOIN cart_items ci ON ci.cart_id = c.id
JOIN products p ON p.id = ci.product_id
WHERE u.email='nguyen.an@gmail.com' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1);

-- Decrease stock and insert stock movements for the order
UPDATE products p
JOIN order_items oi ON oi.product_id = p.id
JOIN orders o ON oi.order_id = o.id
SET p.stock = GREATEST(0, p.stock - oi.quantity)
WHERE o.user_id = (SELECT id FROM users WHERE email='nguyen.an@gmail.com') AND o.order_no = (SELECT order_no FROM orders WHERE user_id = o.user_id ORDER BY created_at DESC LIMIT 1);

INSERT INTO `stock_movements` (`product_id`,`change_qty`,`type`,`reference`,`note`)
SELECT oi.product_id, -oi.quantity, 'sale', o.order_no, 'Đã đặt hàng' FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = (SELECT id FROM users WHERE email='nguyen.an@gmail.com') AND o.order_no = (SELECT order_no FROM orders WHERE user_id = o.user_id ORDER BY created_at DESC LIMIT 1);

-- Create a payment (simulate bank transfer completed)
INSERT INTO `payments` (`order_id`,`payment_method`,`amount`,`status`,`transaction_id`,`provider_info`)
SELECT o.id, 'chuyen_khoan', o.total, 'da_thanh_toan', CONCAT('GD-', LPAD(FLOOR(RAND()*10000),4,'0')), JSON_OBJECT('bank','Vietcombank')
FROM orders o JOIN users u ON o.user_id = u.id WHERE u.email = 'nguyen.an@gmail.com' AND o.payment_status='dang_cho' LIMIT 1;

-- Update order payment_status and order_status after payment
UPDATE orders o
JOIN users u ON o.user_id = u.id
SET o.payment_status = 'da_thanh_toan', o.order_status = 'da_xac_nhan'
WHERE u.email = 'nguyen.an@gmail.com' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1);

-- Create a shipment record
INSERT INTO `shipments` (`order_id`,`carrier`,`tracking_number`,`shipment_status`,`shipped_at`)
SELECT o.id, 'Giao hàng nhanh (GHN)', CONCAT('GHN-', LPAD(FLOOR(RAND()*100000),6,'0')), 'da_gui', NOW()
FROM orders o JOIN users u ON o.user_id = u.id WHERE u.email='nguyen.an@gmail.com' AND o.order_status = 'da_xac_nhan' LIMIT 1;

-- Update order status to shipped and add history entries
UPDATE orders o
JOIN users u ON o.user_id = u.id
SET o.order_status = 'da_gui'
WHERE u.email = 'nguyen.an@gmail.com' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1);

INSERT INTO `order_status_history` (`order_id`,`old_status`,`new_status`,`changed_by`,`note`)
SELECT o.id, 'dang_cho', 'da_xac_nhan', (SELECT id FROM users WHERE username='nguyen' LIMIT 1), 'Đã nhận thanh toán' FROM orders o JOIN users u ON o.user_id=u.id WHERE u.email='nguyen.an@gmail.com' AND o.order_status IN ('da_xac_nhan','da_gui') LIMIT 1;

INSERT INTO `order_status_history` (`order_id`,`old_status`,`new_status`,`changed_by`,`note`)
SELECT o.id, 'da_xac_nhan', 'da_gui', (SELECT id FROM users WHERE username='nguyen' LIMIT 1), 'Đã gửi qua nhà vận chuyển' FROM orders o JOIN users u ON o.user_id=u.id WHERE u.email='nguyen.an@gmail.com' AND o.order_status = 'da_gui' LIMIT 1;

-- Clear John's cart (sample cleanup)
DELETE ci FROM cart_items ci JOIN carts c ON ci.cart_id = c.id JOIN users u ON c.user_id = u.id WHERE u.email='nguyen.an@gmail.com';

-- Sample review (approved)
INSERT IGNORE INTO `reviews` (`product_id`,`user_id`,`rating`,`title`,`comment`,`status`)
SELECT p.id, u.id, 5, 'Rất hài lòng','Máy mỏng, hiệu năng tốt, pin dùng cả ngày, dịch vụ CSKH chu đáo','duyet'
FROM products p JOIN users u ON u.email='nguyen.an@gmail.com' WHERE p.sku = 'ULTRA-001' LIMIT 1;

-- Initialize stock_movements for any products that don't have initial record
INSERT INTO `stock_movements` (`product_id`,`change_qty`,`type`,`reference`,`note`)
SELECT p.id, p.stock, 'initial', NULL, 'Tồn kho ban đầu' FROM products p WHERE NOT EXISTS (SELECT 1 FROM stock_movements sm WHERE sm.product_id = p.id AND sm.type = 'initial');

-- --------------------------------------------------
-- Thêm dữ liệu demo bổ sung cho testing
-- - Tài khoản admin
-- - Người dùng mẫu thứ hai
-- - Nhiều đơn hàng ở trạng thái khác nhau (COD, chuyển khoản, ví điện tử)
-- - Thanh toán, vận chuyển, lịch sử trạng thái, review
-- --------------------------------------------------

-- Admin (lưu ý: nên dùng password_hash trong PHP cho môi trường thật)
INSERT IGNORE INTO `users` (`username`,`email`,`password`,`full_name`,`phone`,`role`)
VALUES ('admin','admin@weblaptop.test',SHA2('admin123',256),'Quản trị Hệ thống','0901122334','admin');

-- Thêm người dùng mẫu 2
INSERT IGNORE INTO `users` (`username`,`email`,`password`,`full_name`,`phone`,`role`)
VALUES ('tran','tran.van@gmail.com',SHA2('tranpass',256),'Trần Văn Bình','0988123456','user');

-- Địa chỉ cho Trần Văn Bình
INSERT IGNORE INTO `user_addresses` (`user_id`,`label`,`recipient_name`,`phone`,`address_line1`,`city`,`district`,`postal_code`,`country`,`is_default`)
SELECT u.id,'Nhà','Trần Văn Bình','0988123456','Số 45, Đường Láng','Hà Nội','Đống Đa','100000','VN',1 FROM users u WHERE u.email='tran.van@gmail.com';

-- Tạo giỏ hàng và thêm sản phẩm cho Trần
INSERT IGNORE INTO `carts` (`user_id`) SELECT id FROM users WHERE email='tran.van@gmail.com';

INSERT INTO `cart_items` (`cart_id`,`product_id`,`quantity`,`unit_price`)
SELECT c.id, p.id, 2, p.price FROM carts c JOIN users u ON c.user_id=u.id JOIN products p ON p.sku='GAME-002' WHERE u.email='tran.van@gmail.com' AND NOT EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id=c.id AND ci.product_id=p.id);

INSERT INTO `cart_items` (`cart_id`,`product_id`,`quantity`,`unit_price`)
SELECT c.id, p.id, 1, p.price FROM carts c JOIN users u ON c.user_id=u.id JOIN products p ON p.sku='ULTRA-001' WHERE u.email='tran.van@gmail.com' AND NOT EXISTS (SELECT 1 FROM cart_items ci WHERE ci.cart_id=c.id AND ci.product_id=p.id);

-- Đơn 1: COD (chưa thanh toán) - trạng thái dang_cho
INSERT INTO `orders` (`order_no`,`user_id`,`address_id`,`subtotal`,`shipping_fee`,`discount`,`total`,`order_status`,`payment_method`,`payment_status`,`notes`)
SELECT CONCAT('WL-', DATE_FORMAT(NOW(),'%Y%m%d'), '-', LPAD(FLOOR(RAND()*10000),4,'0')) as order_no,
  u.id, a.id, (p.price * 1) as subtotal, 20.00 as shipping_fee, 0.00 as discount, (p.price * 1 + 20.00) as total, 'dang_cho', 'tien_mat', 'dang_cho', 'Đơn COD mẫu'
FROM users u JOIN user_addresses a ON a.user_id=u.id JOIN products p ON p.sku='ULTRA-001' WHERE u.email='tran.van@gmail.com' LIMIT 1;

-- Thêm order_items cho Đơn 1
INSERT INTO `order_items` (`order_id`,`product_id`,`product_name`,`sku`,`quantity`,`unit_price`,`subtotal`)
SELECT o.id, p.id, p.name, p.sku, 1, p.price, p.price*1 FROM orders o JOIN users u ON o.user_id=u.id JOIN products p ON p.sku='ULTRA-001' WHERE u.email='tran.van@gmail.com' AND o.payment_method='tien_mat' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1) LIMIT 1;

-- Cập nhật tồn kho và stock_movements cho Đơn 1
UPDATE products p
JOIN order_items oi ON oi.product_id = p.id
JOIN orders o ON oi.order_id = o.id
SET p.stock = GREATEST(0, p.stock - oi.quantity)
WHERE o.user_id = (SELECT id FROM users WHERE email='tran.van@gmail.com') AND o.payment_method='tien_mat' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = o.user_id ORDER BY created_at DESC LIMIT 1);

INSERT INTO `stock_movements` (`product_id`,`change_qty`,`type`,`reference`,`note`)
SELECT oi.product_id, -oi.quantity, 'sale', o.order_no, 'Đơn COD - chưa thanh toán' FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = (SELECT id FROM users WHERE email='tran.van@gmail.com') AND o.payment_method='tien_mat' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = o.user_id ORDER BY created_at DESC LIMIT 1);

-- Đơn 2: Chuyển khoản (đã thanh toán, đã xác nhận, đã gửi)
INSERT INTO `orders` (`order_no`,`user_id`,`address_id`,`subtotal`,`shipping_fee`,`discount`,`total`,`order_status`,`payment_method`,`payment_status`,`notes`)
SELECT CONCAT('WL-', DATE_FORMAT(NOW(),'%Y%m%d'), '-', LPAD(FLOOR(RAND()*10000),4,'0')),
  u.id, a.id, (p.price * 2) as subtotal, 25.00 as shipping_fee, 50.00 as discount, (p.price * 2 + 25.00 - 50.00) as total, 'da_xac_nhan', 'chuyen_khoan', 'da_thanh_toan', 'Đơn chuyển khoản đã thanh toán'
FROM users u JOIN user_addresses a ON a.user_id=u.id JOIN products p ON p.sku='GAME-002' WHERE u.email='tran.van@gmail.com' LIMIT 1;

-- Thêm order_items Đơn 2
INSERT INTO `order_items` (`order_id`,`product_id`,`product_name`,`sku`,`quantity`,`unit_price`,`subtotal`)
SELECT o.id, p.id, p.name, p.sku, 2, p.price, p.price*2 FROM orders o JOIN users u ON o.user_id=u.id JOIN products p ON p.sku='GAME-002' WHERE u.email='tran.van@gmail.com' AND o.payment_method='chuyen_khoan' AND o.payment_status='da_thanh_toan' LIMIT 1;

-- Payment record cho Đơn 2
INSERT INTO `payments` (`order_id`,`payment_method`,`amount`,`status`,`transaction_id`,`provider_info`)
SELECT o.id, 'chuyen_khoan', o.total, 'da_thanh_toan', CONCAT('TR-',LPAD(FLOOR(RAND()*10000),4,'0')), JSON_OBJECT('bank','BIDV') FROM orders o JOIN users u ON o.user_id = u.id WHERE u.email='tran.van@gmail.com' AND o.payment_method='chuyen_khoan' AND o.payment_status='da_thanh_toan' LIMIT 1;

-- Cập nhật tồn kho & stock_movements cho Đơn 2
UPDATE products p
JOIN order_items oi ON oi.product_id = p.id
JOIN orders o ON oi.order_id = o.id
SET p.stock = GREATEST(0, p.stock - oi.quantity)
WHERE o.user_id = (SELECT id FROM users WHERE email='tran.van@gmail.com') AND o.payment_method='chuyen_khoan' AND o.payment_status='da_thanh_toan' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = o.user_id ORDER BY created_at DESC LIMIT 1);

INSERT INTO `stock_movements` (`product_id`,`change_qty`,`type`,`reference`,`note`)
SELECT oi.product_id, -oi.quantity, 'sale', o.order_no, 'Đơn chuyển khoản - đã thanh toán' FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = (SELECT id FROM users WHERE email='tran.van@gmail.com') AND o.payment_method='chuyen_khoan' AND o.payment_status='da_thanh_toan' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = o.user_id ORDER BY created_at DESC LIMIT 1);

-- Tạo vận đơn cho Đơn 2 (đã gửi)
INSERT INTO `shipments` (`order_id`,`carrier`,`tracking_number`,`shipment_status`,`shipped_at`)
SELECT o.id, 'Giao hàng nhanh (GHN)', CONCAT('GHN-',LPAD(FLOOR(RAND()*100000),6,'0')), 'da_gui', NOW() FROM orders o JOIN users u ON o.user_id=u.id WHERE u.email='tran.van@gmail.com' AND o.payment_method='chuyen_khoan' AND o.payment_status='da_thanh_toan' LIMIT 1;

-- Cập nhật trạng thái đơn sang đã gửi
UPDATE orders o JOIN users u ON o.user_id=u.id SET o.order_status = 'da_gui' WHERE u.email='tran.van@gmail.com' AND o.payment_method='chuyen_khoan' AND o.payment_status='da_thanh_toan' AND o.order_no = (SELECT order_no FROM orders WHERE user_id = u.id ORDER BY created_at DESC LIMIT 1);

INSERT INTO `order_status_history` (`order_id`,`old_status`,`new_status`,`changed_by`,`note`)
SELECT o.id, 'da_xac_nhan', 'da_gui', (SELECT id FROM users WHERE username='admin' LIMIT 1), 'Đã gửi qua GHN' FROM orders o JOIN users u ON o.user_id=u.id WHERE u.email='tran.van@gmail.com' AND o.order_status='da_gui' LIMIT 1;

-- Đơn 3: Thanh toán ví điện tử thất bại
INSERT INTO `orders` (`order_no`,`user_id`,`address_id`,`subtotal`,`shipping_fee`,`discount`,`total`,`order_status`,`payment_method`,`payment_status`,`notes`)
SELECT CONCAT('WL-', DATE_FORMAT(NOW(),'%Y%m%d'), '-', LPAD(FLOOR(RAND()*10000),4,'0')),
  u.id, a.id, p.price as subtotal, 15.00 as shipping_fee, 0.00 as discount, p.price + 15.00 as total, 'dang_cho', 'vi_dien_tu', 'that_bai', 'Thanh toán ví thất bại - mẫu'

INSERT INTO `order_items` (`order_id`,`product_id`,`product_name`,`sku`,`quantity`,`unit_price`,`subtotal`)

-- Payment thất bại record
INSERT INTO `payments` (`order_id`,`payment_method`,`amount`,`status`,`transaction_id`,`provider_info`)
SELECT o.id, 'vi_dien_tu', o.total, 'that_bai', CONCAT('EWT-',LPAD(FLOOR(RAND()*10000),4,'0')), JSON_OBJECT('provider','MoMo') FROM orders o JOIN users u ON o.user_id = u.id WHERE u.email='tran.van@gmail.com' AND o.payment_method='vi_dien_tu' LIMIT 1;

-- Đơn 4: Hoàn thành (giao xong và hoàn thành)
INSERT INTO `orders` (`order_no`,`user_id`,`address_id`,`subtotal`,`shipping_fee`,`discount`,`total`,`order_status`,`payment_method`,`payment_status`,`notes`)
SELECT CONCAT('WL-', DATE_FORMAT(NOW(),'%Y%m%d'), '-', LPAD(FLOOR(RAND()*10000),4,'0')),
  u.id, a.id, p.price as subtotal, 20.00 as shipping_fee, 0.00 as discount, p.price + 20.00 as total, 'hoan_thanh', 'chuyen_khoan', 'da_thanh_toan', 'Đơn hoàn thành mẫu'
FROM users u JOIN user_addresses a ON a.user_id=u.id JOIN products p ON p.sku='GAME-002' WHERE u.email='tran.van@gmail.com' LIMIT 1;

INSERT INTO `order_items` (`order_id`,`product_id`,`product_name`,`sku`,`quantity`,`unit_price`,`subtotal`)
SELECT o.id, p.id, p.name, p.sku, 1, p.price, p.price FROM orders o JOIN users u ON o.user_id=u.id JOIN products p ON p.sku='GAME-002' WHERE u.email='tran.van@gmail.com' AND o.order_status='hoan_thanh' LIMIT 1;

INSERT INTO `payments` (`order_id`,`payment_method`,`amount`,`status`,`transaction_id`,`provider_info`)
SELECT o.id, 'chuyen_khoan', o.total, 'da_thanh_toan', CONCAT('TRX-',LPAD(FLOOR(RAND()*10000),4,'0')), JSON_OBJECT('bank','VietinBank') FROM orders o JOIN users u ON o.user_id = u.id WHERE u.email='tran.van@gmail.com' AND o.order_status='hoan_thanh' LIMIT 1;

INSERT INTO `shipments` (`order_id`,`carrier`,`tracking_number`,`shipment_status`,`shipped_at`,`delivered_at`)
SELECT o.id, 'VNPost', CONCAT('VN-',LPAD(FLOOR(RAND()*100000),6,'0')), 'da_giao', NOW(), NOW() FROM orders o JOIN users u ON o.user_id=u.id WHERE u.email='tran.van@gmail.com' AND o.order_status='hoan_thanh' LIMIT 1;

-- Review pending and approved sample
INSERT IGNORE INTO `reviews` (`product_id`,`user_id`,`rating`,`title`,`comment`,`status`)
SELECT p.id, u.id, 4, 'Máy ổn','Hiệu năng tốt, chỉ mong pin tốt hơn','dang_cho' FROM products p JOIN users u ON u.email='tran.van@gmail.com' WHERE p.sku='GAME-002' LIMIT 1;

INSERT IGNORE INTO `reviews` (`product_id`,`user_id`,`rating`,`title`,`comment`,`status`)
SELECT p.id, u.id, 5, 'Tuyệt vời','Sản phẩm đúng như mô tả, giao nhanh','duyet' FROM products p JOIN users u ON u.email='tran.van@gmail.com' WHERE p.sku='GAME-002' LIMIT 1;

-- Đảm bảo tồn kho có bản ghi stock_movements cho mọi sản phẩm mới giảm
INSERT INTO `stock_movements` (`product_id`,`change_qty`,`type`,`reference`,`note`)
SELECT p.id, -SUM(oi.quantity), 'sale', o.order_no, CONCAT('Bán mẫu: ', o.order_no)
FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN products p ON p.id = oi.product_id
WHERE o.user_id IN (SELECT id FROM users WHERE email IN ('tran.van@gmail.com'))
GROUP BY p.id, o.order_no;

-- Kết thúc phần dữ liệu demo bổ sung

-- --------------------------------------------------
-- Thêm danh sách sản phẩm mẫu (dữ liệu thực tế) do người dùng cung cấp
-- --------------------------------------------------

-- Brands thật
INSERT IGNORE INTO `brands` (`name`,`logo`,`created_at`) VALUES
('Dell','', '2023-12-01 09:00:00'),
('Apple','', '2023-12-02 09:00:00'),
('Asus','', '2023-12-01 15:00:00'),
('Lenovo','', '2023-12-03 11:00:00'),
('HP','', '2023-12-04 10:00:00'),
  ('Acer','', '2023-12-06 10:00:00');
-- Categories bổ sung
INSERT IGNORE INTO `categories` (`name`,`slug`,`description`,`created_at`) VALUES
('Laptop Văn Phòng','laptop-van-phong','Laptop dành cho công việc văn phòng', '2023-12-01 08:00:00'),
('Laptop Gaming','laptop-gaming','Laptop hiệu suất cao cho chơi game', '2023-12-01 08:00:00'),
('Laptop Doanh Nhân','laptop-doanh-nhan','Laptop dành cho doanh nhân', '2023-12-02 08:00:00'),
('Laptop Học Tập','laptop-hoc-tap','Laptop phù hợp cho học sinh, sinh viên', '2023-12-03 08:00:00'),
('Laptop Cao Cấp','laptop-cao-cap','Laptop cao cấp cho người dùng chuyên nghiệp', '2023-12-03 09:00:00'),
('Laptop 2 trong 1','laptop-2-trong-1','Laptop 2-in-1 có màn hình cảm ứng', '2023-12-04 08:00:00');

-- Sản phẩm mẫu (idempotent bằng sku)
INSERT IGNORE INTO `products` (`sku`,`name`,`slug`,`brand_id`,`category_id`,`short_description`,`description`,`price`,`sale_price`,`stock`,`is_active`,`created_at`,`updated_at`)
VALUES
('DELLXPS13','Dell XPS 13 9310','dell-xps-13-9310', (SELECT id FROM brands WHERE name='Dell'), (SELECT id FROM categories WHERE name='Laptop Văn Phòng'), 'Laptop mỏng, nhẹ, hiệu suất cao','Màn hình 13.4-inch, Intel Core i7, RAM 16GB, SSD 512GB. Thiết kế sang trọng, hiệu suất mạnh mẽ cho công việc văn phòng và giải trí.', 28999000.00, 25999000.00, 50, 1, '2023-12-01 10:00:00','2023-12-01 10:00:00'),
('MACBOOKAIR2022','MacBook Air M2 (2022)','macbook-air-m2-2022', (SELECT id FROM brands WHERE name='Apple'), (SELECT id FROM categories WHERE name='Laptop Văn Phòng'), 'Mỏng nhẹ, hiệu năng mạnh với chip M2','Màn hình 13.6-inch Retina, chip Apple M2, RAM 8GB, SSD 256GB. Thích hợp cho công việc sáng tạo và sử dụng hàng ngày.', 32990000.00, 30990000.00, 30, 1, '2023-12-02 09:00:00','2023-12-02 09:00:00'),
('ASUSROG14','Asus ROG Zephyrus G14 (2023)','asus-rog-zephyrus-g14-2023', (SELECT id FROM brands WHERE name='Asus'), (SELECT id FROM categories WHERE name='Laptop Gaming'), 'Laptop gaming siêu mạnh, thiết kế mỏng','AMD Ryzen 9 6900HS, RAM 32GB, SSD 1TB, GPU NVIDIA RTX 3060. Màn hình 14-inch 120Hz, thiết kế siêu mỏng và mạnh mẽ.', 44990000.00, NULL, 20, 1, '2023-12-01 15:00:00','2023-12-01 15:00:00'),
('THINKPADX1','Lenovo ThinkPad X1 Carbon Gen 9','lenovo-thinkpad-x1-carbon-gen-9', (SELECT id FROM brands WHERE name='Lenovo'), (SELECT id FROM categories WHERE name='Laptop Doanh Nhân'), 'Laptop doanh nhân bền bỉ, hiệu suất cao','Intel Core i7, RAM 16GB, SSD 512GB, Màn hình 14-inch 4K UHD, thiết kế bền bỉ, trọng lượng nhẹ.', 39000000.00, NULL, 40, 1, '2023-12-03 11:00:00','2023-12-03 11:00:00'),
('DELLINSPIRON15','Dell Inspiron 15 5000','dell-inspiron-15-5000', (SELECT id FROM brands WHERE name='Dell'), (SELECT id FROM categories WHERE name='Laptop Học Tập'), 'Laptop giá rẻ, phù hợp cho học sinh, sinh viên','Intel Core i5-1135G7, RAM 8GB, SSD 512GB. Màn hình 15.6-inch Full HD, máy tính xách tay giá rẻ, đủ mạnh cho các tác vụ học tập cơ bản.', 15990000.00, NULL, 100, 1, '2023-12-03 14:00:00','2023-12-03 14:00:00'),
('PAVILIONX360','HP Pavilion x360 14','hp-pavilion-x360-14', (SELECT id FROM brands WHERE name='HP'), (SELECT id FROM categories WHERE name='Laptop 2 trong 1'), 'Laptop 2 trong 1, màn hình cảm ứng','Intel Core i5, RAM 8GB, SSD 512GB. Màn hình 14-inch cảm ứng, thiết kế linh hoạt có thể xoay 360 độ.', 22500000.00, 20000000.00, 60, 1, '2023-12-04 10:00:00','2023-12-04 10:00:00'), (SELECT id FROM categories WHERE name='Laptop Gaming'), 'Laptop gaming mạnh mẽ, thiết kế mỏng','Intel Core i7-12800H, RAM 16GB, SSD 1TB, GPU NVIDIA RTX 3070 Ti. Màn hình 15.6-inch Full HD 165Hz.', 52000000.00, 49500000.00, 10, 1, '2023-12-05 09:00:00','2023-12-05 09:00:00'),
('PREDATORHELIOS','Acer Predator Helios 300','acer-predator-helios-300', (SELECT id FROM brands WHERE name='Acer'), (SELECT id FROM categories WHERE name='Laptop Gaming'), 'Laptop gaming với hiệu năng vượt trội','Intel Core i7-11800H, RAM 16GB, SSD 512GB, GPU NVIDIA RTX 3060. Màn hình 15.6-inch Full HD 144Hz.', 35500000.00, 32000000.00, 15, 1, '2023-12-06 10:00:00','2023-12-06 10:00:00'),
('XPS15','Dell XPS 15 9500','dell-xps-15-9500', (SELECT id FROM brands WHERE name='Dell'), (SELECT id FROM categories WHERE name='Laptop Cao Cấp'), 'Laptop cao cấp, màn hình 15.6-inch','Intel Core i7-10750H, RAM 16GB, SSD 512GB. Màn hình 15.6-inch 4K OLED, thiết kế tuyệt đẹp và hiệu suất mạnh mẽ.', 49000000.00, 45000000.00, 25, 1, '2023-12-07 10:00:00','2023-12-07 10:00:00'); (SELECT id FROM categories WHERE name='Laptop Cao Cấp'), 'Laptop cao cấp, màn hình 15.6-inch','Intel Core i7-10750H, RAM 16GB, SSD 512GB. Màn hình 15.6-inch 4K OLED, thiết kế tuyệt đẹp và hiệu suất mạnh mẽ.', 49000000.00, 45000000.00, 25, 1, '2023-12-07 10:00:00','2023-12-07 10:00:00'); (SELECT id FROM categories WHERE name='Laptop Cao Cấp'), 'Laptop cao cấp, màn hình 15.6-inch','Intel Core i7-10750H, RAM 16GB, SSD 512GB. Màn hình 15.6-inch 4K OLED, thiết kế tuyệt đẹp và hiệu suất mạnh mẽ.', 49000000.00, 45000000.00, 25, 1, '2023-12-07 10:00:00','2023-12-07 10:00:00'), (SELECT id FROM categories WHERE name='Laptop Văn Phòng'), 'Laptop mỏng nhẹ, màn hình 15-inch','Intel Core i7-1185G7, RAM 16GB, SSD 512GB. Màn hình 15-inch PixelSense, thiết kế mỏng nhẹ, dành cho công việc văn phòng và giải trí.', 29990000.00, NULL, 30, 1, '2023-12-07 15:00:00','2023-12-07 15:00:00');

-- Thông số kỹ thuật mẫu (product_specifications)




-- Ảnh sản phẩm (placeholder)
INSERT IGNORE INTO `product_images` (`product_id`,`url`,`alt`,`position`)
SELECT p.id, CONCAT('https://placehold.co/800x600?text=', REPLACE(p.name,' ','+')), CONCAT(p.name, ' - hình chính'), 0 FROM products p WHERE p.sku IN ('DELLXPS13','MACBOOKAIR2022','ASUSROG14','THINKPADX1','DELLINSPIRON15','PAVILIONX360','PREDATORHELIOS','XPS15');

-- Kết thúc phần cập nhật sản phẩm mẫu

-- --------------------------------------------------
-- Migration: Add auth-related columns and tables
-- --------------------------------------------------

-- Add email verification and login lock fields to users
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `email_verified` TINYINT(1) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `verification_token` VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `verification_expires` DATETIME DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `failed_logins` INT DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `locked_until` DATETIME DEFAULT NULL;

-- Table for persistent auth tokens (remember-me)
CREATE TABLE IF NOT EXISTS `auth_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Table for password reset tokens
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- End of migration

-- End of seed data

