<?php
/**
 * VNPay Return URL Handler
 * Processes payment result from VNPay gateway
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/vnpay.php';

$db = Database::getInstance()->getConnection();

// Get VNPay response parameters
$vnp_Params = $_GET;

// Extract secure hash
$vnp_SecureHash = $vnp_Params['vnp_SecureHash'] ?? '';
unset($vnp_Params['vnp_SecureHash']);
unset($vnp_Params['vnp_SecureHashType']);

// Sort parameters
ksort($vnp_Params);

// Verify signature
$hashData = http_build_query($vnp_Params, '', '&');
$secureHash = hash_hmac('sha512', $hashData, VNPAY_HASH_SECRET);

$isValidSignature = ($secureHash === $vnp_SecureHash);

// Extract payment info
$vnp_TxnRef = $vnp_Params['vnp_TxnRef'] ?? '';
$vnp_ResponseCode = $vnp_Params['vnp_ResponseCode'] ?? '';
$vnp_TransactionNo = $vnp_Params['vnp_TransactionNo'] ?? '';
$vnp_Amount = ($vnp_Params['vnp_Amount'] ?? 0) / 100;
$vnp_BankCode = $vnp_Params['vnp_BankCode'] ?? '';
$vnp_BankTranNo = $vnp_Params['vnp_BankTranNo'] ?? '';
$vnp_CardType = $vnp_Params['vnp_CardType'] ?? '';

if (!$isValidSignature) {
    header('Location: ' . SITE_URL . '/pages/payment/result.php?status=error&message=' . urlencode('Chữ ký không hợp lệ'));
    exit;
}

// Find order by order_code (vnp_TxnRef)
$stmt = $db->prepare("SELECT id FROM orders WHERE order_code = ?");
$stmt->execute([$vnp_TxnRef]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . SITE_URL . '/pages/payment/result.php?status=error&message=' . urlencode('Không tìm thấy đơn hàng'));
    exit;
}

$orderId = $order['id'];

// Update payment record
$paymentStatus = ($vnp_ResponseCode === '00') ? 'completed' : 'failed';

$stmt = $db->prepare("
    UPDATE payments 
    SET 
        vnpay_response_code = ?,
        vnpay_transaction_no = ?,
        bank_code = ?,
        status = ?,
        payment_date = NOW()
    WHERE vnpay_txn_ref = ?
");

$stmt->execute([
    $vnp_ResponseCode,
    $vnp_TransactionNo,
    $vnp_BankCode,
    $paymentStatus,
    $vnp_TxnRef
]);

// Update order
if ($vnp_ResponseCode === '00') {
    $stmt = $db->prepare("
        UPDATE orders 
        SET payment_status = 'paid', payment_method = 'vnpay', status = 'confirmed'
        WHERE id = ?
    ");
    $stmt->execute([$orderId]);

    header('Location: ' . SITE_URL . '/pages/payment/result.php?status=vnpay_success&order_id=' . $orderId . '&vnp_TransactionNo=' . $vnp_TransactionNo);
} else {
    $errorMessage = VNPAY_RESPONSE_CODES[$vnp_ResponseCode] ?? 'Giao dịch không thành công';
    header('Location: ' . SITE_URL . '/pages/payment/result.php?status=vnpay_failed&order_id=' . $orderId . '&code=' . $vnp_ResponseCode . '&message=' . urlencode($errorMessage));
}
exit;