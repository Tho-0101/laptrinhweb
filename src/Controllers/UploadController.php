<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Request;
use App\Response;

final class UploadController
{
    public static function image(): void
    {
        $userId = Auth::userIdFromToken(Request::bearerToken());
        if (!$userId) {
            Response::json(['ok' => false, 'message' => 'Unauthorized', 'errors' => null], 401);
            return;
        }

        $fileKey = self::resolveFileKey();
        if ($fileKey === null) {
            Response::json([
                'ok' => false,
                'message' => 'image file is required',
                'errors' => [
                    'accepted_keys' => ['image', 'file', 'photo'],
                    'received_keys' => array_keys($_FILES),
                ],
            ], 422);
            return;
        }

        $file = $_FILES[$fileKey];
        $uploadError = is_array($file) ? (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
        if (!is_array($file) || $uploadError !== UPLOAD_ERR_OK) {
            Response::json([
                'ok' => false,
                'message' => 'Upload failed',
                'errors' => [
                    'image' => 'invalid_upload',
                    'php_upload_error_code' => $uploadError,
                    'php_upload_error_label' => self::uploadErrorLabel($uploadError),
                ],
            ], 422);
            return;
        }

        $maxBytes = 5 * 1024 * 1024; // 5MB
        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            Response::json(['ok' => false, 'message' => 'Image must be <= 5MB', 'errors' => ['image' => 'max:5mb']], 422);
            return;
        }

        $tmpName = (string)$file['tmp_name'];
        $mime = mime_content_type($tmpName) ?: '';
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            Response::json(['ok' => false, 'message' => 'Only jpg, png, webp are allowed', 'errors' => ['image' => 'invalid_type']], 422);
            return;
        }

        $target = (string)($_POST['target'] ?? 'listing');
        $folder = match ($target) {
            'chat' => 'chat',
            'avatar' => 'avatars',
            default => 'listings',
        };

        $basePublicPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public';
        $uploadDir = $basePublicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder;
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            Response::json(['ok' => false, 'message' => 'Cannot create upload directory', 'errors' => null], 500);
            return;
        }

        $ext = $allowed[$mime];
        $fileName = sprintf('%d_%s.%s', $userId, bin2hex(random_bytes(8)), $ext);
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            Response::json(['ok' => false, 'message' => 'Cannot move uploaded file', 'errors' => null], 500);
            return;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $basePath = '/bike-shop1/public';
        $url = sprintf('%s://%s%s/uploads/%s/%s', $scheme, $host, $basePath, $folder, $fileName);

        Response::json([
            'ok' => true,
            'message' => 'Image uploaded',
            'data' => [
                'field' => $fileKey,
                'url' => $url,
                'path' => '/uploads/' . $folder . '/' . $fileName,
                'target' => $folder,
                'size' => $size,
                'mime' => $mime,
            ],
            'meta' => null,
        ], 201);
    }

    private static function resolveFileKey(): ?string
    {
        $acceptedKeys = ['image', 'file', 'photo'];
        foreach ($acceptedKeys as $key) {
            if (isset($_FILES[$key])) {
                return $key;
            }
        }

        return null;
    }

    private static function uploadErrorLabel(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_OK => 'UPLOAD_ERR_OK',
            UPLOAD_ERR_INI_SIZE => 'UPLOAD_ERR_INI_SIZE',
            UPLOAD_ERR_FORM_SIZE => 'UPLOAD_ERR_FORM_SIZE',
            UPLOAD_ERR_PARTIAL => 'UPLOAD_ERR_PARTIAL',
            UPLOAD_ERR_NO_FILE => 'UPLOAD_ERR_NO_FILE',
            UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
            UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
            UPLOAD_ERR_EXTENSION => 'UPLOAD_ERR_EXTENSION',
            default => 'UPLOAD_ERR_UNKNOWN',
        };
    }
}
