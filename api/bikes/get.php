<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $bikeId = $_GET['id'] ?? 0;
    
    if (!$bikeId) {
        throw new Exception('Bike ID không hợp lệ');
    }
    
    $bike = new Bike();
    $detail = $bike->getById($bikeId);
    
    if (!$detail) {
        throw new Exception('Không tìm thấy xe');
    }
    
    // Increment view count
    $bike->incrementViews($bikeId);
    
    echo json_encode([
        'success' => true,
        'data' => $detail
    ]);
    
} catch (Exception $e) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
