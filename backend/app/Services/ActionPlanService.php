<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\EvidenceStatus;
use App\Enums\FindingDepartmentStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\ActionPlanDocument;
use App\Models\EvidenceSubmission;
use App\Models\Finding;
use App\Models\FindingDepartment;
use App\Support\AuditLogger;
use App\Support\CacheService;
use Illuminate\Http\UploadedFile;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ActionPlanService
{
    public static function paginated(int $perPage = 15, ?FindingDepartment $fd = null): LengthAwarePaginator
    {
        $user = auth()->user();
        $query = ActionPlan::query();

        if ($fd) {
            $query->where('finding_department_id', $fd->id);
        } else {
            $query->whereHas('findingDepartment', function ($q) use ($user) {
                if ($user->role === Role::ManagerDept) {
                    $q->where('department_id', $user->department_id);
                } elseif ($user->role === Role::StaffDept) {
                    $q->whereHas('pics', fn ($picQ) => $picQ->where('user_id', $user->id));
                } elseif ($user->role === Role::ManagerSpi) {
                    $q->whereHas('finding', fn ($f) => $f->whereIn('status', [
                        \App\Enums\FindingStatus::Closed->value,
                        \App\Enums\FindingStatus::CaseClosed->value,
                    ]));
                }
            });
        }

        return $query->withCount('documents')
            ->with(['findingDepartment', 'creator'])
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public static function invalidate(): void
    {
        CacheService::incrementGroupVersion('findings');
    }

    public static function create(FindingDepartment $fd, array $data): ActionPlan
    {
        $user = auth()->user();

        if ($user->role === Role::StaffDept) {
            if (! $fd->pics()->where('user_id', $user->id)->exists()) {
                throw new AuthorizationException('Anda bukan PIC dari departemen temuan ini.');
            }
        } elseif ($user->role === Role::ManagerDept && $fd->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'department' => 'Anda tidak berhak untuk departemen ini.',
            ]);
        }

        $data['finding_department_id'] = $fd->id;
        $data['created_by'] = $user->id;
        $data['status'] = ActionPlanStatus::Draft;

        return DB::transaction(function () use ($fd, $data) {
            $ap = ActionPlan::create($data);
            self::invalidate();

            $picUsers = $fd->pics;
            if ($picUsers->isNotEmpty()) {
                foreach ($picUsers as $pic) {
                    AuditLogger::log('action_plan.created', $pic->id, request()->ip(), 'Rencana aksi dibuat untuk PIC.', [
                        'action_plan_id' => $ap->id,
                        'finding_department_id' => $fd->id,
                    ]);
                }
            }

            return $ap->fresh();
        });
    }

    public static function update(ActionPlan $ap, array $data): ActionPlan
    {
        $user = auth()->user();

        if ($user->role === Role::StaffDept && $ap->created_by !== $user->id) {
            throw ValidationException::withMessages([
                'pic' => 'Anda tidak berhak mengubah rencana aksi ini.',
            ]);
        }

        if ($user->role === Role::ManagerDept) {
            $ap->load('findingDepartment');
            if ($ap->findingDepartment->department_id !== $user->department_id) {
                throw ValidationException::withMessages([
                    'department' => 'Anda tidak berhak untuk departemen ini.',
                ]);
            }
        }

        $ap->fill($data);

        if (isset($data['status']) && $data['status'] !== $ap->getOriginal('status')) {
            $ap->status = ActionPlanStatus::from($data['status']);
        }

        $ap->save();
        self::invalidate();
        return $ap->fresh();
    }

    public static function delete(ActionPlan $ap): void
    {
        $user = auth()->user();

        if ($user->role === Role::StaffDept && $ap->created_by !== $user->id) {
            throw ValidationException::withMessages([
                'pic' => 'Anda tidak berhak menghapus rencana aksi ini.',
            ]);
        }

        $ap->delete();
        self::invalidate();
    }

    public static function submit(ActionPlan $ap): ActionPlan
    {
        $user = auth()->user();

        if ($ap->created_by !== $user->id && ! in_array($user->role, [Role::ManagerDept, Role::AdminSpi, Role::SuperAdmin])) {
            throw ValidationException::withMessages([
                'pic' => 'Hanya PIC yang dapat mengajukan rencana aksi ini.',
            ]);
        }

        if ($ap->status !== ActionPlanStatus::Draft && $ap->status !== ActionPlanStatus::Revision) {
            throw ValidationException::withMessages([
                'status' => 'Hanya rencana aksi dalam status Draft atau Revisi yang dapat diajukan.',
            ]);
        }

        $totalWeight = self::activeWeightForDepartment($ap->finding_department_id, null);
        if ($totalWeight != 100) {
            throw ValidationException::withMessages([
                'weight' => 'Total bobot tindak lanjut aktif harus tepat 100%. Saat ini: ' . number_format((float) $totalWeight, 2) . '%.',
            ]);
        }

        return DB::transaction(function () use ($ap) {
            $ap->status = ActionPlanStatus::Submitted;
            $ap->save();

            AuditLogger::log('action_plan.submitted', auth()->id(), request()->ip(), 'Rencana aksi diajukan.', [
                'action_plan_id' => $ap->id,
            ]);

            self::invalidate();
            return $ap->fresh();
        });
    }

    public static function approve(ActionPlan $ap): ActionPlan
    {
        $user = auth()->user();

        if (! in_array($user->role, [Role::ManagerDept, Role::AdminSpi, Role::SuperAdmin])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Manager Departemen yang dapat menyetujui rencana aksi.',
            ]);
        }

        $ap->load('findingDepartment');
        if ($user->role === Role::ManagerDept && $ap->findingDepartment->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'department' => 'Anda tidak berhak untuk departemen ini.',
            ]);
        }

        if ($ap->status !== ActionPlanStatus::Submitted) {
            throw ValidationException::withMessages([
                'status' => 'Hanya rencana aksi dalam status Diajukan yang dapat disetujui.',
            ]);
        }

        $totalWeight = self::activeWeightForDepartment($ap->finding_department_id, null);
        if ($totalWeight != 100) {
            throw ValidationException::withMessages([
                'weight' => 'Total bobot tindak lanjut aktif harus tepat 100%. Saat ini: ' . number_format((float) $totalWeight, 2) . '%.',
            ]);
        }

        return DB::transaction(function () use ($ap, $user) {
            $ap->status = ActionPlanStatus::WaitingEvidence;
            $ap->approved_by = $user->id;
            $ap->approved_at = now();
            $ap->rejection_reason = null;
            $ap->save();

            $ap->findingDepartment->status = FindingDepartmentStatus::InProgress;
            $ap->findingDepartment->save();

            AuditLogger::log('action_plan.approved', $user->id, request()->ip(), 'Rencana aksi disetujui.', [
                'action_plan_id' => $ap->id,
            ]);

            FindingDepartmentService::invalidate();
            self::invalidate();
            return $ap->fresh();
        });
    }

    public static function reject(ActionPlan $ap, string $reason): ActionPlan
    {
        $user = auth()->user();

        if (! in_array($user->role, [Role::ManagerDept, Role::AdminSpi, Role::SuperAdmin])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Manager Departemen yang dapat menolak rencana aksi.',
            ]);
        }

        $ap->load('findingDepartment');
        if ($user->role === Role::ManagerDept && $ap->findingDepartment->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'department' => 'Anda tidak berhak untuk departemen ini.',
            ]);
        }

        if ($ap->status !== ActionPlanStatus::Submitted) {
            throw ValidationException::withMessages([
                'status' => 'Hanya rencana aksi dalam status Diajukan yang dapat ditolak.',
            ]);
        }

        if (empty($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($ap, $user, $reason) {
            $ap->status = ActionPlanStatus::Rejected;
            $ap->rejection_reason = $reason;
            $ap->approved_by = null;
            $ap->approved_at = null;
            $ap->save();

            AuditLogger::log('action_plan.rejected', $user->id, request()->ip(), 'Rencana aksi ditolak.', [
                'action_plan_id' => $ap->id,
            ]);

            self::invalidate();
            return $ap->fresh();
        });
    }

    public static function requestRevision(ActionPlan $ap, string $reason): ActionPlan
    {
        $user = auth()->user();

        if (! in_array($user->role, [Role::ManagerDept, Role::AdminSpi, Role::SuperAdmin])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Manager Departemen yang dapat meminta revisi rencana aksi.',
            ]);
        }

        $ap->load('findingDepartment');
        if ($user->role === Role::ManagerDept && $ap->findingDepartment->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'department' => 'Anda tidak berhak untuk departemen ini.',
            ]);
        }

        if ($ap->status !== ActionPlanStatus::Submitted) {
            throw ValidationException::withMessages([
                'status' => 'Hanya rencana aksi dalam status Diajukan yang dapat diminta revisi.',
            ]);
        }

        if (empty($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan revisi wajib diisi.',
            ]);
        }

        return DB::transaction(function () use ($ap, $user, $reason) {
            $ap->status = ActionPlanStatus::Revision;
            $ap->rejection_reason = null;
            $ap->approved_by = null;
            $ap->approved_at = null;
            $ap->save();

            AuditLogger::log('action_plan.revision_requested', $user->id, request()->ip(), 'Revisi rencana aksi diminta.', [
                'action_plan_id' => $ap->id,
            ]);

            self::invalidate();
            return $ap->fresh();
        });
    }

    public static function submitEvidence(ActionPlan $ap, array $files): EvidenceSubmission
    {
        $user = auth()->user();

        if ($ap->status !== ActionPlanStatus::WaitingEvidence && $ap->status !== ActionPlanStatus::EvidenceRevision) {
            throw ValidationException::withMessages([
                'status' => 'Evidence hanya dapat diajukan untuk rencana aksi yang disetujui atau diminta revisi.',
            ]);
        }

        if ($user->role === Role::StaffDept && $ap->created_by !== $user->id) {
            throw new AuthorizationException('Anda bukan PIC dari rencana aksi ini.');
        }

        if (empty($files)) {
            throw ValidationException::withMessages([
                'files' => 'Minimal satu file evidence wajib diunggah.',
            ]);
        }

        return DB::transaction(function () use ($ap, $files, $user) {
            $submission = EvidenceSubmission::create([
                'action_plan_id' => $ap->id,
                'status' => EvidenceStatus::Diajukan,
                'submitted_by' => $user->id,
            ]);

            foreach ($files as $file) {
                $label = is_array($file) ? ($file['label'] ?? null) : null;
                $uploaded = is_array($file) ? $file['file'] : $file;

                $path = $uploaded->store('evidence/' . $submission->id, config('upload.disk'));

                $submission->files()->create([
                    'name' => $uploaded->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $uploaded->getMimeType() ?? 'application/octet-stream',
                    'size' => $uploaded->getSize(),
                    'label' => $label,
                ]);
            }

            $ap->status = ActionPlanStatus::EvidenceSubmitted;
            $ap->save();

            AuditLogger::log('evidence.submitted', $user->id, request()->ip(), 'Evidence diajukan.', [
                'action_plan_id' => $ap->id,
                'evidence_submission_id' => $submission->id,
            ]);

            self::invalidate();
            return $submission->fresh();
        });
    }

    public static function approveEvidence(ActionPlan $ap): ActionPlan
    {
        $user = auth()->user();

        if (! in_array($user->role, [Role::ManagerDept, Role::AdminSpi, Role::SuperAdmin])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Manager Departemen yang dapat menyetujui evidence.',
            ]);
        }

        $ap->load('findingDepartment');
        if ($user->role === Role::ManagerDept && $ap->findingDepartment->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'department' => 'Anda tidak berhak untuk departemen ini.',
            ]);
        }

        if ($ap->status !== ActionPlanStatus::EvidenceSubmitted) {
            throw ValidationException::withMessages([
                'status' => 'Hanya rencana aksi dengan evidence diajukan yang dapat disetujui.',
            ]);
        }

        $submission = $ap->evidenceSubmissions()->where('status', EvidenceStatus::Diajukan->value)->latest('id')->first();
        if (! $submission) {
            throw ValidationException::withMessages([
                'evidence' => 'Tidak ada evidence yang diajukan untuk rencana aksi ini.',
            ]);
        }

        return DB::transaction(function () use ($ap, $submission, $user) {
            $submission->status = EvidenceStatus::Disetujui;
            $submission->reviewed_by = $user->id;
            $submission->reviewed_at = now();
            $submission->save();

            $ap->status = ActionPlanStatus::EvidenceApproved;
            $ap->save();

            AuditLogger::log('evidence.approved', $user->id, request()->ip(), 'Evidence disetujui.', [
                'action_plan_id' => $ap->id,
                'evidence_submission_id' => $submission->id,
            ]);

            self::syncDepartmentCompletion($ap->finding_department_id);
            self::invalidate();
            return $ap->fresh();
        });
    }

    public static function requestEvidenceRevision(ActionPlan $ap, string $note): ActionPlan
    {
        $user = auth()->user();

        if (! in_array($user->role, [Role::ManagerDept, Role::AdminSpi, Role::SuperAdmin])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Manager Departemen yang dapat meminta revisi evidence.',
            ]);
        }

        $ap->load('findingDepartment');
        if ($user->role === Role::ManagerDept && $ap->findingDepartment->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'department' => 'Anda tidak berhak untuk departemen ini.',
            ]);
        }

        if ($ap->status !== ActionPlanStatus::EvidenceSubmitted) {
            throw ValidationException::withMessages([
                'status' => 'Hanya rencana aksi dengan evidence diajukan yang dapat diminta revisi.',
            ]);
        }

        if (empty($note)) {
            throw ValidationException::withMessages([
                'note' => 'Catatan revisi wajib diisi.',
            ]);
        }

        $submission = $ap->evidenceSubmissions()->where('status', EvidenceStatus::Diajukan->value)->latest('id')->first();
        if (! $submission) {
            throw ValidationException::withMessages([
                'evidence' => 'Tidak ada evidence yang diajukan untuk rencana aksi ini.',
            ]);
        }

        return DB::transaction(function () use ($ap, $submission, $user, $note) {
            $submission->status = EvidenceStatus::Revisi;
            $submission->reviewed_by = $user->id;
            $submission->reviewed_at = now();
            $submission->revision_note = $note;
            $submission->save();

            $ap->status = ActionPlanStatus::EvidenceRevision;
            $ap->save();

            AuditLogger::log('evidence.revision_requested', $user->id, request()->ip(), 'Revisi evidence diminta.', [
                'action_plan_id' => $ap->id,
                'evidence_submission_id' => $submission->id,
            ]);

            self::invalidate();
            return $ap->fresh();
        });
    }

    /**
     * Progress departemen = jumlah bobot tindak lanjut yang evidence-nya sudah disetujui.
     */
    public static function departmentProgress(int $findingDepartmentId): float
    {
        return (float) ActionPlan::where('finding_department_id', $findingDepartmentId)
            ->whereNull('deleted_at')
            ->where('status', ActionPlanStatus::EvidenceApproved->value)
            ->sum('weight');
    }

