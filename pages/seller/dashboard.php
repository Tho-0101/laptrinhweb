<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('seller');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Get statistics
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM bikes WHERE seller_id = ?) as total_bikes,
        (SELECT COUNT(*) FROM bikes WHERE seller_id = ? AND status = 'approved') as active_bikes,
        (SELECT COUNT(*) FROM orders WHERE seller_id = ?) as total_orders,
        (SELECT COUNT(*) FROM orders WHERE seller_id = ? AND status = 'pending') as pending_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE seller_id = ? AND status = 'completed') as total_revenue
";
$stmt = $db->prepare($statsQuery);
$stmt->execute([$userId, $userId, $userId, $userId, $userId]);
$stats = $stmt->fetch();

// Recent orders
$ordersQuery = "
    SELECT o.*, b.title, b.price, u.full_name as buyer_name
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    JOIN users u ON o.buyer_id = u.id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
";
$stmt = $db->prepare($ordersQuery);
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

// Active bikes
$bikesQuery = "
    SELECT b.*, c.name as category_name
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.seller_id = ? AND b.status = 'approved'
    ORDER BY b.created_at DESC
    LIMIT 4
";
$stmt = $db->prepare($bikesQuery);
$stmt->execute([$userId]);
$activeBikes = $stmt->fetchAll();

// Get seller info
$userQuery = "SELECT full_name, rating, total_sales FROM users WHERE id = ?";
$stmt = $db->prepare($userQuery);
$stmt->execute([$userId]);
$seller = $stmt->fetch();
$stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = FALSE");
$stmt->execute([$userId]);
$unreadMessages = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="../../index.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-house"></i> Trang chủ
                </a>
                <a href="../auth/logout.php" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1"><i class="bi bi-speedometer2"></i> Dashboard - Seller</h2>
                <p class="text-muted mb-0">Chào mừng, <?php echo htmlspecialchars($seller['full_name']); ?>!</p>
            </div>
            <div class="text-end">
                <div class="text-warning mb-1">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <i class="bi bi-star<?php echo $i < round($seller['rating']) ? '-fill' : ''; ?>"></i>
                    <?php endfor; ?>
                    <span class="text-white ms-2"><?php echo number_format($seller['rating'], 1); ?></span>
                </div>
                <small class="text-muted"><?php echo $seller['total_sales']; ?> giao dịch thành công</small>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-bicycle text-primary" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_bikes']; ?></h3>
                    <p class="text-muted mb-0">Tổng tin đăng</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-check-circle text-success" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['active_bikes']; ?></h3>
                    <p class="text-muted mb-0">Đang bán</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-cart-fill text-warning" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['pending_orders']; ?></h3>
                    <p class="text-muted mb-0">Chờ xử lý</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-cash-stack text-info" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0">
                        <?php echo number_format($stats['total_revenue'] / 1000000, 1); ?>M
                    </h3>
                    <p class="text-muted mb-0">Doanh thu</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4"><i class="bi bi-lightning-fill"></i> Thao tác nhanh</h5>
                    <div class="d-grid gap-2">
                        <a href="post-bike.php" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Đăng tin mới
                        </a>
                        <a href="my-bikes.php" class="btn btn-outline-light">
                            <i class="bi bi-bicycle"></i> Quản lý tin đăng
                        </a>
                        <a href="orders.php" class="btn btn-outline-light">
                            <i class="bi bi-cart"></i> Đơn hàng
                        </a>
                        <a href="messages.php" class="btn btn-outline-light">
                            <i class="bi bi-chat-dots"></i> Tin nhắn
                            <?php if ($unreadMessages > 0): ?>
                                <span class="badge bg-danger"><?php echo $unreadMessages; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="reviews.php" class="btn btn-outline-light">
                            <i class="bi bi-star"></i> Đánh giá
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="col-md-8 mb-4">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-cart"></i> Đơn hàng gần đây</h5>
                        <a href="orders.php" class="btn btn-sm btn-outline-light">Xem tất cả</a>
                    </div>

                    <?php if (!empty($recentOrders)): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Xe</th>
                                        <th>Người mua</th>
                                        <th>Giá</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr onclick="location.href='../orders/detail.php?id=<?php echo $order['id']; ?>'"
                                            style="cursor:pointer">
                                            <td><?php echo htmlspecialchars($order['title']); ?></td>
                                            <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                            <td class="text-success"><?php echo number_format($order['total_amount']); ?>₫</td>
                                            <td>
                                                <?php
                                                $badges = [
                                                    'pending' => 'warning',
                                                    'confirmed' => 'info',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger'
                                                ];
                                                $badge = $badges[$order['status']] ?? 'secondary';
                                                ?>
                                                <span
                                                    class="badge bg-<?php echo $badge; ?>"><?php echo $order['status']; ?></span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">Chưa có đơn hàng nào</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Active Bikes -->
        <?php if (!empty($activeBikes)): ?>
            <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-bicycle"></i> Tin đăng đang bán</h5>
                    <a href="my-bikes.php" class="btn btn-sm btn-outline-light">Xem tất cả</a>
                </div>

                <div class="row g-3">
                    <?php foreach ($activeBikes as $bike): ?>
                        <div class="col-md-3">
                            <div class="bike-card" onclick="location.href='../bikes/detail.php?id=<?php echo $bike['id']; ?>'">
                                <?php if (!empty($bike['main_image'])): ?>
                                    <img src="../../<?php echo htmlspecialchars($bike['main_image']); ?>" class="bike-card-img"
                                        alt="<?php echo htmlspecialchars($bike['title']); ?>">
                                <?php endif; ?>
                                <div class="bike-card-body">
                                    <h6 class="bike-title small"><?php echo htmlspecialchars($bike['title']); ?></h6>
                                    <div class="bike-price small"><?php echo number_format($bike['price']); ?>₫</div>
                                    <div class="text-muted small">
                                        <i class="bi bi-eye"></i> <?php echo $bike['view_count']; ?> lượt xem
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>