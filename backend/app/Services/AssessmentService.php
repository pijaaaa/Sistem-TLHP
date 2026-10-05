<?php

namespace App\Services;

use App\Enums\AssessmentStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Finding;
use App\Models\FindingDepartment;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentService
{
    /**
     * Assessment hanya bisa dilakukan setelah SEMUA departemen ronde berjalan
     * meneruskan temuan ke IA.
     */
    public static function assess(Finding $finding, AssessmentStatus $assessment, ?string $note, ?array $departmentIds = null): Finding
    {
        $user = auth()->user();

        if ($user->role !== Role::ManagerIa && $user->role !== Role::SuperAdmin) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Manager IA yang dapat melakukan assessment.',
            ]);
        }

        if ($finding->status !== FindingStatus::PendingIaAssessment) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan dalam status Menunggu Assessment IA yang dapat di-assess.',
            ]);
        }

        if (! $finding->isAssessingReady()) {
            throw ValidationException::withMessages([
                'status' => 'Assessment hanya dapat dilakukan setelah semua departemen meneruskan temuan ke IA.',
            ]);
        }

        if ($assessment->requiresReason() && blank($note)) {
            throw ValidationException::withMessages([
                'note' => 'Alasan wajib diisi untuk status Tidak Dapat Ditindaklanjuti.',
            ]);
        }

        return DB::transaction(function () use ($finding, $assessment, $note, $user, $departmentIds) {
            $finding->assessment_status = $assessment;
            $finding->assessment_note = $note;
            $finding->assessed_by = $user->id;
            $finding->assessed_at = now();

            if ($assessment === AssessmentStatus::TidakDapatDitindaklanjuti) {
                $finding->status = FindingStatus::CaseClosed;
            } elseif ($assessment === AssessmentStatus::Ssr) {
                $finding->status = FindingStatus::PendingVerificationSpi;
            } elseif ($assessment === AssessmentStatus::Bsr) {
                $finding->status = FindingStatus::Distributed;
            } else {
                // Belum Ditindaklanjuti: status default, tidak memicu ronde baru.
                $finding->status = FindingStatus::PendingIaAssessment;
            }

            $finding->save();

            AuditLogger::log('finding.assessed', $user->id, request()->ip(), 'Assessment IA dilakukan.', [
                'finding_id' => $finding->id,
                'assessment_status' => $assessment->value,
                'round' => $finding->current_round,
            ]);

            if ($assessment->startsNewRound()) {
                self::startNewRound($finding, $departmentIds);
            }

            FindingDepartmentService::invalidate();
            return $finding->fresh();
        });
    }

    /**
     * Ronde baru: Finding mendapat round+1 dan kode baru (menjaga keunikan kode),
     * departemen dari ronde sebelumnya terbawa (Manager IA boleh mengubah subset),
     * riwayat ronde lama tetap read-only.
     */
    public static function startNewRound(Finding $finding, ?array $departmentIds): void
    {
        $newRound = $finding->current_round + 1;

        $previous = $finding->currentRoundDepartments()->pluck('department_id')->all();
        $carry = $departmentIds === null || $departmentIds === [] ? $previous : $departmentIds;

        $finding->current_round = $newRound;
        $finding->code = self::nextRoundCode($finding, $newRound);
        $finding->save();

        foreach ($carry as $deptId) {
            FindingDepartment::create([
                'finding_id' => $finding->id,
                'round' => $newRound,
                'department_id' => $deptId,
                'assigned_by' => null,
                'status' => \App\Enums\FindingDepartmentStatus::Received,
            ]);
        }
    }

    protected static function nextRoundCode(Finding $finding, int $round): string
    {
        $suffix = '-R' . $round;
        $base = $finding->code;

        if (! str_contains($base, '-R')) {
            return $base . $suffix;
        }

        $parts = explode('-R', $base);
        return $parts[0] . $suffix;
    }
}