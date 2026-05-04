<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('inspector');

$db = Database::getInstance()->getConnection();

// Get all pending bikes
$query = "
    SELECT b.*, c.name as category_name, u.full_name as seller_name, u.phone as seller_phone
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.seller_id = u.id
    WHERE b.status = 'approved' AND b.is_inspected = 0
    ORDER BY b.created_at ASC
";
$stmt = $db->query($query);
$bikes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xe chờ kiểm định - BikeMarket</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clock-history"></i> Xe chờ kiểm định</h2>
            <span class="badge bg-warning fs-5"><?php echo count($bikes); ?> xe</span>
        </div>

        <?php if (!empty($bikes)): ?>
        <div class="row g-4">
            <?php foreach ($bikes as $bike): ?>
            <div class="col-12">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="row">
                        <div class="col-md-2">
                            <?php if (!empty($bike['main_image'])): ?>
                            <img src="../../<?php echo htmlspecialchars($bike['main_image']); ?>" class="w-100 rounded">
                            <?php else: ?>
                            <div class="bg-dark p-4 rounded text-center">
                                <i class="bi bi-bicycle" style="font-size:3rem"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-7">
                            <h5 class="mb-2">
                                <a href="../bikes/detail.php?id=<?php echo $bike['id']; ?>" 
                                   class="text-white text-decoration-none">
                                    <?php echo htmlspecialchars($bike['title']); ?>
                                </a>
                            </h5>
                            <p class="text-muted mb-2">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($bike['category_name'] ?? 'N/A'); ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person"></i> Người bán: <strong><?php echo htmlspecialchars($bike['seller_name']); ?></strong>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($bike['seller_phone']); ?>
                            </p>
                            <p class="text-muted small mb-0">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>, <?php echo htmlspecialchars($bike['district']); ?>
                            </p>
                        </div>
                        
                        <div class="col-md-3 text-end">
                            <div class="h4 text-success mb-3"><?php echo number_format($bike['price']); ?>₫</div>
                            
                            <p class="text-muted small mb-3">
                                <i class="bi bi-calendar"></i> Đăng ngày: <?php echo date('d/m/Y', strtotime($bike['created_at'])); ?>
                            </p>
                            
                            <div class="d-grid gap-2">
                                <a href="inspect.php?bike_id=<?php echo $bike['id']; ?>" class="btn btn-success">
                                    <i class="bi bi-clipboard-check"></i> Kiểm định ngay
                                </a>
                                <a href="../bikes/detail.php?id=<?php echo $bike['id']; ?>" class="btn btn-outline-light btn-sm">
                                    <i class="bi bi-eye"></i> Xem chi tiết
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
            <i class="bi bi-check-circle" style="font-size:5rem"></i>
            <h3 class="mt-3">Không còn xe chờ kiểm định</h3>
            <p class="text-muted mb-4">Tất cả xe đã được kiểm định!</p>
            <a href="dashboard.php" class="btn btn-success btn-lg">
                <i class="bi bi-speedometer2"></i> Về Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
