<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

// Get NEW bikes (posted in last 30 minutes)
$newBikesQuery = "
    SELECT b.*, c.name as category_name, br.name as brand_name,
           TIMESTAMPDIFF(MINUTE, b.created_at, NOW()) as minutes_ago
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN brands br ON b.brand_id = br.id
    WHERE b.status = 'approved' 
    AND b.created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ORDER BY b.created_at DESC
    LIMIT 8
";
$stmt = $db->query($newBikesQuery);
$newBikes = $stmt->fetchAll();

// Get filters
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$condition = $_GET['condition'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT b.*, c.name as category_name, br.name as brand_name, u.full_name as seller_name, u.rating as seller_rating
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN brands br ON b.brand_id = br.id
    LEFT JOIN users u ON b.seller_id = u.id
    WHERE b.status = 'approved'
";

$params = [];

if ($search) {
    $query .= " AND (b.title LIKE ? OR b.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category) {
    $query .= " AND b.category_id = ?";
    $params[] = $category;
}

if ($brand) {
    $query .= " AND b.brand_id = ?";
    $params[] = $brand;
}

if ($minPrice) {
    $query .= " AND b.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice) {
    $query .= " AND b.price <= ?";
    $params[] = $maxPrice;
}

if ($condition) {
    $query .= " AND b.condition_status = ?";
    $params[] = $condition;
}

$query .= " ORDER BY b.created_at DESC LIMIT 20";

$stmt = $db->prepare($query);
$stmt->execute($params);
$bikes = $stmt->fetchAll();

// Get categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get brands for filter
$brands = $db->query("SELECT * FROM brands ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BikeMarket - Nền tảng mua bán xe đạp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css?v=4.0" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">BIKE<span class="text-success">MARKET</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Trang chủ</a></li>
                    <li class="nav-item"><a class="nav-link" href="pages/bikes/list.php">Mua xe</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars(getUserName()); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (getUserRole() === 'buyer'): ?>
                                    <li><a class="dropdown-item" href="pages/buyer/dashboard.php">Dashboard</a></li>
                                <?php elseif (getUserRole() === 'seller'): ?>
                                    <li><a class="dropdown-item" href="pages/seller/dashboard.php">Dashboard</a></li>
                                <?php elseif (getUserRole() === 'inspector'): ?>
                                    <li><a class="dropdown-item" href="pages/inspector/dashboard.php">Dashboard</a></li>
                                <?php elseif (getUserRole() === 'admin'): ?>
                                    <li><a class="dropdown-item" href="pages/admin/dashboard.php">Admin</a></li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="pages/auth/logout.php">Đăng xuất</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="pages/auth/login.php">Đăng nhập</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-success text-white ms-2"
                                href="pages/auth/register.php">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 mb-4">Nền tảng <span class="text-success">mua bán xe đạp</span> uy tín</h1>
            <p class="lead mb-4">Kết nối người mua và người bán. Kiểm định chất lượng. Giao dịch an toàn.</p>

            <!-- Search Bar -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form method="GET" class="search-bar">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Tìm kiếm xe đạp..."
                                value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-search"></i> Tìm kiếm
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- NEW BIKES SECTION - 30 MINUTES -->
        <?php if (!empty($newBikes)): ?>
            <div class="new-bikes-section mb-5">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h3 class="text-success mb-1">
                                <i class="bi bi-lightning-fill"></i> Xe mới đăng
                            </h3>
                            <p class="text-muted small mb-0">Cập nhật trong 30 phút qua • Tự động ẩn sau 30 phút</p>
                        </div>
                        <div class="badge bg-success" style="font-size:1rem;padding:10px 20px">
                            <?php echo count($newBikes); ?> xe mới
                        </div>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($newBikes as $bike): ?>
                            <div class="col-md-3">
                                <div class="bike-card" style="position:relative"
                                    onclick="location.href='pages/bikes/detail.php?id=<?php echo $bike['id']; ?>'">
                                    <span class="new-badge">
                                        <i class="bi bi-star-fill"></i> MỚI
                                    </span>
                                    <span class="time-badge">
                                        <i class="bi bi-clock"></i> <?php echo $bike['minutes_ago']; ?> phút trước
                                    </span>
                                    <?php if (!empty($bike['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($bike['image_url']); ?>"
                                            alt="<?php echo htmlspecialchars($bike['title']); ?>"
                                            style="width:100%;height:200px;object-fit:cover;border-radius:8px 8px 0 0">
                                    <?php else: ?>
                                        <div class="bike-card-image">
                                            <i class="bi bi-bicycle"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="bike-card-body">
                                        <h6 class="bike-card-title"><?php echo htmlspecialchars($bike['title']); ?></h6>
                                        <p class="bike-card-price"><?php echo number_format($bike['price']); ?>₫</p>
                                        <p class="bike-card-location">
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>
                                        </p>
                                        <div class="bike-card-meta">
                                            <span><?php echo ucfirst($bike['condition_status']); ?></span>
                                            <?php if (!empty($bike['frame_material'])): ?>
                                                <span class="ms-1">• <?php echo htmlspecialchars($bike['frame_material']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="filters-container">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="brand" class="form-select">
                                <option value="">Tất cả thương hiệu</option>
                                <?php foreach ($brands as $br): ?>
                                    <option value="<?php echo $br['id']; ?>" <?php echo $brand == $br['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($br['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="min_price" class="form-control" placeholder="Giá từ"
                                value="<?php echo htmlspecialchars($minPrice); ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="max_price" class="form-control" placeholder="Đến"
                                value="<?php echo htmlspecialchars($maxPrice); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-funnel"></i> Lọc
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- All Bikes Grid -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3">Tất cả xe đăng bán</h4>
            </div>
        </div>

        <?php if (!empty($bikes)): ?>
            <div class="row g-4">
                <?php foreach ($bikes as $bike): ?>
                    <div class="col-md-3">
                        <div class="bike-card" onclick="location.href='pages/bikes/detail.php?id=<?php echo $bike['id']; ?>'">
                            <?php if (!empty($bike['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($bike['image_url']); ?>"
                                    alt="<?php echo htmlspecialchars($bike['title']); ?>"
                                    style="width:100%;height:200px;object-fit:cover;border-radius:8px 8px 0 0">
                            <?php else: ?>
                                <div class="bike-card-image">
                                    <i class="bi bi-bicycle"></i>
                                </div>
                            <?php endif; ?>

                            <div class="bike-card-body">
                                <h6 class="bike-card-title"><?php echo htmlspecialchars($bike['title']); ?></h6>
                                <p class="bike-card-price"><?php echo number_format($bike['price']); ?>₫</p>
                                <p class="bike-card-location">
                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>,
                                    <?php echo htmlspecialchars($bike['district']); ?>
                                </p>
                                <div class="bike-card-meta text-muted small">
                                    <?php
                                    $meta = [];

                                    // Tình trạng
                                    if (!empty($bike['condition_status'])) {
                                        $meta[] = '<i class="bi bi-check-circle"></i> ' . ucfirst($bike['condition_status']);
                                    }

                                    // Chất liệu khung
                                    if (!empty($bike['frame_material'])) {
                                        $meta[] = '<i class="bi bi-gear"></i> ' . htmlspecialchars($bike['frame_material']);
                                    }

                                    // Năm sản xuất
                                    if (!empty($bike['year_of_manufacture'])) {
                                        $meta[] = '<i class="bi bi-calendar"></i> ' . htmlspecialchars($bike['year_of_manufacture']);
                                    }

                                    echo implode(' <span class="mx-1">•</span> ', $meta);
                                    ?>
                                </div>
                                <?php if ($bike['is_inspected']): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-success"><i class="bi bi-patch-check"></i> Đã kiểm định</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-search"></i>
                <h3>Không tìm thấy xe nào</h3>
                <p>Thử thay đổi bộ lọc hoặc tìm kiếm khác</p>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer mt-5">
        <div class="container">
            <div class="row g-4">
                <!-- Column 1: About -->
                <div class="col-lg-3 col-md-6">
                    <h5 class="mb-3">BIKE<span class="text-success">MARKET</span></h5>
                    <p class="text-muted mb-3">Nền tảng mua bán xe đạp uy tín hàng đầu Việt Nam. Kết nối người mua -
                        người bán, kiểm định chất lượng, giao dịch an toàn.</p>
                    <div class="social-links">
                        <a href="#" class="me-3"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="me-3"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="me-3"><i class="bi bi-youtube"></i></a>
                        <a href="#"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>

                <!-- Column 2: Quick Links -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="mb-3">Liên kết nhanh</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="pages/bikes/list.php"><i class="bi bi-chevron-right"></i> Mua xe
                                đạp</a></li>
                        <li class="mb-2"><a href="pages/auth/register.php"><i class="bi bi-chevron-right"></i> Đăng ký
                                bán xe</a></li>
                        <li class="mb-2"><a href="pages/auth/login.php"><i class="bi bi-chevron-right"></i> Đăng
                                nhập</a></li>
                        <li class="mb-2"><a href="#"><i class="bi bi-chevron-right"></i> Hướng dẫn mua bán</a></li>
                        <li class="mb-2"><a href="#"><i class="bi bi-chevron-right"></i> Chính sách bảo mật</a></li>
                    </ul>
                </div>

                <!-- Column 3: Categories -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="mb-3">Danh mục xe</h6>
                    <ul class="list-unstyled footer-links">
                        <li class="mb-2"><a href="?category=1"><i class="bi bi-bicycle"></i> Xe đua (Road Bike)</a></li>
                        <li class="mb-2"><a href="?category=2"><i class="bi bi-bicycle"></i> Xe địa hình (MTB)</a></li>
                        <li class="mb-2"><a href="?category=3"><i class="bi bi-bicycle"></i> Xe điện (E-Bike)</a></li>
                        <li class="mb-2"><a href="?category=4"><i class="bi bi-bicycle"></i> Xe gấp (Folding)</a></li>
                        <li class="mb-2"><a href="?category=5"><i class="bi bi-bicycle"></i> Xe touring</a></li>
                    </ul>
                </div>

                <!-- Column 4: Contact & Newsletter -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="mb-3">Liên hệ</h6>
                    <ul class="list-unstyled contact-info mb-3">
                        <li class="mb-2"><i class="bi bi-envelope"></i> support@bikemarket.vn</li>
                        <li class="mb-2"><i class="bi bi-telephone"></i> 1900 xxxx</li>
                        <li class="mb-2"><i class="bi bi-geo-alt"></i> Hà Nội, Việt Nam</li>
                        <li class="mb-2"><i class="bi bi-clock"></i> 8:00 - 22:00 hàng ngày</li>
                    </ul>
                    <div class="newsletter">
                        <p class="small text-muted mb-2">Nhận tin khuyến mãi</p>
                        <div class="input-group input-group-sm">
                            <input type="email" class="form-control bg-dark text-white border-secondary"
                                placeholder="Email của bạn">
                            <button class="btn btn-success" type="button"><i class="bi bi-send"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <!-- Bottom Footer -->
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="text-muted small mb-0">&copy; 2024 BikeMarket. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-muted small me-3">Điều khoản sử dụng</a>
                    <a href="#" class="text-muted small me-3">Chính sách bảo mật</a>
                    <a href="#" class="text-muted small">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>