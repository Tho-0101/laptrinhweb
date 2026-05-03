<?php
/**
 * User Class - Quản lý người dùng
 * Hỗ trợ 5 roles: guest, buyer, seller, inspector, admin
 */

class User
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Đăng ký user mới
     */
    public function register($data)
    {
        try {
            // Validate email unique
            if ($this->emailExists($data['email'])) {
                throw new Exception('Email đã được sử dụng');
            }

            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

            // Insert user
            $sql = "INSERT INTO users (
                full_name, email, password, phone, role,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $data['full_name'],
                $data['email'],
                $hashedPassword,
                $data['phone'] ?? null,
                $data['role'] ?? ROLE_BUYER
            ]);

            return $this->conn->lastInsertId();

        } catch (Exception $e) {
            error_log("Register error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Đăng nhập
     */
    public function login($email, $password)
    {
        try {
            $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception('Email không tồn tại');
            }

            if (!password_verify($password, $user['password'])) {
                throw new Exception('Mật khẩu không đúng');
            }

            // Update last login
            $this->updateLastLogin($user['id']);

            // Set session
            loginUser($user['id'], $user['role'], $user['full_name'], $user['email']);

            return $user;

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get user by ID
     */
    public function getById($userId)
    {
        $sql = "SELECT 
    id, full_name, email, phone, avatar, role,
    rating, total_sales, total_purchases,
    address, city, district, ward,
    status,
    email_verified,
    verification_token,
    rating_score, total_reviews,
    created_at, updated_at, last_login
FROM users WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Get user profile with stats
     */
    public function getProfile($userId)
    {
        $user = $this->getById($userId);

        if ($user) {
            // Get additional stats
            $user['stats'] = $this->getUserStats($userId);
        }

        return $user;
    }

    /**
     * Get user stats
     */
    public function getUserStats($userId)
    {
        $stats = [
            'total_bikes' => 0,
            'total_orders' => 0,
            'total_reviews' => 0,
            'wishlist_count' => 0,
            'unread_messages' => 0
        ];

        // Total bikes (for seller)
        $sql = "SELECT COUNT(*) as count FROM bikes WHERE seller_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $stats['total_bikes'] = $stmt->fetchColumn();

        // Total orders (for buyer)
        $sql = "SELECT COUNT(*) as count FROM orders WHERE buyer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $stats['total_orders'] = $stmt->fetchColumn();

        // Wishlist count
        $sql = "SELECT COUNT(*) as count FROM favorites WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $stats['wishlist_count'] = $stmt->fetchColumn();

        // Unread messages
        $sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND read_at IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $stats['unread_messages'] = $stmt->fetchColumn();

        return $stats;
    }

    /**
     * Update profile
     */
    public function updateProfile($userId, $data)
    {
        try {
            $fields = [];
            $params = [];

            if (isset($data['full_name'])) {
                $fields[] = "full_name = ?";
                $params[] = $data['full_name'];
            }

            if (isset($data['phone'])) {
                $fields[] = "phone = ?";
                $params[] = $data['phone'];
            }

            if (isset($data['avatar'])) {
                $fields[] = "avatar = ?";
                $params[] = $data['avatar'];
            }

            if (isset($data['address'])) {
                $fields[] = "address = ?";
                $params[] = $data['address'];
            }

            $params[] = $userId;

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute($params);

        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Change password
     */
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        try {
            $user = $this->getById($userId);

            if (!password_verify($oldPassword, $user['password'])) {
                throw new Exception('Mật khẩu cũ không đúng');
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

            $sql = "UPDATE users SET password = ? WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$hashedPassword, $userId]);

        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update rating
     */
    public function updateRating($userId)
    {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
                FROM reviews WHERE reviewee_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        $sql = "UPDATE users SET 
                rating = ?,
                total_reviews = ?
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([
            $result['avg_rating'] ?? 0,
            $result['total_reviews'] ?? 0,
            $userId
        ]);
    }

    /**
     * Check if email exists
     */
    public function emailExists($email)
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Update last login
     */
    private function updateLastLogin($userId)
    {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Get all users (for admin)
     */
    public function getAllUsers($filters = [], $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = "role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(full_name LIKE ? OR email LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        $sql = "SELECT * FROM users WHERE " . implode(' AND ', $where) . "
                ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Get total count
        $sqlCount = "SELECT COUNT(*) FROM users WHERE " . implode(' AND ', $where);
        $stmtCount = $this->conn->prepare($sqlCount);
        $stmtCount->execute(array_slice($params, 0, -2));
        $total = $stmtCount->fetchColumn();

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Update user status (for admin)
     */
    public function updateStatus($userId, $status)
    {
        $sql = "UPDATE users SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $userId]);
    }

    /**
     * Delete user (for admin)
     */
    public function delete($userId)
    {
        $sql = "UPDATE users SET status = 'deleted', deleted_at = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$userId]);
    }
}
?>