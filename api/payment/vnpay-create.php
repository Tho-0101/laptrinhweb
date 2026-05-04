<?php
/**
 * VNPay Payment Creation
 * Creates payment URL and redirects to VNPay gateway
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/vnpay.php';

requireLogin();

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Get order info from GET parameter (sent from checkout.php)
$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: ' . SITE_URL . '/pages/buyer/orders.php?error=missing_order');
    exit;
}

// Fetch order details
$stmt = $db->prepare("
    SELECT o.*, b.title, b.seller_id
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    WHERE o.id = ? AND o.buyer_id = ?
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/pages/buyer/orders.php?error=order_not_found');
    exit;
}

// Check if already paid
if ($order['payment_status'] === 'paid') {
    header('Location: ' . SITE_URL . '/pages/buyer/orders.php?error=already_paid');
    exit;
}

// Determine amount to pay (deposit if exists, otherwise full amount)
$amountToPay = $order['deposit_amount'] > 0 ? $order['deposit_amount'] : $order['total_amount'];

// Generate transaction reference using order_code
$vnp_TxnRef = $order['order_code'] ?? ('ORD' . $orderId . '_' . time());

// Update order payment method
$stmt = $db->prepare("UPDATE orders SET payment_method = 'vnpay' WHERE id = ?");
$stmt->execute([$orderId]);

// Create payment record
$stmt = $db->prepare("
    INSERT INTO payments (
        order_id, buyer_id, seller_id, 
        amount, payment_method, vnpay_txn_ref, status
    ) VALUES (?, ?, ?, ?, 'vnpay', ?, 'pending')
");
$stmt->execute([
    $orderId,
    $userId,
    $order['seller_id'],
    $amountToPay,
    $vnp_TxnRef
]);

// Build VNPay payment URL
$vnp_Params = [
    'vnp_Version' => '2.1.0',
    'vnp_Command' => 'pay',
    'vnp_TmnCode' => VNPAY_TMN_CODE,
    'vnp_Amount' => $amountToPay * 100, // VNPay uses smallest currency unit
    'vnp_CurrCode' => 'VND',
    'vnp_TxnRef' => $vnp_TxnRef,
    'vnp_OrderInfo' => 'Thanh toan don hang ' . $vnp_TxnRef,
    'vnp_OrderType' => 'billpayment',
    'vnp_Locale' => 'vn',
    'vnp_ReturnUrl' => VNPAY_RETURN_URL,
    'vnp_IpAddr' => $_SERVER['REMOTE_ADDR'],
    'vnp_CreateDate' => date('YmdHis')
];

// Sort parameters
ksort($vnp_Params);

// Create hash data
$hashData = http_build_query($vnp_Params, '', '&');
$vnp_SecureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

// Build final URL
$paymentUrl = VNPAY_URL . '?' . $hashData . '&vnp_SecureHash=' . $vnp_SecureHash;

// Redirect to VNPay
header('Location: ' . $paymentUrl);
exit;