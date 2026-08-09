<?php

declare(strict_types=1);

namespace Fmos\Core;

final class Audit
{
    public static function record(
        string $action,
        string $entityType,
        ?int $entityId = null,
        mixed $before = null,
        mixed $after = null,
        ?string $reason = null,
    ): void {
        $pdo = Database::connection();
        $user = Auth::user();
        $stmt = $pdo->prepare(
            'INSERT INTO audit_logs (tenant_id, user_id, entity_type, entity_id, action, before_json, after_json, reason, request_id, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $user['tenant_id'] ?? null,
            $user['id'] ?? null,
            $entityType,
            $entityId,
            $action,
            $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            $reason,
            Logger::requestId(),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }
}
