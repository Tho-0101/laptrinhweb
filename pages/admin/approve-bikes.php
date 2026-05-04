<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bikeId = $_POST['bike_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve') {
        $stmt = $db->prepare("UPDATE bikes SET status = 'approved' WHERE id = ?");
        $stmt->execute([$bikeId]);
        header('Location: approve-bikes.php?success=approved');
        exit;
    } elseif ($action === 'reject') {
        $reason = $_POST['rejection_reason'] ?? '';
        $stmt = $db->prepare("UPDATE bikes SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        $stmt->execute([$reason, $bikeId]);
        header('Location: approve-bikes.php?success=rejected');
        exit;
    }
}

// Get pending bikes
$query = "
    SELECT b.*, c.name as category_name, br.name as brand_name, u.full_name as seller_name, u.email as seller_email
    FROM bikes b
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN brands br ON b.brand_id = br.id
    LEFT JOIN users u ON b.seller_id = u.id
    WHERE b.status = 'pending'
    ORDER BY b.created_at ASC
";
$stmt = $db->query($query);
$pendingBikes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Duyệt tin đăng - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Dashboard</a>
                <a href="../auth/logout.php" class="btn btn-danger btn-sm">Đăng xuất</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-clipboard-check"></i> Kiểm duyệt tin đăng</h2>
            <span class="badge bg-warning fs-5"><?php echo count($pendingBikes); ?> tin chờ</span>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php if ($_GET['success'] === 'approved'): ?>
                ✅ Đã duyệt tin đăng thành công!
            <?php elseif ($_GET['success'] === 'rejected'): ?>
                ❌ Đã từ chối tin đăng!
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($pendingBikes)): ?>
        <div class="row g-4">
            <?php foreach ($pendingBikes as $bike): ?>
            <div class="col-12">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <div class="row">
                        <div class="col-md-2">
                            <?php if (!empty($bike['main_image'])): ?>
                            <img src="../../<?php echo htmlspecialchars($bike['main_image']); ?>" class="w-100 rounded">
                            <?php else: ?>
                            <div class="bg-dark p-4 rounded text-center">
                                <i class="bi bi-bicycle" style="font-size:3rem"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-7">
                            <h5 class="mb-2"><?php echo htmlspecialchars($bike['title']); ?></h5>
                            <p class="text-muted mb-2">
                                <i class="bi bi-tag"></i> <?php echo htmlspecialchars($bike['category_name'] ?? 'N/A'); ?> •
                                <i class="bi bi-award"></i> <?php echo htmlspecialchars($bike['brand_name'] ?? 'N/A'); ?>
                            </p>
                            <p class="text-muted mb-2">
                                <i class="bi bi-person"></i> Seller: <strong><?php echo htmlspecialchars($bike['seller_name']); ?></strong>
                                (<?php echo htmlspecialchars($bike['seller_email']); ?>)
                            </p>
                            <p class="text-muted mb-2">
                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($bike['city']); ?>, <?php echo htmlspecialchars($bike['district']); ?>
                            </p>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-calendar"></i> Đăng ngày: <?php echo date('d/m/Y H:i', strtotime($bike['created_at'])); ?>
                            </p>
                            
                            <div class="mt-3">
                                <p class="mb-1"><strong>Mô tả:</strong></p>
                                <p class="small text-muted">
                                    <?php echo nl2br(htmlspecialchars(substr($bike['description'], 0, 200))); ?>
                                    <?php if (strlen($bike['description']) > 200): ?>...<?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-end">
                            <div class="h4 text-success mb-3"><?php echo number_format($bike['price']); ?>₫</div>
                            
                            <div class="mb-3">
                                <span class="badge bg-secondary">
                                    <?php echo ucfirst($bike['condition_status']); ?>
                                </span>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="bike_id" value="<?php echo $bike['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-check-circle"></i> Duyệt
                                    </button>
                                </form>
                                
                                <button class="btn btn-danger" onclick="rejectBike(<?php echo $bike['id']; ?>)">
                                    <i class="bi bi-x-circle"></i> Từ chối
                                </button>
                                
                                <a href="../bikes/detail.php?id=<?php echo $bike['id']; ?>" 
                                   class="btn btn-outline-light btn-sm" target="_blank">
                                    <i class="bi bi-eye"></i> Xem chi tiết
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
            <i class="bi bi-check-circle" style="font-size:5rem"></i>
            <h3 class="mt-3">Không có tin chờ duyệt</h3>
            <p class="text-muted mb-4">Tất cả tin đăng đã được xử lý!</p>
            <a href="dashboard.php" class="btn btn-success btn-lg">
                <i class="bi bi-speedometer2"></i> Về Dashboard
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark-2-custom">
                <div class="modal-header border-dark">
                    <h5 class="modal-title">Từ chối tin đăng</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="rejectForm">
                    <div class="modal-body">
                        <input type="hidden" name="bike_id" id="rejectBikeId">
                        <input type="hidden" name="action" value="reject">
                        
                        <div class="mb-3">
                            <label class="form-label">Lý do từ chối:</label>
                            <textarea name="rejection_reason" class="form-control bg-dark text-white" 
                                      rows="4" required 
                                      placeholder="VD: Ảnh không rõ, mô tả không đầy đủ..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-dark">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-danger">Từ chối</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function rejectBike(bikeId) {
            document.getElementById('rejectBikeId').value = bikeId;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }
    </script>
</body>
</html>
