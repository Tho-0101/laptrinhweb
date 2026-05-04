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
    
    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception('Email và password không được để trống');
    }
    
    $user = new User();
    $result = $user->login($data['email'], $data['password']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'data' => [
            'user_id' => $result['id'],
            'role' => $result['role'],
            'name' => $result['full_name'],
            'email' => $result['email']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
