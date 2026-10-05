<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\AssessmentStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Models\FindingDepartment;
use App\Models\User;
use App\Support\CacheService;

class DashboardService
{
    public const TTL = 120;

    public static function forUser(User $user): array
    {
        return CacheService::remember(
            'dashboard',
            "user.{$user->id}",
            fn () => self::build($user),
            self::TTL,
        );
    }

    public static function invalidateForUser(User $user): void
    {
        CacheService::forget('dashboard', "user.{$user->id}");
    }

    protected static function build(User $user): array
    {
        return [
            'role' => $user->role->value,
            'role_label' => $user->role->label(),
            'counters' => self::counters($user),
            'findings_by_status' => self::findingsByStatus($user),
        ];
    }

    protected static function counters(User $user): array
    {
        return match ($user->role) {
            Role::ManagerIa => self::managerIaCounters($user),
            Role::ManagerDept => self::managerDeptCounters($user),
            Role::StaffDept => self::staffCounters($user),
            Role::ManagerSpi => self::managerSpiCounters(),
            default => self::adminCounters(),
        };
    }

    protected static function adminCounters(): array
    {
        return [
            'total_temuan' => Finding::count(),
            'draft' => Finding::where('status', FindingStatus::Draft->value)->count(),
            'diajukan_ke_ia' => Finding::where('status', FindingStatus::SentToIa->value)->count(),
            'closed' => Finding::where('status', FindingStatus::Closed->value)->count(),
            'case_closed' => Finding::where('status', FindingStatus::CaseClosed->value)->count(),
        ];
    }

    protected static function managerIaCounters(User $user): array
    {
        return [
            'menunggu_distribusi' => Finding::where('status', FindingStatus::SentToIa->value)->count(),
            'menunggu_assessment' => Finding::where('status', FindingStatus::PendingIaAssessment->value)->count(),
            'dalam_proses' => Finding::whereIn('status', [
                FindingStatus::Distributed->value,
                FindingStatus::InProgress->value,
            ])->count(),
            'menunggu_verifikasi' => Finding::where('status', FindingStatus::PendingVerificationSpi->value)->count(),
        ];
    }

    protected static function managerDeptCounters(User $user): array
    {
        $fdIds = FindingDepartment::where('department_id', $user->department_id)->pluck('id');

        return [
            'dalam_proses' => FindingDepartment::whereIn('id', $fdIds)
                ->whereIn('status', ['pic_ditugaskan', 'dalam_proses'])->count(),
            'selesai_100' => FindingDepartment::whereIn('id', $fdIds)
                ->where('status', 'selesai_100')->count(),
            'menunggu_review_tindak_lanjut' => ActionPlan::whereIn('finding_department_id', $fdIds)
                ->where('status', ActionPlanStatus::Submitted->value)->count(),
            'menunggu_review_evidence' => ActionPlan::whereIn('finding_department_id', $fdIds)
                ->where('status', ActionPlanStatus::EvidenceSubmitted->value)->count(),
        ];
    }

    protected static function staffCounters(User $user): array
    {
        $apQuery = ActionPlan::where('created_by', $user->id);

        return [
            'draft' => (clone $apQuery)->where('status', ActionPlanStatus::Draft->value)->count(),
            'diajukan' => (clone $apQuery)->where('status', ActionPlanStatus::Submitted->value)->count(),
            'revisi' => (clone $apQuery)->where('status', ActionPlanStatus::Revision->value)->count(),
            'menunggu_evidence' => (clone $apQuery)->where('status', ActionPlanStatus::WaitingEvidence->value)->count(),
            'evidence_revisi' => (clone $apQuery)->where('status', ActionPlanStatus::EvidenceRevision->value)->count(),
            'selesai' => (clone $apQuery)->where('status', ActionPlanStatus::EvidenceApproved->value)->count(),
        ];
    }

    protected static function managerSpiCounters(): array
    {
        return [
            'menunggu_verifikasi' => Finding::where('status', FindingStatus::PendingVerificationSpi->value)->count(),
            'closed' => Finding::where('status', FindingStatus::Closed->value)->count(),
            'case_closed' => Finding::where('status', FindingStatus::CaseClosed->value)->count(),
            'assessment_ssr' => Finding::where('assessment_status', AssessmentStatus::Ssr->value)->count(),
        ];
    }

    protected static function findingsByStatus(User $user): array
    {
        return Finding::visible()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }
}