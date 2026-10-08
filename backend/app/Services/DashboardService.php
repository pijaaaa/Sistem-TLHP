<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\Finding;
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
            'findings_by_status' => Finding::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
        ];
    }

    protected static function counters(User $user): array
    {
        return match ($user->role) {
            Role::ManagerDept => self::managerDeptCounters($user),
            Role::StaffDept => self::staffCounters($user),
            Role::Kepala_spi => self::kepalaSpiCounters(),
            default => self::monitorCounters(),
        };
    }

    protected static function monitorCounters(): array
    {
        return [
            'total_temuan' => Finding::count(),
            'draft' => Finding::where('status', FindingStatus::Draft->value)->count(),
            'terdaftar' => Finding::where('status', FindingStatus::Terdaftar->value)->count(),
            'proses_tindak_lanjut' => Finding::where('status', FindingStatus::ProsessTindakLanjut->value)->count(),
            'closed' => Finding::where('status', FindingStatus::Closed->value)->count(),
        ];
    }

    protected static function kepalaSpiCounters(): array
    {
        return [
            'total_temuan' => Finding::count(),
            'menunggu_status_eksternal' => Finding::where('status', FindingStatus::MenungguStatusEksternal->value)->count(),
            'review_spi' => Finding::where('status', FindingStatus::ReviewSpi->value)->count(),
            'closed' => Finding::where('status', FindingStatus::Closed->value)->count(),
        ];
    }

    protected static function managerDeptCounters(User $user): array
    {
        $base = ActionPlan::where('department_id', $user->department_id);

        return [
            'total_action_plan' => (clone $base)->count(),
            'menunggu_penentuan_pic' => (clone $base)->where('status', ActionPlanStatus::MenungguPenentuanPic->value)->count(),
            'proses_tindak_lanjut' => (clone $base)->where('status', ActionPlanStatus::ProsesTindakLanjut->value)->count(),
            'diajukan_ke_spi' => (clone $base)->where('status', ActionPlanStatus::DiajukanKeSpi->value)->count(),
            'closed' => (clone $base)->where('status', ActionPlanStatus::Closed->value)->count(),
        ];
    }

    protected static function staffCounters(User $user): array
    {
        $base = ActionPlan::whereIn('id', function ($q) use ($user) {
            $q->select('action_plan_id')->from('action_plan_assignees')->where('user_id', $user->id);
        });

        return [
            'total_action_plan' => (clone $base)->count(),
            'proses_tindak_lanjut' => (clone $base)->where('status', ActionPlanStatus::ProsesTindakLanjut->value)->count(),
            'diajukan_ke_spi' => (clone $base)->where('status', ActionPlanStatus::DiajukanKeSpi->value)->count(),
            'closed' => (clone $base)->where('status', ActionPlanStatus::Closed->value)->count(),
        ];
    }
}
