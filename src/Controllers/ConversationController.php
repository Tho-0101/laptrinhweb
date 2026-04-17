<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Database;
use App\Request;
use App\Response;

final class ConversationController
{
    public static function index(): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('
            SELECT
                c.id,
                c.listing_id,
                c.buyer_user_id,
                c.seller_user_id,
                c.last_message_at,
                c.created_at,
                l.title AS listing_title,
                buyer.full_name AS buyer_name,
                seller.full_name AS seller_name,
                (
                    SELECT COUNT(*)
                    FROM messages m
                    WHERE m.conversation_id = c.id
                      AND m.sender_user_id <> :uid
                      AND m.is_read = 0
                ) AS unread_count
            FROM conversations c
            LEFT JOIN listings l ON l.id = c.listing_id
            INNER JOIN users buyer ON buyer.id = c.buyer_user_id
            INNER JOIN users seller ON seller.id = c.seller_user_id
            WHERE c.buyer_user_id = :uid OR c.seller_user_id = :uid
            ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
        ');
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();

        Response::json([
            'ok' => true,
            'message' => 'Conversations fetched',
            'data' => $rows,
            'meta' => null,
        ]);
    }

    public static function create(): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $data = Request::jsonBody();
        $listingId = (int)($data['listing_id'] ?? 0);
        if ($listingId <= 0) {
            Response::json(['ok' => false, 'message' => 'listing_id is required', 'errors' => ['listing_id' => 'required']], 422);
            return;
        }

        $pdo = Database::connection();
        $listingStmt = $pdo->prepare('SELECT id, seller_user_id FROM listings WHERE id = :id LIMIT 1');
        $listingStmt->execute(['id' => $listingId]);
        $listing = $listingStmt->fetch();
        if (!$listing) {
            Response::json(['ok' => false, 'message' => 'Listing not found', 'errors' => null], 404);
            return;
        }

        $sellerUserId = (int)$listing['seller_user_id'];
        if ($sellerUserId === $userId) {
            Response::json(['ok' => false, 'message' => 'Cannot create conversation with yourself', 'errors' => null], 422);
            return;
        }

