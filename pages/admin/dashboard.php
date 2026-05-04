<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Get system statistics
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE role = 'buyer') as total_buyers,
        (SELECT COUNT(*) FROM users WHERE role = 'seller') as total_sellers,
        (SELECT COUNT(*) FROM users WHERE role = 'inspector') as total_inspectors,
        (SELECT COUNT(*) FROM bikes) as total_bikes,
        (SELECT COUNT(*) FROM bikes WHERE status = 'pending') as pending_bikes,
        (SELECT COUNT(*) FROM bikes WHERE status = 'approved') as approved_bikes,
        (SELECT COUNT(*) FROM bikes WHERE is_inspected = 1) as inspected_bikes,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed') as total_revenue,
        (SELECT COUNT(*) FROM inspections) as total_inspections
";
$stmt = $db->query($statsQuery);
$stats = $stmt->fetch();

// Recent users
$usersQuery = "
    SELECT id, email, full_name, role, status, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 10
";
$stmt = $db->query($usersQuery);
$recentUsers = $stmt->fetchAll();

// Pending bikes
$pendingQuery = "
    SELECT b.*, c.name as category_name, u.full_name as seller_name
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.seller_id = u.id
    WHERE b.status = 'pending'
    ORDER BY b.created_at DESC
    LIMIT 10
";
$stmt = $db->query($pendingQuery);
$pendingBikes = $stmt->fetchAll();

// Revenue chart data (last 7 days)
$revenueQuery = "
    SELECT DATE(created_at) as date, COUNT(*) as count, SUM(total_amount) as revenue
    FROM orders
    WHERE status = 'completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
";
$stmt = $db->query($revenueQuery);
$revenueData = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BikeMarket</title>
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
                <h2 class="mb-1"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
                <p class="text-muted mb-0">Tổng quan hệ thống BikeMarket</p>
            </div>
            <div class="text-warning h4 mb-0">
                <i class="bi bi-shield-fill-check"></i> ADMIN
            </div>
        </div>

        <!-- Stats Overview - 4x3 Grid -->
        <div class="row g-4 mb-5">
            <!-- Users Stats -->
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-people text-primary" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_users']; ?></h3>
                    <p class="text-muted mb-0">Tổng Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-cart text-info" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_buyers']; ?></h3>
                    <p class="text-muted mb-0">Buyers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-shop text-warning" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_sellers']; ?></h3>
                    <p class="text-muted mb-0">Sellers</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-shield-check text-success" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_inspectors']; ?></h3>
                    <p class="text-muted mb-0">Inspectors</p>
                </div>
            </div>

            <!-- Bikes Stats -->
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-bicycle text-primary" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_bikes']; ?></h3>
                    <p class="text-muted mb-0">Tổng Bikes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-clock-history text-warning" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['pending_bikes']; ?></h3>
                    <p class="text-muted mb-0">Chờ duyệt</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-check-circle text-success" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['approved_bikes']; ?></h3>
                    <p class="text-muted mb-0">Đã duyệt</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-patch-check text-info" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['inspected_bikes']; ?></h3>
                    <p class="text-muted mb-0">Đã kiểm định</p>
                </div>
            </div>

            <!-- Orders & Revenue Stats -->
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-cart-fill text-primary" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_orders']; ?></h3>
                    <p class="text-muted mb-0">Tổng Đơn</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-check-all text-success" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['completed_orders']; ?></h3>
                    <p class="text-muted mb-0">Hoàn thành</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-cash-stack text-warning" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo number_format($stats['total_revenue']/1000000, 1); ?>M</h3>
                    <p class="text-muted mb-0">Doanh thu</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-clipboard-check text-info" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_inspections']; ?></h3>
                    <p class="text-muted mb-0">Kiểm định</p>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mb-5">
            <div class="col-md-12">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4"><i class="bi bi-lightning-fill"></i> Quản lý nhanh</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="manage-users.php" class="btn btn-outline-light w-100">
                                <i class="bi bi-people"></i> Quản lý Users
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="approve-bikes.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-bicycle"></i> Duyệt tin đăng (<?php echo $stats['pending_bikes']; ?>)
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="manage-categories.php" class="btn btn-outline-light w-100">
                                <i class="bi bi-tags"></i> Categories/Brands
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="reports.php" class="btn btn-outline-light w-100">
                                <i class="bi bi-graph-up"></i> Báo cáo & Thống kê
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Users -->
            <div class="col-md-6 mb-4">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Users mới</h5>
                        <a href="manage-users.php" class="btn btn-sm btn-outline-light">Xem tất cả</a>
                    </div>
                    
                    <?php if (!empty($recentUsers)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                <tr onclick="location.href='manage-users.php?id=<?php echo $user['id']; ?>'" style="cursor:pointer">
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo $user['role']; ?></span></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo $user['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Bikes -->
            <div class="col-md-6 mb-4">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Tin chờ duyệt</h5>
                        <a href="approve-bikes.php" class="btn btn-sm btn-outline-warning">Xem tất cả</a>
                    </div>
                    
                    <?php if (!empty($pendingBikes)): ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover">
                            <thead>
                                <tr>
                                    <th>Xe</th>
                                    <th>Seller</th>
                                    <th>Giá</th>
                                    <th>Ngày</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingBikes as $bike): ?>
                                <tr onclick="location.href='approve-bikes.php?id=<?php echo $bike['id']; ?>'" style="cursor:pointer">
                                    <td><?php echo htmlspecialchars($bike['title']); ?></td>
                                    <td><?php echo htmlspecialchars($bike['seller_name']); ?></td>
                                    <td class="text-success"><?php echo number_format($bike['price']/1000000, 1); ?>M</td>
                                    <td><?php echo date('d/m', strtotime($bike['created_at'])); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted text-center py-3">Không có tin chờ duyệt</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
