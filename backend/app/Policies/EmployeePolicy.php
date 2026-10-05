<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'master.employees', 'view');
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'master.employees', 'create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return PermissionService::can($user, 'master.employees', 'update');
    }

    public function delete(User $user, Employee $employee): bool
    {
        return PermissionService::can($user, 'master.employees', 'delete');
    }
}
