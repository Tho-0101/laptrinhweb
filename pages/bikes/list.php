<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

// Get filters from URL
$filters = [
    'category_id' => $_GET['category'] ?? null,
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
    'city' => $_GET['city'] ?? null,
    'condition_status' => $_GET['condition'] ?? null,
    'is_inspected' => isset($_GET['inspected']) ? true : null,
    'sort' => $_GET['sort'] ?? 'newest'
];

$searchTerm = $_GET['q'] ?? '';
$page = $_GET['page'] ?? 1;
$perPage = 12;

$bike = new Bike();
$result = $bike->search($filters, $page, $perPage, $searchTerm);

// Get categories for filter
$stmt = Database::getInstance()->getConnection()->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get cities for filter
$stmtCities = Database::getInstance()->getConnection()->query("SELECT DISTINCT city FROM bikes WHERE city IS NOT NULL ORDER BY city");
$cities = $stmtCities->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách xe đạp - BikeMarket</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
    <link href="../../assets/css/list-page-styles.css" rel="stylesheet">
    <style>
    /* Sticky Filter Sidebar */
    .filter-sidebar-wrapper {
        position: sticky;
        top: 80px;
        /* navbar height + spacing */
    }

    .filter-sidebar {
        max-height: calc(100vh - 100px);
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Custom scrollbar */
    .filter-sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .filter-sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 3px;
    }

    .filter-sidebar::-webkit-scrollbar-thumb {
        background: var(--bs-success);
        border-radius: 3px;
    }

    .filter-sidebar::-webkit-scrollbar-thumb:hover {
        background: #198754;
    }

    /* Firefox */
    .filter-sidebar {
        scrollbar-width: thin;
        scrollbar-color: var(--bs-success) rgba(255, 255, 255, 0.05);
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">
                BIKE<span class="text-success">MARKET</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.php">
                            <i class="bi bi-house-door"></i> Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="list.php">
                            <i class="bi bi-bicycle"></i> Mua xe
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <?php if (getUserRole() === 'seller'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../seller/post-bike.php">
                            <i class="bi bi-plus-circle"></i> Đăng tin
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars(getUserName()); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="../<?php echo getUserRole(); ?>/dashboard.php">
                                    <i class="bi bi-speedometer2"></i> Dashboard
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                                </a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">
                            <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-success text-white ms-2" href="../auth/register.php">
                            <i class="bi bi-person-plus"></i> Đăng ký
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb & Page Header -->
    <div class="bg-dark-2-custom border-bottom border-dark-custom">
        <div class="container py-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="../../index.php"><i class="bi bi-house-door"></i> Trang chủ</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Danh sách xe đạp</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Page Title & Quick Stats -->
    <div class="container mt-4">
        <div class="row align-items-center mb-4">
            <div class="col-md-6">
                <h2 class="mb-2">
                    <i class="bi bi-bicycle text-success"></i> Danh sách xe đạp
                </h2>
                <p class="text-muted mb-0">
                    Tìm thấy <span class="text-success fw-bold"><?php echo $result['total']; ?></span> xe
                    <?php if ($searchTerm): ?>
                    với từ khóa "<strong><?php echo htmlspecialchars($searchTerm); ?></strong>"
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <?php if (isLoggedIn() && getUserRole() === 'seller'): ?>
                <a href="../seller/post-bike.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-circle"></i> Đăng tin bán xe
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Filters Display -->
        <?php 
        $activeFilters = [];
        if ($searchTerm) $activeFilters[] = ['label' => 'Tìm kiếm: ' . $searchTerm, 'key' => 'q'];
        if ($filters['category_id']) {
            $catName = '';
            foreach ($categories as $c) {
                if ($c['id'] == $filters['category_id']) $catName = $c['name'];
            }
            $activeFilters[] = ['label' => 'Danh mục: ' . $catName, 'key' => 'category'];
        }
        if ($filters['min_price']) $activeFilters[] = ['label' => 'Từ: ' . number_format($filters['min_price']) . '₫', 'key' => 'min_price'];
        if ($filters['max_price']) $activeFilters[] = ['label' => 'Đến: ' . number_format($filters['max_price']) . '₫', 'key' => 'max_price'];
        if ($filters['city']) $activeFilters[] = ['label' => 'Thành phố: ' . $filters['city'], 'key' => 'city'];
        if ($filters['condition_status']) {
            $conditionLabels = [
                'new' => 'Mới',
                'like_new' => 'Như mới',
                'good' => 'Tốt',
                'fair' => 'Khá'
            ];
            $conditionLabel = $conditionLabels[$filters['condition_status']] ?? ucfirst($filters['condition_status']);
            $activeFilters[] = ['label' => 'Tình trạng: ' . $conditionLabel, 'key' => 'condition'];
        }
        if (isset($_GET['inspected'])) $activeFilters[] = ['label' => 'Đã kiểm định', 'key' => 'inspected'];
        ?>

        <?php if (!empty($activeFilters)): ?>
        <div class="mb-4">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small">Bộ lọc đang áp dụng:</span>
                <?php foreach ($activeFilters as $filter): ?>
                <?php 
                        $removeParams = $_GET;
                        unset($removeParams[$filter['key']]);
                        ?>
                <span class="badge bg-success d-inline-flex align-items-center gap-2">
                    <?php echo htmlspecialchars($filter['label']); ?>
                    <a href="?<?php echo http_build_query($removeParams); ?>" class="text-white text-decoration-none"
                        title="Xóa bộ lọc này">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </span>
                <?php endforeach; ?>
                <a href="list.php" class="badge bg-danger text-white text-decoration-none">
                    <i class="bi bi-x-circle-fill"></i> Xóa tất cả
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- STICKY Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="filter-sidebar-wrapper">
                    <div class="filter-sidebar bg-dark-2-custom p-4 rounded border-dark-custom">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-funnel"></i> Bộ lọc
                        </h5>

                        <form method="GET" action="list.php" id="filterForm">
                            <!-- Search -->
                            <div class="mb-3">
                                <label class="form-label small text-muted">Tìm kiếm</label>
                                <input type="text" name="q" class="form-control bg-dark text-white"
                                    placeholder="Tên xe..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label small text-muted">Danh mục</label>
                                <select name="category" class="form-select bg-dark text-white">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo $filters['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Price Range -->
                            <div class="mb-3">
                                <label class="form-label small text-muted">Khoảng giá</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" name="min_price" class="form-control bg-dark text-white"
                                            placeholder="Từ" value="<?php echo $filters['min_price'] ?? ''; ?>"
                                            step="100000">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" name="max_price" class="form-control bg-dark text-white"
                                            placeholder="Đến" value="<?php echo $filters['max_price'] ?? ''; ?>"
                                            step="100000">
                                    </div>
                                </div>
                            </div>

                            <!-- City -->
                            <div class="mb-3">
                                <label class="form-label small text-muted">Thành phố</label>
                                <select name="city" class="form-select bg-dark text-white">
                                    <option value="">Tất cả</option>
                                    <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo htmlspecialchars($city); ?>"
                                        <?php echo $filters['city'] == $city ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($city); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Condition -->
                            <div class="mb-3">
                                <label class="form-label small text-muted">Tình trạng</label>
                                <select name="condition" class="form-select bg-dark text-white">
                                    <option value="">Tất cả</option>
                                    <option value="new"
                                        <?php echo $filters['condition_status'] == 'new' ? 'selected' : ''; ?>>Mới
                                    </option>
                                    <option value="like_new"
                                        <?php echo $filters['condition_status'] == 'like_new' ? 'selected' : ''; ?>>Như
                                        mới</option>
                                    <option value="good"
                                        <?php echo $filters['condition_status'] == 'good' ? 'selected' : ''; ?>>Tốt
                                    </option>
                                    <option value="fair"
                                        <?php echo $filters['condition_status'] == 'fair' ? 'selected' : ''; ?>>Khá
                                    </option>
                                </select>
                            </div>

                            <!-- Inspected -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="inspected" id="inspected"
                                    value="1" <?php echo isset($_GET['inspected']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="inspected">
                                    <i class="bi bi-patch-check-fill text-success"></i> Đã kiểm định
                                </label>
                            </div>

                            <!-- Sort -->
                            <div class="mb-3">
                                <label class="form-label small text-muted">Sắp xếp</label>
                                <select name="sort" class="form-select bg-dark text-white">
                                    <option value="newest"
                                        <?php echo $filters['sort'] == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                                    <option value="price_asc"
                                        <?php echo $filters['sort'] == 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần
                                    </option>
                                    <option value="price_desc"
                                        <?php echo $filters['sort'] == 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần
                                    </option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="bi bi-search"></i> Áp dụng
                            </button>
                            <a href="list.php" class="btn btn-outline-light w-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Đặt lại
                            </a>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Bikes Grid -->
            <div class="col-lg-9">
                <?php if (!empty($result['bikes'])): ?>
                <div class="row g-4">
                    <?php foreach ($result['bikes'] as $bike): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="bike-card">
                            <div class="position-relative">
                                <?php if (!empty($bike['main_image'])): ?>
                                <img src="../../<?php echo htmlspecialchars($bike['main_image']); ?>"
                                    alt="<?php echo htmlspecialchars($bike['title']); ?>" class="bike-card-img"
                                    onclick="location.href='detail.php?id=<?php echo $bike['id']; ?>'"
                                    style="cursor:pointer">
                                <?php else: ?>
                                <div class="bike-card-img d-flex align-items-center justify-content-center"
                                    onclick="location.href='detail.php?id=<?php echo $bike['id']; ?>'"
                                    style="cursor:pointer">
                                    <i class="bi bi-bicycle" style="font-size: 3rem; color: var(--gray);"></i>
                                </div>
                                <?php endif; ?>

                                <?php if ($bike['is_inspected']): ?>
                                <span class="badge-inspected">
                                    <i class="bi bi-patch-check-fill"></i> Đã kiểm định
                                </span>
                                <?php endif; ?>

                                <div class="bike-card-overlay">
                                    <a href="detail.php?id=<?php echo $bike['id']; ?>" class="btn btn-light btn-sm">
                                        <i class="bi bi-eye"></i> Xem chi tiết
                                    </a>
                                </div>
                            </div>

                            <div class="bike-card-body">
                                <h5 class="bike-title"
                                    onclick="location.href='detail.php?id=<?php echo $bike['id']; ?>'"
                                    style="cursor:pointer">
                                    <?php echo htmlspecialchars($bike['title']); ?>
                                </h5>
                                <div class="bike-meta">
                                    <i class="bi bi-tag"></i>
                                    <?php echo htmlspecialchars($bike['category_name'] ?? 'N/A'); ?>
                                    <span class="mx-2">•</span>
                                    <i class="bi bi-gear"></i>
                                    <?php
                                            $conditionLabels = [
                                                'new' => 'Mới',
                                                'like_new' => 'Như mới',
                                                'good' => 'Tốt',
                                                'fair' => 'Khá'
                                            ];
                                            echo $conditionLabels[$bike['condition_status']] ?? htmlspecialchars($bike['condition_status']);
                                            ?>
                                </div>
                                <div class="bike-price">
                                    <?php echo number_format($bike['price']); ?>₫
                                </div>
                                <div class="bike-location mb-3">
                                    <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>
                                </div>

                                <div class="bike-card-actions">
                                    <a href="detail.php?id=<?php echo $bike['id']; ?>"
                                        class="btn btn-success btn-sm flex-fill">
                                        <i class="bi bi-cart-plus"></i> Mua ngay
                                    </a>
                                    <?php if (isLoggedIn()): ?>
                                    <button class="btn btn-outline-light btn-sm"
                                        onclick="event.stopPropagation(); toggleWishlist(<?php echo $bike['id']; ?>)">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                    <?php else: ?>
                                    <a href="../auth/login.php" class="btn btn-outline-light btn-sm"
                                        title="Đăng nhập để lưu">
                                        <i class="bi bi-heart"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($result['total_pages'] > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php 
                                // Preserve all filters in pagination
                                $paginationParams = $_GET;
                                unset($paginationParams['page']);
                                ?>
                        <?php for ($i = 1; $i <= $result['total_pages']; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link bg-dark border-dark-custom text-white"
                                href="?<?php echo http_build_query(array_merge($paginationParams, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-search" style="font-size: 5rem;"></i>
                    <h3 class="mt-3">Không tìm thấy xe nào</h3>
                    <p class="text-muted">Thử thay đổi bộ lọc hoặc tìm kiếm khác</p>
                    <a href="list.php" class="btn btn-success">
                        <i class="bi bi-arrow-counterclockwise"></i> Xem tất cả
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleWishlist(bikeId) {
        fetch('../../api/bikes/toggle-favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    bike_id: bikeId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const btn = event.target.closest('button');
                    const icon = btn.querySelector('i');
                    if (data.action === 'added') {
                        icon.classList.remove('bi-heart');
                        icon.classList.add('bi-heart-fill');
                        btn.classList.add('text-danger');
                    } else {
                        icon.classList.remove('bi-heart-fill');
                        icon.classList.add('bi-heart');
                        btn.classList.remove('text-danger');
                    }
                }
            })
            .catch(error => console.error('Error:', error));
    }
    </script>
</body>

</html>