<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Events\ActionPlanSent;
use App\Events\ActionPlanSubmittedToSpi;
use App\Events\PicAssigned;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Models\FollowUp;
use App\Services\FindingStatusService;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ActionPlanService
{
    public function createForDepartments(Finding $finding, array $data, array $department_ids): array
    {
        if (!in_array($finding->status, [FindingStatus::Terdaftar, FindingStatus::ProsessTindakLanjut])) {
            throw ValidationException::withMessages([
                'status' => 'Temuan harus berstatus Terdaftar atau Proses Tindak Lanjut.',
            ]);
        }

        if (empty($department_ids)) {
            throw ValidationException::withMessages([
                'departments' => 'Minimal satu departemen auditee harus dipilih.',
            ]);
        }

        foreach ($department_ids as $dept_id) {
            if (!$finding->auditee_departments()->whereKey($dept_id)->exists()) {
                throw ValidationException::withMessages([
                    'departments' => "Departemen $dept_id bukan auditee temuan ini.",
                ]);
            }
        }

        unset($data['finding_id'], $data['department_ids'], $data['deadline_per_department']);
        $deadline_per_department = request()->input('deadline_per_department', []);

        $action_plans = [];
        foreach ($department_ids as $dept_id) {
            $code = $this->generateCode($finding);
            $ap_data = array_merge($data, [
                'finding_id' => $finding->id,
                'department_id' => $dept_id,
                'code' => $code,
                'status' => ActionPlanStatus::Draft,
                'created_by' => auth()->id(),
            ]);

            if (!empty($deadline_per_department[$dept_id])) {
                $ap_data['deadline'] = $deadline_per_department[$dept_id];
            }

            $ap = ActionPlan::create($ap_data);

            AuditLogger::log('action_plan.created', auth()->id(), request()->ip(), "Action plan {$ap->code} dibuat.", [
                'action_plan_id' => $ap->id,
                'finding_id' => $finding->id,
            ]);

            $action_plans[] = $ap;
        }

        return $action_plans;
    }

    public function send(array $ap_ids): array
    {
        $action_plans = ActionPlan::whereIn('id', $ap_ids)->get();

        if ($action_plans->count() !== count($ap_ids)) {
            throw ValidationException::withMessages([
                'ids' => 'Sebagian action plan tidak ditemukan.',
            ]);
        }

        foreach ($action_plans as $ap) {
            if ($ap->status !== ActionPlanStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Semua action plan harus berstatus DRAFT.',
                ]);
            }
        }

        foreach ($action_plans as $ap) {
            $ap->update([
                'status' => ActionPlanStatus::MenungguPenentuanPic,
                'sent_at' => now(),
            ]);

            AuditLogger::log('action_plan.sent', auth()->id(), request()->ip(), "Action plan {$ap->code} dikirim.", [
                'action_plan_id' => $ap->id,
                'finding_id' => $ap->finding_id,
            ]);

            ActionPlanSent::dispatch($ap);
        }

        return $action_plans->all();
    }

    public function assignPics(ActionPlan $ap, array $user_ids): ActionPlan
    {
        if ($ap->isFrozen()) {
            throw ValidationException::withMessages([
                'status' => 'Action plan Sesuai/Closed beku dan tidak dapat diubah.',
            ]);
        }

        if (empty($user_ids)) {
            throw ValidationException::withMessages([
                'pics' => 'Minimal satu PIC harus ditunjuk.',
            ]);
        }

        if ($ap->status === ActionPlanStatus::Closed) {
            throw ValidationException::withMessages([
                'status' => 'Action plan Closed tidak dapat diubah.',
            ]);
        }

        $users = \App\Models\User::whereIn('id', $user_ids)
            ->where('department_id', $ap->department_id)
            ->where('role', 'staff_dept')
            ->where('is_active', true)
            ->get();

        if ($users->count() !== count($user_ids)) {
            throw ValidationException::withMessages([
                'pics' => 'Semua PIC harus staff_dept aktif di departemen terkait.',
            ]);
        }

        $existing = $ap->assignees()->pluck('users.id')->all();
        $added = array_values(array_diff($user_ids, $existing));
        $removed = array_values(array_diff($existing, $user_ids));

        foreach ($removed as $pic_id) {
            if ($this->picHasActiveFollowUps($ap, $pic_id)) {
                throw ValidationException::withMessages([
                    'pics' => 'PIC yang masih memiliki tindak lanjut aktif tidak dapat dicabut.',
                ]);
            }
        }

        $ap->assignees()->syncWithPivotValues($user_ids, ['assigned_by' => auth()->id()]);

        if ($ap->status === ActionPlanStatus::MenungguPenentuanPic) {
            $ap->update(['status' => ActionPlanStatus::ProsesTindakLanjut]);
        }

        AuditLogger::log('action_plan.pics_assigned', auth()->id(), request()->ip(), "PIC action plan {$ap->code} ditentukan.", [
            'action_plan_id' => $ap->id,
            'finding_id' => $ap->finding_id,
        ]);

        PicAssigned::dispatch($ap, $added);

        return $ap->refresh();
    }

    public function update(ActionPlan $ap, array $data): ActionPlan
    {
        if ($ap->status !== ActionPlanStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Hanya action plan Draft yang dapat diubah. Setelah terkirim, hanya admin SPI yang boleh mengubah deadline.',
            ]);
        }

        $old = $ap->only(array_keys($data));
        $ap->update($data);

        AuditLogger::log('action_plan.updated', auth()->id(), request()?->ip(), "Action plan {$ap->code} diubah.", [
            'action_plan_id' => $ap->id,
            'finding_id' => $ap->finding_id,
            'old' => $old,
            'new' => $ap->only(array_keys($data)),
        ]);

        return $ap->refresh();
    }

    public function destroy(ActionPlan $ap): void
    {
        if ($ap->status !== ActionPlanStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Hanya action plan Draft yang dapat dihapus.',
            ]);
        }

        $ap->delete();

        AuditLogger::log('action_plan.deleted', auth()->id(), request()?->ip(), "Action plan {$ap->code} dihapus.", [
            'action_plan_id' => $ap->id,
            'finding_id' => $ap->finding_id,
        ]);
    }

    public function submitToSpi(ActionPlan $ap): ActionPlan
    {
        if ($ap->status !== ActionPlanStatus::ProsesTindakLanjut) {
            throw ValidationException::withMessages([
                'status' => 'Hanya action plan berstatus Proses Tindak Lanjut yang dapat diajukan ke Admin SPI.',
            ]);
        }

        $followUps = FollowUp::withoutGlobalScopes()
            ->where('action_plan_id', $ap->id)
            ->where('revision_no', $ap->current_revision)
            ->where('status', '!=', \App\Enums\FollowUpStatus::Ditolak->value)
            ->get(['status', 'weight']);

        if ($followUps->isEmpty()) {
            throw ValidationException::withMessages([
                'follow_ups' => 'Belum ada tindak lanjut pada action plan ini.',
            ]);
        }

        $notFinished = $followUps->filter(function ($fu) {
            $status = $fu->status instanceof \App\Enums\FollowUpStatus ? $fu->status->value : $fu->status;
            return $status !== \App\Enums\FollowUpStatus::Selesai->value;
        });
        if ($notFinished->isNotEmpty()) {
            throw ValidationException::withMessages([
                'follow_ups' => 'Semua tindak lanjut harus berstatus Selesai sebelum diajukan ke Admin SPI.',
            ]);
        }

        $totalWeight = (int) $followUps->sum('weight');
        if ($totalWeight !== 100) {
            throw ValidationException::withMessages([
                'weight' => "Total bobot harus tepat 100. Bobot saat ini: {$totalWeight}.",
            ]);
        }

        $ap->update(['status' => ActionPlanStatus::DiajukanKeSpi]);

        AuditLogger::log('action_plan.submitted_to_spi', auth()->id(), request()?->ip(), "Action plan {$ap->code} diajukan ke Admin SPI.", [
            'action_plan_id' => $ap->id,
            'finding_id' => $ap->finding_id,
        ]);

        ActionPlanSubmittedToSpi::dispatch($ap);

        (new FindingStatusService())->recompute($ap->finding);

        return $ap->refresh();
    }

    public function changeDeadline(ActionPlan $ap, string $deadline, ?string $reason = null): ActionPlan
    {
        if ($ap->isFrozen()) {
            throw ValidationException::withMessages([
                'status' => 'Action plan Sesuai/Closed beku dan tidak dapat diubah.',
            ]);
        }

        if ($ap->status === ActionPlanStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Deadline action plan Draft diubah lewat formulir, bukan endpoint ini.',
            ]);
        }

        $old = $ap->deadline?->toDateString();
        $ap->update(['deadline' => $deadline]);

        AuditLogger::log('action_plan.deadline_changed', auth()->id(), request()->ip(), "Deadline action plan {$ap->code} diubah.", [
            'action_plan_id' => $ap->id,
            'finding_id' => $ap->finding_id,
            'old' => ['deadline' => $old],
            'new' => ['deadline' => $deadline],
            'reason' => $reason,
        ]);

        return $ap->refresh();
    }

    private function picHasActiveFollowUps(ActionPlan $ap, int $user_id): bool
    {
        // follow_ups belum ada sampai R4; sampai tabel tersedia otomatis lolos.
        if (!Schema::hasTable('follow_ups') || !Schema::hasTable('follow_up_assignees')) {
            return false;
        }

        return \Illuminate\Support\Facades\DB::table('follow_ups')
            ->join('follow_up_assignees', 'follow_up_assignees.follow_up_id', '=', 'follow_ups.id')
            ->where('follow_ups.action_plan_id', $ap->id)
            ->where('follow_up_assignees.user_id', $user_id)
            ->whereNotIn('follow_ups.status', ['DITOLAK', 'SELESAI'])
            ->exists();
    }

    private function generateCode(Finding $finding): string
    {
        $count = ActionPlan::withTrashed()->where('finding_id', $finding->id)->count();
        return "{$finding->registration_number}/AP-" . str_pad($count + 1, 2, '0', STR_PAD_LEFT);
    }
}
