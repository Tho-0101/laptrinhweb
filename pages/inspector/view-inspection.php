<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('inspector');

$inspectionId = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

// Get inspection details
$query = "
    SELECT i.*, 
       b.title, 
       b.price, 
       (SELECT file_path 
        FROM bike_images 
        WHERE bike_id = b.id AND is_primary = 1 
        LIMIT 1) as main_image,
       b.description, 
       b.city, 
       b.district,
       u.full_name as seller_name, 
       u.phone as seller_phone,
       inspector.full_name as inspector_name
FROM inspections i
JOIN bikes b ON i.bike_id = b.id
JOIN users u ON b.seller_id = u.id
JOIN users inspector ON i.inspector_id = inspector.id
WHERE i.id = ? AND i.inspector_id = ?
";
$stmt = $db->prepare($query);
$stmt->execute([$inspectionId, getUserId()]);
$inspection = $stmt->fetch();

if (!$inspection) {
    header('Location: dashboard.php?error=inspection_not_found');
    exit;
}

// Calculate average score
$scores = [
    $inspection['frame_condition'],
    $inspection['brake_condition'],
    $inspection['gear_condition'],
    $inspection['wheel_condition'],
    $inspection['tire_condition']
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

$badges = [
    'excellent' => ['success', 'Xuất sắc'],
    'good' => ['info', 'Tốt'],
    'fair' => ['warning', 'Khá'],
    'poor' => ['danger', 'Kém']
];
$badgeInfo = $badges[$overall] ?? ['secondary', $overall];
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết kiểm định - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white !important;
            }

            .bg-dark-2-custom {
                background: white !important;
                color: black !important;
            }
        }

        .score-bar {
            height: 25px;
            background: #2d2d2d;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .score-bar-fill {
            height: 100%;
            background: linear-gradient(to right, #dc3545, #ffc107, #28a745);
            transition: width 0.3s;
        }

        .score-value {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
            color: white;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top no-print">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="history.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-clock-history"></i> Lịch sử
                </a>
                <button onclick="window.print()" class="btn btn-success btn-sm">
                    <i class="bi bi-printer"></i> In báo cáo
                </button>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h2 class="mb-2">
                            <i class="bi bi-clipboard-check text-success"></i> Báo cáo kiểm định
                        </h2>
                        <p class="text-muted mb-0">
                            ID: #<?php echo $inspection['id']; ?> |
                            Ngày: <?php echo date('d/m/Y H:i', strtotime($inspection['created_at'])); ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?php echo $badgeInfo[0]; ?> fs-5">
                            <?php echo $badgeInfo[1]; ?>
                        </span>
                        <br>
                        <?php if ($inspection['status'] === 'approved'): ?>
                            <span class="badge bg-success mt-2">
                                <i class="bi bi-check-circle-fill"></i> Đã cấp chứng chỉ
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger mt-2">
                                <i class="bi bi-x-circle-fill"></i> Không đạt
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bike Info -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <?php if (!empty($inspection['main_image'])): ?>
                                <img src="../../<?php echo htmlspecialchars($inspection['main_image']); ?>"
                                    class="w-100 rounded">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <h4 class="mb-3"><?php echo htmlspecialchars($inspection['title']); ?></h4>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <i class="bi bi-person"></i>
                                        <strong>Người bán:</strong>
                                        <?php echo htmlspecialchars($inspection['seller_name']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-telephone"></i>
                                        <strong>SĐT:</strong>
                                        <?php echo htmlspecialchars($inspection['seller_phone']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2">
                                        <i class="bi bi-geo-alt"></i>
                                        <strong>Vị trí:</strong>
                                        <?php echo htmlspecialchars($inspection['city'] . ', ' . $inspection['district']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-tag"></i>
                                        <strong>Giá:</strong>
                                        <span class="text-success fw-bold">
                                            <?php echo number_format($inspection['price']); ?>₫
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <p class="mb-2">
                                <i class="bi bi-shield-check"></i>
                                <strong>Kiểm định viên:</strong>
                                <?php echo htmlspecialchars($inspection['inspector_name']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Overall Score -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-3">
                                <i class="bi bi-calculator"></i> Điểm tổng quan
                            </h5>
                            <div class="score-bar">
                                <div class="score-bar-fill" style="width: <?php echo $avgScore; ?>%"></div>
                                <span class="score-value"><?php echo round($avgScore, 1); ?>/100</span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="display-4 text-success mb-0">
                                <?php echo round($avgScore, 1); ?>
                            </div>
                            <p class="text-muted mb-0">điểm trung bình</p>
                        </div>
                    </div>
                </div>

                <!-- Detailed Scores -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-4">
                        <i class="bi bi-list-check"></i> Chi tiết kiểm định
                    </h5>

                    <!-- 1. Frame -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="bi bi-1-circle text-success"></i> Khung xe (Frame)
                            </h6>
                            <span class="badge bg-secondary"><?php echo $inspection['frame_condition']; ?>/100</span>
                        </div>
                        <div class="score-bar mb-2">
                            <div class="score-bar-fill" style="width: <?php echo $inspection['frame_condition']; ?>%">
                            </div>
                        </div>
                        <?php if (!empty($inspection['frame_notes'])): ?>
                            <p class="text-muted small mb-0 ms-4">
                                💬 <?php echo htmlspecialchars($inspection['frame_notes']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Brake -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="bi bi-2-circle text-success"></i> Phanh (Brake)
                            </h6>
                            <span class="badge bg-secondary"><?php echo $inspection['brake_condition']; ?>/100</span>
                        </div>
                        <div class="score-bar mb-2">
                            <div class="score-bar-fill" style="width: <?php echo $inspection['brake_condition']; ?>%">
                            </div>
                        </div>
                        <?php if (!empty($inspection['brake_notes'])): ?>
                            <p class="text-muted small mb-0 ms-4">
                                💬 <?php echo htmlspecialchars($inspection['brake_notes']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- 3. Gear -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="bi bi-3-circle text-success"></i> Truyền động (Gears)
                            </h6>
                            <span class="badge bg-secondary"><?php echo $inspection['gear_condition']; ?>/100</span>
                        </div>
                        <div class="score-bar mb-2">
                            <div class="score-bar-fill" style="width: <?php echo $inspection['gear_condition']; ?>%">
                            </div>
                        </div>
                        <?php if (!empty($inspection['gear_notes'])): ?>
                            <p class="text-muted small mb-0 ms-4">
                                💬 <?php echo htmlspecialchars($inspection['gear_notes']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- 4. Wheel -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="bi bi-4-circle text-success"></i> Bánh xe (Wheels)
                            </h6>
                            <span class="badge bg-secondary"><?php echo $inspection['wheel_condition']; ?>/100</span>
                        </div>
                        <div class="score-bar mb-2">
                            <div class="score-bar-fill" style="width: <?php echo $inspection['wheel_condition']; ?>%">
                            </div>
                        </div>
                        <?php if (!empty($inspection['wheel_notes'])): ?>
                            <p class="text-muted small mb-0 ms-4">
                                💬 <?php echo htmlspecialchars($inspection['wheel_notes']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- 5. Tire -->
                    <div class="mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="bi bi-5-circle text-success"></i> Lốp & Phụ kiện (Tires)
                            </h6>
                            <span class="badge bg-secondary"><?php echo $inspection['tire_condition']; ?>/100</span>
                        </div>
                        <div class="score-bar mb-2">
                            <div class="score-bar-fill" style="width: <?php echo $inspection['tire_condition']; ?>%">
                            </div>
                        </div>
                        <?php if (!empty($inspection['tire_notes'])): ?>
                            <p class="text-muted small mb-0 ms-4">
                                💬 <?php echo htmlspecialchars($inspection['tire_notes']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Overall Notes -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <h5 class="mb-3">
                        <i class="bi bi-chat-left-text"></i> Nhận xét tổng quan
                    </h5>
                    <p class="mb-0" style="white-space: pre-line;">
                        <?php echo htmlspecialchars($inspection['overall_notes']); ?>
                    </p>
                </div>

                <!-- Actions -->
                <div class="text-center no-print">
                    <a href="history.php" class="btn btn-outline-light me-2">
                        <i class="bi bi-arrow-left"></i> Quay lại
                    </a>
                    <a href="dashboard.php" class="btn btn-success">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>