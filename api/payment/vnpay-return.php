<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

// Verify VNPay signature
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_" && $key != 'vnp_SecureHash') {
        $inputData[$key] = $value;
    }
}

ksort($inputData);
$hashData = "";
foreach ($inputData as $key => $value) {
    $hashData .= urlencode($key) . "=" . urlencode($value) . '&';
}
$hashData = rtrim($hashData, '&');

$secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

if ($secureHash == $vnp_SecureHash) {
    if ($_GET['vnp_ResponseCode'] == '00') {
        // Payment success
        $orderId = explode('_', $_GET['vnp_TxnRef'])[0];
        $transactionId = $_GET['vnp_TransactionNo'];
        
        $order = new Order();
        $order->confirmDeposit($orderId);
        $order->updatePaymentStatus($orderId, 'paid', $transactionId);
        
        header('Location: ../../pages/orders/detail.php?id=' . $orderId . '&payment=success');
    } else {
        header('Location: ../../pages/orders/detail.php?id=' . $orderId . '&payment=failed');
    }
} else {
    die('Invalid signature');
}
?>
