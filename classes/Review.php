<?php
/**
 * Review Class - Hệ thống đánh giá
 */
class Review {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Create review
     */
    public function create($data) {
        $sql = "INSERT INTO reviews (
                reviewer_id, reviewee_id, order_id,
                rating, comment, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['reviewer_id'],
            $data['reviewee_id'],
            $data['order_id'],
            $data['rating'],
            $data['comment'] ?? null
        ]);
        
        // Update user rating
        $this->updateUserRating($data['reviewee_id']);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Get reviews for user
     */
    public function getByUser($userId, $limit = 10) {
        $sql = "SELECT r.*, 
                u.full_name as reviewer_name,
                u.avatar as reviewer_avatar
                FROM reviews r
                LEFT JOIN users u ON r.reviewer_id = u.id
                WHERE r.reviewee_id = ?
                ORDER BY r.created_at DESC
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update user rating
     */
    private function updateUserRating($userId) {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total
                FROM reviews WHERE reviewee_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        $sql = "UPDATE users SET rating = ?, total_reviews = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$result['avg_rating'], $result['total'], $userId]);
    }
}
?>