/**
 * Progress temuan = rata-rata progress seluruh departemen **ronde berjalan**
 * (tampilan). Hanya ronde aktif yang diperhitungkan agar ronde lama tidak
 * menggeser angka.
 */
public static function findingProgress(int $findingId): float
    {
        $finding = Finding::find($findingId, ['id', 'current_round']);

        if (! $finding) {
            return 0.0;
        }

        $departments = FindingDepartment::where('finding_id', $findingId)
            ->where('round', $finding->current_round)
            ->whereNull('deleted_at')
            ->count();

        if ($departments === 0) {
            return 0.0;
        }

        $approved = (float) ActionPlan::query()
            ->join('finding_departments', 'finding_departments.id', '=', 'action_plans.finding_department_id')
            ->where('finding_departments.finding_id', $findingId)
            ->where('finding_departments.round', $finding->current_round)
            ->whereNull('finding_departments.deleted_at')
            ->whereNull('action_plans.deleted_at')
            ->where('action_plans.status', ActionPlanStatus::EvidenceApproved->value)
            ->sum('action_plans.weight');

        return $approved / $departments;
    }

    /**
     * Tempelkan progress ke kumpulan temuan dalam satu halaman agar Resource
     * tidak memicu query per baris (N+1).
     *
     * @param  iterable<Finding>  $findings
     */
    public static function attachFindingProgress($findings): void
    {
        $items = collect($findings)->values();
        $ids = $items->pluck('id')->all();

        if ($ids === []) {
            return;
        }

        $rounds = $items->pluck('current_round', 'id')->all();

        $approved = ActionPlan::query()
            ->join('finding_departments', 'finding_departments.id', '=', 'action_plans.finding_department_id')
            ->whereIn('finding_departments.finding_id', $ids)
            ->whereNull('finding_departments.deleted_at')
            ->whereNull('action_plans.deleted_at')
            ->where('action_plans.status', ActionPlanStatus::EvidenceApproved->value)
            ->groupBy('finding_departments.finding_id')
            ->selectRaw('finding_departments.finding_id, SUM(action_plans.weight) as approved')
            ->pluck('approved', 'finding_departments.finding_id');

        $counts = FindingDepartment::query()
            ->whereIn('finding_id', $ids)
            ->whereNull('deleted_at')
            ->selectRaw('finding_id, round, COUNT(*) as total')
            ->groupBy('finding_id', 'round')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->finding_id . ':' . $row->round => (int) $row->total]);

        foreach ($items as $finding) {
            $id = $finding->id;
            $divisor = $counts[$id . ':' . ($rounds[$id] ?? 1)] ?? 0;
            $finding->setAttribute(
                'progress',
                $divisor > 0 ? (float) ($approved[$id] ?? 0) / $divisor : 0.0,
            );
        }
    }

    /**
     * Tempelkan progress ke kumpulan finding_department dalam satu query.
     *
     * @param  iterable<FindingDepartment>  $departments
     */
    public static function attachDepartmentProgress($departments): void
    {
        $items = collect($departments)->values();
        $ids = $items->pluck('id')->all();

        if ($ids === []) {
            return;
        }

        $approved = ActionPlan::query()
            ->whereIn('finding_department_id', $ids)
            ->whereNull('deleted_at')
            ->where('status', ActionPlanStatus::EvidenceApproved->value)
            ->groupBy('finding_department_id')
            ->selectRaw('finding_department_id, SUM(weight) as approved')
            ->pluck('approved', 'finding_department_id');

        foreach ($items as $fd) {
            $fd->setAttribute('progress', (float) ($approved[$fd->id] ?? 0));
        }
    }

    protected static function syncDepartmentCompletion(int $findingDepartmentId): void
    {
        $fd = FindingDepartment::find($findingDepartmentId);
        if (! $fd) {
            return;
        }

        $activeTotal = self::activeWeightForDepartment($findingDepartmentId, null);
        $approvedTotal = self::departmentProgress($findingDepartmentId);

        if ($activeTotal > 0 && $activeTotal == $approvedTotal) {
            $fd->status = FindingDepartmentStatus::Complete100;
            $fd->save();
        }

        FindingDepartmentService::invalidate();
    }

    public static function overrideWeight(ActionPlan $ap, float $weight): ActionPlan
    {
        $user = auth()->user();

        if ($user->role === Role::StaffDept && $ap->created_by !== $user->id) {
            throw ValidationException::withMessages([
                'pic' => 'Anda tidak berhak mengubah bobot rencana aksi ini.',
            ]);
        }

        if ($user->role === Role::ManagerDept) {
            $ap->load('findingDepartment');
            if ($ap->findingDepartment->department_id !== $user->department_id) {
                throw ValidationException::withMessages([
                    'department' => 'Anda tidak berhak untuk departemen ini.',
                ]);
            }
        }

        $ap->weight = $weight;
        $ap->save();
        self::invalidate();
        return $ap->fresh();
    }

    public static function uploadDocument(ActionPlan $ap, UploadedFile $file, ?string $label): ActionPlanDocument
    {
        $path = $file->store('action_plans/' . $ap->id, config('upload.disk'));

        return $ap->documents()->create([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'label' => $label,
            'uploaded_by' => auth()->id(),
        ]);
    }

    public static function deleteDocument(ActionPlanDocument $document): void
    {
        Storage::disk(config('upload.disk'))->delete($document->path);
        $document->delete();
        self::invalidate();
    }

    public static function downloadDocument(ActionPlanDocument $document): string
    {
        $path = $document->path;
        if (! Storage::disk(config('upload.disk'))->exists($path)) {
            throw ValidationException::withMessages([
                'document' => 'File tidak ditemukan.',
            ]);
        }
        return Storage::disk(config('upload.disk'))->path($path);
    }

    protected static function activeWeightForDepartment(int $findingDepartmentId, ?int $excludeId = null): float
    {
        $query = ActionPlan::where('finding_department_id', $findingDepartmentId)
            ->whereNull('deleted_at');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return (float) $query->whereIn('status', [
            ActionPlanStatus::Draft->value,
            ActionPlanStatus::Submitted->value,
            ActionPlanStatus::Approved->value,
            ActionPlanStatus::Revision->value,
            ActionPlanStatus::WaitingEvidence->value,
            ActionPlanStatus::EvidenceSubmitted->value,
            ActionPlanStatus::EvidenceApproved->value,
            ActionPlanStatus::EvidenceRevision->value,
        ])->sum('weight');
    }
}
