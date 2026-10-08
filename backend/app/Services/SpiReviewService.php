<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\FollowUpStatus;
use App\Enums\RevisionSource;
use App\Enums\Role;
use App\Enums\SpiResult;
use App\Events\SpiReviewCompleted;
use App\Models\ActionPlan;
use App\Models\ActionPlanRevision;
use App\Models\FollowUp;
use App\Models\SpiReview;
use App\Models\SpiReviewItem;
use App\Support\AuditLogger;
use App\Support\CacheService;
use Illuminate\Validation\ValidationException;

class SpiReviewService
{
    public function assess(ActionPlan $actionPlan, array $items): SpiReview
    {
        $this->assertAdmin();
        $this->assertReviewable($actionPlan);

        $review = $this->openReview($actionPlan);

        $validIds = $this->activeFollowUpIds($actionPlan);

        foreach ($items as $item) {
            if (!in_array($item['follow_up_id'], $validIds, true)) {
                throw ValidationException::withMessages([
                    'items' => "Tindak lanjut #{$item['follow_up_id']} bukan tindak lanjut aktif revisi berjalan.",
                ]);
            }

            $result = SpiResult::from($item['result']);
            if ($result === SpiResult::Revisi && empty(trim($item['note'] ?? ''))) {
                throw ValidationException::withMessages([
                    'items' => 'Catatan wajib diisi untuk penilaian Revisi.',
                ]);
            }

            SpiReviewItem::updateOrCreate(
                ['spi_review_id' => $review->id, 'follow_up_id' => $item['follow_up_id']],
                ['result' => $result, 'note' => $item['note'] ?? null],
            );
        }

        AuditLogger::log('spi_review.assessed', auth()->id(), request()?->ip(), "Penilaian SPI action plan {$actionPlan->code} disimpan.", [
            'action_plan_id' => $actionPlan->id,
        ]);

        CacheService::flushGroup('action_plans');

        return $review->refresh();
    }

    public function complete(ActionPlan $actionPlan, ?string $newDeadline = null): ActionPlan
    {
        $this->assertAdmin();
        $this->assertReviewable($actionPlan);

        $review = $this->openReview($actionPlan);
        $review->load('items');

        $activeIds = $this->activeFollowUpIds($actionPlan);
        $nonFinished = FollowUp::withoutGlobalScopes()
            ->where('action_plan_id', $actionPlan->id)
            ->where('revision_no', $actionPlan->current_revision)
            ->whereIn('id', $activeIds)
            ->where('status', '!=', FollowUpStatus::Selesai->value)
            ->pluck('id');

        if ($nonFinished->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Semua tindak lanjut harus berstatus Selesai sebelum review selesai.',
            ]);
        }

        $assessedIds = $review->items->pluck('follow_up_id')->all();
        $missing = array_diff($activeIds, $assessedIds);
        if (!empty($missing)) {
            throw ValidationException::withMessages([
                'items' => 'Semua tindak lanjut revisi berjalan harus dinilai (Sesuai/Revisi). TL belum dinilai: #' . implode(', #', $missing) . '.',
            ]);
        }

        $hasRevision = $review->items->contains(fn ($item) => $item->result === SpiResult::Revisi);

        $review->update(['completed_at' => now()]);

        if ($hasRevision) {
            $nextRevision = $actionPlan->current_revision + 1;

            $reasons = $review->items
                ->filter(fn ($item) => $item->result === SpiResult::Revisi)
                ->pluck('note')
                ->filter()
                ->implode('; ');

            ActionPlanRevision::create([
                'action_plan_id' => $actionPlan->id,
                'revision_no' => $nextRevision,
                'requested_by' => auth()->id(),
                'requested_role' => Role::AdminSpi->value,
                'source' => RevisionSource::SpiReview,
                'reason' => $reasons ?: 'Revisi diperlukan berdasarkan review SPI.',
                'requested_at' => now(),
                'new_deadline' => $newDeadline,
            ]);

            $actionPlan->update([
                'status' => ActionPlanStatus::RevisiSpi,
                'current_revision' => $nextRevision,
            ]);

            // Progres revisi baru dihitung ulang (biasanya 0 sampai TL baru disusun).
            (new ProgressService())->persistApProgress($actionPlan);
        } else {
            $actionPlan->update(['status' => ActionPlanStatus::Sesuai]);
        }

        AuditLogger::log('spi_review.completed', auth()->id(), request()?->ip(), "Review SPI action plan {$actionPlan->code} selesai.", [
            'action_plan_id' => $actionPlan->id,
            'revised' => $hasRevision,
        ]);

        SpiReviewCompleted::dispatch($actionPlan, $hasRevision);

        (new FindingStatusService())->recompute($actionPlan->finding);
        CacheService::flushGroup('action_plans');

        return $actionPlan->refresh();
    }

    private function assertAdmin(): void
    {
        $user = auth()->user();
        if (!in_array($user->role, [Role::AdminSpi, Role::SuperAdmin], true)) {
            throw ValidationException::withMessages([
                'action_plan_id' => 'Hanya Admin SPI yang dapat melakukan review.',
            ]);
        }
    }

    private function assertReviewable(ActionPlan $actionPlan): void
    {
        if ($actionPlan->status !== ActionPlanStatus::DiajukanKeSpi) {
            throw ValidationException::withMessages([
                'status' => 'Hanya action plan berstatus Diajukan ke SPI yang dapat ditinjau.',
            ]);
        }
    }

    private function openReview(ActionPlan $actionPlan): SpiReview
    {
        $review = SpiReview::where('action_plan_id', $actionPlan->id)
            ->where('revision_no', $actionPlan->current_revision)
            ->whereNull('completed_at')
            ->first();

        if (!$review) {
            $review = SpiReview::create([
                'action_plan_id' => $actionPlan->id,
                'revision_no' => $actionPlan->current_revision,
                'reviewer_id' => auth()->id(),
            ]);
        }

        return $review;
    }

    /** ID TL revisi berjalan yang aktif (bukan DITOLAK). */
    private function activeFollowUpIds(ActionPlan $actionPlan): array
    {
        return FollowUp::withoutGlobalScopes()
            ->where('action_plan_id', $actionPlan->id)
            ->where('revision_no', $actionPlan->current_revision)
            ->where('status', '!=', FollowUpStatus::Ditolak->value)
            ->pluck('id')
            ->all();
    }
}