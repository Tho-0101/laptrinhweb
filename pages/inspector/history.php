<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('inspector');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Get inspection history
$query = "
    SELECT 
    i.*, 
    b.title, 
    b.price, 
    (SELECT file_path 
     FROM bike_images 
     WHERE bike_id = b.id AND is_primary = 1 
     LIMIT 1) as main_image,
    u.full_name as seller_name
FROM inspections i
JOIN bikes b ON i.bike_id = b.id
JOIN users u ON b.seller_id = u.id
WHERE i.inspector_id = ?
ORDER BY i.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute([$userId]);
$inspections = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử kiểm định - BikeMarket</title>
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
            <h2><i class="bi bi-clock-history"></i> Lịch sử kiểm định</h2>
            <span class="badge bg-success fs-5"><?php echo count($inspections); ?> kiểm định</span>
        </div>

        <?php if (!empty($inspections)): ?>
            <div class="row g-4">
                <?php foreach ($inspections as $insp):
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
                    ?>
                    <div class="col-12">
                        <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                            <div class="row">
                                <div class="col-md-2">
                                    <?php if (!empty($insp['main_image'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($insp['main_image']); ?>" class="w-100 rounded">
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-7">
                                    <h5 class="mb-2"><?php echo htmlspecialchars($insp['title']); ?></h5>
                                    <p class="text-muted mb-2">
                                        Người bán: <?php echo htmlspecialchars($insp['seller_name']); ?>
                                    </p>

                                    <div class="mb-2">
                                        <span class="badge bg-secondary me-1">
                                            Khung: <?php echo $insp['frame_condition']; ?>/100
                                        </span>
                                        <span class="badge bg-secondary me-1">
                                            Phanh: <?php echo $insp['brake_condition']; ?>/100
                                        </span>
                                        <span class="badge bg-secondary me-1">
                                            Truyền động: <?php echo $insp['gear_condition']; ?>/100
                                        </span>
                                        <span class="badge bg-secondary me-1">
                                            Bánh: <?php echo $insp['wheel_condition']; ?>/100
                                        </span>
                                        <span class="badge bg-secondary">
                                            Lốp: <?php echo $insp['tire_condition']; ?>/100
                                        </span>
                                    </div>

                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-calculator"></i>
                                        Điểm TB: <strong><?php echo round($avgScore, 1); ?>/100</strong>
                                    </p>

                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($insp['created_at'])); ?>
                                    </p>
                                </div>

                                <div class="col-md-3 text-end">
                                    <?php
                                    $badges = [
                                        'excellent' => ['success', 'Xuất sắc'],
                                        'good' => ['info', 'Tốt'],
                                        'fair' => ['warning', 'Khá'],
                                        'poor' => ['danger', 'Kém']
                                    ];
                                    $badgeInfo = $badges[$overall] ?? ['secondary', $overall];
                                    ?>
                                    <span class="badge bg-<?php echo $badgeInfo[0]; ?> mb-3 fs-6">
                                        <?php echo $badgeInfo[1]; ?>
                                    </span>

                                    <div class="mb-3">
                                        <?php if ($insp['status'] === 'approved'): ?>
                                            <i class="bi bi-check-circle-fill text-success"></i> Đã cấp chứng chỉ
                                        <?php else: ?>
                                            <i class="bi bi-x-circle-fill text-danger"></i> Không đạt
                                        <?php endif; ?>
                                    </div>

                                    <a href="view-inspection.php?id=<?php echo $insp['id']; ?>"
                                        class="btn btn-sm btn-outline-light">
                                        <i class="bi bi-eye"></i> Xem báo cáo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-clipboard-x" style="font-size:5rem"></i>
                <h3 class="mt-3">Chưa có lịch sử kiểm định</h3>
                <p class="text-muted mb-4">Bắt đầu kiểm định xe để tạo lịch sử!</p>
                <a href="pending.php" class="btn btn-success btn-lg">
                    <i class="bi bi-clipboard-check"></i> Xe chờ kiểm định
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>