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
    
    if (empty($data['order_id']) || empty($data['status'])) {
        throw new Exception('Order ID và status không được để trống');
    }
    
    $order = new Order();
    $detail = $order->getById($data['order_id']);
    
    // Check permission
    if ($detail['buyer_id'] != getUserId() && 
        $detail['seller_id'] != getUserId() && 
        getUserRole() != ROLE_ADMIN) {
        throw new Exception('Bạn không có quyền cập nhật đơn hàng này');
    }
    
    $order->updateStatus($data['order_id'], $data['status'], $data['note'] ?? null);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật trạng thái thành công'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
