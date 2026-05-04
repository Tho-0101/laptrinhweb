<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getUserRole();
    header('Location: ../' . $role . '/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user = new User();
        $result = $user->login($_POST['email'], $_POST['password']);

        // Redirect based on role
        $redirectMap = [
            ROLE_BUYER => '../buyer/dashboard.php',
            ROLE_SELLER => '../seller/dashboard.php',
            ROLE_INSPECTOR => '../inspector/dashboard.php',
            ROLE_ADMIN => '../admin/dashboard.php'
        ];

        $redirect = $redirectMap[$result['role']] ?? '../../index.php';
        header('Location: ' . $redirect);
        exit;

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
    <title>Đăng nhập - BikeMarket</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;900&display=swap"
        rel="stylesheet">
    <link href="../../assets/css/auth.css" rel="stylesheet">
    <style>
        .password-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .forgot-password-link {
            color: #10b981;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .forgot-password-link:hover {
            color: #059669;
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="logo">
            <h1>BIKE<span>MARKET</span></h1>
            <p>Nền tảng mua bán xe đạp</p>
        </div>

        <h2>Đăng nhập</h2>
        <p class="subtitle">Chào mừng bạn quay trở lại!</p>

        <?php if ($error): ?>
            <div class="error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="your@email.com"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <div class="password-header">
                    <label for="password">Mật khẩu</label>
                    <a href="forgot-password.php" class="forgot-password-link">Quên mật khẩu?</a>
                </div>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn">
                Đăng nhập
            </button>
        </form>

        <div class="divider">
            <span>hoặc</span>
        </div>

        <div class="register-link">
            Chưa có tài khoản?
            <a href="register.php">Đăng ký ngay</a>
        </div>

        <div class="back-home">
            <a href="../../index.php">Về trang chủ</a>
        </div>
    </div>

</body>

</html>