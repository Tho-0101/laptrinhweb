<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getUserRole() != ROLE_INSPECTOR) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $required = ['inspection_id', 'frame_condition', 'brake_condition', 
                 'drivetrain_condition', 'wheel_condition', 'overall_rating'];
    
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            throw new Exception("Trường {$field} không được để trống");
        }
    }
    
    $inspection = new Inspection();
    $inspection->submit($data['inspection_id'], $data);
    
    echo json_encode([
        'success' => true,
        'message' => 'Báo cáo kiểm định đã được gửi'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
