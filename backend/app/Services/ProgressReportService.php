<?php

namespace App\Services;

use App\Enums\FollowUpStatus;
use App\Enums\Role;
use App\Events\CompletionRequested;
use App\Events\ProgressReported;
use App\Models\FollowUp;
use App\Models\FollowUpProgressReport;
use App\Support\AuditLogger;
use Illuminate\Validation\ValidationException;

class ProgressReportService
{
    /**
     * @param  array<int, array{file: \Illuminate\Http\UploadedFile, label: string}>  $documents
     */
    public function report(FollowUp $followUp, int $value, ?string $note, array $documents = []): FollowUpProgressReport
    {
        $this->assertPicActor($followUp);

        if (!in_array($followUp->status, [FollowUpStatus::Disetujui, FollowUpStatus::MenungguPersetujuanSelesai], true)) {
            throw ValidationException::withMessages([
                'status' => 'Progres hanya dapat dilaporkan pada tindak lanjut yang sudah disetujui.',
            ]);
        }

        if ($value < $followUp->progress) {
            throw ValidationException::withMessages([
                'progress_value' => "Progres tidak boleh turun. Nilai terakhir: {$followUp->progress}.",
            ]);
        }

        $report = $followUp->progress_reports()->create([
            'progress_value' => $value,
            'note' => $note,
            'reported_by' => auth()->id(),
            'reported_at' => now(),
        ]);

        foreach ($documents as $doc) {
            $file = $doc['file'];
            $report->documents()->create([
                'label' => $doc['label'],
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('follow-up-progress', config('upload.disk')),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        $isComplete = $value === 100;
        $followUp->update([
            'progress' => $value,
            'status' => $isComplete ? FollowUpStatus::MenungguPersetujuanSelesai : FollowUpStatus::Disetujui,
        ]);

        AuditLogger::log('follow_up.progress_reported', auth()->id(), request()?->ip(), "Progres tindak lanjut dilaporkan: {$value}%.", [
            'follow_up_id' => $followUp->id,
            'action_plan_id' => $followUp->action_plan_id,
        ]);

        $isComplete ? CompletionRequested::dispatch($followUp) : ProgressReported::dispatch($followUp, $value);

        (new ProgressService())->persistApProgress($followUp->actionPlan);

        return $report->load('documents');
    }

    private function assertPicActor(FollowUp $followUp): void
    {
        if ($followUp->actionPlan->isFrozen()) {
            throw ValidationException::withMessages([
                'follow_up_id' => 'Action plan Sesuai/Closed beku; progres tidak dapat dilaporkan.',
            ]);
        }

        $user = auth()->user();
        if ($user->role === Role::SuperAdmin) {
            return;
        }

        $isAssignee = $followUp->assignees()->whereKey($user->id)->exists();
        $isCreator = $followUp->created_by === $user->id;

        if ($user->role !== Role::StaffDept || (!$isAssignee && !$isCreator)) {
            throw ValidationException::withMessages([
                'follow_up_id' => 'Hanya PIC pembuat/penanggung jawab tindak lanjut yang dapat melaporkan progres.',
            ]);
        }
    }
}