<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$orderId = $_GET['id'] ?? 0;

if (!$orderId) {
    header('Location: ../buyer/orders.php');
    exit;
}

// Get order details directly from database
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT o.*,
           b.title as bike_title, 
           b.price as bike_price,
           (SELECT file_path FROM bike_images WHERE bike_id = b.id AND is_primary = 1 LIMIT 1) as bike_image,
           buyer.full_name as buyer_name,
           buyer.email as buyer_email,
           buyer.phone as buyer_phone,
           seller.full_name as seller_name,
           seller.email as seller_email,
           seller.phone as seller_phone
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    JOIN users buyer ON o.buyer_id = buyer.id
    JOIN users seller ON o.seller_id = seller.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$detail = $stmt->fetch();

if (!$detail) {
    header('Location: ../buyer/orders.php?error=order_not_found');
    exit;
}

// Check permissions
$currentUserId = getUserId();
$isBuyer = ($detail['buyer_id'] == $currentUserId);
$isSeller = ($detail['seller_id'] == $currentUserId);
$isAdmin = (getUserRole() === 'admin');

if (!$isBuyer && !$isSeller && !$isAdmin) {
    header('Location: ../buyer/orders.php?error=access_denied');
    exit;
}

// Status badge colors
$statusColors = [
    'pending' => '#fbbf24',
    'confirmed' => '#10b981',
    'shipping' => '#3b82f6',
    'completed' => '#10b981',
    'cancelled' => '#ef4444'
];
$statusColor = $statusColors[$detail['status']] ?? '#737373';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $orderId; ?> - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
    <style>
    .timeline {
        position: relative;
        padding-left: 2rem;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--bs-secondary);
    }

    .timeline-item {
        position: relative;
        margin-bottom: 2rem;
    }

    .timeline-marker {
        position: absolute;
        left: -1.625rem;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid var(--bs-dark);
    }

    .timeline-content {
        padding-left: 1rem;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <a href="../<?php echo getUserRole(); ?>/dashboard.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-receipt"></i> Đơn hàng #<?php echo $detail['order_code']; ?></h2>
            <div>
                <span class="badge" style="background-color: <?php echo $statusColor; ?>; font-size: 1rem;">
                    <?php
                    $statusLabels = [
                        'pending' => 'Chờ xử lý',
                        'confirmed' => 'Đã xác nhận',
                        'shipping' => 'Đang giao',
                        'completed' => 'Hoàn thành',
                        'cancelled' => 'Đã hủy'
                    ];
                    echo $statusLabels[$detail['status']] ?? strtoupper($detail['status']);
                    ?>
                </span>
            </div>
        </div>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Bike Info -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-3"><i class="bi bi-bicycle"></i> Thông tin xe</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <?php if ($detail['bike_image']): ?>
                            <img src="../../<?php echo htmlspecialchars($detail['bike_image']); ?>"
                                class="w-100 rounded" alt="Bike">
                            <?php else: ?>
                            <div class="bg-dark p-4 rounded text-center">
                                <i class="bi bi-bicycle" style="font-size: 3rem;"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <h6><?php echo htmlspecialchars($detail['bike_title']); ?></h6>
                            <div class="h4 text-success mb-3">
                                <?php echo number_format($detail['total_amount']); ?>₫
                            </div>
                            <a href="../bikes/detail.php?id=<?php echo $detail['bike_id']; ?>"
                                class="btn btn-sm btn-outline-light">
                                <i class="bi bi-eye"></i> Xem chi tiết xe
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Payment Actions for Buyer -->
                <?php if ($isBuyer && $detail['payment_status'] === 'unpaid' && $detail['status'] !== 'cancelled' && $detail['payment_method'] !== 'cash'): ?>
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-3"><i class="bi bi-credit-card"></i> Thanh toán</h5>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Đơn hàng chưa thanh toán
                    </div>
                    <div class="d-grid gap-2">
                        <a href="../payment/payment-redirect.php?order_id=<?php echo $orderId; ?>"
                            class="btn btn-success btn-lg">
                            <i class="bi bi-credit-card"></i> Thanh toán ngay
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- COD Info for Buyer -->
                <?php if ($isBuyer && $detail['payment_method'] === 'cash' && $detail['status'] !== 'cancelled'): ?>
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-3"><i class="bi bi-cash-coin"></i> Thanh toán COD</h5>

                    <?php if ($detail['payment_status'] === 'paid'): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Đã thanh toán đầy đủ
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Thanh toán khi nhận xe
                    </div>
                    <?php endif; ?>

                    <?php if ($detail['deposit_amount'] > 0): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tổng tiền:</span>
                            <span><?php echo number_format($detail['total_amount']); ?>₫</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 text-warning">
                            <span>Tiền cọc:</span>
                            <strong><?php echo number_format($detail['deposit_amount']); ?>₫</strong>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>Thanh toán khi nhận:</span>
                            <strong><?php echo number_format($detail['remaining_amount']); ?>₫</strong>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="mb-3">Bạn sẽ thanh toán <strong
                            class="text-success"><?php echo number_format($detail['total_amount']); ?>₫</strong>
                        khi nhận xe.</p>
                    <?php endif; ?>

                    <div class="small text-muted">
                        <i class="bi bi-check-circle"></i> Kiểm tra xe trước khi thanh toán<br>
                        <i class="bi bi-check-circle"></i> Mang đủ tiền mặt<br>
                        <i class="bi bi-check-circle"></i> Người bán sẽ liên hệ sớm
                    </div>
                </div>
                <?php endif; ?>

                <!-- Seller COD Actions -->
                <?php if ($isSeller && $detail['payment_method'] === 'cash' && $detail['payment_status'] === 'unpaid' && $detail['status'] !== 'cancelled'): ?>
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-3"><i class="bi bi-cash-coin"></i> Xác nhận thanh toán</h5>

                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Người mua chưa thanh toán
                    </div>

                    <?php if ($detail['deposit_amount'] > 0): ?>
                    <div class="mb-3 small">
                        <strong>Số tiền cần thu:</strong>
                        <div class="text-success h5"><?php echo number_format($detail['remaining_amount']); ?>₫
                        </div>
                        <div class="text-muted">
                            (Đã cọc: <?php echo number_format($detail['deposit_amount']); ?>₫)
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mb-3 small">
                        <strong>Số tiền cần thu:</strong>
                        <div class="text-success h5"><?php echo number_format($detail['total_amount']); ?>₫</div>
                    </div>
                    <?php endif; ?>

                    <button class="btn btn-success w-100" onclick="confirmPayment(<?php echo $orderId; ?>)">
                        <i class="bi bi-check-circle"></i> Xác nhận đã nhận tiền đầy đủ
                    </button>
                </div>
                <?php endif; ?>

                <!-- Cancel Button for Buyer -->
                <?php if ($isBuyer && $detail['status'] === 'pending'): ?>
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-3"><i class="bi bi-x-circle"></i> Hủy đơn hàng</h5>
                    <p class="text-muted small">Bạn có thể hủy đơn hàng khi đơn hàng đang ở trạng thái "Chờ xử lý"</p>
                    <button class="btn btn-danger w-100" onclick="cancelOrder(<?php echo $orderId; ?>)">
                        <i class="bi bi-x-circle"></i> Hủy đơn hàng
                    </button>
                </div>
                <?php endif; ?>

                <!-- Order Timeline -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-3"><i class="bi bi-clock-history"></i> Lịch sử đơn hàng</h5>

                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <strong>Đơn hàng đã tạo</strong>
                                <div class="text-muted small">
                                    <?php echo date('d/m/Y H:i', strtotime($detail['created_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <?php if (in_array($detail['status'], ['confirmed', 'shipping', 'completed'])): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <strong>Đã xác nhận</strong>
                                <div class="text-muted small">
                                    <?php echo $detail['confirmed_at'] ? date('d/m/Y H:i', strtotime($detail['confirmed_at'])) : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($detail['status'] === 'completed'): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <strong>Đã hoàn thành</strong>
                                <div class="text-muted small">
                                    <?php echo $detail['completed_at'] ? date('d/m/Y H:i', strtotime($detail['completed_at'])) : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($detail['status'] === 'cancelled'): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <strong class="text-danger">Đã hủy</strong>
                                <div class="text-muted small">
                                    <?php echo $detail['cancelled_at'] ? date('d/m/Y H:i', strtotime($detail['cancelled_at'])) : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Payment Summary -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-3"><i class="bi bi-wallet2"></i> Thanh toán</h5>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tổng tiền:</span>
                            <strong><?php echo number_format($detail['total_amount']); ?>₫</strong>
                        </div>

                        <?php if ($detail['deposit_amount'] > 0): ?>
                        <div class="d-flex justify-content-between mb-2 text-success">
                            <span>Đã cọc:</span>
                            <strong><?php echo number_format($detail['deposit_amount']); ?>₫</strong>
                        </div>
                        <div class="d-flex justify-content-between text-muted">
                            <span>Còn lại:</span>
                            <span><?php echo number_format($detail['remaining_amount']); ?>₫</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <hr>

                    <div class="mb-2">
                        <strong>Phương thức:</strong>
                        <?php
                        $methodIcons = [
                            'cash' => '💵',
                            'bank_transfer' => '🏦',
                            'vnpay' => '💳',
                            'momo' => '💰'
                        ];
                        $methods = [
                            'cash' => 'Tiền mặt (COD)',
                            'bank_transfer' => 'Chuyển khoản',
                            'vnpay' => 'VNPay',
                            'momo' => 'MoMo'
                        ];
                        $icon = $methodIcons[$detail['payment_method']] ?? '💳';
                        $method = $methods[$detail['payment_method']] ?? $detail['payment_method'];
                        echo $icon . ' ' . $method;
                        ?>
                    </div>

                    <div class="mb-2">
                        <strong>Trạng thái:</strong>
                        <?php
                        if ($detail['payment_method'] === 'cash' && $detail['payment_status'] === 'unpaid') {
                            echo '<span class="badge bg-warning">Thanh toán khi nhận</span>';
                        } elseif ($detail['payment_status'] === 'paid') {
                            echo '<span class="badge bg-success">Đã thanh toán</span>';
                        } elseif ($detail['payment_status'] === 'unpaid') {
                            echo '<span class="badge bg-danger">Chưa thanh toán</span>';
                        } else {
                            echo '<span class="badge bg-secondary">' . htmlspecialchars($detail['payment_status']) . '</span>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Buyer Info -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h6 class="mb-3"><i class="bi bi-person"></i> Người mua</h6>
                    <div class="mb-2"><strong><?php echo htmlspecialchars($detail['buyer_name']); ?></strong></div>
                    <div class="text-muted small mb-1">
                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($detail['buyer_phone']); ?>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($detail['buyer_email']); ?>
                    </div>
                </div>

                <!-- Seller Info -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h6 class="mb-3"><i class="bi bi-shop"></i> Người bán</h6>
                    <div class="mb-2"><strong><?php echo htmlspecialchars($detail['seller_name']); ?></strong></div>
                    <div class="text-muted small mb-1">
                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($detail['seller_phone']); ?>
                    </div>
                    <div class="text-muted small">
                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($detail['seller_email']); ?>
                    </div>
                </div>

                <!-- Delivery Address -->
                <?php if (!empty($detail['shipping_address'])): ?>
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h6 class="mb-3"><i class="bi bi-geo-alt"></i> Địa chỉ giao hàng</h6>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($detail['shipping_address'])); ?></p>
                    <?php if (!empty($detail['buyer_note'])): ?>
                    <hr>
                    <h6 class="mb-2"><i class="bi bi-chat-left-text"></i> Ghi chú</h6>
                    <p class="mb-0 text-muted small"><?php echo nl2br(htmlspecialchars($detail['buyer_note'])); ?></p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function cancelOrder(orderId) {
        if (!confirm('Bạn có chắc muốn hủy đơn hàng này?')) return;

        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';

        fetch('../../api/orders/update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'cancelled'
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Đơn hàng đã được hủy thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể hủy đơn hàng'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-x-circle"></i> Hủy đơn hàng';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Lỗi kết nối: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-x-circle"></i> Hủy đơn hàng';
            });
    }

    function confirmPayment(orderId) {
        if (!confirm('Xác nhận đã nhận đầy đủ tiền từ người mua?')) return;

        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xử lý...';

        fetch('../../api/orders/confirm-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Đã xác nhận thanh toán thành công!');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể xác nhận thanh toán'));
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-circle"></i> Xác nhận đã nhận tiền đầy đủ';
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Lỗi kết nối: ' + err.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Xác nhận đã nhận tiền đầy đủ';
            });
    }
    </script>
</body>

</html>