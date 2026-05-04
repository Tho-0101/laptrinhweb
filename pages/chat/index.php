<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$db = Database::getInstance()->getConnection();
$userId = getUserId();

// Get all conversations for current user
$stmt = $db->prepare("
    SELECT 
        m.*,
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END as other_user_id,
        other.full_name as other_user_name,
        other.avatar as other_user_avatar,
        b.title as bike_title,
        b.main_image as bike_image,
        (SELECT COUNT(*) FROM messages 
         WHERE receiver_id = ? 
           AND sender_id = other_user_id
           AND is_read = FALSE) as unread_count
    FROM messages m
    LEFT JOIN users other ON (
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id
        END = other.id
    )
    LEFT JOIN bikes b ON m.bike_id = b.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY other_user_id
    ORDER BY m.created_at DESC
");
$stmt->execute([$userId, $userId, $userId, $userId, $userId]);
$conversations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin nhắn - BikeMarket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/style.css?v=4.0" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="../../index.php">BIKE<span class="text-success">MARKET</span></a>
            <div class="d-flex gap-2">
                <a href="../buyer/dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h2 class="mb-4"><i class="bi bi-chat-dots"></i> Tin nhắn</h2>

        <?php if (empty($conversations)): ?>
        <div class="text-center py-5">
            <i class="bi bi-chat-dots text-muted" style="font-size: 5rem;"></i>
            <h3 class="mt-3">Chưa có tin nhắn nào</h3>
            <p class="text-muted">Hãy bắt đầu trò chuyện với người bán!</p>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($conversations as $conv): ?>
            <div class="col-12 mb-3">
                <a href="chat.php?seller_id=<?php echo $conv['other_user_id']; ?><?php echo $conv['bike_id'] ? '&bike_id=' . $conv['bike_id'] : ''; ?>" 
                   class="text-decoration-none">
                    <div class="bg-dark-2-custom p-3 rounded border-dark-custom">
                        <div class="d-flex align-items-center gap-3">
                            <img src="../../<?php echo htmlspecialchars($conv['other_user_avatar'] ?? 'assets/images/default-avatar.png'); ?>" 
                                 style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;" 
                                 alt="User">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($conv['other_user_name']); ?></h6>
                                <p class="text-muted mb-0 small">
                                    <?php echo htmlspecialchars(substr($conv['message'], 0, 50)) . (strlen($conv['message']) > 50 ? '...' : ''); ?>
                                </p>
                                <?php if ($conv['bike_title']): ?>
                                <small class="text-success">
                                    <i class="bi bi-bicycle"></i> <?php echo htmlspecialchars($conv['bike_title']); ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <small class="text-muted">
                                    <?php echo date('H:i d/m', strtotime($conv['created_at'])); ?>
                                </small>
                                <?php if ($conv['unread_count'] > 0): ?>
                                <br>
                                <span class="badge bg-success mt-1"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
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
