<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\ExternalStatus;
use App\Enums\FindingStatus;
use App\Enums\RevisionSource;
use App\Enums\Role;
use App\Events\FindingClosed;
use App\Events\ExternalStatusRecorded;
use App\Models\ActionPlan;
use App\Models\ActionPlanRevision;
use App\Models\ExternalStatusRecord;
use App\Models\Finding;
use App\Support\AuditLogger;
use App\Support\CacheService;
use Illuminate\Validation\ValidationException;

class ExternalStatusService
{
    /**
     * @param  array<int, array{file: \Illuminate\Http\UploadedFile, label: string}>  $documents
     */
    public function record(
        Finding $finding,
        ExternalStatus $status,
        ?string $note,
        array $documents,
        array $actionPlanIds = [],
        ?string $newDeadline = null
    ): ExternalStatusRecord {
        $this->assertKepalaSpi();

        if ($finding->status !== FindingStatus::MenungguStatusEksternal) {
            throw ValidationException::withMessages([
                'status' => 'Status eksternal hanya dapat dicatat saat temuan berstatus Menunggu Status Eksternal.',
            ]);
        }

        if (empty($documents)) {
            throw ValidationException::withMessages([
                'documents' => 'Minimal satu dokumen pendukung harus dilampirkan.',
            ]);
        }

        $record = ExternalStatusRecord::create([
            'finding_id' => $finding->id,
            'status' => $status,
            'note' => $note,
            'recorded_by' => auth()->id(),
            'recorded_at' => now(),
        ]);

        foreach ($documents as $doc) {
            $file = $doc['file'];
            $record->documents()->create([
                'label' => $doc['label'],
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('external-status', config('upload.disk')),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        if ($status->isClosing()) {
            $this->applyClosing($finding);
        } else {
            $this->applyRevision($finding, $record, $actionPlanIds, $note, $newDeadline);
        }

        TaskDispatcher::completeForActor($finding);
        ExternalStatusRecorded::dispatch($finding, $status);

        AuditLogger::log('external_status.recorded', auth()->id(), request()?->ip(), "Status eksternal {$status->value} dicatat untuk temuan {$finding->registration_number}.", [
            'finding_id' => $finding->id,
            'status' => $status->value,
        ]);

        return $record->load('documents');
    }

    private function applyClosing(Finding $finding): void
    {
        $finding->update([
            'status' => FindingStatus::Closed,
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        ActionPlan::withoutGlobalScopes()
            ->where('finding_id', $finding->id)
            ->update(['status' => ActionPlanStatus::Closed]);

        FindingClosed::dispatch($finding);

        CacheService::flushGroup('action_plans');
        CacheService::flushGroup('findings');
        CacheService::flushGroup('dashboard');
    }

    private function applyRevision(Finding $finding, ExternalStatusRecord $record, array $actionPlanIds, ?string $note, ?string $newDeadline): void
    {
        if (empty($actionPlanIds)) {
            throw ValidationException::withMessages([
                'action_plan_ids' => 'Untuk BSR/BD/TDTL wajib memilih minimal satu action plan yang akan diperbaiki.',
            ]);
        }

        $aps = ActionPlan::withoutGlobalScopes()
            ->where('finding_id', $finding->id)
            ->whereIn('id', $actionPlanIds)
            ->get();

        if ($aps->count() !== count(array_unique($actionPlanIds))) {
            throw ValidationException::withMessages([
                'action_plan_ids' => 'Sebagian action plan tidak ditemukan pada temuan ini.',
            ]);
        }

        $notSuitable = $aps->filter(fn (ActionPlan $ap) => $ap->status !== ActionPlanStatus::Sesuai);
        if ($notSuitable->isNotEmpty()) {
            throw ValidationException::withMessages([
                'action_plan_ids' => 'Hanya action plan berstatus Sesuai yang dapat dipilih untuk diperbaiki.',
            ]);
        }

        $record->actionPlans()->sync($actionPlanIds);

        foreach ($aps as $ap) {
            $nextRevision = $ap->current_revision + 1;

            $ap->update([
                'status' => ActionPlanStatus::RevisiSpi,
                'current_revision' => $nextRevision,
            ]);

            ActionPlanRevision::create([
                'action_plan_id' => $ap->id,
                'revision_no' => $nextRevision,
                'requested_by' => auth()->id(),
                'requested_role' => Role::Kepala_spi->value,
                'source' => RevisionSource::ExternalStatus,
                'reason' => $note ?: 'Perbaikan berdasarkan status auditor eksternal.',
                'requested_at' => now(),
                'new_deadline' => $newDeadline,
            ]);

            (new ProgressService())->persistApProgress($ap);
        }

        (new FindingStatusService())->recompute($finding);

        CacheService::flushGroup('action_plans');
        CacheService::flushGroup('findings');
    }

    private function assertKepalaSpi(): void
    {
        $user = auth()->user();
        if (!in_array($user->role, [Role::Kepala_spi, Role::SuperAdmin], true)) {
            throw ValidationException::withMessages([
                'finding_id' => 'Hanya Kepala SPI yang dapat mencatat status auditor eksternal.',
            ]);
        }
    }
}