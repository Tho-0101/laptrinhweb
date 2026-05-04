<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// Get parameters
$sellerId = $_GET['seller_id'] ?? null;
$buyerId = $_GET['buyer_id'] ?? null;
$bikeId = $_GET['bike_id'] ?? null;

// Determine if current user is seller or buyer
$userRole = getUserRole();
$isSeller = ($userRole === 'seller');

// If seller, get buyer info; if buyer, get seller info
if ($isSeller && $buyerId) {
    // Seller viewing chat with buyer
    $otherUserId = $buyerId;
    $stmt = $db->prepare("SELECT id, full_name, avatar, phone FROM users WHERE id = ?");
    $stmt->execute([$buyerId]);
    $otherUser = $stmt->fetch();
    $otherUserLabel = 'Khách hàng';
} else if ($sellerId) {
    // Buyer viewing chat with seller
    $otherUserId = $sellerId;
    $stmt = $db->prepare("SELECT id, full_name, avatar, phone FROM users WHERE id = ? AND role = 'seller'");
    $stmt->execute([$sellerId]);
    $otherUser = $stmt->fetch();
    $otherUserLabel = 'Người bán';
} else {
    header('Location: ../../index.php');
    exit;
}

if (!$otherUser) {
    header('Location: ../../index.php');
    exit;
}

// Get bike info if specified
$bike = null;
if ($bikeId) {
    $stmt = $db->prepare("
    SELECT 
        b.id,
        b.title,
        b.price,
        bi.file_path AS main_image
    FROM bikes b
    LEFT JOIN bike_images bi 
        ON bi.bike_id = b.id AND bi.is_primary = 1
    WHERE b.id = ?
");
    $stmt->execute([$bikeId]);
    $bike = $stmt->fetch();
}

// Get conversation messages
$stmt = $db->prepare("
    SELECT m.*, 
           sender.full_name as sender_name,
           sender.avatar as sender_avatar
    FROM messages m
    LEFT JOIN users sender ON m.sender_id = sender.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?)
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$userId, $otherUserId, $otherUserId, $userId]);
$messages = $stmt->fetchAll();

// Mark messages as read
$stmt = $db->prepare("
    UPDATE messages 
    SET is_read = TRUE 
    WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE
");
$stmt->execute([$userId, $otherUserId]);

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message = trim($_POST['message']);

    $stmt = $db->prepare("
        INSERT INTO messages (sender_id, receiver_id, message, bike_id, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $otherUserId, $message, $bikeId]);

    // Redirect to refresh
    $redirectUrl = 'chat.php?';
    if ($isSeller && $buyerId) {
        $redirectUrl .= 'seller_id=' . $userId . '&buyer_id=' . $buyerId;
    } else {
        $redirectUrl .= 'seller_id=' . $sellerId;
    }
    if ($bikeId) {
        $redirectUrl .= '&bike_id=' . $bikeId;
    }
    header('Location: ' . $redirectUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat với <?php echo htmlspecialchars($otherUser['full_name']); ?> - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
    <style>
        .chat-container {
            max-width: 900px;
            margin: 0 auto;
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: 12px 12px 0 0;
            padding: 1.25rem;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            background: var(--dark);
            border-left: 1px solid var(--dark-3);
            border-right: 1px solid var(--dark-3);
        }

        .chat-input {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: 0 0 12px 12px;
            padding: 1.25rem;
        }

        .message {
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
        }

        .message.sent {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .message-content {
            max-width: 60%;
        }

        .message.sent .message-content {
            text-align: right;
        }

        .message-bubble {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            display: inline-block;
            word-wrap: break-word;
        }

        .message.received .message-bubble {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
        }

        .message.sent .message-bubble {
            background: var(--primary);
            color: #fff;
        }

        .message-time {
            font-size: 0.75rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        .bike-reference {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .bike-reference img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="../<?php echo $isSeller ? 'seller' : 'buyer'; ?>/dashboard.php"
                    class="btn btn-outline-light btn-sm">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a href="../auth/logout.php" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="chat-container">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <img src="../../<?php echo htmlspecialchars($otherUser['avatar'] ?? 'assets/images/default-avatar.png'); ?>"
                            class="message-avatar" alt="<?php echo $otherUserLabel; ?>">
                        <div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($otherUser['full_name']); ?></h5>
                            <small class="text-muted">
                                <i class="bi bi-telephone"></i>
                                <?php echo htmlspecialchars($otherUser['phone'] ?? 'Chưa cập nhật'); ?>
                            </small>
                        </div>
                    </div>
                    <a href="javascript:history.back()" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-arrow-left"></i> Quay lại
                    </a>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="chat-messages" id="chatMessages">
                <?php if ($bike): ?>
                    <div class="bike-reference">
                        <img src="../../<?php echo htmlspecialchars($bike['main_image'] ?? 'assets/images/placeholder.jpg'); ?>"
                            alt="Bike">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($bike['title']); ?></h6>
                            <p class="text-success mb-0 fw-bold"><?php echo number_format($bike['price']); ?>₫</p>
                        </div>
                        <a href="../bikes/detail.php?id=<?php echo $bike['id']; ?>" class="btn btn-sm btn-outline-success">
                            Xem chi tiết
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (empty($messages)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-chat-dots text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3">Chưa có tin nhắn nào. Hãy bắt đầu cuộc trò chuyện!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message <?php echo $msg['sender_id'] == $userId ? 'sent' : 'received'; ?>">
                            <img src="../../<?php echo htmlspecialchars($msg['sender_avatar'] ?? 'assets/images/default-avatar.png'); ?>"
                                class="message-avatar" alt="Avatar">
                            <div class="message-content">
                                <div class="message-bubble">
                                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                </div>
                                <div class="message-time">
                                    <?php echo date('H:i - d/m/Y', strtotime($msg['created_at'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Chat Input -->
            <div class="chat-input">
                <form method="POST" class="d-flex gap-2">
                    <input type="text" name="message" class="form-control" placeholder="Nhập tin nhắn..." required
                        autofocus>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send"></i> Gửi
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto scroll to bottom
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Manual refresh button (no auto-reload)
        // User can press F5 or click refresh button to see new messages
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>