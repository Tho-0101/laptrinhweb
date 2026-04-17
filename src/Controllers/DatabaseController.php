<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Request;
use App\Response;

final class DatabaseController
{
    public static function all(): void
    {
        $token = Request::bearerToken();
        $userId = Auth::userIdFromToken($token);
        if (!$userId) {
            Response::json(['ok' => false, 'message' => 'Unauthorized', 'errors' => null], 401);
            return;
        }

        $pdo = Database::connection();
        $isAdminStmt = $pdo->prepare('SELECT 1 FROM user_roles WHERE user_id = :user_id AND role_id = 1 LIMIT 1');
        $isAdminStmt->execute(['user_id' => $userId]);
        if (!$isAdminStmt->fetch()) {
            Response::json(['ok' => false, 'message' => 'Admin role required', 'errors' => null], 403);
            return;
        }

        $tables = [
            'roles',
            'users',
            'user_roles',
            'user_profiles',
            'brands',
            'bike_types',
            'bike_models',
            'shops',
            'listings',
            'listing_specs',
            'listing_images',
            'listing_status_logs',
            'conversations',
            'messages',
        ];

        $result = [];
        foreach ($tables as $table) {
            $stmt = $pdo->query(sprintf('SELECT * FROM `%s`', $table));
            $rows = $stmt->fetchAll();
            $result[$table] = [
                'count' => count($rows),
                'rows' => $rows,
            ];
        }

        Response::json([
            'ok' => true,
            'message' => 'All database data fetched',
            'data' => $result,
            'meta' => null,
        ]);
    }
}
