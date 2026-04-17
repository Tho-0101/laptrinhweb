<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Request;
use App\Response;

final class AuthController
{
    public static function register(): void
    {
        $data = Request::jsonBody();
        $fullName = trim((string)($data['full_name'] ?? ''));
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $phone = trim((string)($data['phone'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $role = strtolower(trim((string)($data['role'] ?? 'nguoi_mua')));

        if ($fullName === '' || $email === '' || $password === '') {
            Response::json(['ok' => false, 'message' => 'Missing required fields', 'errors' => ['full_name|email|password' => 'required']], 422);
            return;
        }

        $pdo = Database::connection();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            Response::json(['ok' => false, 'message' => 'Email already exists', 'errors' => ['email' => 'duplicate']], 409);
            return;
        }

        $stmt = $pdo->prepare('
            INSERT INTO users (full_name, email, phone, password_hash, status, created_at, updated_at)
            VALUES (:full_name, :email, :phone, :password_hash, "active", NOW(), NOW())
        ');
        $stmt->execute([
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $userId = (int)$pdo->lastInsertId();

        $roleIds = self::resolveRoleIds($role);
        $roleStmt = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id, assigned_at) VALUES (:uid, :role_id, NOW())');
        foreach ($roleIds as $roleId) {
            $roleStmt->execute([
                'uid' => $userId,
                'role_id' => $roleId,
            ]);
        }

        Response::json([
            'ok' => true,
            'message' => 'Register success',
            'data' => [
                'user_id' => $userId,
                'token' => Auth::makeToken($userId),
            ],
            'meta' => null,
        ], 201);
    }

    public static function login(): void
    {
        $data = Request::jsonBody();
        $email = strtolower(trim((string)($data['email'] ?? '')));
        $password = (string)($data['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::json(['ok' => false, 'message' => 'Email and password are required', 'errors' => ['email|password' => 'required']], 422);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, status FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            Response::json(['ok' => false, 'message' => 'Invalid credentials', 'errors' => null], 401);
            return;
        }

        if (($user['status'] ?? '') !== 'active') {
            Response::json(['ok' => false, 'message' => 'Account is not active', 'errors' => null], 403);
            return;
        }

        $update = $pdo->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
        $update->execute(['id' => (int)$user['id']]);

        Response::json([
            'ok' => true,
            'message' => 'Login success',
            'data' => [
                'token' => Auth::makeToken((int)$user['id']),
                'user' => [
                    'id' => (int)$user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                ],
            ],
            'meta' => null,
        ]);
    }

    /**
     * Map frontend role value to database role ids.
     *
     * roles table:
     * 1 admin
     * 2 buyer
     * 3 seller
     * 4 shop_owner
     */
    private static function resolveRoleIds(string $role): array
    {
        return match ($role) {
            'nguoi_ban', 'seller' => [2, 3],
            'cua_hang', 'shop_owner', 'shop' => [2, 3, 4],
            default => [2],
        };
    }
}
