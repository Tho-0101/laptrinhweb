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
    
    $bike = new Bike();
    $isFavorited = $bike->toggleFavorite(getUserId(), $data['bike_id']);
    
    echo json_encode([
        'success' => true,
        'is_favorited' => $isFavorited,
        'message' => $isFavorited ? 'Đã thêm vào yêu thích' : 'Đã xóa khỏi yêu thích'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
