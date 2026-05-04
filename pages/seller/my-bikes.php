<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('seller');

$userId = getUserId();
$db = Database::getInstance()->getConnection();

// Filter
$statusFilter = $_GET['status'] ?? 'all';

$query = "
    SELECT b.*, c.name as category_name, br.name as brand_name
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN brands br ON b.brand_id = br.id
    WHERE b.seller_id = ? AND b.status != 'deleted'
";

$params = [$userId];

if ($statusFilter !== 'all') {
    $query .= " AND b.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);

$bikes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count by status (exclude deleted)
$statsQuery = "
    SELECT status, COUNT(*) as count
    FROM bikes
    WHERE seller_id = ? AND status != 'deleted'
    GROUP BY status
";
$stmt = $db->prepare($statsQuery);
$stmt->execute([$userId]);
$statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$totalCount = array_sum($statusCounts);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý tin đăng - Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
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
            <h2><i class="bi bi-bicycle"></i> Quản lý tin đăng</h2>
            <a href="post-bike.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Đăng tin mới
            </a>
        </div>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" href="?status=all">
                    Tất cả (<?php echo $totalCount; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" href="?status=pending">
                    Chờ duyệt (<?php echo $statusCounts['pending'] ?? 0; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>" href="?status=approved">
                    Đang bán (<?php echo $statusCounts['approved'] ?? 0; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'sold' ? 'active' : ''; ?>" href="?status=sold">
                    Đã bán (<?php echo $statusCounts['sold'] ?? 0; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>" href="?status=rejected">
                    Từ chối (<?php echo $statusCounts['rejected'] ?? 0; ?>)
                </a>
            </li>
        </ul>

        <!-- Bikes List -->
        <?php if (!empty($bikes)): ?>
            <div class="row g-4">
                <?php foreach ($bikes as $bike): ?>
                    <div class="col-12" id="bike-<?php echo $bike['id']; ?>">
                        <div class="bg-dark-2-custom p-3 rounded border-dark-custom">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <?php if (!empty($bike['main_image'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($bike['main_image']); ?>"
                                            class="img-fluid rounded" alt="Bike">
                                    <?php else: ?>
                                        <div class="bg-dark-3 rounded d-flex align-items-center justify-content-center"
                                            style="height:100px">
                                            <i class="bi bi-bicycle" style="font-size:3rem"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-7">
                                    <h5 class="mb-2">
                                        <a href="../bikes/detail.php?id=<?php echo $bike['id']; ?>"
                                            class="text-white text-decoration-none">
                                            <?php echo htmlspecialchars($bike['title']); ?>
                                        </a>
                                        <?php if ($bike['status'] === 'hidden'): ?>
                                            <span class="badge bg-secondary">Đã ẩn</span>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-tag"></i>
                                        <?php echo htmlspecialchars($bike['category_name'] ?? 'N/A'); ?>
                                        • <?php echo htmlspecialchars($bike['brand_name'] ?? 'N/A'); ?>
                                    </p>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>
                                        <?php if ($bike['district']): ?>,
                                            <?php echo htmlspecialchars($bike['district']); ?>         <?php endif; ?>
                                    </p>
                                    <p class="text-muted small mb-0">
                                        <i class="bi bi-eye"></i> <?php echo $bike['view_count']; ?> lượt xem •
                                        <i class="bi bi-heart"></i> <?php echo $bike['favorite_count']; ?> yêu thích •
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($bike['created_at'])); ?>
                                    </p>
                                </div>

                                <div class="col-md-3 text-end">
                                    <div class="h4 text-success mb-3"><?php echo number_format($bike['price']); ?>₫</div>

                                    <?php
                                    $badges = [
                                        'pending' => ['warning', 'Chờ duyệt'],
                                        'approved' => ['success', 'Đang bán'],
                                        'rejected' => ['danger', 'Từ chối'],
                                        'sold' => ['secondary', 'Đã bán']
                                    ];
                                    $status = $badges[$bike['status']] ?? ['secondary', $bike['status']];
                                    ?>
                                    <span class="badge bg-<?php echo $status[0]; ?> mb-3"><?php echo $status[1]; ?></span>

                                    <div class="d-grid gap-2">
                                        <a href="edit-bike.php?id=<?php echo $bike['id']; ?>"
                                            class="btn btn-sm btn-outline-light">
                                            <i class="bi bi-pencil"></i> Sửa
                                        </a>

                                        <?php if ($bike['status'] === 'approved' || $bike['status'] === 'hidden'): ?>

                                            <?php if ($bike['status'] !== 'hidden'): ?>
                                                <button class="btn btn-sm btn-warning"
                                                    onclick="toggleVisibility(<?php echo $bike['id']; ?>)">
                                                    <i class="bi bi-eye-slash"></i> Ẩn
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-sm btn-info"
                                                    onclick="toggleVisibility(<?php echo $bike['id']; ?>)">
                                                    <i class="bi bi-eye"></i> Hiện
                                                </button>
                                            <?php endif; ?>

                                        <?php endif; ?>

                                        <button class="btn btn-sm btn-danger" onclick="deleteBike(<?php echo $bike['id']; ?>)">
                                            <i class="bi bi-trash"></i> Xóa
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <?php if ($bike['status'] === 'rejected' && !empty($bike['rejection_reason'])): ?>
                                <div class="alert alert-danger mt-3 mb-0">
                                    <strong>Lý do từ chối:</strong> <?php echo htmlspecialchars($bike['rejection_reason']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-bicycle text-muted" style="font-size:5rem"></i>
                <h3 class="mt-3">Chưa có tin đăng nào</h3>
                <p class="text-muted">Bắt đầu đăng tin bán xe của bạn ngay!</p>
                <a href="post-bike.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Đăng tin đầu tiên
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle visibility (Hide/Show)
        function toggleVisibility(bikeId) {
            if (!confirm('Bạn có chắc muốn thay đổi trạng thái hiển thị của tin đăng này?')) {
                return;
            }

            fetch('../../api/bikes/toggle-visibility.php', {
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
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra. Vui lòng thử lại.');
                });
        }

        // Delete bike
        function deleteBike(bikeId) {
            if (!confirm(
                '⚠️ Bạn có chắc muốn xóa tin đăng này?\n\nLưu ý: Tin đăng đã có đơn hàng không thể xóa. Bạn có thể ẩn tin đăng thay vì xóa.'
            )) {
                return;
            }

            fetch('../../api/bikes/delete.php', {
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
                        alert(data.message);
                        // Remove from DOM
                        const bikeElement = document.getElementById('bike-' + bikeId);
                        if (bikeElement) {
                            bikeElement.remove();
                        }
                    } else {
                        alert('❌ ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra. Vui lòng thử lại.');
                });
        }
    </script>
</body>

</html>