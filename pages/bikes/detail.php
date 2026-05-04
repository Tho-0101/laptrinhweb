<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

$bikeId = $_GET['id'] ?? 0;
if (!$bikeId) {
    header('Location: list.php');
    exit;
}

$bike = new Bike();
$detail = $bike->getById($bikeId);

if (!$detail) {
    header('Location: list.php');
    exit;
}

$bike->incrementViews($bikeId);

// Get related bikes
$relatedResult = $bike->search(['category_id' => $detail['category_id']], 1, 4);
$related = array_filter($relatedResult['bikes'], function ($b) use ($bikeId) {
    return $b['id'] != $bikeId;
});

$isGuest = !isLoggedIn();

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($detail['title']); ?> - BikeMarket</title>
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
                <?php if ($isGuest): ?>
                    <a href="../auth/login.php" class="btn btn-outline-light btn-sm">Đăng nhập</a>
                    <a href="../auth/register.php" class="btn btn-success btn-sm">Đăng ký</a>
                <?php else: ?>
                    <a href="../<?php echo getUserRole(); ?>/dashboard.php" class="btn btn-success btn-sm">Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <!-- Images -->
            <div class="col-lg-7 mb-4">
                <?php if (!empty($detail['images'])): ?>

                    <!-- ẢNH CHÍNH -->
                    <img id="mainImg" src="../../<?php echo htmlspecialchars($detail['images'][0]['file_path']); ?>"
                        style="width: 100%; border-radius: 0.5rem;">

                    <!-- THUMBNAIL -->
                    <?php if (count($detail['images']) > 1): ?>
                        <div class="d-flex gap-2 mt-2" style="overflow-x: auto;">
                            <?php foreach ($detail['images'] as $img): ?>
                                <img src="../../<?php echo htmlspecialchars($img['file_path']); ?>"
                                    onclick="document.getElementById('mainImg').src=this.src"
                                    style="width:80px;height:80px;object-fit:cover;cursor:pointer" class="rounded">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>

                    <!-- fallback -->
                    <img src="https://via.placeholder.com/600x400" style="width: 100%; border-radius: 0.5rem;">

                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="col-lg-5">
                <h1 class="h3"><?php echo htmlspecialchars($detail['title']); ?></h1>
                <div class="h2 text-success my-3"><?php echo number_format($detail['price']); ?>₫</div>

                <div class="mb-4">
                    <div><strong>Danh mục:</strong> <?php echo $detail['category_name']; ?></div>
                    <div><strong>Tình trạng:</strong> <?php echo $detail['condition_status']; ?></div>
                    <div><strong>Vị trí:</strong> <?php echo $detail['city']; ?></div>
                </div>

                <!-- Actions -->
                <?php
                $currentUserId = isLoggedIn() ? getUserId() : null;
                $isOwnBike = $currentUserId && $currentUserId == $detail['seller_id'];
                ?>

                <div class="d-grid gap-2">
                    <?php if ($isGuest): ?>
                        <!-- Guest User -->
                        <a href="../auth/login.php" class="btn btn-success btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập để mua xe
                        </a>
                        <a href="../auth/login.php" class="btn btn-outline-light">
                            <i class="bi bi-chat"></i> Đăng nhập để nhắn tin
                        </a>

                    <?php elseif ($isOwnBike): ?>
                        <!-- Own Bike - Cannot buy -->
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i> Đây là xe của bạn
                        </div>
                        <a href="../seller/my-bikes.php" class="btn btn-outline-secondary">
                            <i class="bi bi-bicycle"></i> Quản lý xe của tôi
                        </a>
                        <a href="../seller/edit-bike.php?id=<?php echo $detail['id']; ?>" class="btn btn-outline-light">
                            <i class="bi bi-pencil"></i> Chỉnh sửa
                        </a>

                    <?php else: ?>
                        <!-- Other's Bike - Can buy (both buyer and seller) -->
                        <a href="../orders/create.php?bike_id=<?php echo $detail['id']; ?>" class="btn btn-success btn-lg">
                            <i class="bi bi-cart-plus"></i> Đặt mua ngay
                        </a>
                        <a href="../chat/chat.php?seller_id=<?php echo $detail['seller_id']; ?>&bike_id=<?php echo $bikeId; ?>"
                            class="btn btn-outline-light">
                            <i class="bi bi-chat-dots"></i> Nhắn tin người bán
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Seller -->
                <div class="bg-dark-2-custom p-3 rounded mt-4">
                    <h6><i class="bi bi-person"></i> Người bán</h6>
                    <?php if ($isGuest): ?>
                        <div class="text-center py-3">
                            <i class="bi bi-lock" style="font-size:2rem"></i>
                            <p class="text-muted small mt-2">Đăng nhập để xem thông tin</p>
                            <a href="../auth/login.php" class="btn btn-sm btn-success">Đăng nhập</a>
                        </div>
                    <?php else: ?>
                        <div class="fw-bold"><?php echo $detail['seller_name']; ?></div>
                        <?php if ($detail['seller_phone']): ?>
                            <a href="tel:<?php echo $detail['seller_phone']; ?>" class="text-success">
                                <i class="bi bi-telephone"></i> <?php echo $detail['seller_phone']; ?>
                            </a>
                      <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div class="mt-5">
            <h4><i class="bi bi-info-circle"></i> Mô tả</h4>
            <div class="bg-dark-2-custom p-4 rounded">
                <p style="white-space:pre-line"><?php echo htmlspecialchars($detail['description']); ?></p>
            </div>
        </div>

        <!-- Related -->
        <?php if (!empty($related)): ?>
            <div class="mt-5">
                <h4><i class="bi bi-bicycle"></i> Xe tương tự</h4>
                <div class="row g-4">
             <?php foreach (array_slice($related, 0, 4) as $rel): ?>
                        <div class="col-md-3">
                            <div class="bike-card" onclick="location.href='detail.php?id=<?php echo $rel['id']; ?>'">
                                <div class="bike-card-body">
                                    <h6 class="bike-title small"><?php echo $rel['title']; ?></h6>
                                    <div class="bike-price small"><?php echo number_format($rel['price']); ?>₫</div>
                                </div>
                            </div>
                        </div>
                  <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>