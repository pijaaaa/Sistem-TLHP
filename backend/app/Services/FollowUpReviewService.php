<?php

namespace App\Services;

use App\Enums\CommentKind;
use App\Enums\FollowUpStatus;
use App\Enums\ReviewDecision;
use App\Enums\Role;
use App\Events\CompletionDecided;
use App\Events\FollowUpDecided;
use App\Events\FollowUpReturnedToRevision;
use App\Events\IaCommentAdded;
use App\Models\FollowUp;
use App\Models\FollowUpComment;
use App\Models\FollowUpReview;
use App\Models\User;
use App\Support\AuditLogger;
use App\Services\TaskDispatcher;
use Illuminate\Validation\ValidationException;

class FollowUpReviewService
{
    public function approve(FollowUp $followUp): FollowUp
    {
        $this->assertManager($followUp);
        $this->assertStatus($followUp, FollowUpStatus::Diajukan, 'Hanya tindak lanjut berstatus Diajukan yang dapat disetujui.');

        $followUp->update([
            'status' => FollowUpStatus::Disetujui,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $this->record($followUp, ReviewDecision::Setujui, null);
        $this->audit('follow_up.approved', $followUp, 'Tindak lanjut disetujui manager.');
        FollowUpDecided::dispatch($followUp, ReviewDecision::Setujui);

        (new ProgressService())->persistApProgress($followUp->actionPlan);

        return $followUp->refresh();
    }

    public function requestRevision(FollowUp $followUp, string $note): FollowUp
    {
        $this->assertManager($followUp);
        $this->assertStatus($followUp, FollowUpStatus::Diajukan, 'Hanya tindak lanjut berstatus Diajukan yang dapat direvisi.');

        $followUp->update(['status' => FollowUpStatus::Revisi]);

        $this->record($followUp, ReviewDecision::Revisi, $note);
        $this->audit('follow_up.revision_requested', $followUp, 'Manager meminta revisi tindak lanjut.');
        FollowUpDecided::dispatch($followUp, ReviewDecision::Revisi);

        return $followUp->refresh();
    }

    public function reject(FollowUp $followUp, string $note): FollowUp
    {
        $this->assertManager($followUp);
        $this->assertStatus($followUp, FollowUpStatus::Diajukan, 'Hanya tindak lanjut berstatus Diajukan yang dapat ditolak.');

        $followUp->update(['status' => FollowUpStatus::Ditolak]);

        $this->record($followUp, ReviewDecision::Tolak, $note);
        $this->audit('follow_up.rejected', $followUp, 'Tindak lanjut ditolak manager.');
        FollowUpDecided::dispatch($followUp, ReviewDecision::Tolak);

        (new ProgressService())->persistApProgress($followUp->actionPlan);

        return $followUp->refresh();
    }

    public function returnToRevision(FollowUp $followUp, string $note): FollowUp
    {
        $this->assertManager($followUp);

        if ($followUp->status !== FollowUpStatus::Disetujui) {
            throw ValidationException::withMessages([
                'status' => 'Hanya tindak lanjut Disetujui yang dapat dikembalikan ke Revisi.',
            ]);
        }

        // Progres tetap dipertahankan; hanya status yang dikembalikan.
        $followUp->update(['status' => FollowUpStatus::Revisi]);

        $this->record($followUp, ReviewDecision::KembaliRevisi, $note);
        $this->audit('follow_up.returned_to_revision', $followUp, 'Tindak lanjut dikembalikan ke revisi atas masukan IA.');
        FollowUpReturnedToRevision::dispatch($followUp);

        (new ProgressService())->persistApProgress($followUp->actionPlan);

        return $followUp->refresh();
    }

    public function overrideWeight(FollowUp $followUp, int $weight): FollowUp
    {
        $this->assertManager($followUp);

        if ($followUp->status === FollowUpStatus::Selesai) {
            throw ValidationException::withMessages([
                'status' => 'Bobot tindak lanjut Selesai tidak dapat diubah.',
            ]);
        }

        $available = (new FollowUpService())->availableWeight($followUp->actionPlan, $followUp->id);

        if ($weight > $available) {
            throw ValidationException::withMessages([
                'weight' => "Total bobot tindak lanjut aktif melebihi 100. Sisa bobot yang tersedia: {$available}.",
            ]);
        }

        $old = $followUp->weight;
        $followUp->update(['weight' => $weight]);

        $this->record($followUp, ReviewDecision::OverrideBobot, null, $old, $weight);
        $this->audit('follow_up.weight_overridden', $followUp, "Bobot tindak lanjut diubah {$old} → {$weight}.", [
            'weight_before' => $old,
            'weight_after' => $weight,
        ]);

        (new ProgressService())->persistApProgress($followUp->actionPlan);

        return $followUp->refresh();
    }

    public function approveCompletion(FollowUp $followUp): FollowUp
    {
        $this->assertManager($followUp);

        if ($followUp->status !== FollowUpStatus::MenungguPersetujuanSelesai) {
            throw ValidationException::withMessages([
                'status' => 'Hanya tindak lanjut berstatus Menunggu Persetujuan Selesai yang dapat diselesaikan.',
            ]);
        }

        $followUp->update([
            'status' => FollowUpStatus::Selesai,
            'completed_at' => now(),
        ]);

        $this->record($followUp, ReviewDecision::Selesaikan, null);
        $this->audit('follow_up.completion_approved', $followUp, 'Penyelesaian tindak lanjut disetujui.');
        CompletionDecided::dispatch($followUp, true);

        (new ProgressService())->persistApProgress($followUp->actionPlan);

        return $followUp->refresh();
    }

    public function requestCompletionRevision(FollowUp $followUp, string $note): FollowUp
    {
        $this->assertManager($followUp);

        if ($followUp->status !== FollowUpStatus::MenungguPersetujuanSelesai) {
            throw ValidationException::withMessages([
                'status' => 'Hanya tindak lanjut berstatus Menunggu Persetujuan Selesai yang dapat diminta revisi penyelesaian.',
            ]);
        }

        // Progres tetap 100; PIC menambah laporan/dokumen lalu melapor 100 lagi.
        $followUp->update(['status' => FollowUpStatus::Disetujui]);

        $this->record($followUp, ReviewDecision::RevisiSelesai, $note);
        $this->audit('follow_up.completion_revision_requested', $followUp, 'Manager meminta revisi penyelesaian tindak lanjut.');
        CompletionDecided::dispatch($followUp, false);

        (new ProgressService())->persistApProgress($followUp->actionPlan);

        return $followUp->refresh();
    }

    public function addComment(FollowUp $followUp, CommentKind $kind, string $body): FollowUpComment
    {
        $user = auth()->user();

        if ($kind === CommentKind::IaComment) {
            if ($user->role !== Role::InternalAudit && $user->role !== Role::SuperAdmin) {
                throw ValidationException::withMessages([
                    'kind' => 'Hanya Internal Audit yang dapat menambah komentar IA.',
                ]);
            }

            if (!$followUp->status->isVisibleToMonitor()) {
                throw ValidationException::withMessages([
                    'status' => 'Komentar IA hanya dapat ditambahkan pada tindak lanjut yang sudah disetujui manager.',
                ]);
            }
        } else {
            $isPic = $followUp->assignees()->whereKey($user->id)->exists();
            $isManager = $user->role === Role::ManagerDept
                && $followUp->actionPlan->department_id === $user->department_id;
            $isMonitorAfterApproval = $user->role->isMonitor() && $followUp->status->isVisibleToMonitor();
            if ($user->role !== Role::SuperAdmin && !$isPic && !$isManager && !$isMonitorAfterApproval) {
                throw ValidationException::withMessages([
                    'kind' => 'Tidak berhak menambah komentar pada tindak lanjut ini.',
                ]);
            }
        }

        $comment = $followUp->comments()->create([
            'author_id' => $user->id,
            'kind' => $kind,
            'body' => $body,
        ]);

        $this->audit('follow_up.comment_added', $followUp, 'Komentar ditambahkan pada tindak lanjut.');
        if ($kind === CommentKind::IaComment) {
            IaCommentAdded::dispatch($comment);
        }

        return $comment->load('author');
    }

    private function record(FollowUp $followUp, ReviewDecision $decision, ?string $note, ?int $before = null, ?int $after = null): void
    {
        FollowUpReview::create([
            'follow_up_id' => $followUp->id,
            'reviewer_id' => auth()->id(),
            'decision' => $decision,
            'note' => $note,
            'weight_before' => $before ?? $followUp->weight,
            'weight_after' => $after ?? $followUp->weight,
        ]);

        TaskDispatcher::completeForActor($followUp);
    }

    private function assertManager(FollowUp $followUp): void
    {
        if ($followUp->actionPlan->isFrozen()) {
            throw ValidationException::withMessages([
                'follow_up_id' => 'Action plan Sesuai/Closed beku; keputusan tidak dapat diambil.',
            ]);
        }

        $user = auth()->user();
        if ($user->role === Role::SuperAdmin) {
            return;
        }

        if ($user->role !== Role::ManagerDept || $followUp->actionPlan->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'follow_up_id' => 'Hanya manager departemen pemilik action plan yang dapat mengambil keputusan ini.',
            ]);
        }
    }

    private function assertStatus(FollowUp $followUp, FollowUpStatus $expected, string $message): void
    {
        if ($followUp->status !== $expected) {
            throw ValidationException::withMessages(['status' => $message]);
        }
    }

    private function audit(string $action, FollowUp $followUp, string $description, ?array $data = null): void
    {
        AuditLogger::log($action, auth()->id(), request()?->ip(), $description, array_merge([
            'follow_up_id' => $followUp->id,
            'action_plan_id' => $followUp->action_plan_id,
        ], $data ?? []));
    }
}