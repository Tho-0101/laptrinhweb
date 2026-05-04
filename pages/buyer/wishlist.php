<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('buyer');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Get wishlist with bike details
$query = "
    SELECT f.*, b.*, c.name as category_name, u.full_name as seller_name
    FROM favorites f
    JOIN bikes b ON f.bike_id = b.id
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN users u ON b.seller_id = u.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
";
$stmt = $db->prepare($query);
$stmt->execute([$userId]);
$wishlist = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách yêu thích - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-heart-fill text-danger"></i> Danh sách yêu thích</h2>
            <span class="badge bg-success fs-5"><?php echo count($wishlist); ?> xe</span>
        </div>

        <?php if (!empty($wishlist)): ?>
            <div class="row g-4">
                <?php foreach ($wishlist as $bike): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="bike-card position-relative">
                            <!-- Remove button -->
                            <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" style="z-index:10"
                                onclick="removeFavorite(<?php echo $bike['bike_id']; ?>, event)">
                                <i class="bi bi-x-lg"></i>
                            </button>

                            <div onclick="location.href='../bikes/detail.php?id=<?php echo $bike['bike_id']; ?>'">
                                <div class="position-relative">
                                    <?php if (!empty($bike['main_image'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($bike['main_image']); ?>"
                                            alt="<?php echo htmlspecialchars($bike['title']); ?>" class="bike-card-img">
                                    <?php else: ?>
                                        <div class="bike-card-img d-flex align-items-center justify-content-center">
                                            <i class="bi bi-bicycle" style="font-size:3rem;color:var(--gray)"></i>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($bike['is_inspected']): ?>
                                        <span class="badge-inspected">
                                            <i class="bi bi-patch-check-fill"></i> Đã kiểm định
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="bike-card-body">
                                    <h5 class="bike-title"><?php echo htmlspecialchars($bike['title']); ?></h5>
                                    <div class="bike-meta">
                                        <i class="bi bi-tag"></i>
                                        <?php echo htmlspecialchars($bike['category_name'] ?? 'N/A'); ?>
                                        <span class="mx-2">•</span>
                                        <i class="bi bi-gear"></i> <?php echo htmlspecialchars($bike['condition_status']); ?>
                                    </div>
                                    <div class="bike-price"><?php echo number_format($bike['price']); ?>₫</div>
                                    <div class="bike-location">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>
                                    </div>
                                    <div class="mt-3">
                                        <a href="../orders/create.php?bike_id=<?php echo $bike['bike_id']; ?>"
                                            class="btn btn-success btn-sm w-100" onclick="event.stopPropagation()">
                                            <i class="bi bi-cart-plus"></i> Đặt mua ngay
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
                <i class="bi bi-heart" style="font-size:5rem"></i>
                <h3 class="mt-3">Chưa có xe yêu thích</h3>
                <p class="text-muted mb-4">Khám phá và thêm những chiếc xe bạn thích vào danh sách!</p>
                <a href="../bikes/list.php" class="btn btn-success btn-lg">
                    <i class="bi bi-search"></i> Tìm xe ngay
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function removeFavorite(bikeId, event) {
            event.stopPropagation();

            if (!confirm('Xóa xe này khỏi danh sách yêu thích?')) return;

            fetch('../../api/bikes/toggle-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    bike_id: bikeId
                })
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(err => alert('Lỗi kết nối'));
        }
    </script>
</body>

</html>