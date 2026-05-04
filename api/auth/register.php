<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate
    $required = ['full_name', 'email', 'password', 'role'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Trường {$field} không được để trống");
        }
    }
    
    if (strlen($data['password']) < 6) {
        throw new Exception('Mật khẩu phải có ít nhất 6 ký tự');
    }
    
    $user = new User();
    $userId = $user->register($data);
    
    // Auto login
    $user->login($data['email'], $data['password']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đăng ký thành công',
        'data' => ['user_id' => $userId]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
