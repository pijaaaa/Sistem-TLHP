<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FindingDocument;
use App\Services\PermissionService;
use Illuminate\Auth\Access\HandlesAuthorization;

class FindingDocumentPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ?FindingDocument $document = null): bool
    {
        return PermissionService::can($user, 'findings.reports', 'view')
            || PermissionService::can($user, 'findings.list', 'view');
    }

    public function create(User $user): bool
    {
        return PermissionService::can($user, 'findings.reports', 'create');
    }

    public function delete(User $user, FindingDocument $document): bool
    {
        return PermissionService::can($user, 'findings.reports', 'delete');
    }
}
