<?php

/**
 * VNPay Configuration
 * Get credentials from: https://sandbox.vnpayment.vn/
 */

// VNPay Sandbox Configuration (for testing)
define('VNPAY_TMN_CODE', 'DEMOV210'); // Demo Terminal Code
define('VNPAY_HASH_SECRET', 'RAOEXHYVSDDIIENYWSLDIIZTANXUXZFJ'); // Demo Secret Key
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'); // Sandbox Payment URL
define('VNPAY_RETURN_URL', SITE_URL . '/api/payment/vnpay-return.php'); // Return URL

/**
 * PRODUCTION Configuration (uncomment when going live):
 * 
 * define('VNPAY_TMN_CODE', 'YOUR_PRODUCTION_TMN_CODE');
 * define('VNPAY_HASH_SECRET', 'YOUR_PRODUCTION_HASH_SECRET');
 * define('VNPAY_URL', 'https://vnpayment.vn/paymentv2/vpcpay.html');
 */

// Payment Method Codes
define('VNPAY_BANK_CODES', [
    'VNPAYQR' => 'QR Pay',
    'VNBANK' => 'Thẻ ATM nội địa',
    'INTCARD' => 'Thẻ quốc tế',
    'VIETQR' => 'VietQR',
    'NCB' => 'Ngân hàng NCB',
    'VIETCOMBANK' => 'Vietcombank',
    'TECHCOMBANK' => 'Techcombank',
    'MBBANK' => 'MB Bank',
    'VIETINBANK' => 'VietinBank',
    'BIDV' => 'BIDV',
    'ACB' => 'ACB',
    'SACOMBANK' => 'Sacombank',
    'AGRIBANK' => 'Agribank'
]);

// Response Codes
define('VNPAY_RESPONSE_CODES', [
    '00' => 'Giao dịch thành công',
    '07' => 'Trừ tiền thành công. Giao dịch bị nghi ngờ (liên quan tới lừa đảo, giao dịch bất thường)',
    '09' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng chưa đăng ký dịch vụ InternetBanking tại ngân hàng',
    '10' => 'Giao dịch không thành công do: Khách hàng xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
    '11' => 'Giao dịch không thành công do: Đã hết hạn chờ thanh toán',
    '12' => 'Giao dịch không thành công do: Thẻ/Tài khoản của khách hàng bị khóa',
    '13' => 'Giao dịch không thành công do Quý khách nhập sai mật khẩu xác thực giao dịch (OTP)',
    '24' => 'Giao dịch không thành công do: Khách hàng hủy giao dịch',
    '51' => 'Giao dịch không thành công do: Tài khoản của quý khách không đủ số dư để thực hiện giao dịch',
    '65' => 'Giao dịch không thành công do: Tài khoản của Quý khách đã vượt quá hạn mức giao dịch trong ngày',
    '75' => 'Ngân hàng thanh toán đang bảo trì',
    '79' => 'Giao dịch không thành công do: KH nhập sai mật khẩu thanh toán quá số lần quy định',
    '99' => 'Các lỗi khác'
]);