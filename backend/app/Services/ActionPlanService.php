<?php

namespace App\Services;

use App\Enums\ActionPlanStatus;
use App\Enums\FindingDepartmentStatus;
use App\Enums\Role;
use App\Models\ActionPlan;
use App\Models\ActionPlanDocument;
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

        return $query->with(['findingDepartment', 'creator'])->orderBy('id', 'desc')->paginate($perPage);
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
            $ap->status = ActionPlanStatus::Approved;
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
            ActionPlanStatus::Revision->value,
            ActionPlanStatus::WaitingEvidence->value,
            ActionPlanStatus::EvidenceSubmitted->value,
            ActionPlanStatus::EvidenceRevision->value,
        ])->sum('weight');
    }
}
