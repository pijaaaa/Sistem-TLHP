<?php

namespace App\Support;

use App\Models\Audit;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /**
     * Mapping key pada payload ke tipe entitas, dipakai untuk mengisi
     * entity_type/entity_id tanpa mengubah signature call site yang sudah ada.
     */
    private const ENTITY_KEYS = [
        'finding_id' => 'finding',
        'finding_department_id' => 'finding_department',
        'action_plan_id' => 'action_plan',
        'evidence_submission_id' => 'evidence_submission',
    ];

    public static function log(
        string $action,
        ?int $userId = null,
        ?string $ip = null,
        ?string $description = null,
        ?array $data = null
    ): void {
        try {
            [$entityType, $entityId] = self::resolveEntity($data);

            Audit::create([
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'user_id' => $userId,
                'ip_address' => $ip,
                'description' => $description,
                'payload' => $data,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Audit tidak boleh menggagalkan operasi bisnis.
            Log::warning('Audit gagal disimpan', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }

        CacheService::incrementGroupVersion('audit_trail');
    }

    private static function resolveEntity(?array $data): array
    {
        foreach (self::ENTITY_KEYS as $key => $type) {
            if (! empty($data[$key])) {
                return [$type, (int) $data[$key]];
            }
        }

        return [null, null];
    }
}