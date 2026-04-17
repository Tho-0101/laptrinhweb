<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Response;

final class BrandController
{
    public static function index(): void
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT id, name, country FROM brands ORDER BY name ASC');
        $rows = $stmt->fetchAll();

        Response::json([
            'ok' => true,
            'message' => 'Brands fetched',
            'data' => $rows,
            'meta' => null,
        ]);
    }
}
