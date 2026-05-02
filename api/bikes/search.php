<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

try {
    $filters = [
        'category_id' => $_GET['category_id'] ?? null,
        'min_price' => $_GET['min_price'] ?? null,
        'max_price' => $_GET['max_price'] ?? null,
        'city' => $_GET['city'] ?? null,
        'condition_status' => $_GET['condition'] ?? null,
        'is_inspected' => isset($_GET['inspected']) ? (bool)$_GET['inspected'] : null,
        'sort' => $_GET['sort'] ?? 'newest'
    ];
    
    $page = $_GET['page'] ?? 1;
    $perPage = $_GET['per_page'] ?? 12;
    $searchTerm = $_GET['q'] ?? '';
    
    $bike = new Bike();
    $result = $bike->search($filters, $page, $perPage, $searchTerm);
    
    echo json_encode([
        'success' => true,
        'data' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
