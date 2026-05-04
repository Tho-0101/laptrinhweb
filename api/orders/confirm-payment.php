<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

requireLogin();

// Only seller can confirm payment
if (getUserRole() !== 'seller') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? 0;

if (!$orderId) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đơn hàng']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Verify order belongs to this seller
    $stmt = $db->prepare("
        SELECT id, payment_method, payment_status 
        FROM orders 
        WHERE id = ? AND seller_id = ?
    ");
    $stmt->execute([$orderId, getUserId()]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }

    // Can only confirm COD payments
    if ($order['payment_method'] !== 'cash') {
        echo json_encode(['success' => false, 'message' => 'Chỉ áp dụng cho đơn hàng COD']);
        exit;
    }

    // Update payment status to paid WITHOUT updated_at
    $stmt = $db->prepare("
        UPDATE orders 
        SET payment_status = 'paid',
            status = 'completed',
            completed_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$orderId]);

    echo json_encode([
        'success' => true,
        'message' => 'Đã xác nhận thanh toán thành công'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}