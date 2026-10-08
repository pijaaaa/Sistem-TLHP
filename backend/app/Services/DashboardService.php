<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Enums\InboxTaskStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\FollowUp;
use App\Models\Finding;
use App\Models\InboxTask;
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
            'follow_ups_by_status' => FollowUp::query()
                ->whereNull('deleted_at')
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->all(),
            'pending_tasks' => InboxTask::where('recipient_id', $user->id)
                ->where('status', InboxTaskStatus::Open->value)
                ->orderByDesc('received_at')
                ->limit(10)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'task_type' => $t->task_type->value,
                    'task_type_label' => $t->task_type->label(),
                    'subject_type' => $t->subject_type,
                    'subject_id' => $t->subject_id,
                    'title' => $t->title,
                    'received_at' => $t->received_at?->toDateTimeString(),
                ])
                ->values(),
            'recent_notifications' => $user->notifications()->latest()->limit(5)->get()->map(fn ($n) => [
                'id' => $n->getKey(),
                'title' => $n->data['title'] ?? '',
                'data' => $n->data['data'] ?? [],
                'read_at' => $n->read_at?->toDateTimeString(),
                'created_at' => $n->created_at?->toDateTimeString(),
            ])->values(),
            'widgets' => self::roleWidgets($user),
        ];
    }

    private static function roleWidgets(User $user): array
    {
        return match ($user->role) {
            Role::ManagerDept => [
                'antrian_persetujuan' => FollowUp::where('status', FollowUpStatus::Diajukan->value)->count(),
                'tl_terlambat' => FollowUp::where('status', '!=', FollowUpStatus::Selesai->value)
                    ->where('target_date', '<', now())
                    ->count(),
            ],
            Role::StaffDept => [
                'tl_menunggu_aksi' => FollowUp::whereIn('status', [FollowUpStatus::Draft->value, FollowUpStatus::Revisi->value])->count(),
            ],
            Role::Kepala_spi => [
                'menunggu_status_eksternal' => Finding::where('status', FindingStatus::MenungguStatusEksternal->value)->count(),
            ],
            Role::AdminSpi, Role::InternalAudit => [
                'ap_menunggu_pic' => ActionPlan::where('status', ActionPlanStatus::MenungguPenentuanPic->value)->count(),
                'spi_queue' => ActionPlan::where('status', ActionPlanStatus::DiajukanKeSpi->value)->count(),
            ],
            default => [],
        };
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
