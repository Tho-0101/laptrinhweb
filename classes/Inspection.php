<?php
/**
 * Inspection Class - Hệ thống kiểm định
 */
class Inspection {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Create inspection request
     */
    public function create($bikeId, $inspectorId) {
        $sql = "INSERT INTO inspections (bike_id, inspector_id, status, created_at)
                VALUES (?, ?, 'pending', NOW())";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$bikeId, $inspectorId]);
        return $this->conn->lastInsertId();
    }
    
    /**
     * Submit inspection report
     */
    public function submit($inspectionId, $data) {
        $sql = "UPDATE inspections SET
                frame_condition = ?,
                brake_condition = ?,
                drivetrain_condition = ?,
                wheel_condition = ?,
                overall_rating = ?,
                notes = ?,
                status = 'completed',
                completed_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $result = $stmt->execute([
            $data['frame_condition'],
            $data['brake_condition'],
            $data['drivetrain_condition'],
            $data['wheel_condition'],
            $data['overall_rating'],
            $data['notes'] ?? null,
            $inspectionId
        ]);
        
        // Mark bike as inspected
        $inspection = $this->getById($inspectionId);
        $this->markBikeInspected($inspection['bike_id']);
        
        return $result;
    }
    
    /**
     * Get inspection by ID
     */
    public function getById($inspectionId) {
        $sql = "SELECT i.*, b.title as bike_title, u.full_name as inspector_name
                FROM inspections i
                LEFT JOIN bikes b ON i.bike_id = b.id
                LEFT JOIN users u ON i.inspector_id = u.id
                WHERE i.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$inspectionId]);
        return $stmt->fetch();
    }
    
    /**
     * Get inspections by inspector
     */
    public function getByInspector($inspectorId, $status = null) {
        $where = ['i.inspector_id = ?'];
        $params = [$inspectorId];
        
        if ($status) {
            $where[] = "i.status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT i.*, b.title as bike_title, u.full_name as seller_name
                FROM inspections i
                LEFT JOIN bikes b ON i.bike_id = b.id
                LEFT JOIN users u ON b.seller_id = u.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY i.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Mark bike as inspected
     */
    private function markBikeInspected($bikeId) {
        $sql = "UPDATE bikes SET is_inspected = 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$bikeId]);
    }
}
?>
