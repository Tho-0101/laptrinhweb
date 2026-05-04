<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('admin');

$db = Database::getInstance()->getConnection();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $newStatus = $_POST['status'] === 'active' ? 'banned' : 'active';
        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $userId]);
    }
    
    header('Location: manage-users.php?success=updated');
    exit;
}

// Filter
$roleFilter = $_GET['role'] ?? 'all';

$query = "SELECT * FROM users WHERE 1=1";
if ($roleFilter !== 'all') {
    $query .= " AND role = :role";
}
$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
if ($roleFilter !== 'all') {
    $stmt->execute([':role' => $roleFilter]);
} else {
    $stmt->execute();
}
$users = $stmt->fetchAll();

// Count by role
$statsQuery = "
    SELECT role, COUNT(*) as count
    FROM users
    GROUP BY role
";
$stmt = $db->query($statsQuery);
$roleCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Users - Admin</title>
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
        <h2 class="mb-4"><i class="bi bi-people"></i> Quản lý Users</h2>

        <!-- Filter Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $roleFilter === 'all' ? 'active' : ''; ?>" href="?role=all">
                    Tất cả (<?php echo array_sum($roleCounts); ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $roleFilter === 'buyer' ? 'active' : ''; ?>" href="?role=buyer">
                    Buyers (<?php echo $roleCounts['buyer'] ?? 0; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $roleFilter === 'seller' ? 'active' : ''; ?>" href="?role=seller">
                    Sellers (<?php echo $roleCounts['seller'] ?? 0; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $roleFilter === 'inspector' ? 'active' : ''; ?>" href="?role=inspector">
                    Inspectors (<?php echo $roleCounts['inspector'] ?? 0; ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $roleFilter === 'admin' ? 'active' : ''; ?>" href="?role=admin">
                    Admins (<?php echo $roleCounts['admin'] ?? 0; ?>)
                </a>
            </li>
        </ul>

        <?php if (!empty($users)): ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Tên</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th>Ngày tạo</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td>
                            <?php
                            $roleColors = [
                                'buyer' => 'info',
                                'seller' => 'warning',
                                'inspector' => 'success',
                                'admin' => 'danger'
                            ];
                            $color = $roleColors[$user['role']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>"><?php echo $user['role']; ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo $user['status']; ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'seller'): ?>
                                ⭐ <?php echo number_format($user['rating'], 1); ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display:inline">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                <button type="submit" class="btn btn-sm btn-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>">
                                    <?php echo $user['status'] === 'active' ? 'Ban' : 'Unban'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
