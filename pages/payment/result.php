<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$status = $_GET['status'] ?? '';
$orderId = $_GET['order_id'] ?? 0;

// Get order details if order_id provided
$orderDetail = null;
if ($orderId) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT o.*, b.title as bike_title, b.price as bike_price
        FROM orders o
        JOIN bikes b ON o.bike_id = b.id
        WHERE o.id = ? AND o.buyer_id = ?
    ");
    $stmt->execute([$orderId, getUserId()]);
    $orderDetail = $stmt->fetch();
}

// Determine status type (ONLY COD & VNPay)
$isSuccess = in_array($status, ['cod', 'vnpay_success']);
$isFailed = in_array($status, ['vnpay_failed', 'failed']);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả thanh toán - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
    <style>
        .result-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }

        .result-success {
            color: #10b981;
        }

        .result-failed {
            color: #ef4444;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="text-center mb-4">
                    <?php if ($isSuccess): ?>
                        <i class="bi bi-check-circle-fill result-icon result-success"></i>
                        <h2 class="text-success">Đặt hàng thành công!</h2>

                    <?php elseif ($isFailed): ?>
                        <i class="bi bi-x-circle-fill result-icon result-failed"></i>
                        <h2 class="text-danger">Thanh toán thất bại</h2>

                    <?php else: ?>
                        <i class="bi bi-exclamation-triangle-fill result-icon result-failed"></i>
                        <h2>Trạng thái không xác định</h2>
                    <?php endif; ?>
                </div>

                <!-- Order Info Card -->
                <?php if ($orderDetail): ?>
                    <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                        <h5 class="mb-3"><i class="bi bi-receipt"></i> Thông tin đơn hàng</h5>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Mã đơn hàng:</div>
                            <div class="col-6 text-end">
                                <strong><?php echo htmlspecialchars($orderDetail['order_code'] ?? '#' . $orderId); ?></strong>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Xe:</div>
                            <div class="col-6 text-end"><?php echo htmlspecialchars($orderDetail['bike_title']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Tổng tiền:</div>
                            <div class="col-6 text-end text-success">
                                <strong><?php echo number_format($orderDetail['total_amount']); ?>₫</strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Status Specific Content (ONLY COD & VNPay) -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <?php if ($status === 'cod'): ?>
                        <!-- COD Success -->
                        <h5 class="mb-3">💵 Thanh toán khi nhận xe (COD)</h5>
                        <div class="alert alert-success">
                            <i class="bi bi-info-circle"></i> Đơn hàng của bạn đã được xác nhận
                        </div>
                        <p>Bạn sẽ thanh toán tiền mặt khi nhận xe từ người bán.</p>

                        <?php if ($orderDetail && $orderDetail['deposit_amount'] > 0): ?>
                            <div class="bg-dark p-3 rounded mb-3">
                                <h6 class="text-warning mb-3">Thông tin thanh toán:</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tổng tiền:</span>
                                    <span><?php echo number_format($orderDetail['total_amount']); ?>₫</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-warning">
                                    <span>Tiền cọc:</span>
                                    <strong><?php echo number_format($orderDetail['deposit_amount']); ?>₫</strong>
                                </div>
                                <div class="d-flex justify-content-between text-success">
                                    <span>Thanh toán khi nhận:</span>
                                    <strong><?php echo number_format($orderDetail['remaining_amount']); ?>₫</strong>
                                </div>
                            </div>
                        <?php endif; ?>

                        <p><strong>Lưu ý:</strong></p>
                        <ul>
                            <li>Kiểm tra kỹ xe trước khi thanh toán</li>
                            <li>Mang theo đủ tiền mặt:
                                <?php
                                $amountToPayCOD = $orderDetail ?
                                    ($orderDetail['deposit_amount'] > 0 ? $orderDetail['remaining_amount'] : $orderDetail['total_amount'])
                                    : 0;
                                echo number_format($amountToPayCOD);
                                ?>₫
                            </li>
                            <li>Người bán sẽ liên hệ với bạn sớm</li>
                        </ul>

                    <?php elseif ($status === 'vnpay_success'): ?>
                        <!-- VNPay Success -->
                        <h5 class="mb-3">💳 VNPay - Thanh toán thành công</h5>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle"></i> Giao dịch đã được xử lý thành công
                        </div>
                        <?php if (isset($_GET['vnp_TransactionNo'])): ?>
                            <p>Mã giao dịch VNPay: <strong><?php echo htmlspecialchars($_GET['vnp_TransactionNo']); ?></strong>
                            </p>
                        <?php endif; ?>
                        <p>Người bán sẽ liên hệ với bạn để giao xe sớm nhất.</p>

                    <?php elseif ($status === 'vnpay_failed'): ?>
                        <!-- VNPay Failed -->
                        <h5 class="mb-3">💳 VNPay - Giao dịch thất bại</h5>
                        <div class="alert alert-danger">
                            <i class="bi bi-x-circle"></i> Thanh toán không thành công
                        </div>
                        <?php if (isset($_GET['message'])): ?>
                            <p class="text-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                <?php echo htmlspecialchars($_GET['message']); ?>
                            </p>
                        <?php endif; ?>
                        <p>Nguyên nhân có thể do:</p>
                        <ul>
                            <li>Hủy giao dịch</li>
                            <li>Thẻ không đủ số dư</li>
                            <li>Thông tin thẻ không chính xác</li>
                            <li>Lỗi kết nối</li>
                        </ul>
                        <p>Vui lòng thử lại hoặc chọn thanh toán COD.</p>

                    <?php else: ?>
                        <!-- Unknown Status -->
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> Không xác định được trạng thái thanh toán
                        </div>
                        <p>Vui lòng kiểm tra đơn hàng trong lịch sử mua hàng.</p>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2">
                    <?php if ($orderId): ?>
                        <a href="../orders/detail.php?id=<?php echo $orderId; ?>" class="btn btn-primary">
                            <i class="bi bi-receipt"></i> Xem chi tiết đơn hàng
                        </a>
                    <?php endif; ?>

                    <?php if ($isFailed && $orderId): ?>
                        <a href="checkout.php?order_id=<?php echo $orderId; ?>" class="btn btn-warning">
                            <i class="bi bi-arrow-clockwise"></i> Thử lại
                        </a>
                    <?php endif; ?>

                    <a href="../buyer/orders.php" class="btn btn-outline-light">
                        <i class="bi bi-list"></i> Danh sách đơn hàng
                    </a>

                    <a href="../../index.php" class="btn btn-outline-light">
                        <i class="bi bi-house"></i> Về trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>