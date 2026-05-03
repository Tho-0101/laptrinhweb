<?php
/**
 * Bike Class - Quản lý xe đạp
 */

class Bike {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }
    
    /**
     * Tạo tin đăng xe mới
     */
    public function create($data) {
        try {
            $this->conn->beginTransaction();
            
            $sql = "INSERT INTO bikes (
                seller_id, title, description, price, original_price,
                category_id, brand_id, frame_size, wheel_size, material,
                condition_status, year_of_manufacture, weight,
                city, district, address,
                status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                'pending', NOW()
            )";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['seller_id'],
                $data['title'],
                $data['description'],
                $data['price'],
                $data['original_price'] ?? null,
                $data['category_id'],
                $data['brand_id'] ?? null,
                $data['frame_size'] ?? null,
                $data['wheel_size'] ?? null,
                $data['material'] ?? null,
                $data['condition_status'],
                $data['year_of_manufacture'] ?? null,
                $data['weight'] ?? null,
                $data['city'],
                $data['district'],
                $data['address'] ?? null
            ]);
            
            $bikeId = $this->conn->lastInsertId();
            
            if (!empty($data['images'])) {
                $this->addImages($bikeId, $data['images']);
            }
            
            $this->conn->commit();
            return $bikeId;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Create bike error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get bike by ID with all details
     */
    public function getById($bikeId) {
        $sql = "SELECT 
            b.*,
            c.name as category_name,
            br.name as brand_name,
            users.full_name as seller_name,
            users.phone as seller_phone,
            users.avatar as seller_avatar,
            users.rating as seller_rating,
            (SELECT COUNT(*) FROM favorites WHERE bike_id = b.id) as favorite_count,
            (SELECT COUNT(*) FROM bike_images WHERE bike_id = b.id) as image_count
        FROM bikes b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN brands br ON b.brand_id = br.id
        LEFT JOIN users ON b.seller_id = users.id
        WHERE b.id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$bikeId]);
        $bike = $stmt->fetch();
        
        if ($bike) {
            $bike['images'] = $this->getImages($bikeId);
            $bike['inspection'] = $this->getInspection($bikeId);
        }
        
        return $bike;
    }
    
    /**
     * Search bikes with filters - FIXED QUERY
     */
    public function search($filters = [], $page = 1, $perPage = 12, $searchTerm = '') {
        $offset = ($page - 1) * $perPage;
        $where = ['b.status = "approved"'];
        $params = [];
        
        if (!empty($searchTerm)) {
            $where[] = "(b.title LIKE ? OR b.description LIKE ?)";
            $params[] = "%{$searchTerm}%";
            $params[] = "%{$searchTerm}%";
        }
        
        if (!empty($filters['category_id'])) {
            $where[] = "b.category_id = ?";
            $params[] = $filters['category_id'];
        }
        
        if (!empty($filters['brand_id'])) {
            $where[] = "b.brand_id = ?";
            $params[] = $filters['brand_id'];
        }
        
        if (isset($filters['min_price'])) {
            $where[] = "b.price >= ?";
            $params[] = $filters['min_price'];
        }
        if (isset($filters['max_price'])) {
            $where[] = "b.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        if (!empty($filters['condition_status'])) {
            $where[] = "b.condition_status = ?";
            $params[] = $filters['condition_status'];
        }
        
        if (!empty($filters['city'])) {
            $where[] = "b.city = ?";
            $params[] = $filters['city'];
        }
        
        if (isset($filters['is_featured'])) {
            $where[] = "b.is_featured = ?";
            $params[] = $filters['is_featured'];
        }
        
        if (isset($filters['is_inspected'])) {
            $where[] = "b.is_inspected = ?";
            $params[] = $filters['is_inspected'];
        }
        
        $orderBy = "b.created_at DESC";
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'price_asc':
                    $orderBy = "b.price ASC";
                    break;
                case 'price_desc':
                    $orderBy = "b.price DESC";
                    break;
                case 'newest':
                    $orderBy = "b.created_at DESC";
                    break;
                case 'oldest':
                    $orderBy = "b.created_at ASC";
                    break;
            }
        }
        
        $sql = "SELECT 
            b.*,
            c.name as category_name,
            br.name as brand_name,
            users.full_name as seller_name,
            users.rating as seller_rating,
            (SELECT image_url FROM bike_images WHERE bike_id = b.id ORDER BY display_order LIMIT 1) as main_image
        FROM bikes b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN brands br ON b.brand_id = br.id
        LEFT JOIN users ON b.seller_id = users.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY {$orderBy}
        LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $bikes = $stmt->fetchAll();
        
        $sqlCount = "SELECT COUNT(*) FROM bikes b WHERE " . implode(' AND ', $where);
        $stmtCount = $this->conn->prepare($sqlCount);
        $stmtCount->execute(array_slice($params, 0, -2));
        $total = $stmtCount->fetchColumn();
        
        return [
            'bikes' => $bikes,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }
    
    public function getBySeller($sellerId, $status = null) {
        $where = ['seller_id = ?'];
        $params = [$sellerId];
        
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT 
            b.*,
            c.name as category_name,
            (SELECT image_url FROM bike_images WHERE bike_id = b.id ORDER BY display_order LIMIT 1) as main_image,
            (SELECT COUNT(*) FROM orders WHERE bike_id = b.id) as order_count
        FROM bikes b
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY b.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function update($bikeId, $data) {
        try {
            $fields = [];
            $params = [];
            
            $allowedFields = [
                'title', 'description', 'price', 'original_price',
                'category_id', 'brand_id', 'frame_size', 'wheel_size',
                'material', 'condition_status', 'year_of_manufacture',
                'weight', 'city', 'district', 'address'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
            
            $params[] = $bikeId;
            
            $sql = "UPDATE bikes SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);
            
        } catch (Exception $e) {
            error_log("Update bike error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateStatus($bikeId, $status) {
        $sql = "UPDATE bikes SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $bikeId]);
    }
    
    public function delete($bikeId) {
        $sql = "UPDATE bikes SET status = 'deleted', deleted_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$bikeId]);
    }
    
    public function addImages($bikeId, $images) {
        foreach ($images as $index => $image) {
            $sql = "INSERT INTO bike_images (bike_id, image_url, display_order, created_at)
                    VALUES (?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$bikeId, $image, $index + 1]);
        }
    }
    
    public function getImages($bikeId) {
        $sql = "SELECT * FROM bike_images WHERE bike_id = ? ORDER BY display_order";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$bikeId]);
        return $stmt->fetchAll();
    }
    
    private function getInspection($bikeId) {
        $sql = "SELECT i.*, users.full_name as inspector_name
                FROM inspections i
                LEFT JOIN users ON i.inspector_id = users.id
                WHERE i.bike_id = ? AND i.status = 'completed'
                ORDER BY i.completed_at DESC
                LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$bikeId]);
        return $stmt->fetch();
    }
    
    public function incrementViews($bikeId) {
        $sql = "UPDATE bikes SET view_count = view_count + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$bikeId]);
    }
    
    public function toggleFavorite($userId, $bikeId) {
        $sql = "SELECT * FROM favorites WHERE user_id = ? AND bike_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $bikeId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            $sql = "DELETE FROM favorites WHERE user_id = ? AND bike_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $bikeId]);
            return false;
        } else {
            $sql = "INSERT INTO favorites (user_id, bike_id, created_at) VALUES (?, ?, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $bikeId]);
            return true;
        }
    }
    
    public function isFavorited($userId, $bikeId) {
        $sql = "SELECT COUNT(*) FROM favorites WHERE user_id = ? AND bike_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId, $bikeId]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function getFavorites($userId) {
        $sql = "SELECT 
            b.*,
            c.name as category_name,
            (SELECT image_url FROM bike_images WHERE bike_id = b.id ORDER BY display_order LIMIT 1) as main_image
        FROM bikes b
        INNER JOIN favorites f ON b.id = f.bike_id
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE f.user_id = ? AND b.status = 'approved'
        ORDER BY f.created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
?>
