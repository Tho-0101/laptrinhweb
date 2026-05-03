<?php
/**
 * Order Class - Quản lý đơn hàng & đặt cọc
 */

class Order
{
    private $db;
    private $conn;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Tạo đơn hàng mới
     */
    public function create($data)
    {
        try {
            $this->conn->beginTransaction();

            $bike = $this->getBikeInfo($data['bike_id']);
            if (!$bike || $bike['status'] !== BIKE_STATUS_APPROVED) {
                throw new Exception('Xe không còn khả dụng');
            }

            $totalAmount = (float) $bike['price'];

            $depositAmount = !empty($data['deposit'])
                ? $totalAmount * (MIN_DEPOSIT_PERCENT / 100)
                : 0;

            $remainingAmount = $totalAmount - $depositAmount;

            $orderCode = 'ORD-' . date('YmdHis') . '-' . strtoupper(substr(uniqid(), -5));

            // Order Type
            $orderType = $depositAmount > 0 ? ORDER_TYPE_DEPOSIT : ORDER_TYPE_FULL_PAYMENT;

            // Delivery Method
            $deliveryMethod = DELIVERY_METHOD_PICKUP;

            // Payment Method
            $paymentMethod = $data['payment_method'] ?? 'cash';

            // Payment Status
            $paymentStatus = PAYMENT_STATUS_UNPAID;
            if ($depositAmount > 0 && $depositAmount < $totalAmount) {
                $paymentStatus = PAYMENT_STATUS_PARTIALLY_PAID;
            } elseif ($depositAmount >= $totalAmount && $depositAmount > 0) {
                $paymentStatus = PAYMENT_STATUS_PAID;
            }

            // Order Status
            $status = ORDER_STATUS_PENDING;

            $sql = "INSERT INTO orders (
            order_code,
            buyer_id,
            seller_id,
            bike_id,
            order_type,
            total_amount,
            deposit_amount,
            remaining_amount,
            delivery_method,
            shipping_address,
            shipping_city,
            shipping_district,
            shipping_ward,
            shipping_phone,
            shipping_note,
            payment_method,
            payment_status,
            status,
            buyer_note,
            created_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
        )";

            $stmt = $this->conn->prepare($sql);

            $stmt->execute([
                $orderCode,
                $data['buyer_id'],
                $bike['seller_id'],
                $data['bike_id'],
                $orderType,
                $totalAmount,
                $depositAmount,
                $remainingAmount,
                $deliveryMethod,
                null,
                null,
                null,
                null,
                null,
                null,
                $paymentMethod,
                $paymentStatus,
                $status,
                null
            ]);

            $orderId = $this->conn->lastInsertId();

            // Update bike status to reserved
            $this->updateBikeStatus($data['bike_id'], BIKE_STATUS_RESERVED);

            // Create notification
            $this->createNotification($bike['seller_id'], $orderId, 'new_order');

            $this->conn->commit();
            return $orderId;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Create order error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get order by ID
     */
    public function getById($orderId)
    {
        $sql = "SELECT 
    o.*,
    b.title as bike_title,
    b.price as bike_price,
    buyer.full_name as buyer_name,
    buyer.phone as buyer_phone,
    buyer.email as buyer_email,
    seller.full_name as seller_name,
    seller.phone as seller_phone,
    seller.email as seller_email,
    (SELECT file_path 
     FROM bike_images 
     WHERE bike_id = o.bike_id AND is_primary = 1 
     LIMIT 1) as bike_image
FROM orders o
LEFT JOIN bikes b ON o.bike_id = b.id
LEFT JOIN users buyer ON o.buyer_id = buyer.id
LEFT JOIN users seller ON o.seller_id = seller.id
WHERE o.id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }

    /**
     * Get orders by buyer
     */
    public function getByBuyer($buyerId, $status = null)
    {
        $where = ['o.buyer_id = ?'];
        $params = [$buyerId];

        if ($status) {
            $where[] = "o.status = ?";
            $params[] = $status;
        }

        $sql = "SELECT 
            o.*,
            b.title as bike_title,
            seller.full_name as seller_name,
            (SELECT image_path FROM bike_images WHERE bike_id = o.bike_id AND is_main = 1 LIMIT 1) as bike_image
        FROM orders o
        LEFT JOIN bikes b ON o.bike_id = b.id
        LEFT JOIN users seller ON o.seller_id = seller.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get orders by seller
     */
    public function getBySeller($sellerId, $status = null)
    {
        $where = ['o.seller_id = ?'];
        $params = [$sellerId];

        if ($status) {
            $where[] = "o.status = ?";
            $params[] = $status;
        }

        $sql = "SELECT 
            o.*,
            b.title as bike_title,
            buyer.full_name as buyer_name,
            buyer.phone as buyer_phone,
            (SELECT image_path FROM bike_images WHERE bike_id = o.bike_id AND is_main = 1 LIMIT 1) as bike_image
        FROM orders o
        LEFT JOIN bikes b ON o.bike_id = b.id
        LEFT JOIN users buyer ON o.buyer_id = buyer.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY o.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update order status
     */
    public function updateStatus($orderId, $status, $note = null)
    {
        try {
            $sql = "UPDATE orders SET 
                    status = ?,
                    updated_at = NOW()";
            $params = [$status];

            if ($status === ORDER_STATUS_COMPLETED) {
                $sql .= ", completed_at = NOW()";
            } elseif ($status === ORDER_STATUS_CANCELLED) {
                $sql .= ", cancelled_at = NOW()";
            }

            if ($note) {
                $sql .= ", admin_note = ?";
                $params[] = $note;
            }

            $sql .= " WHERE id = ?";
            $params[] = $orderId;

            $stmt = $this->conn->prepare($sql);
            $result = $stmt->execute($params);

            // Update bike status based on order status
            $order = $this->getById($orderId);
            if ($status === ORDER_STATUS_COMPLETED) {
                $this->updateBikeStatus($order['bike_id'], BIKE_STATUS_SOLD);
            } elseif ($status === ORDER_STATUS_CANCELLED) {
                $this->updateBikeStatus($order['bike_id'], BIKE_STATUS_APPROVED);
            }

            return $result;

        } catch (Exception $e) {
            error_log("Update order status error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $paymentStatus, $transactionId = null)
    {
        $sql = "UPDATE orders SET 
                payment_status = ?,
                transaction_id = ?,
                paid_at = NOW(),
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$paymentStatus, $transactionId, $orderId]);
    }

    /**
     * Confirm deposit payment
     */
    public function confirmDeposit($orderId)
    {
        try {
            $this->conn->beginTransaction();

            $this->updateStatus($orderId, ORDER_STATUS_DEPOSIT_PAID);
            $this->updatePaymentStatus($orderId, PAYMENT_STATUS_PARTIALLY_PAID);

            $order = $this->getById($orderId);
            $this->createNotification($order['seller_id'], $orderId, 'deposit_received');

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Confirm deposit error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Complete order
     */
    public function complete($orderId)
    {
        try {
            $this->conn->beginTransaction();

            $this->updateStatus($orderId, ORDER_STATUS_COMPLETED);
            $this->updatePaymentStatus($orderId, PAYMENT_STATUS_PAID);

            $order = $this->getById($orderId);

            // Update seller stats
            $this->updateSellerStats($order['seller_id']);

            // Create notifications
            $this->createNotification($order['buyer_id'], $orderId, 'order_completed');
            $this->createNotification($order['seller_id'], $orderId, 'order_completed');

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Complete order error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cancel order
     */
    public function cancel($orderId, $reason = null)
    {
        try {
            $this->conn->beginTransaction();

            $this->updateStatus($orderId, ORDER_STATUS_CANCELLED, $reason);

            $order = $this->getById($orderId);
            $this->createNotification($order['buyer_id'], $orderId, 'order_cancelled');
            $this->createNotification($order['seller_id'], $orderId, 'order_cancelled');

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Cancel order error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get bike info
     */
    private function getBikeInfo($bikeId)
    {
        $sql = "SELECT * FROM bikes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$bikeId]);
        return $stmt->fetch();
    }

    /**
     * Update bike status
     */
    private function updateBikeStatus($bikeId, $status)
    {
        $sql = "UPDATE bikes SET status = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$status, $bikeId]);
    }

    /**
     * Update seller stats
     */
    private function updateSellerStats($sellerId)
    {
        $sql = "UPDATE users SET total_sales = total_sales + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$sellerId]);
    }

    /**
     * Create notification
     */
    private function createNotification($userId, $orderId, $type)
    {
        // Check if notifications table exists
        try {
            $sql = "INSERT INTO notifications (user_id, type, reference_id, created_at)
                    VALUES (?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($sql);
            return $stmt->execute([$userId, $type, $orderId]);
        } catch (Exception $e) {
            // If table doesn't exist, just log and continue
            error_log("Notification not created: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order statistics
     */
    public function getStatistics($filters = [])
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['start_date'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['end_date'];
        }

        $sql = "SELECT 
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            SUM(CASE WHEN status = '" . ORDER_STATUS_COMPLETED . "' THEN 1 ELSE 0 END) as completed_orders,
            SUM(CASE WHEN status = '" . ORDER_STATUS_CANCELLED . "' THEN 1 ELSE 0 END) as cancelled_orders,
            AVG(total_amount) as average_order_value
        FROM orders WHERE " . implode(' AND ', $where);

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}