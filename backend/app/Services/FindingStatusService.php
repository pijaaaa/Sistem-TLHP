<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Support\CacheService;
use Illuminate\Validation\ValidationException;

class FindingStatusService
{
    /**
     * Hitung ulang status temuan dari status action plan-nya.
     * Hanya berlaku untuk temuan yang sudah diaktifkan.
     */
    public function recompute(Finding $finding): Finding
    {
        if (!in_array($finding->status, [
            FindingStatus::ProsessTindakLanjut,
            FindingStatus::ReviewSpi,
            FindingStatus::MenungguStatusEksternal,
        ], true)) {
            return $finding;
        }

        $statuses = ActionPlan::withoutGlobalScopes()
            ->where('finding_id', $finding->id)
            ->pluck('status')
            ->map(fn ($s) => $s instanceof ActionPlanStatus ? $s->value : $s);

        if ($statuses->isEmpty()) {
            return $finding;
        }

        $notSubmitted = $statuses->reject(fn ($s) => in_array($s, [
            ActionPlanStatus::DiajukanKeSpi->value,
            ActionPlanStatus::Sesuai->value,
        ], true))->isNotEmpty();

        if ($notSubmitted) {
            $newStatus = FindingStatus::ProsessTindakLanjut;
        } elseif ($statuses->contains(ActionPlanStatus::DiajukanKeSpi->value)) {
            $newStatus = FindingStatus::ReviewSpi;
        } else {
            $newStatus = FindingStatus::MenungguStatusEksternal;
        }

        $finding->update(['status' => $newStatus]);

        CacheService::flushGroup('findings');
        CacheService::flushGroup('dashboard');

        return $finding->refresh();
    }
}