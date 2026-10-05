<?php

use App\Enums\Role;
use App\Models\Department;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserMenuPermission;
use App\Services\PermissionService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Hash;

test('izin efektif role saja: admin_spi bisa view findings.reports', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $user = User::create([
        'name' => 'Admin SPI',
        'email' => 'admin@example.com',
        'username' => 'admin',
        'password' => Hash::make('password'),
        'role' => Role::AdminSpi,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
                     ->getJson('/api/v1/auth/me');

    $perms = $response->json('data.permissions');
    expect($perms['findings.reports']['view'])->toBeTrue();
    expect($perms['findings.reports']['create'])->toBeTrue();
});

test('override user bisa menambah akses', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $user = User::create([
        'name' => 'Staff',
        'email' => 'staff@example.com',
        'username' => 'staff',
        'password' => Hash::make('password'),
        'role' => Role::StaffDept,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    // Staff dept default tidak punya akses assessments
    $menu = Menu::where('code', 'assessments')->first();

    UserMenuPermission::create([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_create' => true,
    ]);

    $response = $this->actingAs($user, 'sanctum')
                     ->getJson('/api/v1/auth/me');

    $perms = $response->json('data.permissions');
    expect($perms['assessments']['view'])->toBeTrue();
    expect($perms['assessments']['create'])->toBeTrue();
});

test('override user bisa mencabut akses', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $user = User::create([
        'name' => 'Admin',
        'email' => 'admin2@example.com',
        'username' => 'admin2',
        'password' => Hash::make('password'),
        'role' => Role::AdminSpi,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    $menu = Menu::where('code', 'findings.reports')->first();

    UserMenuPermission::create([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'can_view' => false,
    ]);

    $response = $this->actingAs($user, 'sanctum')
                     ->getJson('/api/v1/auth/me');

    $perms = $response->json('data.permissions');
    expect($perms['findings.reports']['view'])->toBeFalse();
});

test('create tanpa view tidak efektif', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $user = User::create([
        'name' => 'Staff',
        'email' => 'staff3@example.com',
        'username' => 'staff3',
        'password' => Hash::make('password'),
        'role' => Role::StaffDept,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    $menu = Menu::where('code', 'assessments')->first();

    UserMenuPermission::create([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'can_view' => false,
        'can_create' => true, // tidak efektif karena view false
    ]);

    $response = $this->actingAs($user, 'sanctum')
                     ->getJson('/api/v1/auth/me');

    $perms = $response->json('data.permissions');
    expect($perms['assessments']['view'])->toBeFalse();
    expect($perms['assessments']['create'])->toBeFalse();
});

test('route ditolak 403 tanpa izin view', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $user = User::create([
        'name' => 'No Akses',
        'email' => 'noaccess@example.com',
        'username' => 'noaccess',
        'password' => Hash::make('password'),
        'role' => Role::StaffDept,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    // Staff dept default tidak punya dashboard view di seeder, tapi kita set false
    $dashboardMenu = Menu::where('code', 'dashboard')->first();
    UserMenuPermission::create([
        'user_id' => $user->id,
        'menu_id' => $dashboardMenu->id,
        'can_view' => false,
    ]);

    $response = $this->actingAs($user, 'sanctum')
                     ->getJson('/api/v1/dashboard');

    $response->assertStatus(403);
});

test('cache terinvalidasi saat izin user berubah', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    $user = User::create([
        'name' => 'Staff',
        'email' => 'staff4@example.com',
        'username' => 'staff4',
        'password' => Hash::make('password'),
        'role' => Role::StaffDept,
        'department_id' => Department::first()->id,
        'is_active' => true,
    ]);

    // Hit pertama: hasil di-cache, staff dept tidak punya akses assessments
    $perms1 = PermissionService::effective($user);
    expect($perms1['assessments']['view'])->toBeFalse();

    // Tambahkan override user agar dapat view assessments
    $menu = Menu::where('code', 'assessments')->first();
    UserMenuPermission::create([
        'user_id' => $user->id,
        'menu_id' => $menu->id,
        'can_view' => true,
        'can_create' => true,
    ]);

    // Hit kedua: masih ter cache, nilai lama masih false
    $perms2 = PermissionService::effective($user);
    expect($perms2['assessments']['view'])->toBeFalse();

    // Invalidasi cache untuk user ini
    PermissionService::invalidateForUser($user);

    // Hit ketiga: di-rebuild dari DB, kini mencerminkan override
    $perms3 = PermissionService::effective($user);
    expect($perms3['assessments']['view'])->toBeTrue();
    expect($perms3['assessments']['create'])->toBeTrue();
});