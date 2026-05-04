<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();
requireRole('seller');

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// Get all conversations where seller is involved
$stmt = $db->prepare("
    SELECT 
        DISTINCT
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END as other_user_id,
        (SELECT full_name FROM users WHERE id = 
            CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
        ) as other_user_name,
        (SELECT avatar FROM users WHERE id = 
            CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
        ) as other_user_avatar,
        (SELECT message FROM messages 
         WHERE (sender_id = ? AND receiver_id = other_user_id) 
            OR (sender_id = other_user_id AND receiver_id = ?)
         ORDER BY created_at DESC LIMIT 1
        ) as last_message,
        (SELECT created_at FROM messages 
         WHERE (sender_id = ? AND receiver_id = other_user_id) 
            OR (sender_id = other_user_id AND receiver_id = ?)
         ORDER BY created_at DESC LIMIT 1
        ) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE receiver_id = ? AND sender_id = other_user_id AND is_read = FALSE
        ) as unread_count,
        (SELECT bike_id FROM messages 
         WHERE (sender_id = ? AND receiver_id = other_user_id) 
            OR (sender_id = other_user_id AND receiver_id = ?)
         ORDER BY created_at DESC LIMIT 1
        ) as bike_id
    FROM messages m
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY last_message_time DESC
");
$stmt->execute([
    $userId, $userId, $userId, $userId, $userId, 
    $userId, $userId, $userId, $userId, $userId,
    $userId, $userId
]);
$conversations = $stmt->fetchAll();

// Get bike titles for conversations
foreach ($conversations as &$conv) {
    if ($conv['bike_id']) {
        $stmt = $db->prepare("SELECT title FROM bikes WHERE id = ?");
        $stmt->execute([$conv['bike_id']]);
        $bike = $stmt->fetch();
        $conv['bike_title'] = $bike['title'] ?? null;
    }
}
unset($conv);

// Get total unread count
$stmt = $db->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = FALSE");
$stmt->execute([$userId]);
$totalUnread = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin nhắn - Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
    <style>
        .conversation-card {
            background: var(--dark-2);
            border: 1px solid var(--dark-3);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .conversation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            border-color: var(--primary);
        }
        .conversation-card.unread {
            border-color: var(--primary);
            background: rgba(16, 185, 129, 0.05);
        }
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }
        .unread-badge {
            background: var(--primary);
            color: #fff;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
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
                <a href="../auth/logout.php" class="btn btn-danger btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2>
                    <i class="bi bi-chat-dots"></i> Tin nhắn
                    <?php if ($totalUnread > 0): ?>
                    <span class="badge bg-danger"><?php echo $totalUnread; ?></span>
                    <?php endif; ?>
                </h2>
                <p class="text-muted mb-0">Quản lý tin nhắn từ khách hàng</p>
            </div>
        </div>

        <?php if (empty($conversations)): ?>
        <div class="text-center py-5">
            <i class="bi bi-chat-dots text-muted" style="font-size: 5rem; opacity: 0.5;"></i>
            <h3 class="mt-3">Chưa có tin nhắn nào</h3>
            <p class="text-muted">Khách hàng sẽ nhắn tin khi quan tâm đến sản phẩm của bạn</p>
            <a href="my-bikes.php" class="btn btn-success mt-3">
                <i class="bi bi-bicycle"></i> Xem tin đăng
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($conversations as $conv): ?>
            <div class="col-12">
                <a href="../chat/chat.php?seller_id=<?php echo $userId; ?>&buyer_id=<?php echo $conv['other_user_id']; ?><?php echo $conv['bike_id'] ? '&bike_id=' . $conv['bike_id'] : ''; ?>" 
                   class="text-decoration-none">
                    <div class="conversation-card <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <img src="../../<?php echo htmlspecialchars($conv['other_user_avatar'] ?? 'assets/images/default-avatar.png'); ?>" 
                                     class="user-avatar" 
                                     alt="User">
                            </div>
                            <div class="col">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0 text-white">
                                        <?php echo htmlspecialchars($conv['other_user_name']); ?>
                                        <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="unread-badge ms-2"><?php echo $conv['unread_count']; ?> mới</span>
                                        <?php endif; ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('H:i - d/m', strtotime($conv['last_message_time'])); ?>
                                    </small>
                                </div>
                                <p class="text-muted mb-0 small">
                                    <?php echo htmlspecialchars(substr($conv['last_message'], 0, 80)) . (strlen($conv['last_message']) > 80 ? '...' : ''); ?>
                                </p>
                                <?php if ($conv['bike_title']): ?>
                                <small class="text-success">
                                    <i class="bi bi-bicycle"></i> <?php echo htmlspecialchars($conv['bike_title']); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-chevron-right text-muted"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
