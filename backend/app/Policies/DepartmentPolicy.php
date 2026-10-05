<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'master.departments', 'view');
    }

    public function view(User $user, Department $department): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'master.departments', 'create');
    }

    public function update(User $user, Department $department): bool
    {
        return PermissionService::can($user, 'master.departments', 'update');
    }

    public function delete(User $user, Department $department): bool
    {
        return PermissionService::can($user, 'master.departments', 'delete');
    }
}
