<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin(); // Both buyer AND seller can create orders

$bikeId = $_GET['bike_id'] ?? 0;

if (!$bikeId) {
    header('Location: ../bikes/list.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get bike details
$stmt = $db->prepare("
    SELECT b.*, u.full_name as seller_name, u.phone as seller_phone,
           c.name as category_name,
           (SELECT file_path FROM bike_images WHERE bike_id = b.id AND is_primary = 1 LIMIT 1) as image
    FROM bikes b
    JOIN users u ON b.seller_id = u.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.id = ? AND b.status = 'approved'
");
$stmt->execute([$bikeId]);
$bike = $stmt->fetch();

if (!$bike) {
    header('Location: ../bikes/list.php?error=bike_not_found');
    exit;
}

// Check if user is trying to buy their own bike
$currentUserId = getUserId();
if ($currentUserId == $bike['seller_id']) {
    header('Location: ../bikes/detail.php?id=' . $bikeId . '&error=cannot_buy_own_bike');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deliveryAddress = trim($_POST['delivery_address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $depositAmount = floatval($_POST['deposit_amount'] ?? 0);

    // Calculate remaining amount
    $totalAmount = $bike['price'];
    $remainingAmount = $totalAmount - $depositAmount;

    if (empty($deliveryAddress)) {
        $error = "Vui lòng nhập địa chỉ giao hàng";
    } elseif ($depositAmount < 0) {
        $error = "Số tiền cọc không hợp lệ";
    } elseif ($depositAmount > $totalAmount) {
        $error = "Số tiền cọc không được lớn hơn tổng tiền";
    } else {
        try {
            // Generate unique order code
            $orderCode = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));

            // Create order
            $stmt = $db->prepare("
                INSERT INTO orders (
                    order_code, buyer_id, seller_id, bike_id, 
                    total_amount, deposit_amount, remaining_amount,
                    shipping_address, buyer_note,
                    status, payment_status, payment_method
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid', ?)
            ");

            $stmt->execute([
                $orderCode,               // order_code (unique)
                $currentUserId,           // buyer_id (can be seller role!)
                $bike['seller_id'],       // seller_id
                $bikeId,                  // bike_id
                $totalAmount,             // total_amount
                $depositAmount,           // deposit_amount
                $remainingAmount,         // remaining_amount
                $deliveryAddress,         // shipping_address
                $notes,                   // buyer_note
                $paymentMethod            // payment_method
            ]);

            $orderId = $db->lastInsertId();

            // Redirect based on payment method
            if ($paymentMethod === 'cash') {
                // COD - Direct to result page
                $stmt = $db->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
                $stmt->execute([$orderId]);
                header('Location: ../payment/result.php?status=cod&order_id=' . $orderId);

            } elseif ($paymentMethod === 'bank_transfer') {
                // Bank Transfer - Show bank details
                header('Location: ../payment/result.php?status=bank_transfer&order_id=' . $orderId);

            } elseif ($paymentMethod === 'vnpay') {
                // VNPay - Go to VNPay creation
                header('Location: ../payment/checkout.php?order_id=' . $orderId);
            }
            exit;

        } catch (PDOException $e) {
            $error = "Có lỗi xảy ra: " . $e->getMessage();
            // Debug: uncomment to see full error
            // error_log("Order creation error: " . $e->getMessage());
        }
    }
}

$userName = getUserName();
$userRole = getUserRole();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt mua xe - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
    <style>
        .payment-methods .form-check {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-methods .form-check:hover {
            border-color: var(--bs-success) !important;
            background-color: rgba(25, 135, 84, 0.1);
        }

        .payment-methods .form-check-input:checked+.form-check-label {
            color: var(--bs-success);
        }

        .payment-methods .form-check:has(.form-check-input:checked) {
            border-color: var(--bs-success) !important;
            background-color: rgba(25, 135, 84, 0.15);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="../<?php echo $userRole; ?>/dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="mb-4"><i class="bi bi-cart-plus"></i> Đặt mua xe</h2>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Bike Info -->
                    <div class="col-md-5">
                        <div class="bg-dark-2-custom p-4 rounded border-dark-custom sticky-top" style="top: 20px;">
                            <h5 class="mb-3">Thông tin xe</h5>

                            <?php if ($bike['image']): ?>
                                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($bike['image']); ?>"
                                    class="w-100 rounded mb-3" style="max-height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-dark p-5 rounded text-center mb-3">
                                    <i class="bi bi-bicycle" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>

                            <h6><?php echo htmlspecialchars($bike['title']); ?></h6>
                            <div class="text-muted mb-2">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($bike['category_name']); ?>
                            </div>
                            <div class="text-muted mb-3">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Tổng tiền:</span>
                                <span class="h4 text-success mb-0">
                                    <?php echo number_format($bike['price']); ?>₫
                                </span>
                            </div>

                            <hr>

                            <div class="small">
                                <div class="text-muted mb-1">Người bán:</div>
                                <div class="fw-bold"><?php echo htmlspecialchars($bike['seller_name']); ?></div>
                                <?php if ($bike['seller_phone']): ?>
                                    <div class="text-success">
                                        <i class="bi bi-telephone"></i>
                                        <?php echo htmlspecialchars($bike['seller_phone']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Order Form -->
                    <div class="col-md-7">
                        <form method="POST" class="bg-dark-2-custom p-4 rounded border-dark-custom">
                            <h5 class="mb-4">Thông tin đặt hàng</h5>

                            <!-- Buyer Info -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person"></i> Người mua
                                </label>
                                <input type="text" class="form-control"
                                    value="<?php echo htmlspecialchars($userName); ?>" readonly>
                                <small class="text-muted">
                                    <?php if ($userRole === 'seller'): ?>
                                        <i class="bi bi-info-circle"></i> Bạn đang mua với tư cách cá nhân
                                    <?php endif; ?>
                                </small>
                            </div>

                            <!-- Delivery Address -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-geo-alt"></i> Địa chỉ giao hàng <span class="text-danger">*</span>
                                </label>
                                <textarea name="delivery_address" class="form-control" rows="3" required
                                    placeholder="Nhập địa chỉ nhận xe..."><?php echo htmlspecialchars($_POST['delivery_address'] ?? ''); ?></textarea>
                                <small class="text-muted">Địa chỉ chi tiết để người bán giao xe</small>
                            </div>

                            <!-- Notes -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-chat-left-text"></i> Ghi chú (tùy chọn)
                                </label>
                                <textarea name="notes" class="form-control" rows="2"
                                    placeholder="Ghi chú thêm cho người bán..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>

                            <!-- Payment Method Selection -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-credit-card"></i> Phương thức thanh toán <span
                                        class="text-danger">*</span>
                                </label>

                                <div class="payment-methods">
                                    <!-- COD -->
                                    <div class="form-check p-3 border border-secondary rounded mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method"
                                            id="payment_cod" value="cash" checked>
                                        <label class="form-check-label w-100" for="payment_cod">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-cash-coin text-success" style="font-size: 1.5rem;"></i>
                                                <div class="ms-3">
                                                    <strong>💵 Tiền mặt (COD)</strong>
                                                    <div class="small text-muted">Thanh toán khi nhận xe</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>



                                    <!-- VNPay -->
                                    <div class="form-check p-3 border border-secondary rounded mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method"
                                            id="payment_vnpay" value="vnpay">
                                        <label class="form-check-label w-100" for="payment_vnpay">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-credit-card text-primary"
                                                    style="font-size: 1.5rem;"></i>
                                                <div class="ms-3">
                                                    <strong>💳 VNPay Online</strong>
                                                    <div class="small text-muted">QR Code, ATM, Visa/Mastercard</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> Bạn sẽ được chuyển đến trang thanh toán sau khi
                                    đặt hàng
                                </small>
                            </div>

                            <!-- Deposit Amount -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-wallet2"></i> Tiền cọc (tùy chọn)
                                </label>
                                <div class="input-group">
                                    <input type="number" name="deposit_amount" id="deposit_amount" class="form-control"
                                        value="0" min="0" max="<?php echo $bike['price']; ?>" step="100000"
                                        placeholder="Nhập số tiền cọc...">
                                    <span class="input-group-text">₫</span>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> Đặt cọc trước, thanh toán phần còn lại khi nhận xe
                                </small>
                                <div id="deposit-info" class="mt-2 small"></div>
                            </div>

                            <!-- Order Summary -->
                            <div class="bg-dark p-3 rounded mb-4">
                                <h6 class="mb-3">Tóm tắt đơn hàng</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Giá xe:</span>
                                    <span id="bike-price"><?php echo number_format($bike['price']); ?>₫</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Phí vận chuyển:</span>
                                    <span class="text-success">Thỏa thuận</span>
                                </div>
                                <div id="deposit-summary" style="display: none;">
                                    <hr>
                                    <div class="d-flex justify-content-between mb-2 text-warning">
                                        <span>Tiền cọc:</span>
                                        <strong id="deposit-display">0₫</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 text-info">
                                        <span>Còn lại khi nhận:</span>
                                        <strong id="remaining-display">0₫</strong>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Tổng cộng:</strong>
                                    <strong class="text-success h5 mb-0">
                                        <?php echo number_format($bike['price']); ?>₫
                                    </strong>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-cart-check"></i> Xác nhận đặt mua
                                </button>
                                <a href="../bikes/detail.php?id=<?php echo $bikeId; ?>" class="btn btn-outline-light">
                                    <i class="bi bi-arrow-left"></i> Quay lại
                                </a>
                            </div>

                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i> Sau khi đặt hàng, bạn sẽ được chuyển đến trang
                                    thanh toán
                                </small>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const bikePrice = <?php echo $bike['price']; ?>;
        const depositInput = document.getElementById('deposit_amount');
        const depositInfo = document.getElementById('deposit-info');
        const depositSummary = document.getElementById('deposit-summary');
        const depositDisplay = document.getElementById('deposit-display');
        const remainingDisplay = document.getElementById('remaining-display');

        function updateDepositCalculation() {
            const depositAmount = parseFloat(depositInput.value) || 0;
            const remainingAmount = bikePrice - depositAmount;

            if (depositAmount > 0) {
                // Show summary section
                depositSummary.style.display = 'block';

                // Update displays
                depositDisplay.textContent = depositAmount.toLocaleString('vi-VN') + '₫';
                remainingDisplay.textContent = remainingAmount.toLocaleString('vi-VN') + '₫';

                // Show info message
                depositInfo.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Cọc: ' +
                    depositAmount.toLocaleString('vi-VN') + '₫ | Còn lại: ' +
                    remainingAmount.toLocaleString('vi-VN') + '₫</span>';
            } else {
                // Hide summary section
                depositSummary.style.display = 'none';
                depositInfo.innerHTML = '';
            }

            // Validate
            if (depositAmount > bikePrice) {
                depositInfo.innerHTML =
                    '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Tiền cọc không được lớn hơn tổng tiền!</span>';
            } else if (depositAmount < 0) {
                depositInfo.innerHTML =
                    '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> Tiền cọc không hợp lệ!</span>';
            }
        }

        // Update on input change
        depositInput.addEventListener('input', updateDepositCalculation);

        // Quick deposit buttons (optional - can add to HTML)
        function setDeposit(percentage) {
            depositInput.value = Math.floor(bikePrice * percentage / 100000) * 100000;
            updateDepositCalculation();
        }
    </script>
</body>

</html>