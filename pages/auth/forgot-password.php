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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $error = 'Vui lòng nhập email';
    } else {
        try {
            $db = Database::getInstance()->getConnection();

            // Check if email exists
            $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store token in database (you'll need to create this table)
                $stmt = $db->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at, created_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE token = ?, expires_at = ?, created_at = NOW()
                ");
                $stmt->execute([$user['id'], $token, $expires, $token, $expires]);

                // In production, send email here
                // For now, just show success message
                $success = 'Đã gửi link đặt lại mật khẩu đến email của bạn. Vui lòng kiểm tra hộp thư.';

                // TODO: Send email with reset link
                // $resetLink = SITE_URL . '/pages/auth/reset-password.php?token=' . $token;

            } else {
                // Don't reveal if email exists or not (security)
                $success = 'Nếu email tồn tại trong hệ thống, link đặt lại mật khẩu đã được gửi.';
            }

        } catch (Exception $e) {
            $error = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - BikeMarket</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;900&display=swap"
        rel="stylesheet">
    <link href="../../assets/css/auth.css" rel="stylesheet">
</head>

<body>

    <div class="login-container">
        <div class="logo">
            <h1>BIKE<span>MARKET</span></h1>
            <p>Nền tảng mua bán xe đạp</p>
        </div>

        <h2>Quên mật khẩu</h2>
        <p class="subtitle">Nhập email của bạn để nhận link đặt lại mật khẩu</p>

        <?php if ($success): ?>
            <div class="success"
                style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                ✓ <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn">
                    Đặt lại mật khẩu
                </button>
            </form>
        <?php else: ?>
            <a href="login.php" class="btn" style="display: inline-block; text-align: center; text-decoration: none;">
                ← Quay lại đăng nhập
            </a>
        <?php endif; ?>

        <div class="divider">
            <span>hoặc</span>
        </div>

        <div class="register-link">
            Đã nhớ mật khẩu?
            <a href="login.php">Đăng nhập</a>
        </div>

        <div class="back-home">
            <a href="../../index.php">← Về trang chủ</a>
        </div>
    </div>

</body>

</html>