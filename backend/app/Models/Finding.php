<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\FindingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Finding extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'finding_date',
        'severity',
        'recommendation',
        'auditor_action_plan',
        'status',
        'created_by',
        'is_active',
    ];

    protected $casts = [
        'finding_date' => 'date',
        'status' => FindingStatus::class,
        'is_active' => 'boolean',
    ];

    public function scopeVisible(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user) {
            return $query;
        }

        if (in_array($user->role, [Role::AdminSpi, Role::SuperAdmin, Role::ManagerIa])) {
            if ($user->role === Role::ManagerIa) {
                $query->where('status', '!=', FindingStatus::Draft->value);
            }
            return $query;
        }

        if ($user->role === Role::ManagerDept) {
            return $query->whereHas('findingDepartments', function (Builder $q) use ($user) {
                $q->where('department_id', $user->department_id)
                    ->whereNull('deleted_at');
            });
        }

        if ($user->role === Role::StaffDept) {
            return $query->whereHas('findingDepartments.pics', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($user->role === Role::ManagerSpi) {
            return $query->whereIn('status', [
                FindingStatus::PendingVerificationSpi->value,
                FindingStatus::Closed->value,
                FindingStatus::CaseClosed->value,
            ]);
        }

        return $query;
    }

    public function isEditableByAdmin(): bool
    {
        return $this->status->isEditableByAdmin();
    }

    public function documents()
    {
        return $this->hasMany(FindingDocument::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function findingDepartments()
    {
        return $this->hasMany(FindingDepartment::class);
    }

    public function currentRoundDepartments()
    {
        return $this->findingDepartments()
            ->whereNull('deleted_at')
            ->where('round', $this->current_round);
    }

    public function verifications()
    {
        return $this->hasMany(FindingVerification::class);
    }

    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }

    public function isAssessingReady(): bool
    {
        $fds = $this->currentRoundDepartments()->get();

        return $fds->isNotEmpty() && $fds->every(
            fn (FindingDepartment $fd) => $fd->status === \App\Enums\FindingDepartmentStatus::ForwardedToIa,
        );
    }
}
