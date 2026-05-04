<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Date range filter
$dateRange = $_GET['range'] ?? '7days';
$dateCondition = match ($dateRange) {
    '7days' => 'DATE_SUB(NOW(), INTERVAL 7 DAY)',
    '30days' => 'DATE_SUB(NOW(), INTERVAL 30 DAY)',
    '90days' => 'DATE_SUB(NOW(), INTERVAL 90 DAY)',
    'year' => 'DATE_SUB(NOW(), INTERVAL 1 YEAR)',
    default => 'DATE_SUB(NOW(), INTERVAL 7 DAY)'
};

// Revenue by day
$revenueQuery = "
    SELECT DATE(created_at) as date, 
           COUNT(*) as order_count, 
           SUM(total_amount) as revenue
    FROM orders
    WHERE status = 'completed' AND created_at >= $dateCondition
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$revenueData = $db->query($revenueQuery)->fetchAll();

// Bikes by category
$categoryQuery = "
    SELECT c.name, COUNT(b.id) as count
    FROM categories c
    LEFT JOIN bikes b ON c.id = b.category_id
    GROUP BY c.id, c.name
    ORDER BY count DESC
";
$categoryData = $db->query($categoryQuery)->fetchAll();

// Bikes by status
$statusQuery = "
    SELECT status, COUNT(*) as count
    FROM bikes
    GROUP BY status
";
$statusData = $db->query($statusQuery)->fetchAll();

// Top sellers
$topSellersQuery = "
    SELECT u.full_name, u.email, u.rating,
           COUNT(DISTINCT b.id) as total_bikes,
           COUNT(DISTINCT o.id) as total_sales,
           COALESCE(SUM(o.total_amount), 0) as total_revenue
    FROM users u
    LEFT JOIN bikes b ON u.id = b.seller_id
    LEFT JOIN orders o ON b.id = o.bike_id AND o.status = 'completed'
    WHERE u.role = 'seller'
    GROUP BY u.id
    ORDER BY total_revenue DESC
    LIMIT 10
";
$topSellers = $db->query($topSellersQuery)->fetchAll();

// Inspection statistics
$inspectionQuery = "
    SELECT 
        COUNT(*) as total_inspections,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
        AVG((frame_condition + brake_condition + gear_condition + wheel_condition + tire_condition) / 5) as avg_score
    FROM inspections
    WHERE created_at >= $dateCondition
";
$inspectionStats = $db->query($inspectionQuery)->fetch();

// User growth
$userGrowthQuery = "
    SELECT DATE(created_at) as date, COUNT(*) as new_users
    FROM users
    WHERE created_at >= $dateCondition
    GROUP BY DATE(created_at)
    ORDER BY date ASC
";
$userGrowth = $db->query($userGrowthQuery)->fetchAll();

