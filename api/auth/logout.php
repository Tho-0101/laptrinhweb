<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';

header('Content-Type: application/json');

logoutUser();

echo json_encode([
    'success' => true,
    'message' => 'Đăng xuất thành công'
]);
?>
