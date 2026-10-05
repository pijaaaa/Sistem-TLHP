<?php

namespace App\Support;

class AuditLogger
{
    public static function log(
        string $action,
        ?int $userId = null,
        ?string $ip = null,
        ?string $description = null,
        ?array $data = null
    ): void {
        // Stub: tabel audits akan dibuat di M10
        // Untuk sementat log ke Laravel log
        \Log::info("Audit: {$action}", [
            'user_id' => $userId,
            'ip' => $ip,
            'description' => $description,
            'data' => $data,
        ]);
    }
}
