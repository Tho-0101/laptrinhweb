<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$orderId = $_GET['order_id'] ?? 0;

// Get order details
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT o.*, b.title as bike_title, b.price as bike_price,
           u.full_name as seller_name
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    JOIN users u ON o.seller_id = u.id
    WHERE o.id = ? AND o.buyer_id = ?
");
$stmt->execute([$orderId, getUserId()]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ../buyer/orders.php?error=order_not_found');
    exit;
}

// Handle payment method selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? '';

    if ($paymentMethod === 'cash') {
        // COD - Update order and redirect to result
        $stmt = $db->prepare("UPDATE orders SET payment_method = 'cash', status = 'confirmed' WHERE id = ?");
        $stmt->execute([$orderId]);
        header('Location: result.php?status=cod&order_id=' . $orderId);
        exit;

    } elseif ($paymentMethod === 'bank_transfer') {
        // Bank Transfer - Update and show bank details
        $stmt = $db->prepare("UPDATE orders SET payment_method = 'bank_transfer' WHERE id = ?");
        $stmt->execute([$orderId]);
        header('Location: result.php?status=bank_transfer&order_id=' . $orderId);
        exit;

    } elseif ($paymentMethod === 'vnpay') {
        // VNPay - Redirect to VNPay creation
        header('Location: ../../api/payment/vnpay-create.php?order_id=' . $orderId);
        exit;
    }
}

$userName = getUserName();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
    <style>
        .payment-option {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid var(--bs-secondary);
        }

        .payment-option:hover {
            border-color: var(--bs-success);
            background-color: rgba(25, 135, 84, 0.1);
        }

        .payment-option input[type="radio"]:checked+label {
            color: var(--bs-success);
        }

        .payment-option:has(input:checked) {
            border-color: var(--bs-success) !important;
            background-color: rgba(25, 135, 84, 0.15);
        }

        .payment-icon {
            font-size: 2.5rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <span class="text-muted">Đăng nhập: <?php echo htmlspecialchars($userName); ?></span>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="mb-4"><i class="bi bi-credit-card"></i> Chọn phương thức thanh toán</h2>

                <!-- Order Info -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-3">Thông tin đơn hàng</h5>
                    <div class="row mb-2">
                        <div class="col-6 text-muted">Mã đơn:</div>
                        <div class="col-6 text-end">
                            <strong><?php echo htmlspecialchars($order['order_code']); ?></strong>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-muted">Xe:</div>
                        <div class="col-6 text-end"><?php echo htmlspecialchars($order['bike_title']); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6 text-muted">Người bán:</div>
                        <div class="col-6 text-end"><?php echo htmlspecialchars($order['seller_name']); ?></div>
                    </div>

                    <?php if ($order['deposit_amount'] > 0): ?>
                        <hr>
                        <div class="row mb-2">
                            <div class="col-6 text-muted">Tổng tiền:</div>
                            <div class="col-6 text-end"><?php echo number_format($order['total_amount']); ?>₫</div>
                        </div>
                        <div class="row mb-2 text-warning">
                            <div class="col-6">Tiền cọc:</div>
                            <div class="col-6 text-end">
                                <strong><?php echo number_format($order['deposit_amount']); ?>₫</strong>
                            </div>
                        </div>
                        <div class="row mb-2 text-info">
                            <div class="col-6">Còn lại:</div>
                            <div class="col-6 text-end">
                                <strong><?php echo number_format($order['remaining_amount']); ?>₫</strong>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr>
                    <div class="row">
                        <div class="col-6"><strong>Cần thanh toán:</strong></div>
                        <div class="col-6 text-end">
                            <strong class="text-success h4 mb-0">
                                <?php
                                $amountToPay = $order['deposit_amount'] > 0 ? $order['deposit_amount'] : $order['total_amount'];
                                echo number_format($amountToPay);
                                ?>₫
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Payment Methods -->
                <form method="POST">
                    <div class="mb-4">
                        <h5 class="mb-3">Chọn phương thức thanh toán</h5>

                        <!-- Cash/COD -->
                        <div class="payment-option bg-dark-2-custom p-4 rounded mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="method_cash"
                                    value="cash" checked>
                                <label class="form-check-label w-100" for="method_cash">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-cash-coin text-success payment-icon me-3"></i>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">💵 Tiền mặt (COD)</h6>
                                            <p class="text-muted small mb-0">
                                                Thanh toán khi nhận xe. Kiểm tra xe trước khi thanh toán.
                                            </p>
                                            <?php if ($order['deposit_amount'] > 0): ?>
                                                <div class="alert alert-warning mt-2 mb-0 small">
                                                    <i class="bi bi-info-circle"></i>
                                                    Bạn cần thanh toán
                                                    <strong><?php echo number_format($order['deposit_amount']); ?>₫</strong>
                                                    tiền cọc trước.
                                                    Số còn lại
                                                    <strong><?php echo number_format($order['remaining_amount']); ?>₫</strong>
                                                    thanh toán khi nhận xe.
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>


                        <!-- VNPay -->
                        <div class="payment-option bg-dark-2-custom p-4 rounded mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="method_vnpay"
                                    value="vnpay">
                                <label class="form-check-label w-100" for="method_vnpay">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-credit-card text-primary payment-icon me-3"></i>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">💳 VNPay Online</h6>
                                            <p class="text-muted small mb-0">
                                                Thanh toán qua QR Code, ATM, Visa/Mastercard. Xác nhận ngay lập tức.
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <button type="submit" id="submitBtn" class="btn btn-success btn-lg">
                            <i class="bi bi-cash-coin"></i> Xác nhận đặt hàng COD
                        </button>
                        <a href="../orders/detail.php?id=<?php echo $orderId; ?>" class="btn btn-outline-light">
                            <i class="bi bi-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update submit button text based on selected payment method
        const submitBtn = document.getElementById('submitBtn');
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');

        function updateButtonText() {
            const selected = document.querySelector('input[name="payment_method"]:checked').value;

            if (selected === 'cash') {
                submitBtn.innerHTML = '<i class="bi bi-cash-coin"></i> Xác nhận đặt hàng COD';
                submitBtn.className = 'btn btn-success btn-lg';
            } else if (selected === 'bank_transfer') {
                submitBtn.innerHTML = '<i class="bi bi-bank"></i> Xác nhận & xem thông tin CK';
                submitBtn.className = 'btn btn-warning btn-lg';
            } else if (selected === 'vnpay') {
                submitBtn.innerHTML = '<i class="bi bi-credit-card"></i> Thanh toán VNPay';
                submitBtn.className = 'btn btn-primary btn-lg';
            }
        }

        // Update on radio change
        paymentMethods.forEach(radio => {
            radio.addEventListener('change', updateButtonText);
        });

        // Set initial text
        updateButtonText();
    </script>
</body>

</html>