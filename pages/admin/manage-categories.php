<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_category') {
        $name = $_POST['name'];
        $slug = strtolower(str_replace(' ', '-', $name));
        $stmt = $db->prepare("INSERT INTO categories (name, slug, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $slug]);
    } elseif ($action === 'add_brand') {
        $name = $_POST['name'];
        $slug = strtolower(str_replace(' ', '-', $name));
        $stmt = $db->prepare("INSERT INTO brands (name, slug, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$name, $slug]);
    }
    
    header('Location: manage-categories.php?success=added');
    exit;
}

// Get categories
$categories = $db->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get brands
$brands = $db->query("SELECT * FROM brands ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Categories & Brands - Admin</title>
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
        <h2 class="mb-4"><i class="bi bi-tags"></i> Quản lý Categories & Brands</h2>

        <div class="row">
            <!-- Categories -->
            <div class="col-md-6">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4">Categories (<?php echo count($categories); ?>)</h5>
                    
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="add_category">
                        <div class="input-group">
                            <input type="text" name="name" class="form-control bg-dark text-white" 
                                   placeholder="Tên danh mục mới" required>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus"></i> Thêm
                            </button>
                        </div>
                    </form>
                    
                    <div class="list-group">
                        <?php foreach ($categories as $cat): ?>
                        <div class="list-group-item bg-dark text-white border-secondary">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Brands -->
            <div class="col-md-6">
                <div class="bg-dark-2-custom p-4 rounded border-dark-custom">
                    <h5 class="mb-4">Brands (<?php echo count($brands); ?>)</h5>
                    
                    <form method="POST" class="mb-4">
                        <input type="hidden" name="action" value="add_brand">
                        <div class="input-group">
                            <input type="text" name="name" class="form-control bg-dark text-white" 
                                   placeholder="Tên thương hiệu mới" required>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-plus"></i> Thêm
                            </button>
                        </div>
                    </form>
                    
                    <div class="list-group">
                        <?php foreach ($brands as $brand): ?>
                        <div class="list-group-item bg-dark text-white border-secondary">
                            <?php echo htmlspecialchars($brand['name']); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
