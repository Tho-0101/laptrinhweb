<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$orderId = $data['order_id'] ?? 0;
$status = $data['status'] ?? '';

if (!$orderId || !$status) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Get order to check permissions
    $stmt = $db->prepare("SELECT buyer_id, seller_id, status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
        exit;
    }

    $currentUserId = getUserId();
    $userRole = getUserRole();

    // Check permissions
    $canUpdate = false;

    if ($status === 'cancelled') {
        // Buyer can cancel if order is pending
        if ($order['buyer_id'] == $currentUserId && $order['status'] === 'pending') {
            $canUpdate = true;
        }
        // Seller or Admin can always cancel
        if ($order['seller_id'] == $currentUserId || $userRole === 'admin') {
            $canUpdate = true;
        }
    } else {
        // Only seller or admin can update to other statuses
        if ($order['seller_id'] == $currentUserId || $userRole === 'admin') {
            $canUpdate = true;
        }
    }

    if (!$canUpdate) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền thực hiện']);
        exit;
    }

    // Update order status WITHOUT updated_at column
    $updateQuery = "UPDATE orders SET status = ?";
    $params = [$status];

    // Add timestamp columns based on status (only if they exist)
    if ($status === 'confirmed') {
        $updateQuery .= ", confirmed_at = NOW()";
    } elseif ($status === 'completed') {
        $updateQuery .= ", completed_at = NOW()";
    } elseif ($status === 'cancelled') {
        $updateQuery .= ", cancelled_at = NOW()";
    }

    $updateQuery .= " WHERE id = ?";
    $params[] = $orderId;

    $stmt = $db->prepare($updateQuery);
    $stmt->execute($params);

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật trạng thái thành công'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi database: ' . $e->getMessage()
    ]);
}