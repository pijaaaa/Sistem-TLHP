<?php

use App\Enums\Role;
use App\Models\Department;
use App\Models\Menu;
use App\Models\RoleMenuPermission;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

test('login sukses dengan kredensial benar', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'username' => 'test',
        'password' => Hash::make('password'),
        'role' => Role::AdminSpi,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
             ->assertJsonStructure(['data' => ['user', 'token']]);
});

test('login gagal dengan password salah', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'username' => 'test',
        'password' => Hash::make('password'),
        'role' => Role::AdminSpi,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(401)
             ->assertJson(['message' => 'Email atau password salah.']);
});

test('user nonaktif tidak bisa login', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    User::create([
        'name' => 'Inactive',
        'email' => 'inactive@example.com',
        'username' => 'inactive',
        'password' => Hash::make('password'),
        'role' => Role::AdminSpi,
        'department_id' => Department::first()->id,
        'is_active' => false,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'inactive@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(403)
             ->assertJson(['message' => 'Akun tidak aktif.']);
});

test('auth me mengembalikan user, role, department, menus, permissions', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'username' => 'test',
        'password' => Hash::make('password'),
        'role' => Role::AdminSpi,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
                     ->getJson('/api/v1/auth/me');

    $response->assertOk()
             ->assertJsonStructure([
                 'data' => [
                     'user',
                     'role',
                     'department',
                     'permissions',
                     'menus',
                 ]
             ]);
});

test('unauthenticated request ke dashboard mengembalikan 401', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $response = $this->getJson('/api/v1/dashboard');
    $response->assertStatus(401);
});