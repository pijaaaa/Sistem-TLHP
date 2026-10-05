<?php

namespace App\Services;

use App\Enums\FindingStatus;
use App\Enums\FindingDepartmentStatus;
use App\Enums\Role;
use App\Models\Department;
use App\Models\Finding;
use App\Models\FindingDepartment;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\CacheService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FindingDepartmentService
{
    public static function distribute(Finding $finding, array $departmentIds): void
    {
        if ($finding->status !== FindingStatus::SentToIa) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan dalam status Dikirim ke IA yang dapat didistribusikan.',
            ]);
        }

        if (empty($departmentIds)) {
            throw ValidationException::withMessages([
                'department_ids' => 'Pilih setidaknya satu departemen.',
            ]);
        }

        $departments = Department::whereIn('id', $departmentIds)->where('is_active', true)->get();
        if ($departments->count() !== count($departmentIds)) {
            throw ValidationException::withMessages([
                'department_ids' => 'Beberapa departemen tidak ditemukan atau tidak aktif.',
            ]);
        }

        $existing = FindingDepartment::where('finding_id', $finding->id)
            ->whereIn('department_id', $departmentIds)
            ->pluck('department_id')
            ->toArray();

        if (!empty($existing)) {
            throw ValidationException::withMessages([
                'department_ids' => 'Beberapa departemen sudah ditugaskan untuk temuan ini.',
            ]);
        }

        DB::transaction(function () use ($finding, $departmentIds) {
            $userId = auth()->id();

            foreach ($departmentIds as $deptId) {
                FindingDepartment::create([
                    'finding_id' => $finding->id,
                    'department_id' => $deptId,
                    'assigned_by' => $userId,
                    'status' => FindingDepartmentStatus::Received,
                ]);

                AuditLogger::log('finding.distributed', $userId, request()->ip(), 'Temuan didistribusikan ke departemen.', [
                    'finding_id' => $finding->id,
                    'department_id' => $deptId,
                ]);
            }

            $finding->status = FindingStatus::Distributed;
            $finding->save();
        });

        self::invalidate();
    }

    public static function assignPics(FindingDepartment $fd, array $picIds): void
    {
        if ($fd->status !== FindingDepartmentStatus::Received) {
            throw ValidationException::withMessages([
                'status' => 'Hanya departemen dengan status Diterima yang dapat ditugaskan PIC.',
            ]);
        }

        if (empty($picIds)) {
            throw ValidationException::withMessages([
                'pic_ids' => 'Pilih setidaknya satu PIC.',
            ]);
        }

        $pics = User::whereIn('id', $picIds)->get();
        if ($pics->count() !== count($picIds)) {
            throw ValidationException::withMessages([
                'pic_ids' => 'Beberapa pengguna tidak ditemukan.',
            ]);
        }

        foreach ($pics as $pic) {
            if ($pic->department_id !== $fd->department_id) {
                throw ValidationException::withMessages([
                    'pic_ids' => 'Semua PIC harus dari departemen yang sama dengan temuan departemen ini.',
                ]);
            }
            if ($pic->role !== Role::StaffDept) {
                throw ValidationException::withMessages([
                    'pic_ids' => 'Hanya Staff Departemen yang dapat ditugaskan sebagai PIC.',
                ]);
            }
        }

        DB::transaction(function () use ($fd, $picIds) {
            $userId = auth()->id();

            foreach ($picIds as $picId) {
                if (!$fd->pics()->where('user_id', $picId)->exists()) {
                    $fd->pics()->attach($picId, ['assigned_by' => $userId]);

                    AuditLogger::log('pic.assigned', $userId, request()->ip(), 'PIC ditugaskan untuk temuan departemen.', [
                        'finding_department_id' => $fd->id,
                        'user_id' => $picId,
                    ]);
                }
            }

            $fd->status = FindingDepartmentStatus::PicAssigned;
            $fd->save();
        });

        self::invalidate();
    }

    /**
     * Teruskan temuan departemen ke IA. Hanya bisa saat progress departemen 100%.
     * Setelah semua departemen meneruskan, temuan menunggu assessment IA.
     */
    public static function forwardToIA(FindingDepartment $fd): FindingDepartment
    {
        $user = auth()->user();

        if (! in_array($user->role, [Role::ManagerDept, Role::AdminSpi, Role::SuperAdmin])) {
            throw ValidationException::withMessages([
                'role' => 'Hanya Manager Departemen yang dapat meneruskan temuan ke IA.',
            ]);
        }

        if ($user->role === Role::ManagerDept && $fd->department_id !== $user->department_id) {
            throw ValidationException::withMessages([
                'department' => 'Anda tidak berhak untuk departemen ini.',
            ]);
        }

        if ($fd->status !== FindingDepartmentStatus::Complete100) {
            throw ValidationException::withMessages([
                'status' => 'Hanya temuan departemen dengan status Selesai 100% yang dapat diteruskan ke IA.',
            ]);
        }

        return DB::transaction(function () use ($fd, $user) {
            $fd->status = FindingDepartmentStatus::ForwardedToIa;
            $fd->save();

            AuditLogger::log('finding_department.forwarded_to_ia', $user->id, request()->ip(), 'Temuan diteruskan ke IA.', [
                'finding_department_id' => $fd->id,
            ]);

            $finding = $fd->finding;
            if ($finding) {
                $pending = $finding->findingDepartments()
                    ->whereNull('deleted_at')
                    ->where('status', '!=', FindingDepartmentStatus::ForwardedToIa->value)
                    ->exists();

                if (! $pending && in_array($finding->status, [
                    FindingStatus::Distributed,
                    FindingStatus::InProgress,
                ], true)) {
                    $finding->status = FindingStatus::PendingIaAssessment;
                    $finding->save();
                }
            }

            self::invalidate();
            return $fd->fresh();
        });
    }

    public static function invalidate(): void
    {
        FindingService::invalidate();
        CacheService::incrementGroupVersion('findings');
    }
}
