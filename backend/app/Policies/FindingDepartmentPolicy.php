<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\FindingDepartment;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class FindingDepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'findings.distribution', 'view')
            || PermissionService::can($user, 'findings.list', 'view');
    }

    public function view(User $user, FindingDepartment $findingDepartment): bool
    {
        if (in_array($user->role, [Role::AdminSpi, Role::SuperAdmin, Role::ManagerIa])) {
            return true;
        }

        if ($user->role === Role::ManagerDept && $findingDepartment->department_id === $user->department_id) {
            return true;
        }

        if ($user->role === Role::StaffDept && $findingDepartment->pics()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return false;
    }

    public function distribute(User $user): bool
    {
        return $user->role === Role::ManagerIa;
    }

    public function assignPics(User $user, FindingDepartment $findingDepartment): bool
    {
        if (! in_array($user->role, [Role::ManagerDept, Role::AdminSpi, Role::SuperAdmin])) {
            return false;
        }

        if ($user->role === Role::ManagerDept && $findingDepartment->department_id !== $user->department_id) {
            return false;
        }

        return true;
    }
}
