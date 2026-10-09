<?php

namespace App\Http\Controllers\Api;

use App\Enums\FollowUpStatus;
use App\Http\Controllers\Controller;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Models\FollowUp;
use App\Support\ApiResponse;
use App\Support\CacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function departments(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ActionPlan::class);

        return $this->remember($request, 'departments', function () use ($request) {
            [$aps, $fups] = $this->scopedData($request);

            $groups = $aps->groupBy('department_id');

            $rows = $groups->map(function ($apGroup, $departmentId) use ($fups) {
                $department = $apGroup->first()->department;

                $apIds = $apGroup->pluck('id');
                $items = $fups->whereIn('action_plan_id', $apIds);

                $done = $items->where('status', FollowUpStatus::Selesai->value);
                $overdue = $items->reject(fn ($f) => $f->status === FollowUpStatus::Selesai->value)
                    ->filter(fn ($f) => $f->target_date && $f->target_date->startOfDay() < now()->startOfDay());

                return [
                    'department_id' => (int) $departmentId,
                    'department' => $department?->name ?? '-',
                    'total_action_plans' => $apGroup->count(),
                    'total_follow_ups' => $items->count(),
                    'avg_progress' => round($apGroup->avg('progress') ?? 0, 2),
                    'selesai' => $done->count(),
                    'terlambat' => $overdue->count(),
                ];
            })->values()->sortByDesc('total_action_plans')->values();

            return ['data' => $rows];
        });
    }

    public function late(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FollowUp::class);

        return $this->remember($request, 'late', function () use ($request) {
            [$aps, $fups] = $this->scopedData($request);

            $apOf = $aps->keyBy('id');
            $rows = $fups
                ->reject(fn ($f) => $f->status === FollowUpStatus::Selesai->value)
                ->filter(fn ($f) => $f->target_date && $f->target_date->startOfDay() < now()->startOfDay())
                ->values()
                ->map(function ($f) use ($apOf) {
                    $ap = $apOf->get($f->action_plan_id);

                    return [
                        'follow_up_id' => $f->id,
                        'description' => $f->description,
                        'target_date' => $f->target_date->toDateString(),
                        'days_overdue' => (int) $f->target_date->startOfDay()->diffInDays(now()->startOfDay()),
                        'status' => $f->status->value,
                        'action_plan_code' => $ap?->code ?? '-',
                        'department' => $ap?->department?->name ?? '-',
                        'finding' => $ap?->finding?->registration_number ?? '-',
                        'progress' => (int) $f->progress,
                    ];
                })
                ->sortByDesc('days_overdue')
                ->values();

            return ['data' => $rows];
        });
    }

    public function findingAge(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Finding::class);

        return $this->remember($request, 'age', function () use ($request) {
            $query = Finding::query();
            $this->applyFindingFilters($request, $query);

            $ages = $query->pluck('finding_date')->filter()->map(fn ($d) => (int) $d->startOfDay()->diffInDays(now()->startOfDay()));

            $groups = ['< 30 hari' => 0, '30–60 hari' => 0, '61–90 hari' => 0, '91–180 hari' => 0, '> 180 hari' => 0];
            foreach ($ages as $age) {
                match (true) {
                    $age < 30 => $groups['< 30 hari']++,
                    $age < 61 => $groups['30–60 hari']++,
                    $age < 91 => $groups['61–90 hari']++,
                    $age < 181 => $groups['91–180 hari']++,
                    default => $groups['> 180 hari']++,
                };
            }

            return ['data' => collect($groups)->map(fn ($count, $label) => ['group' => $label, 'count' => $count])->values()];
        });
    }

    public function risk(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ActionPlan::class);

        return $this->remember($request, 'risk', function () use ($request) {
            $query = ActionPlan::query()->with('finding');
            $this->applyApFilters($request, $query);

            $rows = $query->get(['id', 'risk', 'loss_idr', 'loss_usd']);

            $grouped = $rows->map(fn ($ap) => [
                'risk' => $ap->risk?->value ?? '-',
                'risk_label' => $ap->risk?->label() ?? '-',
                'count' => 1,
                'loss_idr' => (float) ($ap->loss_idr ?? 0),
                'loss_usd' => (float) ($ap->loss_usd ?? 0),
            ])->groupBy('risk')->map(function ($group) {
                return [
                    'risk' => $group[0]['risk'],
                    'risk_label' => $group[0]['risk_label'],
                    'count' => $group->count(),
                    'loss_idr' => round($group->sum('loss_idr'), 2),
                    'loss_usd' => round($group->sum('loss_usd'), 2),
                ];
            })->values();

            return ['data' => $grouped];
        });
    }

    private function scopedData(Request $request): array
    {
        $apQuery = ActionPlan::query()->with(['finding', 'department']);
        $this->applyApFilters($request, $apQuery);
        $aps = $apQuery->get();

        $apIds = $aps->pluck('id');
        $fups = collect();
        if ($apIds->isNotEmpty()) {
            $fups = FollowUp::query()
                ->whereIn('action_plan_id', $apIds)
                ->get();
        }

        return [$aps, $fups];
    }

    private function applyFindingFilters(Request $request, $query): void
    {
        if ($fiscalYear = $request->query('fiscal_year')) {
            $query->whereYear('response_period_start', (int) $fiscalYear);
        }
        if ($source = $request->query('source')) {
            $query->where('source', $source);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($departmentId = $request->query('department_id')) {
            $query->whereHas('auditee_departments', fn ($q) => $q->where('departments.id', (int) $departmentId));
        }
    }

    private function applyApFilters(Request $request, $query): void
    {
        if ($departmentId = $request->query('department_id')) {
            $query->where('department_id', (int) $departmentId);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($fiscalYear = $request->query('fiscal_year')) {
            $query->whereHas('finding', fn ($q) => $q->whereYear('response_period_start', (int) $fiscalYear));
        }
        if ($source = $request->query('source')) {
            $query->whereHas('finding', fn ($q) => $q->where('source', $source));
        }
    }

    private function remember(Request $request, string $type, \Closure $callback): JsonResponse
    {
        $user = auth()->user();
        $filters = md5(http_build_query($request->only(['fiscal_year', 'source', 'department_id', 'status'])));

        $data = CacheService::remember('reports', "{$type}.{$user->id}.{$filters}", $callback, 300);

        return ApiResponse::success($data);
    }
}