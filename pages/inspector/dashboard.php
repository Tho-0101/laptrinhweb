<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('inspector');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Get statistics
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM bikes WHERE status = 'approved' AND is_inspected = 0) as pending_inspections,
        (SELECT COUNT(*) FROM inspections WHERE inspector_id = ?) as total_inspections,
        (SELECT COUNT(*) FROM inspections WHERE inspector_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as monthly_inspections,
        (SELECT COUNT(*) FROM bikes WHERE inspector_id = ? AND is_inspected = 1) as certified_bikes
";
$stmt = $db->prepare($statsQuery);
$stmt->execute([$userId, $userId, $userId]);
$stats = $stmt->fetch();

// Recent inspections
$inspectionsQuery = "
    SELECT i.*, b.title, b.price, b.city, u.full_name as seller_name
    FROM inspections i
    JOIN bikes b ON i.bike_id = b.id
    JOIN users u ON b.seller_id = u.id
    WHERE i.inspector_id = ?
    ORDER BY i.created_at DESC
    LIMIT 5
";
$stmt = $db->prepare($inspectionsQuery);
$stmt->execute([$userId]);
$recentInspections = $stmt->fetchAll();

// Pending bikes
$pendingQuery = "
    SELECT b.*, c.name as category_name, u.full_name as seller_name
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.seller_id = u.id
    WHERE b.status = 'approved' AND b.is_inspected = 0
    ORDER BY b.created_at ASC
    LIMIT 10
";
$stmt = $db->query($pendingQuery);
$pendingBikes = $stmt->fetchAll();

// Get inspector info
$userQuery = "SELECT 
    u.full_name,
    COUNT(b.id) as total_inspections,
    SUM(CASE WHEN b.status = 'approved' THEN 1 ELSE 0 END) as approved_bikes,
    SUM(CASE WHEN b.status = 'rejected' THEN 1 ELSE 0 END) as rejected_bikes
FROM users u
LEFT JOIN bikes b ON b.inspector_id = u.id
WHERE u.id = ?
GROUP BY u.id";
$stmt = $db->prepare($userQuery);
$stmt->execute([$userId]);
$inspector = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Inspector</title>
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
                <h2 class="mb-1"><i class="bi bi-shield-check"></i> Dashboard - Inspector</h2>
                <p class="text-muted mb-0">Chào mừng, <?php echo htmlspecialchars($inspector['full_name']); ?>!</p>
            </div>
            <div class="text-end">
                <div class="text-warning h4 mb-0">
                    <i class="bi bi-patch-check-fill"></i> <?php echo $inspector['total_inspections']; ?>
                </div>
                <small class="text-muted">Tổng số kiểm định</small>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-clock-history text-warning" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['pending_inspections']; ?></h3>
                    <p class="text-muted mb-0">Chờ kiểm định</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-check-circle text-success" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['total_inspections']; ?></h3>
                    <p class="text-muted mb-0">Tổng kiểm định</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-calendar-month text-primary" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['monthly_inspections']; ?></h3>
                    <p class="text-muted mb-0">Tháng này</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                    <i class="bi bi-patch-check text-info" style="font-size:2.5rem"></i>
                    <h3 class="text-success mt-3 mb-0"><?php echo $stats['certified_bikes']; ?></h3>
                    <p class="text-muted mb-0">Đã cấp chứng chỉ</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-4 mb-4">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4"><i class="bi bi-lightning-fill"></i> Thao tác nhanh</h5>
                    <div class="d-grid gap-2">
                        <a href="pending.php" class="btn btn-success">
                            <i class="bi bi-clipboard-check"></i> Xe chờ kiểm định
                        </a>
                        <a href="history.php" class="btn btn-outline-light">
                            <i class="bi bi-clock-history"></i> Lịch sử kiểm định
                        </a>
                        <a href="disputes.php" class="btn btn-outline-light">
                            <i class="bi bi-exclamation-triangle"></i> Tranh chấp
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Inspections -->
            <div class="col-md-8 mb-4">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Kiểm định gần đây</h5>
                        <a href="history.php" class="btn btn-sm btn-outline-light">Xem tất cả</a>
                    </div>

                    <?php if (!empty($recentInspections)): ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover">
                                <thead>
                                    <tr>
                                        <th>Xe</th>
                                        <th>Người bán</th>
                                        <th>Điểm TB</th>
                                        <th>Kết quả</th>
                                        <th>Ngày</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentInspections as $insp):
                                        // Calculate average score
                                        $scores = [
                                            $insp['frame_condition'],
                                            $insp['brake_condition'],
                                            $insp['gear_condition'],
                                            $insp['wheel_condition'],
                                            $insp['tire_condition']
                                        ];

                                        $avgScore = array_sum($scores) / count($scores);

                                        // Determine overall rating
                                        if ($avgScore >= 90)
                                            $overall = 'excellent';
                                        elseif ($avgScore >= 70)
                                            $overall = 'good';
                                        elseif ($avgScore >= 50)
                                            $overall = 'fair';
                                        else
                                            $overall = 'poor';

                                        // Badge info
                                        $badges = [
                                            'excellent' => ['success', 'Xuất sắc'],
                                            'good' => ['info', 'Tốt'],
                                            'fair' => ['warning', 'Khá'],
                                            'poor' => ['danger', 'Kém']
                                        ];
                                        $badgeInfo = $badges[$overall] ?? ['secondary', $overall];
                                        ?>
                                        <tr style="cursor:pointer"
                                            onclick="location.href='view-inspection.php?id=<?php echo $insp['id']; ?>'">
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($insp['title']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($insp['city']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($insp['seller_name']); ?></td>
                                            <td>
                                                <span class="fw-bold text-success"><?php echo round($avgScore, 1); ?></span>
                                                <small class="text-muted">/100</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $badgeInfo[0]; ?> mb-1">
                                                    <?php echo $badgeInfo[1]; ?>
                                                </span>
                                                <br>
                                                <?php if ($insp['status'] === 'approved'): ?>
                                                    <small class="text-success">
                                                        <i class="bi bi-check-circle-fill"></i> Đạt
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-danger">
                                                        <i class="bi bi-x-circle-fill"></i> Không đạt
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('d/m/Y', strtotime($insp['created_at'])); ?>
                                                <br>
                                                <small
                                                    class="text-muted"><?php echo date('H:i', strtotime($insp['created_at'])); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                            <p class="text-muted mt-2 mb-0">Chưa có kiểm định nào</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pending Bikes -->
        <?php if (!empty($pendingBikes)): ?>
            <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Xe chờ kiểm định</h5>
                    <span class="badge bg-warning"><?php echo count($pendingBikes); ?> xe</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-dark table-hover">
                        <thead>
                            <tr>
                                <th>Xe</th>
                                <th>Danh mục</th>
                                <th>Người bán</th>
                                <th>Vị trí</th>
                                <th>Ngày đăng</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingBikes as $bike): ?>
                                <tr>
                                    <td>
                                        <a href="../bikes/detail.php?id=<?php echo $bike['id']; ?>"
                                            class="text-white text-decoration-none">
                                            <?php echo htmlspecialchars($bike['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($bike['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($bike['seller_name']); ?></td>
                                    <td><?php echo htmlspecialchars($bike['city']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($bike['created_at'])); ?></td>
                                    <td>
                                        <a href="inspect.php?bike_id=<?php echo $bike['id']; ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-clipboard-check"></i> Kiểm định
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>