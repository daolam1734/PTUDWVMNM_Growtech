<?php
// PDO connection helper
$DB_HOST = '127.0.0.1';
$DB_NAME = 'weblaptop';
$DB_USER = 'root';
$DB_PASS = '';

define('BASE_URL', '/weblaptop/');

// Bank Transfer Info
define('BANK_NAME', 'Ngân hàng Quân đội (MB)');
define('BANK_ID', 'MB'); // VietQR Bank ID
define('BANK_ACCOUNT_NAME', 'DAO CONG HOANG LAM');
define('BANK_ACCOUNT_NUMBER', '713076819999');
define('BANK_BRANCH', 'Vĩnh Long');

// MoMo Info
define('MOMO_PHONE', '0375370660');
define('MOMO_NAME', 'DAO CONG HOANG LAM');

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die('DB Connection failed: ' . $e->getMessage());
}
