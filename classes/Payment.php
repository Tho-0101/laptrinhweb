<?php
/**
 * Payment Class - Xử lý thanh toán
 */
class Payment {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Create VNPay payment URL
     */
    public function createVNPayUrl($orderId, $amount, $returnUrl) {
        $vnp_TmnCode = VNPAY_TMN_CODE;
        $vnp_HashSecret = VNPAY_HASH_SECRET;
        $vnp_Url = VNPAY_URL;
        
        $vnp_TxnRef = $orderId . '_' . time();
        $vnp_OrderInfo = "Thanh toán đơn hàng #" . $orderId;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $amount * 100;
        $vnp_Locale = 'vn';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $returnUrl,
            "vnp_TxnRef" => $vnp_TxnRef
        );
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }
        
        $vnp_Url = $vnp_Url . "?" . $query;
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        
        return $vnp_Url;
    }
    
    /**
     * Create MoMo payment
     */
    public function createMoMoPayment($orderId, $amount) {
        $endpoint = MOMO_ENDPOINT;
        $partnerCode = MOMO_PARTNER_CODE;
        $accessKey = MOMO_ACCESS_KEY;
        $secretKey = MOMO_SECRET_KEY;
        $orderInfo = "Thanh toán đơn hàng #" . $orderId;
        $returnUrl = MOMO_RETURN_URL;
        $notifyurl = MOMO_NOTIFY_URL;
        $requestId = time() . "";
        $requestType = "captureWallet";
        $extraData = "";
        
        $rawHash = "accessKey=" . $accessKey .
                   "&amount=" . $amount .
                   "&extraData=" . $extraData .
                   "&ipnUrl=" . $notifyurl .
                   "&orderId=" . $orderId .
                   "&orderInfo=" . $orderInfo .
                   "&partnerCode=" . $partnerCode .
                   "&redirectUrl=" . $returnUrl .
                   "&requestId=" . $requestId .
                   "&requestType=" . $requestType;
        
        $signature = hash_hmac("sha256", $rawHash, $secretKey);
        
        $data = array(
            'partnerCode' => $partnerCode,
            'partnerName' => "BikeMarket",
            'storeId' => "BikeMarket",
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $returnUrl,
            'ipnUrl' => $notifyurl,
            'lang' => 'vi',
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature
        );
        
        $result = $this->execPostRequest($endpoint, json_encode($data));
        $jsonResult = json_decode($result, true);
        
        return $jsonResult;
    }
    
    /**
     * Execute POST request
     */
    private function execPostRequest($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
?>
