<?php

namespace App\Policies;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'master.employees', 'view');
    }

    public function view(User $user, User $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'master.employees', 'create');
    }

    public function update(User $user, User $model): bool
    {
        return PermissionService::can($user, 'master.employees', 'update');
    }

    public function delete(User $user, User $model): bool
    {
        return PermissionService::can($user, 'master.employees', 'delete');
    }
}
