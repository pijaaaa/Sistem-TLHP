<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class CacheService
{
    private static array $groups = [
        'departments', 'employees', 'users', 'audit_trail', 'dashboard',
        'permissions', 'menus', 'findings', 'lookups', 'action_plans', 'notifications', 'reports',
    ];

    public static function version(string $group): int
    {
        return Cache::get("_version_{$group}", 1);
    }

    public static function remember(string $group, string $key, Closure $callback, ?int $ttl = null): mixed
    {
        if (! in_array($group, self::$groups)) {
            throw new InvalidArgumentException("Cache group [{$group}] is not registered. Use one of: " . implode(', ', self::$groups));
        }

        $version = self::version($group);
        $fullKey = "{$group}.v{$version}.{$key}";

        return Cache::remember($fullKey, $ttl ?? 3600, $callback);
    }

    public static function forget(string $group, string $key): void
    {
        $version = self::version($group);
        $fullKey = "{$group}.v{$version}.{$key}";

        Cache::forget($fullKey);
    }

    public static function flushGroup(string $group): void
    {
        if (! in_array($group, self::$groups)) {
            throw new InvalidArgumentException("Cache group [{$group}] is not registered. Use one of: " . implode(', ', self::$groups));
        }

        $version = self::version($group);
        Cache::put("_version_{$group}", $version + 1, 86400);
    }

    public static function incrementGroupVersion(string $group): void
    {
        self::flushGroup($group);
    }

    public static function getRegisteredGroups(): array
    {
        return self::$groups;
    }
}
