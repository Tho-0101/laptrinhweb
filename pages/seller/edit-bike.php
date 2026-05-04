<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('seller');

$bikeId = $_GET['id'] ?? null;
if (!$bikeId) {
    header('Location: my-bikes.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Get bike details - verify ownership
$stmt = $db->prepare("
    SELECT b.* FROM bikes b 
    WHERE b.id = ? AND b.seller_id = ?
");
$stmt->execute([$bikeId, getUserId()]);
$bike = $stmt->fetch();

if (!$bike) {
    header('Location: my-bikes.php?error=not_found');
    exit;
}

// Get categories and brands
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$brands = $db->query("SELECT * FROM brands ORDER BY name")->fetchAll();

// Get existing images
$stmt = $db->prepare("
    SELECT * 
    FROM bike_images 
    WHERE bike_id = ? 
    ORDER BY is_primary DESC, id
");
$stmt->execute([$bikeId]);
$images = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $price = (float)$_POST['price'];
        $category_id = (int)$_POST['category_id'];
        $brand_id = (int)$_POST['brand_id'];
        $condition_status = $_POST['condition_status'];
        $year = $_POST['year'] ? (int)$_POST['year'] : null;
        $frame_size = $_POST['frame_size'] ?? null;
        $wheel_size = $_POST['wheel_size'] ?? null;
        $material = $_POST['material'] ?? null;
        $weight = $_POST['weight'] ? (float)$_POST['weight'] : null;
        $city = trim($_POST['city']);
        $district = trim($_POST['district'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        // Update bike
        $stmt = $db->prepare("
            UPDATE bikes SET
                title = ?,
                description = ?,
                price = ?,
                category_id = ?,
                brand_id = ?,
                condition_status = ?,
                year = ?,
                frame_size = ?,
                wheel_size = ?,
                material = ?,
                weight = ?,
                city = ?,
                district = ?,
                address = ?,
                updated_at = NOW()
            WHERE id = ? AND seller_id = ?
        ");
        
        $stmt->execute([
            $title, $description, $price, $category_id, $brand_id,
            $condition_status, $year, $frame_size, $wheel_size,
            $material, $weight, $city, $district, $address,
            $bikeId, getUserId()
        ]);
        
        $success = 'Cập nhật thông tin thành công!';
        
        // Refresh bike data
        $stmt = $db->prepare("SELECT * FROM bikes WHERE id = ?");
        $stmt->execute([$bikeId]);
        $bike = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa tin đăng - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
    <link href="../../assets/css/components/forms.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="my-bikes.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
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
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h2 class="mb-4">
                        <i class="bi bi-pencil-square text-success"></i> Chỉnh sửa tin đăng
                    </h2>

                    <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data">
                        <!-- Basic Info -->
                        <h5 class="mb-3">Thông tin cơ bản</h5>

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>Tiêu đề <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control"
                                        value="<?php echo htmlspecialchars($bike['title']); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Danh mục <span class="text-danger">*</span></label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">Chọn danh mục</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"
                                            <?php echo $bike['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Thương hiệu <span class="text-danger">*</span></label>
                                    <select name="brand_id" class="form-select" required>
                                        <option value="">Chọn thương hiệu</option>
                                        <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo $brand['id']; ?>"
                                            <?php echo $bike['brand_id'] == $brand['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Giá bán (VNĐ) <span class="text-danger">*</span></label>
                                    <input type="number" name="price" class="form-control"
                                        value="<?php echo $bike['price']; ?>" required min="0">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Tình trạng <span class="text-danger">*</span></label>
                                    <select name="condition_status" class="form-select" required>
                                        <option value="new"
                                            <?php echo $bike['condition_status'] == 'new' ? 'selected' : ''; ?>>Mới 100%
                                        </option>
                                        <option value="like_new"
                                            <?php echo $bike['condition_status'] == 'like_new' ? 'selected' : ''; ?>>Như
                                            mới (>95%)</option>
                                        <option value="good"
                                            <?php echo $bike['condition_status'] == 'good' ? 'selected' : ''; ?>>Tốt
                                            (80-95%)</option>
                                        <option value="fair"
                                            <?php echo $bike['condition_status'] == 'fair' ? 'selected' : ''; ?>>Khá
                                            (60-80%)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label>Mô tả chi tiết <span class="text-danger">*</span></label>
                                    <textarea name="description" class="form-control" rows="6"
                                        required><?php echo htmlspecialchars($bike['description']); ?></textarea>
                                    <small class="text-muted">Mô tả càng chi tiết càng tốt</small>
                                </div>
                            </div>
                        </div>

                        <!-- Specifications -->
                        <h5 class="mb-3">Thông số kỹ thuật</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Năm sản xuất</label>
                                    <input type="number" name="year" class="form-control"
                                        value="<?php echo $bike['year_of_manufacture']; ?>" min="1900"
                                        max="<?php echo date('Y'); ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kích thước khung</label>
                                    <input type="text" name="frame_size" class="form-control"
                                        value="<?php echo htmlspecialchars($bike['frame_size'] ?? ''); ?>"
                                        placeholder="VD: M, L, 17 inch">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kích thước bánh xe</label>
                                    <input type="text" name="wheel_size" class="form-control"
                                        value="<?php echo htmlspecialchars($bike['wheel_size'] ?? ''); ?>"
                                        placeholder="VD: 26, 27.5, 29 inch">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Chất liệu khung</label>
                                    <input type="text" name="material" class="form-control"
                                        value="<?php echo htmlspecialchars($bike['material'] ?? ''); ?>"
                                        placeholder="VD: Nhôm, Carbon, Thép">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Trọng lượng (kg)</label>
                                    <input type="number" name="weight" class="form-control" step="0.1"
                                        value="<?php echo $bike['weight']; ?>" placeholder="VD: 12.5">
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <h5 class="mb-3">Địa chỉ</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Thành phố <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control"
                                        value="<?php echo htmlspecialchars($bike['city']); ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Quận/Huyện</label>
                                    <input type="text" name="district" class="form-control"
                                        value="<?php echo htmlspecialchars($bike['district'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-group">
                                    <label>Địa chỉ cụ thể</label>
                                    <input type="text" name="address" class="form-control"
                                        value="<?php echo htmlspecialchars($bike['address'] ?? ''); ?>"
                                        placeholder="Số nhà, tên đường">
                                </div>
                            </div>
                        </div>

                        <!-- Current Images -->
                        <?php if (!empty($images)): ?>
                        <h5 class="mb-3">Hình ảnh hiện tại</h5>
                        <div class="row g-3 mb-4">
                            <?php foreach ($images as $img): ?>
                            <div class="col-md-3">
                                <div class="position-relative">
                                    <img src="../../<?php echo htmlspecialchars($img['file_path']); ?>"
                                        class="img-fluid rounded" alt="Bike image">
                                    <?php if ($img['is_primary']): ?>
                                    <span class="badge bg-success position-absolute top-0 start-0 m-2">
                                        Ảnh chính
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i> Để thay đổi ảnh, vui lòng liên hệ admin
                        </small>
                        <?php endif; ?>

                        <hr class="my-4">

                        <!-- Actions -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle"></i> Lưu thay đổi
                            </button>
                            <a href="my-bikes.php" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-x-circle"></i> Hủy bỏ
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>