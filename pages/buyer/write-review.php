<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('buyer');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// Get order_id from URL
$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    header('Location: orders.php');
    exit;
}

// Get order details
$stmt = $db->prepare("
    SELECT o.*, b.*, b.id as bike_id, b.title as bike_title, 
           s.id as seller_id, s.full_name as seller_name
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    JOIN users s ON b.seller_id = s.id
    WHERE o.id = ? AND o.buyer_id = ? AND o.status = 'completed'
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Check if already reviewed
$stmt = $db->prepare("SELECT id FROM reviews WHERE order_id = ? AND buyer_id = ?");
$stmt->execute([$orderId, $userId]);
$existingReview = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingReview) {
    $rating = $_POST['rating'] ?? 0;
    $comment = trim($_POST['comment'] ?? '');

    if ($rating >= 1 && $rating <= 5) {
        $stmt = $db->prepare("
            INSERT INTO reviews (
                buyer_id, seller_id, bike_id, order_id,
                rating, comment, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $order['seller_id'],
            $order['bike_id'],
            $orderId,
            $rating,
            $comment
        ]);

        // Update seller rating
        $stmt = $db->prepare("
            UPDATE users 
            SET rating = (SELECT AVG(rating) FROM reviews WHERE seller_id = ?),
                total_reviews = (SELECT COUNT(*) FROM reviews WHERE seller_id = ?)
            WHERE id = ?
        ");
        $stmt->execute([$order['seller_id'], $order['seller_id'], $order['seller_id']]);

        $_SESSION['success'] = 'Cảm ơn bạn đã đánh giá!';
        header('Location: orders.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đánh giá sản phẩm - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
    <style>
        .rating-input {
            font-size: 3rem;
            color: #ddd;
            cursor: pointer;
            transition: all 0.2s;
        }
        .rating-input:hover,
        .rating-input.active {
            color: #ffc107;
            transform: scale(1.1);
        }
        .bike-info {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: 12px;
            padding: 1.5rem;
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h2 class="mb-4"><i class="bi bi-star-fill text-warning"></i> Đánh giá sản phẩm</h2>

                <?php if ($existingReview): ?>
                <div class="alert alert-info">
                    <i class="bi bi-check-circle"></i> Bạn đã đánh giá sản phẩm này rồi!
                    <a href="orders.php" class="btn btn-sm btn-outline-primary ms-3">
                        Quay lại đơn hàng
                    </a>
                </div>
                <?php else: ?>

                <!-- Product Info -->
                <div class="bike-info mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <?php if (!empty($order['main_image'])): ?>
                            <img src="../../<?php echo htmlspecialchars($order['main_image']); ?>" 
                                 class="img-fluid rounded" alt="Bike">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <h5 class="mb-2"><?php echo htmlspecialchars($order['bike_title']); ?></h5>
                            <p class="text-muted mb-0">
                                Người bán: <?php echo htmlspecialchars($order['seller_name']); ?>
                            </p>
                            <p class="text-success mb-0 fw-bold">
                                <?php echo number_format($order['total_amount']); ?>₫
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Review Form -->
                <form method="POST" class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="mb-4 text-center">
                        <label class="form-label mb-3">Đánh giá của bạn:</label>
                        <div class="rating-stars-input">
                            <input type="hidden" name="rating" id="ratingValue" value="0" required>
                            <i class="bi bi-star rating-input" data-rating="1"></i>
                            <i class="bi bi-star rating-input" data-rating="2"></i>
                            <i class="bi bi-star rating-input" data-rating="3"></i>
                            <i class="bi bi-star rating-input" data-rating="4"></i>
                            <i class="bi bi-star rating-input" data-rating="5"></i>
                        </div>
                        <div id="ratingText" class="text-muted mt-2">Chưa đánh giá</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Nhận xét của bạn:</label>
                        <textarea name="comment" 
                                  class="form-control" 
                                  rows="5" 
                                  placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."
                                  required></textarea>
                        <small class="text-muted">
                            Hãy chia sẻ cảm nhận thực sự để giúp người mua khác!
                        </small>
                    </div>

                    <div class="d-flex gap-2 justify-content-end">
                        <a href="orders.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Quay lại
                        </a>
                        <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                            <i class="bi bi-send"></i> Gửi đánh giá
                        </button>
                    </div>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Rating system
        const stars = document.querySelectorAll('.rating-input');
        const ratingValue = document.getElementById('ratingValue');
        const ratingText = document.getElementById('ratingText');
        const submitBtn = document.getElementById('submitBtn');

        const ratingTexts = {
            0: 'Chưa đánh giá',
            1: 'Rất tệ',
            2: 'Tệ',
            3: 'Bình thường',
            4: 'Tốt',
            5: 'Rất tốt'
        };

        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.getAttribute('data-rating');
                ratingValue.value = rating;
                ratingText.textContent = ratingTexts[rating];
                
                // Update stars
                stars.forEach(s => {
                    const starRating = s.getAttribute('data-rating');
                    if (starRating <= rating) {
                        s.classList.add('active');
                        s.classList.remove('bi-star');
                        s.classList.add('bi-star-fill');
                    } else {
                        s.classList.remove('active');
                        s.classList.remove('bi-star-fill');
                        s.classList.add('bi-star');
                    }
                });

                // Enable submit button
                submitBtn.disabled = false;
            });

            // Hover effect
            star.addEventListener('mouseenter', function() {
                const rating = this.getAttribute('data-rating');
                stars.forEach(s => {
                    const starRating = s.getAttribute('data-rating');
                    if (starRating <= rating) {
                        s.classList.add('active');
                    }
                });
            });

            star.addEventListener('mouseleave', function() {
                const currentRating = ratingValue.value;
                stars.forEach(s => {
                    const starRating = s.getAttribute('data-rating');
                    if (starRating > currentRating) {
                        s.classList.remove('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
