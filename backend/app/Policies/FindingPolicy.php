<?php

namespace App\Policies;

use App\Enums\FindingStatus;
use App\Enums\Role;
use App\Models\Finding;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class FindingPolicy
{
    use HandlesAuthorization;

    private const WRITERS = [Role::AdminSpi, Role::InternalAudit];

    public function viewAny(User $user): bool
    {
        return PermissionService::can($user, 'findings', 'view');
    }

    public function view(User $user, Finding $finding): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isWriter($user) && PermissionService::can($user, 'findings', 'create');
    }

    public function update(User $user, Finding $finding): bool
    {
        return $this->isWriter($user) && PermissionService::can($user, 'findings', 'update');
    }

    public function register(User $user, Finding $finding): bool
    {
        return $this->update($user, $finding) && $finding->status === FindingStatus::Draft;
    }

    public function activate(User $user, Finding $finding): bool
    {
        return $this->update($user, $finding) && $finding->status === FindingStatus::Terdaftar;
    }

    public function delete(User $user, Finding $finding): bool
    {
        return $this->isWriter($user)
            && PermissionService::can($user, 'findings', 'delete')
            && $finding->status === FindingStatus::Draft;
    }

    private function isWriter(User $user): bool
    {
        return $user->role === Role::SuperAdmin || in_array($user->role, self::WRITERS, true);
    }
}
