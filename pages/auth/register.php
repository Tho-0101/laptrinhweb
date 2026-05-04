<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

if (isLoggedIn()) {
    header('Location: /index.php');
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception('Mật khẩu xác nhận không khớp');
        }

        $user = new User();
        $userId = $user->register([
            'full_name' => $_POST['full_name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'phone' => $_POST['phone'],
            'role' => $_POST['role'] ?? ROLE_BUYER
        ]);

        $success = true;

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - BikeMarket</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;900&display=swap"
        rel="stylesheet">
    <link href="../../assets/css/auth.css" rel="stylesheet">
</head>

<body>
    <div class="register-container">
        <h2> Đăng ký tài khoản</h2>

        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">
                ✅ Đăng ký thành công!
                <a href="login.php" style="color: #10b981; text-decoration: underline;">Đăng nhập ngay</a>
            </div>
        <?php else: ?>

            <form method="POST">
                <div class="form-group">
                    <label>Họ tên *</label>
                    <input type="text" name="full_name" required>
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="tel" name="phone">
                </div>

                <div class="form-group">
                    <label>Vai trò *</label>
                    <select name="role" required>
                        <option value="buyer">Người mua</option>
                        <option value="seller">Người bán</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Mật khẩu *</label>
                    <input type="password" name="password" required minlength="6">
                </div>

                <div class="form-group">
                    <label>Xác nhận mật khẩu *</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn">Đăng ký</button>
            </form>

        <?php endif; ?>

        <div class="login-link">
            Đã có tài khoản? <a href="login.php">Đăng nhập</a>
        </div>
    </div>
</body>

</html>