        $existsStmt = $pdo->prepare('
            SELECT id
            FROM conversations
            WHERE listing_id = :listing_id AND buyer_user_id = :buyer_user_id AND seller_user_id = :seller_user_id
            LIMIT 1
        ');
        $existsStmt->execute([
            'listing_id' => $listingId,
            'buyer_user_id' => $userId,
            'seller_user_id' => $sellerUserId,
        ]);
        $existing = $existsStmt->fetch();
        if ($existing) {
            Response::json([
                'ok' => true,
                'message' => 'Conversation already exists',
                'data' => ['id' => (int)$existing['id']],
                'meta' => null,
            ]);
            return;
        }

        $insert = $pdo->prepare('
            INSERT INTO conversations (listing_id, buyer_user_id, seller_user_id, created_at)
            VALUES (:listing_id, :buyer_user_id, :seller_user_id, NOW())
        ');
        $insert->execute([
            'listing_id' => $listingId,
            'buyer_user_id' => $userId,
            'seller_user_id' => $sellerUserId,
        ]);

        Response::json([
            'ok' => true,
            'message' => 'Conversation created',
            'data' => ['id' => (int)$pdo->lastInsertId()],
            'meta' => null,
        ], 201);
    }

    public static function messages(int $conversationId): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $pdo = Database::connection();
        $conv = self::getUserConversation($pdo, $conversationId, $userId);
        if (!$conv) {
            Response::json(['ok' => false, 'message' => 'Conversation not found', 'errors' => null], 404);
            return;
        }

        $stmt = $pdo->prepare('
            SELECT id, conversation_id, sender_user_id, content, image_url, is_read, sent_at
            FROM messages
            WHERE conversation_id = :conversation_id
            ORDER BY sent_at ASC, id ASC
        ');
        $stmt->execute(['conversation_id' => $conversationId]);
        $rows = $stmt->fetchAll();

        $markRead = $pdo->prepare('
            UPDATE messages
            SET is_read = 1
            WHERE conversation_id = :conversation_id
              AND sender_user_id <> :current_user_id
              AND is_read = 0
        ');
        $markRead->execute([
            'conversation_id' => $conversationId,
            'current_user_id' => $userId,
        ]);

        Response::json([
            'ok' => true,
            'message' => 'Messages fetched',
            'data' => $rows,
            'meta' => null,
        ]);
    }

    public static function sendMessage(int $conversationId): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $data = Request::jsonBody();
        $content = trim((string)($data['content'] ?? ''));
        $imageUrl = trim((string)($data['image_url'] ?? ''));
        if ($content === '' && $imageUrl === '') {
            Response::json(['ok' => false, 'message' => 'content or image_url is required', 'errors' => ['content|image_url' => 'required']], 422);
            return;
        }

        $pdo = Database::connection();
        $conv = self::getUserConversation($pdo, $conversationId, $userId);
        if (!$conv) {
            Response::json(['ok' => false, 'message' => 'Conversation not found', 'errors' => null], 404);
            return;
        }

        $insert = $pdo->prepare('
            INSERT INTO messages (conversation_id, sender_user_id, content, image_url, is_read, sent_at)
            VALUES (:conversation_id, :sender_user_id, :content, :image_url, 0, NOW())
        ');
        $insert->execute([
            'conversation_id' => $conversationId,
            'sender_user_id' => $userId,
            'content' => $content !== '' ? $content : null,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
        ]);

        $updateConv = $pdo->prepare('UPDATE conversations SET last_message_at = NOW() WHERE id = :id');
        $updateConv->execute(['id' => $conversationId]);

        Response::json([
            'ok' => true,
            'message' => 'Message sent',
            'data' => ['id' => (int)$pdo->lastInsertId()],
            'meta' => null,
        ], 201);
    }

    public static function unreadCount(): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $pdo = Database::connection();
        $stmt = $pdo->prepare('
            SELECT COUNT(*) AS unread_count
            FROM messages m
            INNER JOIN conversations c ON c.id = m.conversation_id
            WHERE (c.buyer_user_id = :uid OR c.seller_user_id = :uid)
              AND m.sender_user_id <> :uid
              AND m.is_read = 0
        ');
        $stmt->execute(['uid' => $userId]);
        $unreadCount = (int)$stmt->fetchColumn();

        Response::json([
            'ok' => true,
            'message' => 'Unread count fetched',
            'data' => [
                'unread_count' => $unreadCount,
            ],
            'meta' => null,
        ]);
    }

    public static function markRead(int $conversationId): void
    {
        $userId = self::authUserId();
        if ($userId === null) {
            return;
        }

        $pdo = Database::connection();
        $conv = self::getUserConversation($pdo, $conversationId, $userId);
        if (!$conv) {
            Response::json(['ok' => false, 'message' => 'Conversation not found', 'errors' => null], 404);
            return;
        }

        $stmt = $pdo->prepare('
            UPDATE messages
            SET is_read = 1
            WHERE conversation_id = :conversation_id
              AND sender_user_id <> :current_user_id
              AND is_read = 0
        ');
        $stmt->execute([
            'conversation_id' => $conversationId,
            'current_user_id' => $userId,
        ]);

        Response::json([
            'ok' => true,
            'message' => 'Messages marked as read',
            'data' => [
                'conversation_id' => $conversationId,
                'affected_rows' => $stmt->rowCount(),
            ],
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

    private static function getUserConversation(\PDO $pdo, int $conversationId, int $userId): array|false
    {
        $stmt = $pdo->prepare('
            SELECT id
            FROM conversations
            WHERE id = :id AND (buyer_user_id = :uid OR seller_user_id = :uid)
            LIMIT 1
        ');
        $stmt->execute([
            'id' => $conversationId,
            'uid' => $userId,
        ]);

        return $stmt->fetch();
    }
}
