<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\FollowUpStatus;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Models\FollowUp;
use App\Support\CacheService;

class ProgressService
{
    public const COUNTED_STATUSES = [
        FollowUpStatus::Disetujui->value,
        FollowUpStatus::MenungguPersetujuanSelesai->value,
        FollowUpStatus::Selesai->value,
    ];

    /** Progres AP = Σ(bobot × progres) / Σ(bobot) atas TL disetujui ke atas pada revisi berjalan. */
    public function apProgress(ActionPlan $actionPlan): float
    {
        $rows = FollowUp::withoutGlobalScopes()
            ->where('action_plan_id', $actionPlan->id)
            ->where('revision_no', $actionPlan->current_revision)
            ->whereIn('status', self::COUNTED_STATUSES)
            ->get(['weight', 'progress']);

        $weightSum = $rows->sum('weight');
        if ($weightSum === 0) {
            return 0.0;
        }

        $weighted = $rows->sum(fn ($row) => $row->weight * $row->progress);

        return round($weighted / $weightSum, 2);
    }

    /** Hitung, simpan ke action_plans.progress, dan invalidasi cache. */
    public function persistApProgress(ActionPlan $actionPlan): float
    {
        $progress = $this->apProgress($actionPlan);

        $actionPlan->update(['progress' => $progress]);

        CacheService::flushGroup('action_plans');
        CacheService::flushGroup('findings');
        CacheService::flushGroup('dashboard');

        return $progress;
    }

    /** Progres temuan = rata-rata progres AP (AP SESUAI/CLOSED = 100; AP DRAFT tidak dihitung). */
    public function findingProgress(Finding $finding): ?float
    {
        $aps = ActionPlan::withoutGlobalScopes()
            ->where('finding_id', $finding->id)
            ->get(['status', 'progress']);

        $values = $aps->reduce(function (array $acc, ActionPlan $ap) {
            if ($ap->status === ActionPlanStatus::Draft) {
                return $acc;
            }

            if ($ap->status === ActionPlanStatus::Sesuai || $ap->status === ActionPlanStatus::Closed) {
                $acc[] = 100.0;
                return $acc;
            }

            $acc[] = (float) $ap->progress;
            return $acc;
        }, []);

        if (empty($values)) {
            return null;
        }

        return round(array_sum($values) / count($values), 2);
    }
}