<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

requireLogin();
requireRole('seller');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$bikeId = $data['bike_id'] ?? null;

if (!$bikeId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Bike ID required']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // kiểm tra quyền
    $stmt = $db->prepare("SELECT id FROM bikes WHERE id = ? AND seller_id = ?");
    $stmt->execute([$bikeId, getUserId()]);
    $bike = $stmt->fetch();

    if (!$bike) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Bike not found']);
        exit;
    }

    // kiểm tra đơn hàng
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE bike_id = ?");
    $stmt->execute([$bikeId]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa xe đã có đơn hàng'
        ]);
        exit;
    }

    // 🔥 XÓA ẢNH TRƯỚC (tránh lỗi FK)
    $stmt = $db->prepare("DELETE FROM bike_images WHERE bike_id = ?");
    $stmt->execute([$bikeId]);

    // 🔥 XÓA XE
    $stmt = $db->prepare("DELETE FROM bikes WHERE id = ?");
    $stmt->execute([$bikeId]);

    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa vĩnh viễn'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() // debug
    ]);
}