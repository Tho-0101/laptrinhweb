<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('buyer');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Get statistics
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM favorites WHERE user_id = ?) as wishlist_count,
        (SELECT COUNT(*) FROM orders WHERE buyer_id = ?) as orders_count,
        (SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = ?) as unread_messages
";
$stmt = $db->prepare($statsQuery);
$stmt->execute([$userId, $userId, $userId, $userId]);
$stats = $stmt->fetch();

// Recent orders
$ordersQuery = "
    SELECT o.*, b.title, b.price, b.image_url, u.full_name as seller_name
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    JOIN users u ON b.seller_id = u.id
    WHERE o.buyer_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
";
$stmt = $db->prepare($ordersQuery);
$stmt->execute([$userId]);
$recentOrders = $stmt->fetchAll();

// Wishlist
$wishlistQuery = "
    SELECT b.*, c.name as category_name
    FROM favorites f
    JOIN bikes b ON f.bike_id = b.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT 4
";
$stmt = $db->prepare($wishlistQuery);
$stmt->execute([$userId]);
$wishlist = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Buyer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
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
        <h2 class="mb-4">
            <i class="bi bi-speedometer2"></i> Dashboard - Buyer
        </h2>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-heart-fill text-danger" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['wishlist_count']; ?></h3>
                    <p class="text-muted mb-0">Yêu thích</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-cart-fill text-primary" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['orders_count']; ?></h3>
                    <p class="text-muted mb-0">Đơn hàng</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-clock-fill text-warning" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['pending_orders']; ?></h3>
                    <p class="text-muted mb-0">Chờ xử lý</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-chat-dots-fill text-info" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['unread_messages']; ?></h3>
                    <p class="text-muted mb-0">Tin nhắn</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4"><i class="bi bi-lightning-fill"></i> Thao tác nhanh</h5>
                    <div class="d-grid gap-2">
                        <a href="../bikes/list.php" class="btn btn-success">
                            <i class="bi bi-search"></i> Tìm xe mới
                        </a>
                        <a href="wishlist.php" class="btn btn-outline-light">
                            <i class="bi bi-heart"></i> Xem yêu thích
                        </a>
                        <a href="orders.php" class="btn btn-outline-light">
                            <i class="bi bi-cart"></i> Đơn hàng của tôi
                        </a>
                        <a href="../chat/inbox.php" class="btn btn-outline-light">
                            <i class="bi bi-chat-dots"></i> Tin nhắn
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
                                            <td class="text-success"><?php echo number_format($order['price']); ?>₫</td>
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

        <!-- Wishlist Preview -->
        <?php if (!empty($wishlist)): ?>
            <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-heart-fill text-danger"></i> Xe yêu thích</h5>
                    <a href="wishlist.php" class="btn btn-sm btn-outline-light">Xem tất cả</a>
                </div>

                <div class="row g-3">
                    <?php foreach ($wishlist as $bike): ?>
                        <div class="col-md-3">
                            <div class="bike-card" onclick="location.href='../bikes/detail.php?id=<?php echo $bike['id']; ?>'">
                                <div class="bike-card-body">
                                    <h6 class="bike-title small"><?php echo htmlspecialchars($bike['title']); ?></h6>
                                    <div class="bike-price small"><?php echo number_format($bike['price']); ?>₫</div>
                                    <div class="bike-location small">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>
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