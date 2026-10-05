<?php

namespace Tests\Unit;

use App\Support\CacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_remember_stores_and_returns_value(): void
    {
        $value = CacheService::remember('departments', 'test_key', fn() => 'test_value', 60);

        $this->assertEquals('test_value', $value);
    }

    public function test_remember_calls_callback_only_once(): void
    {
        $calls = 0;
        CacheService::remember('departments', 'key1', function () use (&$calls) {
            $calls++;
            return 'first';
        }, 60);

        $value = CacheService::remember('departments', 'key1', function () use (&$calls) {
            $calls++;
            return 'second';
        }, 60);

        $this->assertEquals('first', $value);
        $this->assertEquals(1, $calls);
    }

    public function test_forget_removes_value(): void
    {
        CacheService::remember('departments', 'key2', fn() => 'value2', 60);
        CacheService::forget('departments', 'key2');

        $value = CacheService::remember('departments', 'key2', fn() => 'new_value', 60);
        $this->assertEquals('new_value', $value);
    }

    public function test_flush_group_changes_version(): void
    {
        CacheService::remember('employees', 'key1', fn() => 'old_value', 60);
        CacheService::flushGroup('employees');

        $calls = 0;
        CacheService::remember('employees', 'key1', function () use (&$calls) {
            $calls++;
            return 'new_value';
        }, 60);

        $this->assertEquals(1, $calls);
    }

    public function test_different_groups_have_independent_versions(): void
    {
        CacheService::remember('employees', 'key', fn() => 'value_a', 60);
        CacheService::remember('users', 'key', fn() => 'value_b', 60);
        CacheService::flushGroup('employees');

        $aCalls = 0;
        $bCalls = 0;

        CacheService::remember('employees', 'key', function () use (&$aCalls) {
            $aCalls++;
            return 'new_a';
        }, 60);

        CacheService::remember('users', 'key', function () use (&$bCalls) {
            $bCalls++;
            return 'new_b';
        }, 60);

        $this->assertEquals(1, $aCalls);
        $this->assertEquals(0, $bCalls);
    }

    public function test_throws_on_unregistered_group(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        CacheService::remember('unregistered_group', 'key', fn() => 'val', 60);
    }
}
