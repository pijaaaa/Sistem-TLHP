<?php

namespace App\Models;

use App\Enums\FindingDepartmentStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FindingDepartment extends Model
{
    use SoftDeletes;

    protected $table = 'finding_departments';

    protected $fillable = [
        'finding_id',
        'department_id',
        'assigned_by',
        'status',
    ];

    protected $casts = [
        'status' => FindingDepartmentStatus::class,
    ];

    public function scopeVisible(Builder $query): Builder
    {
        $user = auth()->user();
        if (! $user) {
            return $query;
        }

        if (in_array($user->role, [Role::AdminSpi, Role::SuperAdmin, Role::ManagerIa])) {
            return $query;
        }

        if ($user->role === Role::ManagerDept) {
            return $query->where('department_id', $user->department_id);
        }

        if ($user->role === Role::StaffDept) {
            return $query->whereHas('pics', function (Builder $q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($user->role === Role::ManagerSpi) {
            return $query->whereHas('finding', function (Builder $q) {
                $q->whereIn('status', [
                    \App\Enums\FindingStatus::Closed->value,
                    \App\Enums\FindingStatus::CaseClosed->value,
                ]);
            });
        }

        return $query;
    }

    public function finding()
    {
        return $this->belongsTo(Finding::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function assigner()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function pics()
    {
        return $this->belongsToMany(User::class, 'finding_department_pics')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }
}
