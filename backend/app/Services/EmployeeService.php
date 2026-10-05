<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeService
{
    public static function all(): Collection
    {
        return CacheService::remember('employees', 'all', fn () => Employee::with('department')->orderBy('name')->get(), 3600);
    }

    // ponytail: paginator tidak di-cache; cache hanya untuk all().
    public static function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return Employee::with('department')->orderBy('name')->paginate($perPage);
    }

    public static function invalidate(): void
    {
        CacheService::flushGroup('employees');
    }

    public static function create(array $data): Employee
    {
        $employee = Employee::create($data);
        CacheService::flushGroup('users');
        return $employee;
    }

    public static function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        self::invalidate();
        return $employee->fresh('department');
    }

    public static function delete(Employee $employee): void
    {
        $employee->delete();
        self::invalidate();
    }
}
