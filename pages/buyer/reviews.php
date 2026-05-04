<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('buyer');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Get all reviews by this buyer
$query = "
    SELECT 
        r.*,
        b.title as bike_title,
        b.main_image,
        b.seller_id,
        s.full_name as seller_name
    FROM reviews r
    JOIN bikes b ON r.bike_id = b.id
    JOIN users s ON b.seller_id = s.id
    WHERE r.buyer_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $db->prepare($query);
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá của tôi - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
    <style>
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .review-card {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        .bike-thumbnail {
            width: 100px;
            height: 100px;
            border-radius: 8px;
            object-fit: cover;
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
        <h2 class="mb-4"><i class="bi bi-star-fill text-warning"></i> Đánh giá của tôi</h2>

        <?php if (!empty($reviews)): ?>
        <?php foreach ($reviews as $review): ?>
        <div class="review-card">
            <div class="row">
                <div class="col-md-2">
                    <?php if (!empty($review['main_image'])): ?>
                    <img src="../../<?php echo htmlspecialchars($review['main_image']); ?>" 
                         class="bike-thumbnail" alt="Bike">
                    <?php endif; ?>
                </div>
                <div class="col-md-10">
                    <div class="d-flex justify-content-between mb-2">
                        <div>
                            <h5 class="mb-1">
                                <a href="../bikes/detail.php?id=<?php echo $review['bike_id']; ?>" 
                                   class="text-white text-decoration-none">
                                    <?php echo htmlspecialchars($review['bike_title']); ?>
                                </a>
                            </h5>
                            <small class="text-muted">
                                Người bán: <?php echo htmlspecialchars($review['seller_name']); ?>
                            </small>
                        </div>
                        <small class="text-muted">
                            <?php echo date('d/m/Y', strtotime($review['created_at'])); ?>
                        </small>
                    </div>
                    
                    <div class="rating-stars mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>

                    <?php if (!empty($review['comment'])): ?>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-star text-muted" style="font-size: 5rem;"></i>
            <h3 class="mt-3">Chưa có đánh giá nào</h3>
            <p class="text-muted">Bạn có thể đánh giá sản phẩm sau khi mua hàng</p>
            <a href="../bikes/list.php" class="btn btn-success">
                <i class="bi bi-bicycle"></i> Mua xe ngay
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
