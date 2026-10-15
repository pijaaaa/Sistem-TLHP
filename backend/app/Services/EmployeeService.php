<?php

namespace App\Services;

use App\Models\Employee;
use App\Support\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    public static function all(): Collection
    {
        return CacheService::remember('employees', 'all', fn () => Employee::with(['department', 'user'])->orderBy('name')->get(), 3600);
    }

    // ponytail: paginator tidak di-cache; cache hanya untuk all().
    public static function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return Employee::with(['department', 'user'])->orderBy('name')->paginate($perPage);
    }

    public static function invalidate(): void
    {
        CacheService::flushGroup('employees');
    }

    public static function create(array $data): Employee
    {
        $employee = DB::transaction(function () use ($data) {
            $employee = Employee::create(Arr::only($data, ['nik', 'name', 'position', 'department_id', 'is_active']));

            $employee->user()->create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => $data['role'],
                'department_id' => $employee->department_id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $employee;
        });

        self::invalidate();
        CacheService::flushGroup('users');
        return $employee->load(['department', 'user']);
    }

    public static function update(Employee $employee, array $data): Employee
    {
        $employee->update($data);
        self::invalidate();
        return $employee->fresh(['department', 'user']);
    }

    public static function delete(Employee $employee): void
    {
        $employee->delete();
        self::invalidate();
    }
}
