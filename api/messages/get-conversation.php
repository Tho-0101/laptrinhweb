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
    $partnerId = $_GET['partner_id'] ?? 0;
    
    if (!$partnerId) {
        throw new Exception('Partner ID không hợp lệ');
    }
    
    $message = new Message();
    $messages = $message->getConversation(getUserId(), $partnerId);
    
    // Mark as read
    $message->markAsRead(getUserId(), $partnerId);
    
    echo json_encode([
        'success' => true,
        'data' => $messages
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
