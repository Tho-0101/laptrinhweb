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
    
    if (empty($data['bike_id'])) {
        throw new Exception('Bike ID không được để trống');
    }
    
    $orderData = [
        'buyer_id' => getUserId(),
        'bike_id' => $data['bike_id'],
        'deposit' => $data['deposit'] ?? false,
        'payment_method' => $data['payment_method'] ?? PAYMENT_METHOD_COD
    ];
    
    $order = new Order();
    $orderId = $order->create($orderData);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đơn hàng đã được tạo',
        'data' => [
            'order_id' => $orderId
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
