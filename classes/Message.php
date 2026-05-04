<?php
/**
 * Message Class - Hệ thống chat
 */
class Message
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Send message
     */
    public function send($senderId, $receiverId, $message, $bikeId = null)
    {
        $sql = "INSERT INTO messages (sender_id, receiver_id, bike_id, message, created_at)
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$senderId, $receiverId, $bikeId, $message]);
        return $this->conn->lastInsertId();
    }

    /**
     * Get conversation
     */
    public function getConversation($user1Id, $user2Id, $limit = 50)
    {
        $sql = "SELECT m.*, 
                sender.full_name as sender_name,
                sender.avatar as sender_avatar
                FROM messages m
                LEFT JOIN users sender ON m.sender_id = sender.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?)
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at DESC
                LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$user1Id, $user2Id, $user2Id, $user1Id, $limit]);
        return array_reverse($stmt->fetchAll());
    }

    /**
     * Get conversations list
     */
    public function getConversations($userId)
    {
        $sql = "SELECT 
    CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END as partner_id,
    u.full_name as partner_name,
    u.avatar as partner_avatar,
    m.message as last_message,
    m.created_at as last_message_time,
    (m.read_at IS NULL AND m.receiver_id = ?) as is_unread
FROM messages m
INNER JOIN (
    SELECT 
        CASE 
            WHEN sender_id = ? THEN receiver_id 
            ELSE sender_id 
        END as partner,
        MAX(created_at) as max_time
    FROM messages
    WHERE sender_id = ? OR receiver_id = ?
    GROUP BY partner
) latest 
ON (
    (m.sender_id = ? AND m.receiver_id = latest.partner) OR
    (m.receiver_id = ? AND m.sender_id = latest.partner)
)
AND m.created_at = latest.max_time
LEFT JOIN users u 
ON u.id = (
    CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END
)
ORDER BY m.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $userId, // 1
            $userId, // 2
            $userId, // 3
            $userId, // 4
            $userId, // 5
            $userId, // 6
            $userId, // 7
            $userId  // 8
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Mark as read
     */
    public function markAsRead($userId, $senderId)
    {
        $sql = "UPDATE messages SET read_at = NOW() 
                WHERE receiver_id = ? AND sender_id = ? AND read_at IS NULL";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$userId, $senderId]);
    }

    /**
     * Get unread count
     */
    public function getUnreadCount($userId)
    {
        $sql = "SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND read_at IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
}
?>