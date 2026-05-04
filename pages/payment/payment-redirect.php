<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$orderId = $_GET['order_id'] ?? 0;

if (!$orderId) {
    header('Location: ../buyer/orders.php?error=missing_order');
    exit;
}

// Get order payment method
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT payment_method, buyer_id 
    FROM orders 
    WHERE id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order || $order['buyer_id'] != getUserId()) {
    header('Location: ../buyer/orders.php?error=invalid_order');
    exit;
}

// Smart redirect based on payment method
if ($order['payment_method'] === 'vnpay') {
    // Go directly to VNPay
    header('Location: ../../api/payment/vnpay-create.php?order_id=' . $orderId);

} elseif ($order['payment_method'] === 'cash') {
    // COD - already confirmed, go to result
    header('Location: result.php?status=cod&order_id=' . $orderId);

} elseif ($order['payment_method'] === 'bank_transfer') {
    // Bank - show bank details
    header('Location: result.php?status=bank_transfer&order_id=' . $orderId);

} else {
    // No payment method set or unknown - go to checkout to select
    header('Location: checkout.php?order_id=' . $orderId);
}

exit;