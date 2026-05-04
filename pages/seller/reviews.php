<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('seller');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Get all reviews for seller's bikes
$query = "
    SELECT 
        r.*,
        b.title as bike_title,
        b.main_image,
        u.full_name as buyer_name,
        u.avatar as buyer_avatar
    FROM reviews r
    JOIN bikes b ON r.bike_id = b.id
    JOIN users u ON r.buyer_id = u.id
    WHERE b.seller_id = ?
    ORDER BY r.created_at DESC
";

$stmt = $db->prepare("
    SELECT r.*, 
           u.full_name AS buyer_name,
           b.title AS bike_title
    FROM reviews r
    JOIN users u ON r.buyer_id = u.id
    JOIN bikes b ON r.bike_id = b.id
    WHERE r.seller_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$reviews = $stmt->fetchAll();

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
    FROM reviews r
    JOIN bikes b ON r.bike_id = b.id
    WHERE b.seller_id = ?
";

$stmt = $db->prepare($statsQuery);
$stmt->execute([$userId]);
$stats = $stmt->fetch();

$totalReviews = $stats['total_reviews'] ?? 0;
$avgRating = $stats['avg_rating'] ? round($stats['avg_rating'], 1) : 0;
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
    <link href="../../assets/css/pages/dashboard.css" rel="stylesheet">
    <style>
    .rating-stars {
        color: #ffc107;
        font-size: 1.2rem;
    }

    .rating-bar {
        height: 8px;
        background: var(--dark-3);
        border-radius: 4px;
        overflow: hidden;
    }

    .rating-bar-fill {
        height: 100%;
        background: #ffc107;
        transition: width 0.3s;
    }

    .review-card {
        background: var(--dark-2);
        border: 1px solid var(--dark-3);
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.3s;
    }

    .review-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .reviewer-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }

    .bike-thumbnail {
        width: 80px;
        height: 80px;
        border-radius: 8px;
        object-fit: cover;
    }

    .stats-card {
        background: var(--dark-2);
        border: 1px solid var(--dark-3);
        border-radius: 12px;
        padding: 2rem;
    }

    .big-rating {
        font-size: 3rem;
        font-weight: 700;
        color: var(--primary);
    }
    </style>
</head>

<body>
    <!-- Navbar -->
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
        <div class="row mb-4">
            <div class="col">
                <h2><i class="bi bi-star-fill text-warning"></i> Đánh giá của khách hàng</h2>
                <p class="text-muted">Xem các đánh giá từ người mua về sản phẩm của bạn</p>
            </div>
        </div>

        <div class="row mb-4">
            <!-- Overall Stats -->
            <div class="col-md-4 mb-4">
                <div class="stats-card text-center">
                    <div class="big-rating mb-2"><?php echo $avgRating; ?></div>
                    <div class="rating-stars mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star<?php echo $i <= round($avgRating) ? '-fill' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-muted mb-0"><?php echo $totalReviews; ?> đánh giá</p>
                </div>
            </div>

            <!-- Rating Breakdown -->
            <div class="col-md-8 mb-4">
                <div class="stats-card">
                    <h5 class="mb-3">Phân bố đánh giá</h5>

                    <?php
                    $ratings = [
                        5 => $stats['five_stars'] ?? 0,
                        4 => $stats['four_stars'] ?? 0,
                        3 => $stats['three_stars'] ?? 0,
                        2 => $stats['two_stars'] ?? 0,
                        1 => $stats['one_star'] ?? 0
                    ];
                    
                    foreach ($ratings as $star => $count):
                        $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                    ?>
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2" style="min-width: 60px;">
                            <?php echo $star; ?> <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <div class="flex-grow-1 rating-bar">
                            <div class="rating-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="ms-2 text-muted" style="min-width: 50px; text-align: right;">
                            <?php echo $count; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Reviews List -->
        <?php if (!empty($reviews)): ?>
        <div class="row">
            <div class="col-12">
                <h4 class="mb-3">Tất cả đánh giá (<?php echo count($reviews); ?>)</h4>

                <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="row">
                        <!-- Reviewer Info -->
                        <div class="col-md-8">
                            <div class="d-flex mb-3">
                                <img src="../../<?php echo htmlspecialchars($review['buyer_avatar'] ?? 'assets/images/default-avatar.png'); ?>"
                                    class="reviewer-avatar me-3" alt="Buyer">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($review['buyer_name']); ?></h6>
                                            <div class="rating-stars" style="font-size: 1rem;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i
                                                    class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Review Content -->
                            <?php if (!empty($review['comment'])): ?>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            <?php else: ?>
                            <p class="text-muted mb-0"><em>Không có bình luận</em></p>
                            <?php endif; ?>
                        </div>

                        <!-- Bike Info -->
                        <div class="col-md-4 text-end">
                            <div class="d-flex align-items-center justify-content-end">
                                <?php if (!empty($review['main_image'])): ?>
                                <img src="../../<?php echo htmlspecialchars($review['main_image']); ?>"
                                    class="bike-thumbnail me-2" alt="Bike">
                                <?php endif; ?>
                                <div class="text-start">
                                    <small class="text-muted d-block">Sản phẩm:</small>
                                    <a href="../bikes/detail.php?id=<?php echo $review['bike_id']; ?>"
                                        class="text-white text-decoration-none">
                                        <strong><?php echo htmlspecialchars($review['bike_title']); ?></strong>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Empty State -->
        <div class="text-center py-5">
            <i class="bi bi-star text-muted" style="font-size: 5rem; opacity: 0.5;"></i>
            <h3 class="mt-3">Chưa có đánh giá nào</h3>
            <p class="text-muted">Khách hàng sẽ có thể đánh giá sau khi mua xe của bạn</p>
            <a href="my-bikes.php" class="btn btn-success mt-3">
                <i class="bi bi-bicycle"></i> Xem tin đăng của tôi
            </a>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>