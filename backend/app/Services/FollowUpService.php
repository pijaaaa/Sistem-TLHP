<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\FollowUpStatus;
use App\Enums\Role;
use App\Events\FollowUpSubmitted;
use App\Models\ActionPlan;
use App\Models\FollowUp;
use App\Models\User;
use App\Support\AuditLogger;
use App\Services\TaskDispatcher;
use Illuminate\Validation\ValidationException;

class FollowUpService
{
    private const MAX_TOTAL_WEIGHT = 100;

    public function createBatch(ActionPlan $actionPlan, array $rows): array
    {
        if ($actionPlan->status !== ActionPlanStatus::ProsesTindakLanjut) {
            throw ValidationException::withMessages([
                'action_plan_id' => 'Action plan harus berstatus Proses Tindak Lanjut untuk menyusun tindak lanjut.',
            ]);
        }

        $this->assertNotFrozen($actionPlan);

        $this->assertActorIsPic($actionPlan);

        if (empty($rows)) {
            throw ValidationException::withMessages([
                'rows' => 'Minimal satu tindak lanjut harus diisi.',
            ]);
        }

        $available = $this->availableWeight($actionPlan);

        $created = [];
        foreach ($rows as $row) {
            $available = $this->validateRow($actionPlan, $row, $available);

            $followUp = FollowUp::create([
                'action_plan_id' => $actionPlan->id,
                'revision_no' => $actionPlan->current_revision,
                'description' => $row['description'],
                'target_date' => $row['target_date'],
                'weight' => $row['weight'],
                'status' => FollowUpStatus::Draft,
                'linked_follow_up_id' => $row['linked_follow_up_id'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $followUp->assignees()->sync($row['pic_ids']);

            AuditLogger::log('follow_up.created', auth()->id(), request()?->ip(), 'Tindak lanjut draft dibuat.', [
                'follow_up_id' => $followUp->id,
                'action_plan_id' => $actionPlan->id,
            ]);

            $created[] = $followUp->load('assignees');
        }

        TaskDispatcher::completeForActor($actionPlan);

        return $created;
    }

    public function update(FollowUp $followUp, array $data): FollowUp
    {
        $this->assertNotFrozen($followUp->actionPlan);

        if (!$followUp->status->isEditable()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya tindak lanjut Draft atau Revisi yang dapat diubah.',
            ]);
        }

        $this->assertPicActor($followUp);

        $old = collect(['description', 'target_date', 'weight'])
            ->mapWithKeys(fn ($f) => [$f => $followUp->getAttribute($f)])
            ->all();

        if (isset($data['weight']) && (int) $data['weight'] !== $followUp->weight) {
            $available = $this->availableWeight($followUp->actionPlan, $followUp->id);
            if ($data['weight'] > $available) {
                throw ValidationException::withMessages([
                    'weight' => "Total bobot tindak lanjut aktif melebihi " . self::MAX_TOTAL_WEIGHT . ". Sisa bobot yang tersedia: {$available}.",
                ]);
            }
        }

        if (isset($data['target_date'])) {
            $this->assertDateInsideDeadline($followUp->actionPlan, $data['target_date']);
        }

        if (isset($data['linked_follow_up_id']) && $data['linked_follow_up_id']) {
            $this->assertLinkedFollowUp($followUp->actionPlan, $data['linked_follow_up_id'], $followUp->id);
        }

        $followUp->update(collect($data)->only(['description', 'target_date', 'weight', 'linked_follow_up_id'])->all());

        if (isset($data['pic_ids'])) {
            $followUp->assignees()->sync($data['pic_ids']);
        }

        AuditLogger::log('follow_up.updated', auth()->id(), request()?->ip(), 'Tindak lanjut diubah.', [
            'follow_up_id' => $followUp->id,
            'action_plan_id' => $followUp->action_plan_id,
            'old' => $old,
        ]);

        (new ProgressService())->persistApProgress($followUp->actionPlan);
        TaskDispatcher::completeForActor($followUp);

        return $followUp->refresh();
    }

    public function submit(array $ids): array
    {
        $followUps = FollowUp::whereIn('id', $ids)->with('actionPlan')->get();

        if ($followUps->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'ids' => 'Sebagian tindak lanjut tidak ditemukan.',
            ]);
        }

        foreach ($followUps as $followUp) {
            $this->assertNotFrozen($followUp->actionPlan);

            if (!$followUp->status->isEditable()) {
                throw ValidationException::withMessages([
                    'status' => "Tindak lanjut #{$followUp->id} tidak berstatus Draft atau Revisi.",
                ]);
            }

            $this->assertPicActor($followUp);
        }

        foreach ($followUps as $followUp) {
            $followUp->update(['status' => FollowUpStatus::Diajukan]);

            AuditLogger::log('follow_up.submitted', auth()->id(), request()?->ip(), 'Tindak lanjut diajukan.', [
                'follow_up_id' => $followUp->id,
                'action_plan_id' => $followUp->action_plan_id,
            ]);

            FollowUpSubmitted::dispatch($followUp);
            TaskDispatcher::completeForActor($followUp);
        }

