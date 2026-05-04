<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
requireLogin();

$message = new Message();
$conversations = $message->getConversations(getUserId());
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Tin nhắn - BikeMarket</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;700;900&display=swap" rel="stylesheet">
</head>
<body style="font-family: 'Space Grotesk'; background: #0a0a0a; color: #fff; margin: 0;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
        <h1 style="font-size: 2.5rem; font-weight: 900; margin-bottom: 2rem;">💬 Tin nhắn</h1>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem; background: rgba(23,23,23,0.5); border-radius: 1rem; overflow: hidden; height: 600px;">
            
            <!-- Conversations List -->
            <div style="border-right: 1px solid #404040; overflow-y: auto;">
                <div style="padding: 1rem; border-bottom: 1px solid #404040;">
                    <h3>Cuộc hội thoại</h3>
                </div>
                
                <?php foreach($conversations as $conv): ?>
                <div onclick="location.href='conversation.php?user_id=<?php echo $conv['partner_id']; ?>'" 
                     style="padding: 1rem; border-bottom: 1px solid #262626; cursor: pointer; transition: background 0.2s; <?php echo $conv['is_unread'] ? 'background: rgba(16,185,129,0.1);' : ''; ?>"
                     onmouseover="this.style.background='rgba(16,185,129,0.05)'" 
                     onmouseout="this.style.background='<?php echo $conv['is_unread'] ? 'rgba(16,185,129,0.1)' : 'transparent'; ?>'">
                    
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #10b981, #34d399); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.25rem;">
                            <?php echo strtoupper(substr($conv['partner_name'], 0, 1)); ?>
                        </div>
                        
                        <div style="flex: 1; overflow: hidden;">
                            <h4 style="margin-bottom: 0.25rem; font-weight: 700;">
                                <?php echo htmlspecialchars($conv['partner_name']); ?>
                                <?php if($conv['is_unread']): ?>
                                <span style="display: inline-block; width: 8px; height: 8px; background: #10b981; border-radius: 50%; margin-left: 0.5rem;"></span>
                                <?php endif; ?>
                            </h4>
                            <p style="color: #737373; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo htmlspecialchars(substr($conv['last_message'], 0, 50)); ?>...
                            </p>
                            <p style="color: #737373; font-size: 0.75rem; margin-top: 0.25rem;">
                                <?php echo date('d/m/Y H:i', strtotime($conv['last_message_time'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($conversations)): ?>
                <div style="padding: 3rem; text-align: center; color: #737373;">
                    <p>Chưa có tin nhắn nào</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Empty State -->
            <div style="display: flex; align-items: center; justify-content: center; color: #737373;">
                <div style="text-align: center;">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 1rem;">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <p>Chọn một cuộc hội thoại để bắt đầu chat</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
