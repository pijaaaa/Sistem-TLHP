<?php

namespace App\Services;

use App\Models\Department;
use App\Support\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public static function all(): Collection
    {
        return CacheService::remember('departments', 'all', fn () => Department::orderBy('name')->get(), 3600);
    }

    // ponytail: paginator tidak di-cache (stateful per-request, serializer rapuh);
    // cache hanya untuk all() yang dipakai form select.
    public static function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return Department::orderBy('name')->paginate($perPage);
    }

    public static function invalidate(): void
    {
        CacheService::flushGroup('departments');
    }

    public static function create(array $data): Department
    {
        $department = Department::create($data);
        self::invalidate();
        return $department;
    }

    public static function update(Department $department, array $data): Department
    {
        $department->update($data);
        self::invalidate();
        return $department->fresh();
    }

    public static function delete(Department $department): void
    {
        $department->delete();
        self::invalidate();
    }
}