        return $followUps->all();
    }

    private function validateRow(ActionPlan $actionPlan, array $row, int $available): int
    {
        $this->assertDateInsideDeadline($actionPlan, $row['target_date']);

        if ($row['weight'] > $available) {
            throw ValidationException::withMessages([
                'weight' => "Total bobot tindak lanjut aktif melebihi " . self::MAX_TOTAL_WEIGHT . ". Sisa bobot yang tersedia: {$available}.",
            ]);
        }

        if (isset($row['linked_follow_up_id']) && $row['linked_follow_up_id']) {
            $this->assertLinkedFollowUp($actionPlan, $row['linked_follow_up_id']);
        }

        $this->validatePics($actionPlan, $row['pic_ids']);

        return $available - (int) $row['weight'];
    }

    /** Jumlah bobot TL aktif pada AP + revisi berjalan (DITOLAK tidak dihitung). */
    public function activeWeight(ActionPlan $actionPlan): int
    {
        return (int) FollowUp::withoutGlobalScopes()
            ->where('action_plan_id', $actionPlan->id)
            ->where('revision_no', $actionPlan->current_revision)
            ->where('status', '!=', FollowUpStatus::Ditolak->value)
            ->sum('weight');
    }

    public function availableWeight(ActionPlan $actionPlan, ?int $excludingId = null): int
    {
        $used = $this->activeWeight($actionPlan);
        if ($excludingId) {
            $used -= (int) FollowUp::withoutGlobalScopes()->whereKey($excludingId)->value('weight');
        }

        return max(0, self::MAX_TOTAL_WEIGHT - (int) $used);
    }

    private function assertDateInsideDeadline(ActionPlan $actionPlan, string $targetDate): void
    {
        if ($actionPlan->deadline && $targetDate > $actionPlan->deadline->toDateString()) {
            throw ValidationException::withMessages([
                'target_date' => "Target tanggal tidak boleh melewati deadline action plan ({$actionPlan->deadline->toDateString()}).",
            ]);
        }
    }

    private function assertLinkedFollowUp(ActionPlan $actionPlan, int $linkedId, ?int $currentId = null): void
    {
        $linked = FollowUp::withoutGlobalScopes()->find($linkedId);
        if (!$linked
            || $linked->action_plan_id !== $actionPlan->id
            || $linked->revision_no >= $actionPlan->current_revision
            || ($currentId && $linkedId === $currentId)
        ) {
            throw ValidationException::withMessages([
                'linked_follow_up_id' => 'Tautan revisi hanya valid ke tindak lanjut pada action plan yang sama dari revisi sebelumnya.',
            ]);
        }
    }

    private function validatePics(ActionPlan $actionPlan, array $picIds): void
    {
        if (empty($picIds)) {
            throw ValidationException::withMessages([
                'pic_ids' => 'Minimal satu PIC harus ditunjuk.',
            ]);
        }

        $valid = $actionPlan->assignees()->whereIn('users.id', $picIds)->pluck('users.id')->all();

        if (count($valid) !== count(array_unique($picIds))) {
            throw ValidationException::withMessages([
                'pic_ids' => 'Semua PIC tindak lanjut harus merupakan PIC yang ditunjuk pada action plan.',
            ]);
        }
    }

    private function assertNotFrozen(ActionPlan $actionPlan): void
    {
        if ($actionPlan->isFrozen()) {
            throw ValidationException::withMessages([
                'action_plan_id' => 'Action plan Sesuai/Closed beku; tindak lanjut di dalamnya tidak dapat diubah.',
            ]);
        }
    }

    private function assertPicActor(FollowUp $followUp): void
    {
        $this->assertNotFrozen($followUp->actionPlan);

        $user = auth()->user();
        if ($user->role === Role::SuperAdmin) {
            return;
        }

        $isAssignee = $followUp->assignees()->whereKey($user->id)->exists();
        $isCreator = $followUp->created_by === $user->id;

        if ($user->role !== Role::StaffDept || (!$isAssignee && !$isCreator)) {
            throw ValidationException::withMessages([
                'follow_up_id' => 'Hanya PIC pembuat/penanggung jawab tindak lanjut yang dapat melakukan aksi ini.',
            ]);
        }
    }

    private function assertActorIsPic(ActionPlan $actionPlan): void
    {
        $this->assertNotFrozen($actionPlan);

        $user = auth()->user();
        if ($user->role === Role::SuperAdmin) {
            return;
        }

        if ($user->role !== Role::StaffDept || !$actionPlan->assignees()->whereKey($user->id)->exists()) {
            throw ValidationException::withMessages([
                'action_plan_id' => 'Hanya PIC yang ditunjuk pada action plan yang dapat menyusun tindak lanjut.',
            ]);
        }
    }
}