<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('seller');

$db = Database::getInstance()->getConnection();

// Get categories
$stmt = $db->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Get brands
$stmt = $db->query("SELECT * FROM brands ORDER BY name");
$brands = $stmt->fetchAll();

$errors = [];
$success = false;

// Tạo slug
function createSlug($string)
{
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9]+/i', '-', $string);
    return trim($string, '-');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $required = ['title', 'category_id', 'brand_id', 'price', 'description', 'condition_status', 'city', 'district'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Vui lòng điền đầy đủ thông tin bắt buộc";
            break;
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $title = $_POST['title'];
            $slug = createSlug($title) . '-' . time(); // 🔥 FIX slug

            $stmt = $db->prepare("
    INSERT INTO bikes (
        seller_id, category_id, brand_id, title, slug, description, price,
        frame_size, wheel_size, frame_material, year_of_manufacture, weight,
        condition_status, city, district, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
");

            $stmt->execute([
                getUserId(),
                $_POST['category_id'],
                $_POST['brand_id'],
                $_POST['title'],
                createSlug($_POST['title']), // ✅ FIX SLUG
                $_POST['description'],
                $_POST['price'],
                $_POST['frame_size'] ?? null,
                $_POST['wheel_size'] ?? null,
                $_POST['material'] ?? null,
                $_POST['year_of_manufacture'] ?? null,
                $_POST['weight'] ?? null,
                $_POST['condition_status'],
                $_POST['city'],
                $_POST['district']
            ]);

            $bikeId = $db->lastInsertId();

            // Upload ảnh
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $uploadDir = __DIR__ . '/../../assets/uploads/bikes/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);

                $imageCount = 0;
                foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                    if (!empty($tmpName) && $_FILES['images']['error'][$key] === 0) {

                        $extension = pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION);
                        $filename = 'bike_' . $bikeId . '_' . time() . '_' . $imageCount . '.' . $extension;
                        $filepath = $uploadDir . $filename;
                        $dbPath = 'assets/uploads/bikes/' . $filename;

                        if (move_uploaded_file($tmpName, $filepath)) {

                            $isMain = ($imageCount === 0) ? 1 : 0;

                            $stmt = $db->prepare("
                                    INSERT INTO bike_images (bike_id, file_path, file_type, is_primary, display_order)
                                    VALUES (?, ?, 'image', ?, ?)");
                            $stmt->execute([
                                $bikeId,
                                $dbPath,
                                $isMain,
                                $imageCount + 1
                            ]);

                            // 🔥 FIX: DB bạn là image_url (không phải main_image)
                            if ($isMain) {
                                $stmt = $db->prepare("UPDATE bikes SET image_url = ? WHERE id = ?");
                                $stmt->execute([$dbPath, $bikeId]);
                            }

                            $imageCount++;
                        }
                    }
                }
            }

            $db->commit();
            header('Location: my-bikes.php?success=posted');
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Lỗi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng tin bán xe - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=2.0" rel="stylesheet">
    <style>
        .image-preview {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .image-preview img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--border-dark);
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
                <a href="../auth/logout.php" class="btn btn-danger btn-sm">Đăng xuất</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4"><i class="bi bi-plus-circle"></i> Đăng tin bán xe</h2>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo $error; ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="bg-dark-2-custom p-4 rounded">
                    <!-- Hình ảnh -->
                    <h5 class="mb-3"><i class="bi bi-images"></i> Hình ảnh xe</h5>

                    <div class="mb-4">
                        <label class="form-label">Chọn ảnh xe (tối đa 5 ảnh) <span class="text-danger">*</span></label>
                        <input type="file" name="images[]" class="form-control bg-dark text-white" accept="image/*"
                            multiple max="5" onchange="previewImages(event)" required>
                        <small class="text-muted">Ảnh đầu tiên sẽ là ảnh đại diện. Định dạng: JPG, PNG. Tối đa
                            5MB/ảnh.</small>
                        <div id="imagePreview" class="image-preview"></div>
                    </div>

                    <hr class="my-4">

                    <!-- Thông tin cơ bản -->
                    <h5 class="mb-3"><i class="bi bi-info-circle"></i> Thông tin cơ bản</h5>

                    <div class="mb-3">
                        <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control bg-dark text-white"
                            placeholder="VD: Xe đạp Giant TCR Advanced Pro 2023" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Danh mục <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select bg-dark text-white" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Thương hiệu <span class="text-danger">*</span></label>
                            <select name="brand_id" class="form-select bg-dark text-white" required>
                                <option value="">Chọn thương hiệu</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?php echo $brand['id']; ?>">
                                        <?php echo htmlspecialchars($brand['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Giá bán (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" name="price" class="form-control bg-dark text-white"
                            placeholder="VD: 15000000" required>
                        <small class="text-muted">Nhập số tiền không có dấu chấm/phẩy. VD: 15000000 = 15 triệu</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mô tả chi tiết <span class="text-danger">*</span></label>
                        <textarea name="description" rows="6" class="form-control bg-dark text-white"
                            placeholder="Mô tả chi tiết về xe: tình trạng, lịch sử sử dụng, lý do bán..."
                            required></textarea>
                    </div>

                    <hr class="my-4">

                    <!-- Thông số kỹ thuật -->
                    <h5 class="mb-3"><i class="bi bi-gear"></i> Thông số kỹ thuật</h5>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kích thước khung</label>
                            <input type="text" name="frame_size" class="form-control bg-dark text-white"
                                placeholder="VD: 54cm, M, L">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kích thước bánh</label>
                            <input type="text" name="wheel_size" class="form-control bg-dark text-white"
                                placeholder="VD: 700c, 27.5, 29">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label">Chất liệu</label>
                            <input type="text" name="material" class="form-control bg-dark text-white"
                                placeholder="VD: Carbon, Nhôm, Thép">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Năm sản xuất</label>
                            <input type="number" name="year_of_manufacture" class="form-control bg-dark text-white"
                                placeholder="VD: 2023" min="1990" max="2026">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trọng lượng (kg)</label>
                            <input type="number" step="0.1" name="weight" class="form-control bg-dark text-white"
                                placeholder="VD: 8.5">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Tình trạng & Vị trí -->
                    <h5 class="mb-3"><i class="bi bi-geo-alt"></i> Tình trạng & Vị trí</h5>

                    <div class="mb-3">
                        <label class="form-label">Tình trạng <span class="text-danger">*</span></label>
                        <select name="condition_status" class="form-select bg-dark text-white" required>
                            <option value="">Chọn tình trạng</option>
                            <option value="new">Mới 100%</option>
                            <option value="like_new">Như mới (>95%)</option>
                            <option value="good">Tốt (80-95%)</option>
                            <option value="fair">Khá (60-80%)</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Thành phố <span class="text-danger">*</span></label>
                            <input type="text" name="city" class="form-control bg-dark text-white"
                                placeholder="VD: Hà Nội, TP.HCM" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Quận/Huyện <span class="text-danger">*</span></label>
                            <input type="text" name="district" class="form-control bg-dark text-white"
                                placeholder="VD: Cầu Giấy, Quận 1" required>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Submit -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle"></i> Đăng tin
                        </button>
                        <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle"></i>
                        <strong>Lưu ý:</strong> Tin đăng sẽ được kiểm duyệt trước khi hiển thị công khai.
                        Sau khi admin duyệt, xe sẽ xuất hiện tại trang chủ.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function previewImages(event) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';

            const files = event.target.files;
            if (files.length > 5) {
                alert('Chỉ được chọn tối đa 5 ảnh!');
                event.target.value = '';
                return;
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();

                reader.onload = function (e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    if (i === 0) {
                        const badge = document.createElement('div');
                        badge.style.cssText =
                            'position:absolute;top:5px;left:5px;background:#10b981;padding:5px 10px;border-radius:5px;font-size:12px';
                        badge.textContent = 'Ảnh đại diện';
                        const wrapper = document.createElement('div');
                        wrapper.style.position = 'relative';
                        wrapper.appendChild(img);
                        wrapper.appendChild(badge);
                        preview.appendChild(wrapper);
                    } else {
                        preview.appendChild(img);
                    }
                }

                reader.readAsDataURL(file);
            }
        }
    </script>
</body>

</html>