<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('seller');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Filter
$statusFilter = $_GET['status'] ?? 'all';

$query = "
    SELECT o.*, b.title, b.price, b.image_url, u.full_name as buyer_name, u.phone as buyer_phone
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    JOIN users u ON o.buyer_id = u.id
    WHERE o.seller_id = ?
";

$params = [$userId];

if ($statusFilter !== 'all') {
    $query .= " AND o.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by status
$statsQuery = "
    SELECT status, COUNT(*) as count
    FROM orders
    WHERE seller_id = ?
    GROUP BY status
";
$stmt = $db->prepare($statsQuery);
$stmt->execute([$userId]);
$statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
                <a href="../auth/logout.php" class="btn btn-danger btn-sm">Đăng xuất</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4"><i class="bi bi-cart"></i> Quản lý đơn hàng</h2>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" href="?status=all">
                    Tất cả (<?php echo count($orders); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" href="?status=pending">
                    Chờ xử lý (<?php echo $statusCounts['pending'] ?? 0; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'confirmed' ? 'active' : ''; ?>"
                    href="?status=confirmed">
                    Đã xác nhận (<?php echo $statusCounts['confirmed'] ?? 0; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>"
                    href="?status=completed">
                    Hoàn thành (<?php echo $statusCounts['completed'] ?? 0; ?>)
                </a>
            </li>
        </ul>

        <?php if (!empty($orders)): ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
            <div class="col-12">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="row">
                        <div class="col-md-2">
                            <?php if (!empty($order['main_image'])): ?>
                            <img src="../../<?php echo htmlspecialchars($order['main_image']); ?>"
                                class="w-100 rounded">
                            <?php else: ?>
                            <div class="bg-dark p-4 rounded text-center"><i class="bi bi-bicycle"
                                    style="font-size:3rem"></i></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <h5 class="mb-2"><?php echo htmlspecialchars($order['title']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person"></i> Người mua:
                                <strong><?php echo htmlspecialchars($order['buyer_name']); ?></strong>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($order['buyer_phone']); ?>
                            </p>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-calendar"></i>
                                <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </p>
                        </div>

                        <div class="col-md-4 text-end">
                            <div class="h4 text-success mb-3"><?php echo number_format($order['total_amount']); ?>₫
                            </div>

                            <?php
                                    $badges = [
                                        'pending' => ['warning', 'Chờ xử lý'],
                                        'confirmed' => ['info', 'Đã xác nhận'],
                                        'shipping' => ['primary', 'Đang giao'],
                                        'completed' => ['success', 'Hoàn thành'],
                                        'cancelled' => ['danger', 'Đã hủy']
                                    ];
                                    $status = $badges[$order['status']] ?? ['secondary', $order['status']];
                                    ?>
                            <span class="badge bg-<?php echo $status[0]; ?> mb-3"><?php echo $status[1]; ?></span>

                            <div class="d-grid gap-2">
                                <?php if ($order['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-success"
                                    onclick="confirmOrder(<?php echo $order['id']; ?>)">
                                    <i class="bi bi-check-circle"></i> Xác nhận
                                </button>
                                <button class="btn btn-sm btn-danger"
                                    onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                    <i class="bi bi-x-circle"></i> Từ chối
                                </button>
                                <?php elseif ($order['status'] === 'confirmed'): ?>
                                <button class="btn btn-sm btn-primary"
                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'shipping')">
                                    <i class="bi bi-truck"></i> Đang giao
                                </button>
                                <?php elseif ($order['status'] === 'shipping'): ?>
                                <button class="btn btn-sm btn-success"
                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed')">
                                    <i class="bi bi-check-all"></i> Hoàn thành
                                </button>
                                <?php endif; ?>

                                <a href="../orders/detail.php?id=<?php echo $order['id']; ?>"
                                    class="btn btn-sm btn-outline-light">
                                    <i class="bi bi-eye"></i> Chi tiết
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-cart-x" style="font-size:5rem"></i>
            <h3 class="mt-3">Chưa có đơn hàng nào</h3>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmOrder(orderId) {
        if (!confirm('Xác nhận đơn hàng này?')) return;
        updateOrderStatus(orderId, 'confirmed');
    }

    function cancelOrder(orderId) {
        const reason = prompt('Lý do từ chối:');
        if (!reason) return;

        fetch('../../api/orders/update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: 'cancelled',
                    cancellation_reason: reason
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Lỗi: ' + data.message);
            });
    }

    function updateOrderStatus(orderId, newStatus) {
        fetch('../../api/orders/update-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    order_id: orderId,
                    status: newStatus
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Lỗi: ' + data.message);
            });
    }
    </script>
</body>

</html>