<?php

namespace App\Services;

use App\Models\User;
use App\Support\CacheService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public static function paginated(int $perPage = 15): LengthAwarePaginator
    {
        return CacheService::remember('users', "page.{$perPage}", fn () => User::with(['department', 'employee'])->orderBy('name')->paginate($perPage), 600);
    }

    public static function invalidate(): void
    {
        CacheService::flushGroup('users');
    }

    public static function create(array $data): User
    {
        $data['password'] = $data['password'] ?? Hash::make('password');
        $user = User::create($data);
        self::invalidate();
        return $user;
    }

    public static function update(User $user, array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);
        self::invalidate();
        PermissionService::invalidateForUser($user);
        return $user->fresh(['department', 'employee']);
    }

    public static function delete(User $user): void
    {
        $user->delete();
        self::invalidate();
    }
}
