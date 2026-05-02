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

    // Lấy status thay vì is_hidden
    $stmt = $db->prepare("SELECT id, status FROM bikes WHERE id = ? AND seller_id = ?");
    $stmt->execute([$bikeId, getUserId()]);
    $bike = $stmt->fetch();

    if (!$bike) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Bike not found']);
        exit;
    }

    // Toggle status
    $newStatus = ($bike['status'] === 'hidden') ? 'approved' : 'hidden';

    $stmt = $db->prepare("UPDATE bikes SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $bikeId]);

    echo json_encode([
        'success' => true,
        'status' => $newStatus,
        'message' => $newStatus === 'hidden' ? 'Đã ẩn tin đăng' : 'Đã hiện tin đăng'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage() //  để debug (sau này có thể ẩn)
    ]);
}