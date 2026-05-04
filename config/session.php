<?php
/**
 * Session Management with Security Features
 */

// Define constants if not already defined
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 3600 * 24 * 7); // 7 days
}
if (!defined('CSRF_TOKEN_NAME')) {
    define('CSRF_TOKEN_NAME', '_csrf_token');
}

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
    
    session_start();
}

// Regenerate session ID on first access
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
    $_SESSION['created_at'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// Check session timeout
if (isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['last_activity'] = time();

// Session hijacking protection
if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? '')) {
    session_unset();
    session_destroy();
    session_start();
}

/**
 * Helper Functions
 */

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole() {
    return $_SESSION['user_role'] ?? ROLE_GUEST;
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

function requireLogin($redirectUrl = '/pages/auth/login.php') {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirectUrl);
        exit;
    }
}

function requireRole($allowedRoles, $redirectUrl = '/index.php') {
    requireLogin();
    
    $userRole = getUserRole();
    $allowedRoles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
    
    if (!in_array($userRole, $allowedRoles)) {
        http_response_code(403);
        header('Location: ' . $redirectUrl . '?error=access_denied');
        exit;
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function loginUser($userId, $userRole, $userName, $userEmail) {
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $userRole;
    $_SESSION['user_name'] = $userName;
    $_SESSION['user_email'] = $userEmail;
    $_SESSION['login_time'] = time();
}

function logoutUser() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}
?>
