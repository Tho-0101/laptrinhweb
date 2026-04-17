<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Request;
use App\Response;

final class ListingController
{
    public static function index(): void
    {
        $pdo = Database::connection();

        $status = $_GET['status'] ?? 'published';
        $keyword = isset($_GET['keyword']) ? trim((string)$_GET['keyword']) : null;
        $brandId = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : null;
        $bikeTypeId = isset($_GET['bike_type_id']) ? (int)$_GET['bike_type_id'] : null;
        $district = isset($_GET['district']) ? trim((string)$_GET['district']) : null;
        $min = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
        $max = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
        $province = isset($_GET['province']) ? trim((string)$_GET['province']) : null;
        $sort = isset($_GET['sort']) ? trim((string)$_GET['sort']) : 'newest';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $baseSql = '
            FROM listings l
            INNER JOIN users u ON u.id = l.seller_user_id
            INNER JOIN bike_models bm ON bm.id = l.bike_model_id
            INNER JOIN bike_types bt ON bt.id = bm.bike_type_id
            INNER JOIN brands b ON b.id = bm.brand_id
            LEFT JOIN listing_images li
                ON li.listing_id = l.id AND li.is_primary = 1
            WHERE 1=1
        ';

        $bindings = [];
        if ($status !== '') {
            $baseSql .= ' AND l.status = :status';
            $bindings['status'] = $status;
        }
        if ($keyword !== null && $keyword !== '') {
            $baseSql .= ' AND (l.title LIKE :keyword OR l.description LIKE :keyword OR bm.name LIKE :keyword OR b.name LIKE :keyword)';
            $bindings['keyword'] = '%' . $keyword . '%';
        }
        if ($brandId !== null && $brandId > 0) {
            $baseSql .= ' AND b.id = :brand_id';
            $bindings['brand_id'] = $brandId;
        }
        if ($bikeTypeId !== null && $bikeTypeId > 0) {
            $baseSql .= ' AND bt.id = :bike_type_id';
            $bindings['bike_type_id'] = $bikeTypeId;
        }
        if ($min !== null) {
            $baseSql .= ' AND l.price >= :min_price';
            $bindings['min_price'] = $min;
        }
        if ($max !== null) {
            $baseSql .= ' AND l.price <= :max_price';
            $bindings['max_price'] = $max;
        }
        if ($province !== null && $province !== '') {
            $baseSql .= ' AND l.location_province = :province';
            $bindings['province'] = $province;
        }
        if ($district !== null && $district !== '') {
            $baseSql .= ' AND l.location_district = :district';
            $bindings['district'] = $district;
        }

        $orderSql = match ($sort) {
            'price_asc' => 'ORDER BY l.price ASC',
            'price_desc' => 'ORDER BY l.price DESC',
            'oldest' => 'ORDER BY l.created_at ASC',
            default => 'ORDER BY l.created_at DESC',
        };

        $countStmt = $pdo->prepare('SELECT COUNT(DISTINCT l.id) ' . $baseSql);
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetchColumn();

        $sql = '
            SELECT
                l.id,
                l.title,
                l.price,
                l.status,
                l.condition_level,
                l.location_province,
                l.location_district,
                l.view_count,
                l.created_at,
                u.full_name AS seller_name,
                b.name AS brand_name,
                bt.name AS bike_type_name,
                bm.name AS model_name,
                li.image_url AS primary_image
            ' . $baseSql . '
            GROUP BY l.id
            ' . $orderSql . '
            LIMIT :limit OFFSET :offset
        ';

        $stmt = $pdo->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        Response::json([
            'ok' => true,
            'message' => 'Listings fetched',
            'data' => $rows,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int)max(1, (int)ceil($total / $limit)),
            ],
        ]);
    }

    public static function show(int $id): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('
            SELECT
                l.*,
                u.full_name AS seller_name,
                u.phone AS seller_phone,
                b.name AS brand_name,
                bt.name AS type_name,
                bm.name AS model_name,
                bm.frame_material,
                bm.default_wheel_size
            FROM listings l
            INNER JOIN users u ON u.id = l.seller_user_id
            INNER JOIN bike_models bm ON bm.id = l.bike_model_id
            INNER JOIN brands b ON b.id = bm.brand_id
            INNER JOIN bike_types bt ON bt.id = bm.bike_type_id
            WHERE l.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id]);
        $listing = $stmt->fetch();

        if (!$listing) {
            Response::json(['ok' => false, 'message' => 'Listing not found', 'errors' => null], 404);
            return;
        }

        $specStmt = $pdo->prepare('SELECT * FROM listing_specs WHERE listing_id = :id LIMIT 1');
        $specStmt->execute(['id' => $id]);
        $spec = $specStmt->fetch();

        $imgStmt = $pdo->prepare('SELECT id, image_url, is_primary, sort_order FROM listing_images WHERE listing_id = :id ORDER BY sort_order ASC');
        $imgStmt->execute(['id' => $id]);
        $images = $imgStmt->fetchAll();

        Response::json([
            'ok' => true,
            'message' => 'Listing detail fetched',
            'data' => [
                'listing' => $listing,
                'spec' => $spec,
                'images' => $images,
            ],
            'meta' => null,
        ]);
    }

    public static function store(): void
    {
        $token = Request::bearerToken();
        $userId = Auth::userIdFromToken($token);
        if (!$userId) {
            Response::json(['ok' => false, 'message' => 'Unauthorized', 'errors' => null], 401);
            return;
        }

        $data = Request::jsonBody();
        $required = ['bike_model_id', 'title', 'description', 'condition_level', 'price', 'location_province', 'location_district'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                Response::json(['ok' => false, 'message' => "Missing field: {$field}", 'errors' => [$field => 'required']], 422);
                return;
            }
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('
            INSERT INTO listings (
                seller_user_id, shop_id, bike_model_id, title, description, year_manufactured,
                condition_level, odometer_km, price, negotiable, location_province, location_district,
                status, published_at, created_at, updated_at
            )
            VALUES (
                :seller_user_id, :shop_id, :bike_model_id, :title, :description, :year_manufactured,
                :condition_level, :odometer_km, :price, :negotiable, :location_province, :location_district,
                :status, :published_at, NOW(), NOW()
            )
        ');

        $status = (string)($data['status'] ?? 'published');
        $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;

        $stmt->execute([
            'seller_user_id' => $userId,
            'shop_id' => $data['shop_id'] ?? null,
            'bike_model_id' => (int)$data['bike_model_id'],
            'title' => trim((string)$data['title']),
            'description' => trim((string)$data['description']),
            'year_manufactured' => $data['year_manufactured'] ?? null,
            'condition_level' => (string)$data['condition_level'],
            'odometer_km' => (int)($data['odometer_km'] ?? 0),
            'price' => (float)$data['price'],
            'negotiable' => isset($data['negotiable']) ? (int)(bool)$data['negotiable'] : 1,
            'location_province' => trim((string)$data['location_province']),
            'location_district' => trim((string)$data['location_district']),
            'status' => $status,
            'published_at' => $publishedAt,
        ]);

        $listingId = (int)$pdo->lastInsertId();

        $imageUrls = [];
        if (isset($data['image_url']) && trim((string)$data['image_url']) !== '') {
            $imageUrls[] = trim((string)$data['image_url']);
        }
        if (isset($data['image_urls']) && is_array($data['image_urls'])) {
            foreach ($data['image_urls'] as $url) {
                $value = trim((string)$url);
                if ($value !== '') {
                    $imageUrls[] = $value;
                }
            }
        }
        $imageUrls = array_values(array_unique($imageUrls));
        if (!empty($imageUrls)) {
            $imgStmt = $pdo->prepare('
                INSERT INTO listing_images (listing_id, image_url, is_primary, sort_order, created_at)
                VALUES (:listing_id, :image_url, :is_primary, :sort_order, NOW())
            ');
            foreach ($imageUrls as $index => $url) {
                $imgStmt->execute([
                    'listing_id' => $listingId,
                    'image_url' => $url,
                    'is_primary' => $index === 0 ? 1 : 0,
                    'sort_order' => $index + 1,
                ]);
            }
        }

        $log = $pdo->prepare('
            INSERT INTO listing_status_logs (listing_id, old_status, new_status, changed_by, note, changed_at)
            VALUES (:listing_id, NULL, :new_status, :changed_by, :note, NOW())
        ');
        $log->execute([
            'listing_id' => $listingId,
            'new_status' => $status,
            'changed_by' => $userId,
            'note' => 'Created by API',
        ]);

        Response::json([
            'ok' => true,
            'message' => 'Listing created',
            'data' => ['id' => $listingId],
            'meta' => null,
        ], 201);
    }

    public static function update(int $id): void
    {
        $token = Request::bearerToken();
        $userId = Auth::userIdFromToken($token);
        if (!$userId) {
            Response::json(['ok' => false, 'message' => 'Unauthorized', 'errors' => null], 401);
            return;
        }

        $pdo = Database::connection();
        $listingStmt = $pdo->prepare('SELECT id, seller_user_id, status FROM listings WHERE id = :id LIMIT 1');
        $listingStmt->execute(['id' => $id]);
        $listing = $listingStmt->fetch();
        if (!$listing) {
            Response::json(['ok' => false, 'message' => 'Listing not found', 'errors' => null], 404);
            return;
        }

        $isAdmin = self::isAdmin($pdo, $userId);
        if (!self::canManageListing($pdo, $userId, (int)$listing['seller_user_id'])) {
            Response::json(['ok' => false, 'message' => 'Forbidden', 'errors' => null], 403);
            return;
        }
        if ((string)$listing['status'] === 'sold' && !$isAdmin) {
            Response::json(['ok' => false, 'message' => 'Sold listing cannot be updated', 'errors' => null], 422);
            return;
        }

        $data = Request::jsonBody();
        $updatableFields = [
            'bike_model_id',
            'title',
            'description',
            'year_manufactured',
            'condition_level',
            'odometer_km',
            'price',
            'negotiable',
            'location_province',
            'location_district',
            'status',
            'shop_id',
        ];

        $setParts = [];
        $bindings = ['id' => $id];
        foreach ($updatableFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $setParts[] = "{$field} = :{$field}";
            if ($field === 'negotiable') {
                $bindings[$field] = (int)(bool)$data[$field];
            } else {
                $bindings[$field] = $data[$field];
            }
        }

        if (empty($setParts)) {
            Response::json(['ok' => false, 'message' => 'No fields to update', 'errors' => null], 422);
            return;
        }

        $oldStatus = (string)$listing['status'];
        $newStatus = isset($data['status']) ? (string)$data['status'] : $oldStatus;

        if (array_key_exists('status', $data)) {
            if ($newStatus === 'published') {
                $setParts[] = 'published_at = COALESCE(published_at, NOW())';
            }
            if ($newStatus === 'sold') {
                $setParts[] = 'sold_at = NOW()';
            }
        }

        $sql = 'UPDATE listings SET ' . implode(', ', $setParts) . ', updated_at = NOW() WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($bindings);

        if ($oldStatus !== $newStatus) {
            $log = $pdo->prepare('
                INSERT INTO listing_status_logs (listing_id, old_status, new_status, changed_by, note, changed_at)
                VALUES (:listing_id, :old_status, :new_status, :changed_by, :note, NOW())
            ');
            $log->execute([
                'listing_id' => $id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'changed_by' => $userId,
                'note' => 'Updated by API',
            ]);
        }

        Response::json([
            'ok' => true,
            'message' => 'Listing updated',
            'data' => ['id' => $id],
            'meta' => null,
        ]);
    }

    public static function delete(int $id): void
    {
        $token = Request::bearerToken();
        $userId = Auth::userIdFromToken($token);
        if (!$userId) {
            Response::json(['ok' => false, 'message' => 'Unauthorized', 'errors' => null], 401);
            return;
        }

        $pdo = Database::connection();
        $listingStmt = $pdo->prepare('SELECT id, seller_user_id FROM listings WHERE id = :id LIMIT 1');
        $listingStmt->execute(['id' => $id]);
        $listing = $listingStmt->fetch();
        if (!$listing) {
            Response::json(['ok' => false, 'message' => 'Listing not found', 'errors' => null], 404);
            return;
        }

        $isAdmin = self::isAdmin($pdo, $userId);
        if (!self::canManageListing($pdo, $userId, (int)$listing['seller_user_id'])) {
            Response::json(['ok' => false, 'message' => 'Forbidden', 'errors' => null], 403);
            return;
        }
        $statusStmt = $pdo->prepare('SELECT status FROM listings WHERE id = :id LIMIT 1');
        $statusStmt->execute(['id' => $id]);
        $statusRow = $statusStmt->fetch();
        if ($statusRow && (string)$statusRow['status'] === 'sold' && !$isAdmin) {
            Response::json(['ok' => false, 'message' => 'Sold listing cannot be deleted', 'errors' => null], 422);
            return;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM listing_images WHERE listing_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM listing_specs WHERE listing_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM listing_status_logs WHERE listing_id = :id')->execute(['id' => $id]);
            $pdo->prepare('DELETE FROM listings WHERE id = :id')->execute(['id' => $id]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            Response::json(['ok' => false, 'message' => 'Delete failed', 'errors' => ['exception' => $e->getMessage()]], 500);
            return;
        }

        Response::json([
            'ok' => true,
            'message' => 'Listing deleted',
            'data' => ['id' => $id],
            'meta' => null,
        ]);
    }

    private static function canManageListing(\PDO $pdo, int $currentUserId, int $ownerUserId): bool
    {
        if ($currentUserId === $ownerUserId) {
            return true;
        }

        return self::isAdmin($pdo, $currentUserId);
    }

    private static function isAdmin(\PDO $pdo, int $userId): bool
    {
        $adminStmt = $pdo->prepare('SELECT 1 FROM user_roles WHERE user_id = :uid AND role_id = 1 LIMIT 1');
        $adminStmt->execute(['uid' => $userId]);
        return (bool)$adminStmt->fetch();
    }
}
