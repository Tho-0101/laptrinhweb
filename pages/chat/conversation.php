<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$partnerId = $_GET['user_id'] ?? 0;
$message = new Message();

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message->send(getUserId(), $partnerId, $_POST['message']);
    header('Location: conversation.php?user_id=' . $partnerId);
    exit;
}

// Get conversation
$messages = $message->getConversation(getUserId(), $partnerId);
$message->markAsRead(getUserId(), $partnerId);

// Get partner info
$user = new User();
$partner = $user->getById($partnerId);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chat với <?php echo htmlspecialchars($partner['full_name']); ?> - BikeMarket</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;700;900&display=swap" rel="stylesheet">
</head>
<body style="font-family: 'Space Grotesk'; background: #0a0a0a; color: #fff; margin: 0;">
    <div class="container" style="max-width: 900px; margin: 0 auto; padding: 2rem;">
        <a href="inbox.php" style="color: #10b981; text-decoration: none; margin-bottom: 1rem; display: inline-block;">← Quay lại inbox</a>

        <div style="background: rgba(23,23,23,0.5); border-radius: 1rem; overflow: hidden;">
            
            <!-- Header -->
            <div style="padding: 1rem; border-bottom: 1px solid #404040; display: flex; align-items: center; gap: 1rem;">
                <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #34d399); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.25rem;">
                    <?php echo strtoupper(substr($partner['full_name'], 0, 1)); ?>
                </div>
                <div>
                    <h3 style="margin: 0;"><?php echo htmlspecialchars($partner['full_name']); ?></h3>
                    <p style="color: #737373; font-size: 0.875rem; margin: 0;">
                        <?php echo $partner['role']; ?>
                        <?php if($partner['rating']): ?>
                        | ⭐ <?php echo number_format($partner['rating'], 1); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Messages -->
            <div id="messages" style="height: 400px; overflow-y: auto; padding: 1rem;">
                <?php foreach($messages as $msg): ?>
                <div style="margin-bottom: 1rem; display: flex; justify-content: <?php echo $msg['sender_id'] == getUserId() ? 'flex-end' : 'flex-start'; ?>;">
                    <div style="max-width: 70%; padding: 0.75rem 1rem; border-radius: 1rem; background: <?php echo $msg['sender_id'] == getUserId() ? 'linear-gradient(135deg, #10b981, #34d399)' : 'rgba(23,23,23,0.8)'; ?>; color: <?php echo $msg['sender_id'] == getUserId() ? '#0a0a0a' : '#fff'; ?>;">
                        <p style="margin: 0;"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                        <p style="font-size: 0.75rem; margin: 0.25rem 0 0; opacity: 0.7;">
                            <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Send Form -->
            <form method="POST" style="padding: 1rem; border-top: 1px solid #404040; display: flex; gap: 1rem;">
                <input type="text" name="message" required placeholder="Nhập tin nhắn..." 
                       style="flex: 1; padding: 0.75rem 1rem; background: #171717; border: 1px solid #404040; border-radius: 0.75rem; color: #fff;">
                <button type="submit" style="padding: 0.75rem 1.5rem; background: linear-gradient(135deg, #10b981, #34d399); color: #0a0a0a; font-weight: 700; border: none; border-radius: 0.75rem; cursor: pointer;">
                    Gửi
                </button>
            </form>
        </div>
    </div>

    <script>
        // Auto scroll to bottom
        document.getElementById('messages').scrollTop = document.getElementById('messages').scrollHeight;
        
        // Auto refresh every 3 seconds
        setInterval(() => {
            location.reload();
        }, 3000);
    </script>
</body>
</html>
