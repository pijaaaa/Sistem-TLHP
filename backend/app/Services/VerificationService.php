<?php

namespace App\Services;

use App\Enums\AuditorConclusion;
use App\Enums\AssessmentStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Finding;
use App\Models\FindingVerification;
use App\Support\AuditLogger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerificationService
{
    public static function pending(int $perPage = 15): LengthAwarePaginator
    {
        $paginator = Finding::where('status', FindingStatus::PendingVerificationSpi->value)
            ->withCount('documents')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        ActionPlanService::attachFindingProgress($paginator->items());

        return $paginator;
    }

    /**
     * Manager SPI mencatat hasil pemeriksaan auditor eksternal.
     * - closed: hanya bila assessment sebelumnya SSR, lalu temuan CLOSED.
     * - needs_revision: kembalikan ke Manager IA sebagai BSR (ronde baru).
     */
    public static function record(Finding $finding, AuditorConclusion $conclusion, array $data): FindingVerification
    {
        $user = auth()->user();

        if (! in_array($user->role, [Role::ManagerSpi, Role::SuperAdmin])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Manager SPI yang dapat mencatat hasil verifikasi.',
            ]);
        }

        if ($finding->status !== FindingStatus::PendingVerificationSpi) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan dalam status Menunggu Verifikasi SPI yang dapat diverifikasi.',
            ]);
        }

        if ($conclusion->closesFinding() && $finding->assessment_status !== AssessmentStatus::Ssr) {
            throw ValidationException::withMessages([
                'assessment' => 'Temuan hanya dapat ditutup bila assessment IA sebelumnya berstatus SSR.',
            ]);
        }

        if ($conclusion === AuditorConclusion::NeedsRevision && blank($data['notes'] ?? null)) {
            throw ValidationException::withMessages([
                'notes' => 'Catatan wajib diisi saat mengembalikan temuan ke Manager IA.',
            ]);
        }

        return DB::transaction(function () use ($finding, $conclusion, $data, $user) {
            $verification = FindingVerification::create([
                'finding_id' => $finding->id,
                'round' => $finding->current_round,
                'auditor_result' => $data['auditor_result'] ?? null,
                'auditor_conclusion' => $conclusion,
                'verified_date' => $data['verified_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_closed' => $conclusion->closesFinding(),
                'created_by' => $user->id,
            ]);

            if ($conclusion->closesFinding()) {
                $finding->status = FindingStatus::Closed;
                $finding->save();
            } else {
                $finding->assessment_status = AssessmentStatus::Bsr;
                $finding->status = FindingStatus::Distributed;
                $finding->save();
                AssessmentService::startNewRound($finding, null);
            }

            AuditLogger::log('finding.verified', $user->id, request()->ip(), 'Hasil auditor eksternal dicatat.', [
                'finding_id' => $finding->id,
                'conclusion' => $conclusion->value,
                'round' => $verification->round,
            ]);

            FindingDepartmentService::invalidate();
            return $verification->fresh();
        });
    }
}