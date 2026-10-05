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

    public static function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return CacheService::remember('departments', "page.{$perPage}", fn () => Department::orderBy('name')->paginate($perPage), 600);
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
