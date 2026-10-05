<?php

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

test('debug dashboard exception', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    $this->withoutExceptionHandling();

    try {
        $this->getJson('/api/v1/dashboard');
    } catch (Throwable $e) {
        dump('exception class', get_class($e));
        dump('message', $e->getMessage());
        dump('is AuthException', $e instanceof \Illuminate\Auth\AuthenticationException);
    }
    expect(true)->toBeTrue();
});

test('debug me permissions', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $dept = Department::first();

    $user = User::create([
        'name' => 'Debug',
        'email' => 'debug2@example.com',
        'username' => 'debug2',
        'password' => Hash::make('password'),
        'role' => Role::AdminSpi,
        'department_id' => $dept->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me');

    dump('status', $response->status());
    $perms = $response->json('data.permissions');
    dump('findings.reports perms', $perms['findings.reports'] ?? 'NOT SET');
    expect(true)->toBeTrue();
});
