<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['receiver_id']) || empty($data['message'])) {
        throw new Exception('Receiver ID và message không được để trống');
    }
    
    $message = new Message();
    $messageId = $message->send(
        getUserId(),
        $data['receiver_id'],
        $data['message'],
        $data['bike_id'] ?? null
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Tin nhắn đã được gửi',
        'data' => ['message_id' => $messageId]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