// Overall statistics
$overallQuery = "
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM bikes) as total_bikes,
        (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed') as total_revenue,
        (SELECT COUNT(*) FROM inspections) as total_inspections
";
$overall = $db->query($overallQuery)->fetch();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo & Thống kê - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="../auth/logout.php" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-graph-up"></i> Báo cáo & Thống kê</h2>

            <!-- Date Range Filter -->
            <div class="btn-group" role="group">
                <a href="?range=7days"
                    class="btn btn-sm btn-<?php echo $dateRange === '7days' ? 'success' : 'outline-light'; ?>">
                    7 ngày
                </a>
                <a href="?range=30days"
                    class="btn btn-sm btn-<?php echo $dateRange === '30days' ? 'success' : 'outline-light'; ?>">
                    30 ngày
                </a>
                <a href="?range=90days"
                    class="btn btn-sm btn-<?php echo $dateRange === '90days' ? 'success' : 'outline-light'; ?>">
                    90 ngày
                </a>
                <a href="?range=year"
                    class="btn btn-sm btn-<?php echo $dateRange === 'year' ? 'success' : 'outline-light'; ?>">
                    1 năm
                </a>
            </div>
        </div>

        <!-- Overall Stats -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-people text-primary" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo number_format($overall['total_users']); ?></h3>
                    <p class="text-muted mb-0">Tổng Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-bicycle text-info" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo number_format($overall['total_bikes']); ?></h3>
                    <p class="text-muted mb-0">Tổng Bikes</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-cart-check text-warning" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo number_format($overall['completed_orders']); ?></h3>
                    <p class="text-muted mb-0">Đơn hoàn thành</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-cash-stack text-success" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0">
                        <?php echo number_format($overall['total_revenue'] / 1000000, 1); ?>M
                    </h3>
                    <p class="text-muted mb-0">Tổng doanh thu</p>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row g-4 mb-4">
            <!-- Revenue Chart -->
            <div class="col-md-8">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4">
                        <i class="bi bi-graph-up"></i> Doanh thu theo ngày
                    </h5>
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>

            <!-- Bikes by Status -->
            <div class="col-md-4">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4">
                        <i class="bi bi-pie-chart"></i> Trạng thái Bikes
                    </h5>
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row g-4 mb-4">
            <!-- Bikes by Category -->
            <div class="col-md-6">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4">
                        <i class="bi bi-bar-chart"></i> Bikes theo danh mục
                    </h5>
                    <canvas id="categoryChart" height="120"></canvas>
                </div>
            </div>

            <!-- User Growth -->
            <div class="col-md-6">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4">
                        <i class="bi bi-graph-up-arrow"></i> Tăng trưởng Users
                    </h5>
                    <canvas id="userGrowthChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <!-- Inspection Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-12">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4">
                        <i class="bi bi-clipboard-check"></i> Thống kê kiểm định
                    </h5>
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <h3 class="text-success"><?php echo $inspectionStats['total_inspections'] ?? 0; ?></h3>
                            <p class="text-muted">Tổng kiểm định</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h3 class="text-success"><?php echo $inspectionStats['approved'] ?? 0; ?></h3>
                            <p class="text-muted">Đạt</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h3 class="text-danger"><?php echo $inspectionStats['rejected'] ?? 0; ?></h3>
                            <p class="text-muted">Không đạt</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <h3 class="text-warning">
                                <?php echo number_format($inspectionStats['avg_score'] ?? 0, 1); ?>/100
                            </h3>
                            <p class="text-muted">Điểm TB</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Sellers -->
        <div class="row g-4">
            <div class="col-md-12">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4">
                        <i class="bi bi-trophy"></i> Top Sellers
                    </h5>
                    <?php if (!empty($topSellers)): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Tên</th>
                                        <th>Email</th>
                                        <th>Rating</th>
                                        <th>Số xe</th>
                                        <th>Số đơn</th>
                                        <th>Doanh thu</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topSellers as $index => $seller): ?>
                                        <tr>
                                            <td>
                                                <?php if ($index < 3): ?>
                                                    <span class="badge bg-warning">
                                                        <?php echo ['🥇', '🥈', '🥉'][$index]; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?php echo $index + 1; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($seller['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($seller['email']); ?></td>
                                            <td>⭐ <?php echo number_format($seller['rating'], 1); ?></td>
                                            <td><?php echo $seller['total_bikes']; ?> xe</td>
                                            <td><?php echo $seller['total_sales']; ?> đơn</td>
                                            <td class="text-success fw-bold">
                                                <?php echo number_format($seller['total_revenue'] / 1000000, 1); ?>M
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center py-3">Chưa có dữ liệu</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($revenueData, 'date')); ?>,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: <?php echo json_encode(array_column($revenueData, 'revenue')); ?>,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: '#fff'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#fff'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    }
                }
            }
        });

        // Status Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($statusData, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($statusData, 'count')); ?>,
                    backgroundColor: ['#10b981', '#fbbf24', '#ef4444', '#6b7280']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                }
            }
        });

        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($categoryData, 'name')); ?>,
                datasets: [{
                    label: 'Số lượng',
                    data: <?php echo json_encode(array_column($categoryData, 'count')); ?>,
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: '#fff'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#fff'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    }
                }
            }
        });

        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($userGrowth, 'date')); ?>,
                datasets: [{
                    label: 'Users mới',
                    data: <?php echo json_encode(array_column($userGrowth, 'new_users')); ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            color: '#fff'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#fff'
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.1)'
                        }
                    }
                }
            }
        });
    </script>
</body>

</html>