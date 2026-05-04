<?php
/**
 * BikeMarket - Website Kết Nối Mua Bán Xe Đạp Thể Thao Cũ
 * Main Configuration File
 */

// Load constants first
require_once __DIR__ . '/constants.php';

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Site settings
define('SITE_NAME', 'BikeMarket - Nền tảng mua bán xe đạp');
define('SITE_URL', 'http://localhost:8888/bike-marketplace-complete');
define('SITE_EMAIL', 'support@bikemarket.vn');
define('ADMIN_EMAIL', 'admin@bikemarket.vn');

// Paths
define('ROOT_PATH', __DIR__ . '/..');
define('UPLOAD_DIR', ROOT_PATH . '/assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');

// Upload settings
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);
define('MAX_IMAGES_PER_BIKE', 10);

// Security settings
define('PASSWORD_MIN_LENGTH', 6);
define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days
define('CSRF_TOKEN_NAME', '_csrf_token');

// Error reporting (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
