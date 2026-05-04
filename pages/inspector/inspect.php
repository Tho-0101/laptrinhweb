<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('inspector');

$bikeId = $_GET['bike_id'] ?? 0;
$db = Database::getInstance()->getConnection();

// Get bike details
$stmt = $db->prepare("
    SELECT b.*, c.name as category_name, u.full_name as seller_name
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.seller_id = u.id
    WHERE b.id = ?
");
$stmt->execute([$bikeId]);
$bike = $stmt->fetch();

if (!$bike) {
    header('Location: dashboard.php?error=bike_not_found');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get scores
        $frameScore = (int) ($_POST['frame_condition'] ?? 50);
        $brakeScore = (int) ($_POST['brake_condition'] ?? 50);
        $gearScore = (int) ($_POST['gear_condition'] ?? 50);
        $wheelScore = (int) ($_POST['wheel_condition'] ?? 50);
        $tireScore = (int) ($_POST['tire_condition'] ?? 50);

        // Get notes
        $frameNotes = trim($_POST['frame_notes'] ?? '');
        $brakeNotes = trim($_POST['brake_notes'] ?? '');
        $gearNotes = trim($_POST['gear_notes'] ?? '');
        $wheelNotes = trim($_POST['wheel_notes'] ?? '');
        $tireNotes = trim($_POST['tire_notes'] ?? '');
        $overallNotes = trim($_POST['overall_notes'] ?? '');

        // Calculate average
        $avgScore = ($frameScore + $brakeScore + $gearScore + $wheelScore + $tireScore) / 5;
        $isApproved = $avgScore >= 50;

        // ✅ FIX: Use correct ENUM values for inspections.status
        // Database accepts: 'pending', 'approved', 'rejected'
        $status = $isApproved ? 'approved' : 'rejected';

        // ✅ PREPARE INSERT QUERY
        $sql = "
            INSERT INTO inspections (
                bike_id, 
                inspector_id, 
                frame_condition, 
                frame_notes,
                brake_condition, 
                brake_notes,
                gear_condition, 
                gear_notes,
                wheel_condition, 
                wheel_notes,
                tire_condition, 
                tire_notes,
                overall_notes,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = $db->prepare($sql);

        // ✅ EXECUTE WITH EXACTLY 14 PARAMETERS
        $params = [
            $bikeId,              // 1
            getUserId(),          // 2
            $frameScore,          // 3
            $frameNotes,          // 4
            $brakeScore,          // 5
            $brakeNotes,          // 6
            $gearScore,           // 7
            $gearNotes,           // 8
            $wheelScore,          // 9
            $wheelNotes,          // 10
            $tireScore,           // 11
            $tireNotes,           // 12
            $overallNotes,        // 13
            $status               // 14: 'approved' or 'rejected' ✅
        ];

        $stmt->execute($params);

        // ✅ UPDATE BIKE IF APPROVED
        if ($isApproved) {
            $updateSql = "
                UPDATE bikes 
                SET is_inspected = 1, 
                    inspector_id = ?, 
                    inspection_date = NOW()
                WHERE id = ?
            ";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute([getUserId(), $bikeId]);
        }

        header('Location: dashboard.php?success=inspected&score=' . round($avgScore));
        exit;

    } catch (PDOException $e) {
        $errors[] = "Lỗi database: " . $e->getMessage();
        error_log("Inspection error: " . $e->getMessage());
    } catch (Exception $e) {
        $errors[] = "Lỗi: " . $e->getMessage();
        error_log("Inspection error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kiểm định xe - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
    <style>
        .checklist-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .score-slider {
            width: 100%;
            height: 40px;
            -webkit-appearance: none;
            background: linear-gradient(to right, #dc3545 0%, #ffc107 50%, #28a745 100%);
            border-radius: 10px;
            outline: none;
        }

        .score-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 25px;
            height: 25px;
            background: white;
            border: 3px solid #10b981;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }

        .score-slider::-moz-range-thumb {
            width: 25px;
            height: 25px;
            background: white;
            border: 3px solid #10b981;
            border-radius: 50%;
            cursor: pointer;
        }

        .score-value {
            font-size: 2rem;
            font-weight: bold;
            color: #10b981;
        }
    </style>
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4">
                    <i class="bi bi-clipboard-check text-success"></i> Kiểm định xe
                </h2>

                <!-- Bike Info -->
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-2"><?php echo htmlspecialchars($bike['title']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($bike['category_name']); ?> •
                                <i class="bi bi-person"></i> <?php echo htmlspecialchars($bike['seller_name']); ?> •
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="h4 text-success mb-0">
                                <?php echo number_format($bike['price']); ?>₫
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6 class="mb-2"><i class="bi bi-exclamation-triangle"></i> Lỗi:</h6>
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle"></i>
                        <strong>Hướng dẫn:</strong> Đánh giá từng bộ phận từ 0-100 điểm.
                        Xe sẽ được cấp chứng chỉ nếu điểm trung bình ≥ 50.
                    </div>

                    <!-- 1. Frame -->
                    <div class="checklist-item">
                        <h5 class="mb-3">
                            <i class="bi bi-1-circle text-success"></i> Khung xe (Frame)
                        </h5>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Điểm đánh giá</label>
                                <span class="score-value" id="frame-score">50</span>
                            </div>
                            <input type="range" name="frame_condition" class="score-slider" min="0" max="100" value="50"
                                oninput="updateScore('frame', this.value)" required>
                            <div class="d-flex justify-content-between text-muted small mt-1">
                                <span>0 - Kém</span>
                                <span>50 - TB</span>
                                <span>100 - Xuất sắc</span>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Ghi chú</label>
                            <textarea name="frame_notes" rows="2"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Tình trạng sơn, vết xước..."></textarea>
                        </div>
                    </div>

                    <!-- 2. Brake -->
                    <div class="checklist-item">
                        <h5 class="mb-3">
                            <i class="bi bi-2-circle text-success"></i> Phanh (Brake)
                        </h5>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Điểm đánh giá</label>
                                <span class="score-value" id="brake-score">50</span>
                            </div>
                            <input type="range" name="brake_condition" class="score-slider" min="0" max="100" value="50"
                                oninput="updateScore('brake', this.value)" required>
                        </div>
                        <div>
                            <label class="form-label">Ghi chú</label>
                            <textarea name="brake_notes" rows="2"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Ma sát phanh, dây phanh..."></textarea>
                        </div>
                    </div>

                    <!-- 3. Gear -->
                    <div class="checklist-item">
                        <h5 class="mb-3">
                            <i class="bi bi-3-circle text-success"></i> Truyền động (Gears)
                        </h5>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Điểm đánh giá</label>
                                <span class="score-value" id="gear-score">50</span>
                            </div>
                            <input type="range" name="gear_condition" class="score-slider" min="0" max="100" value="50"
                                oninput="updateScore('gear', this.value)" required>
                        </div>
                        <div>
                            <label class="form-label">Ghi chú</label>
                            <textarea name="gear_notes" rows="2"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Xích, líp, pát..."></textarea>
                        </div>
                    </div>

                    <!-- 4. Wheel -->
                    <div class="checklist-item">
                        <h5 class="mb-3">
                            <i class="bi bi-4-circle text-success"></i> Bánh xe (Wheels)
                        </h5>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Điểm đánh giá</label>
                                <span class="score-value" id="wheel-score">50</span>
                            </div>
                            <input type="range" name="wheel_condition" class="score-slider" min="0" max="100" value="50"
                                oninput="updateScore('wheel', this.value)" required>
                        </div>
                        <div>
                            <label class="form-label">Ghi chú</label>
                            <textarea name="wheel_notes" rows="2"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Vành, nan hoa..."></textarea>
                        </div>
                    </div>

                    <!-- 5. Tire -->
                    <div class="checklist-item">
                        <h5 class="mb-3">
                            <i class="bi bi-5-circle text-success"></i> Lốp & Phụ kiện (Tires)
                        </h5>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Điểm đánh giá</label>
                                <span class="score-value" id="tire-score">50</span>
                            </div>
                            <input type="range" name="tire_condition" class="score-slider" min="0" max="100" value="50"
                                oninput="updateScore('tire', this.value)" required>
                        </div>
                        <div>
                            <label class="form-label">Ghi chú</label>
                            <textarea name="tire_notes" rows="2"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Lốp, ruột, đèn..."></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Overall -->
                    <div class="checklist-item">
                        <h5 class="mb-3">
                            <i class="bi bi-clipboard-check text-success"></i> Đánh giá tổng quan
                        </h5>
                        <div class="mb-3">
                            <label class="form-label">Nhận xét tổng thể <span class="text-danger">*</span></label>
                            <textarea name="overall_notes" rows="4"
                                class="form-control bg-dark text-white border-secondary"
                                placeholder="Đánh giá chung về xe, điểm mạnh, điểm yếu..." required></textarea>
                        </div>
                        <div class="alert alert-dark border-secondary">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-calculator"></i> Điểm TB:</span>
                                <span class="h4 mb-0 text-success" id="avg-score">50.0</span>
                            </div>
                            <div class="mt-2 small">
                                <span id="pass-status" class="text-success">✅ Đủ điều kiện</span>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="dashboard.php" class="btn btn-outline-light">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Hoàn thành kiểm định
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateScore(type, value) {
            document.getElementById(type + '-score').textContent = value;
            calculateAverage();
        }

        function calculateAverage() {
            const scores = [
                parseInt(document.querySelector('[name="frame_condition"]').value),
                parseInt(document.querySelector('[name="brake_condition"]').value),
                parseInt(document.querySelector('[name="gear_condition"]').value),
                parseInt(document.querySelector('[name="wheel_condition"]').value),
                parseInt(document.querySelector('[name="tire_condition"]').value)
            ];

            const avg = scores.reduce((a, b) => a + b, 0) / scores.length;
            document.getElementById('avg-score').textContent = avg.toFixed(1);

            const statusEl = document.getElementById('pass-status');
            if (avg >= 50) {
                statusEl.innerHTML = '✅ Đủ điều kiện cấp chứng chỉ';
                statusEl.className = 'text-success';
            } else {
                statusEl.innerHTML = '❌ Chưa đủ điều kiện';
                statusEl.className = 'text-danger';
            }
        }

        calculateAverage();
    </script>
</body>

</html>