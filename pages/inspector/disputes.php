<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('inspector');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// TODO: Get disputes from database when table is created
// For now, show empty state
$disputes = [];

// Example query for future implementation:
// $query = "
//     SELECT d.*, i.*, b.title, u.full_name as complainant_name
//     FROM disputes d
//     JOIN inspections i ON d.inspection_id = i.id
//     JOIN bikes b ON i.bike_id = b.id
//     JOIN users u ON d.user_id = u.id
//     WHERE i.inspector_id = ?
//     ORDER BY d.created_at DESC
// ";
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tranh chấp - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>
                <i class="bi bi-exclamation-triangle text-warning"></i> Tranh chấp
            </h2>
            <span class="badge bg-secondary fs-5"><?php echo count($disputes); ?> tranh chấp</span>
        </div>

        <?php if (!empty($disputes)): ?>
            <!-- Disputes List (Future Implementation) -->
            <div class="row g-4">
                <?php foreach ($disputes as $dispute): ?>
                    <div class="col-12">
                        <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                            <div class="row">
                                <div class="col-md-9">
                                    <h5 class="mb-2">
                                        <span class="badge bg-danger me-2">Mới</span>
                                        <?php echo htmlspecialchars($dispute['title']); ?>
                                    </h5>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-person"></i>
                                        Người khiếu nại: <?php echo htmlspecialchars($dispute['complainant_name']); ?>
                                    </p>
                                    <p class="mb-0">
                                        <?php echo htmlspecialchars($dispute['description']); ?>
                                    </p>
                                </div>
                                <div class="col-md-3 text-end">
                                    <p class="text-muted small mb-2">
                                        <?php echo date('d/m/Y H:i', strtotime($dispute['created_at'])); ?>
                                    </p>
                                    <a href="dispute-detail.php?id=<?php echo $dispute['id']; ?>"
                                        class="btn btn-sm btn-warning">
                                        <i class="bi bi-eye"></i> Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <i class="bi bi-shield-check text-success" style="font-size:5rem"></i>
                <h3 class="mt-3 text-success">Tuyệt vời! Không có tranh chấp</h3>
                <p class="text-muted mb-4">
                    Tất cả các kiểm định của bạn đều được chấp nhận.<br>
                    Không có khiếu nại hoặc tranh chấp nào.
                </p>

                <div class="bg-dark-2-custom p-4 rounded border-dark-custom mt-4" style="max-width: 600px; margin: 0 auto;">
                    <h5 class="mb-3">
                        <i class="bi bi-info-circle text-info"></i> Về tranh chấp
                    </h5>
                    <p class="text-muted mb-2">
                        Tranh chấp có thể phát sinh khi:
                    </p>
                    <ul class="text-muted">
                        <li>Người bán không đồng ý với kết quả kiểm định</li>
                        <li>Người mua phát hiện sai lệch sau khi mua xe</li>
                        <li>Có khiếu nại về độ chính xác của báo cáo</li>
                    </ul>
                    <div class="alert alert-info mb-0 mt-3">
                        <i class="bi bi-lightbulb"></i>
                        <strong>Lưu ý:</strong> Kiểm định chính xác và chi tiết giúp giảm thiểu tranh chấp.
                    </div>
                </div>


            </div>
        <?php endif; ?>

        <!-- Statistics (Future Implementation) -->
        <?php if (false): // Enable when disputes table exists ?>
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                        <i class="bi bi-exclamation-circle text-warning" style="font-size:2.5rem"></i>
                        <h3 class="text-warning mt-3 mb-0">0</h3>
                        <p class="text-muted mb-0">Chờ xử lý</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                        <i class="bi bi-check-circle text-success" style="font-size:2.5rem"></i>
                        <h3 class="text-success mt-3 mb-0">0</h3>
                        <p class="text-muted mb-0">Đã giải quyết</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="bg-dark-2-custom p-4 rounded border-dark-custom text-center">
                        <i class="bi bi-x-circle text-danger" style="font-size:2.5rem"></i>
                        <h3 class="text-danger mt-3 mb-0">0</h3>
                        <p class="text-muted mb-0">Chưa giải quyết</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>