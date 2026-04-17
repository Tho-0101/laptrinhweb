<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Request;
use App\Response;

final class UserController
{
    public static function me(): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('
            SELECT
                u.id,
                u.full_name,
                u.email,
                u.phone,
                u.avatar_url,
                u.status,
                u.created_at,
                up.bio,
                up.gender,
                up.birth_date,
                up.province,
                up.district,
                up.ward,
                up.street,
                up.identity_verified,
                up.rating_avg,
                up.rating_count,
                up.total_success_orders
            FROM users u
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE u.id = :id
            LIMIT 1
        ');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::json(['ok' => false, 'message' => 'User not found', 'errors' => null], 404);
            return;
        }

        $roleStmt = $pdo->prepare('
            SELECT r.code, r.name
            FROM user_roles ur
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id = :user_id
            ORDER BY r.id ASC
        ');
        $roleStmt->execute(['user_id' => $userId]);
        $roles = $roleStmt->fetchAll();

        Response::json([
            'ok' => true,
            'message' => 'Current user fetched',
            'data' => [
                'user' => $user,
                'roles' => $roles,
            ],
            'meta' => null,
        ]);
    }

    public static function updateMe(): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $data = Request::jsonBody();
        $pdo = Database::connection();

        $userFields = ['full_name', 'phone', 'avatar_url'];
        $userSetParts = [];
        $userBindings = ['id' => $userId];
        foreach ($userFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $userSetParts[] = "{$field} = :{$field}";
            $userBindings[$field] = trim((string)$data[$field]) !== '' ? trim((string)$data[$field]) : null;
        }

        if (!empty($userSetParts)) {
            $sql = 'UPDATE users SET ' . implode(', ', $userSetParts) . ', updated_at = NOW() WHERE id = :id';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($userBindings);
        }

        $profileFields = ['bio', 'gender', 'birth_date', 'province', 'district', 'ward', 'street'];
        $profileData = ['user_id' => $userId];
        $hasProfileField = false;
        foreach ($profileFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $profileData[$field] = trim((string)$data[$field]) !== '' ? trim((string)$data[$field]) : null;
            $hasProfileField = true;
        }

        if ($hasProfileField) {
            $pdo->prepare('INSERT IGNORE INTO user_profiles (user_id, created_at, updated_at) VALUES (:user_id, NOW(), NOW())')
                ->execute(['user_id' => $userId]);

            $profileSetParts = [];
            $profileBindings = ['user_id' => $userId];
            foreach ($profileFields as $field) {
                if (!array_key_exists($field, $profileData)) {
                    continue;
                }
                $profileSetParts[] = "{$field} = :{$field}";
                $profileBindings[$field] = $profileData[$field];
            }

            if (!empty($profileSetParts)) {
                $profileSql = 'UPDATE user_profiles SET ' . implode(', ', $profileSetParts) . ', updated_at = NOW() WHERE user_id = :user_id';
                $profileStmt = $pdo->prepare($profileSql);
                $profileStmt->execute($profileBindings);
            }
        }

        Response::json([
            'ok' => true,
            'message' => 'Profile updated',
            'data' => ['user_id' => $userId],
            'meta' => null,
        ]);
    }

    public static function changePassword(): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $data = Request::jsonBody();
        $currentPassword = (string)($data['current_password'] ?? '');
        $newPassword = (string)($data['new_password'] ?? '');
        $confirmPassword = (string)($data['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            Response::json([
                'ok' => false,
                'message' => 'current_password, new_password and confirm_password are required',
                'errors' => ['current_password|new_password|confirm_password' => 'required'],
            ], 422);
            return;
        }

        if (strlen($newPassword) < 8) {
            Response::json([
                'ok' => false,
                'message' => 'New password must be at least 8 characters',
                'errors' => ['new_password' => 'min:8'],
            ], 422);
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Response::json([
                'ok' => false,
                'message' => 'Password confirmation does not match',
                'errors' => ['confirm_password' => 'mismatch'],
            ], 422);
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            Response::json(['ok' => false, 'message' => 'User not found', 'errors' => null], 404);
            return;
        }

        if (!password_verify($currentPassword, (string)$user['password_hash'])) {
            Response::json([
                'ok' => false,
                'message' => 'Current password is incorrect',
                'errors' => ['current_password' => 'invalid'],
            ], 422);
            return;
        }

        if (password_verify($newPassword, (string)$user['password_hash'])) {
            Response::json([
                'ok' => false,
                'message' => 'New password must be different from current password',
                'errors' => ['new_password' => 'same_as_current'],
            ], 422);
            return;
        }

        $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        $update->execute([
            'password_hash' => password_hash($newPassword, PASSWORD_BCRYPT),
            'id' => $userId,
        ]);

        Response::json([
            'ok' => true,
            'message' => 'Password changed successfully',
            'data' => ['user_id' => $userId],
            'meta' => null,
        ]);
    }

    private static function authUserId(): ?int
    {
        $userId = Auth::userIdFromToken(Request::bearerToken());
        if (!$userId) {
            Response::json(['ok' => false, 'message' => 'Unauthorized', 'errors' => null], 401);
            return null;
        }
        return $userId;
    }
}
