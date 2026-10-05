<?php

use App\Enums\Role;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Menu;
use App\Models\User;
use App\Services\DepartmentService;
use App\Services\PermissionService;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;

function makeUser(Role $role, string $deptCode = 'FINANCE_ICT'): User
{
    $dept = Department::where('code', $deptCode)->first();
    return User::create([
        'name' => 'User',
        'email' => $role->value . '@example.com',
        'username' => $role->value,
        'password' => Hash::make('password'),
        'role' => $role->value,
        'department_id' => $dept->id,
        'is_active' => true,
    ]);
}

test('department list requires permission view', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    makeUser(Role::StaffDept);
    $staff = User::where('role', Role::StaffDept->value)->first();
    // Staff dept default punya master.departments view (lihat), tapi tidak create
    $response = $this->actingAs($staff, 'sanctum')->getJson('/api/v1/master/departments');
    $response->assertOk();
});

test('staff tidak boleh POST department (403)', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    makeUser(Role::StaffDept);
    $staff = User::where('role', Role::StaffDept->value)->first();

    $response = $this->actingAs($staff, 'sanctum')->postJson('/api/v1/master/departments', [
        'code' => 'X', 'name' => 'X',
    ]);
    $response->assertStatus(403);
});

test('admin bisa CRUD department', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    makeUser(Role::AdminSpi);
    $admin = User::where('role', Role::AdminSpi->value)->first();

    $create = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/master/departments', [
        'code' => 'NEWTES', 'name' => 'Departemen Test',
    ]);
    $create->assertStatus(201);
    $id = $create->json('data.id');

    $show = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/master/departments/{$id}");
    $show->assertOk();

    $update = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/master/departments/{$id}", [
        'code' => 'NEWUP', 'name' => 'Updated Name', 'is_active' => true,
    ]);
    $update->assertOk();

    $delete = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/master/departments/{$id}");
    $delete->assertOk();

    expect(Department::withTrashed()->find($id)->deleted_at)->not->toBeNull();
});

test('department cache terinvalidasi setelah create', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);

    DepartmentService::all(); // prime cache
    makeUser(Role::AdminSpi);
    $admin = User::where('role', Role::AdminSpi->value)->first();

    $before = DepartmentService::all()->where('code', 'CACHETEST')->isEmpty();
    expect($before)->toBeTrue();

    $this->actingAs($admin, 'sanctum')->postJson('/api/v1/master/departments', [
        'code' => 'CACHETEST', 'name' => 'Cache Test',
    ])->assertStatus(201);

    $after = DepartmentService::all()->where('code', 'CACHETEST')->isNotEmpty();
    expect($after)->toBeTrue();
});

test('employee create mem-validasi department_id exists', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    makeUser(Role::AdminSpi);
    $admin = User::where('role', Role::AdminSpi->value)->first();

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/master/employees', [
        'nik' => '123', 'name' => 'Budi', 'position' => 'Staff', 'department_id' => '9999',
    ]);
    $response->assertStatus(422);
});

test('user create mem-validasi role enum dan password confirmed', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    makeUser(Role::AdminSpi);
    $admin = User::where('role', Role::AdminSpi->value)->first();

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/master/users', [
        'name' => 'Budi',
        'email' => 'budi@example.com',
        'username' => 'budi',
        'role' => 'invalid_role',
        'department_id' => Department::first()->id,
    ]);
    $response->assertStatus(422);

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/master/users', [
        'name' => 'Budi',
        'email' => 'budi2@example.com',
        'username' => 'budi2',
        'role' => Role::StaffDept->value,
        'department_id' => Department::first()->id,
        'password' => 'secret',
    ]);
    $response->assertStatus(422);
});

test('superadmin bisa read & update role permission matrix', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class, UserSeeder::class]);
    $super = User::where('role', Role::SuperAdmin->value)->first();

    $matrix = $this->actingAs($super, 'sanctum')->getJson('/api/v1/access/permissions/roles/' . Role::StaffDept->value);
    $matrix->assertOk();
    expect($matrix->json('data.matrix'))->toBeArray();

    // Cabut semua view pada staff_dept untuk assessments, lalu restore via update
    $updated = $this->actingAs($super, 'sanctum')->putJson('/api/v1/access/permissions/roles/' . Role::StaffDept->value, [
        'permissions' => [
            'assessments' => ['view' => false, 'create' => false, 'update' => false, 'delete' => false],
        ],
    ]);
    $updated->assertOk();
    expect(PermissionService::roleMatrix(Role::StaffDept)['assessments']['view'])->toBeFalse();

    // Cache user dengan role tersebut terinvalidasi otomatis; effective rebuild
    $staff = makeUser(Role::StaffDept);
    PermissionService::invalidateForUser($staff);
    expect(PermissionService::effective($staff)['assessments']['view'])->toBeFalse();
});

test('user menu override dapat dibaca & diupdate', function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class, UserSeeder::class]);
    $super = User::where('role', Role::SuperAdmin->value)->first();
    $staff = makeUser(Role::StaffDept);

    $this->actingAs($super, 'sanctum')->putJson('/api/v1/access/permissions/users/' . $staff->id, [
        'permissions' => [
            'assessments' => ['view' => true, 'create' => true, 'update' => null, 'delete' => null],
        ],
    ])->assertOk();

    $matrix = $this->actingAs($super, 'sanctum')->getJson('/api/v1/access/permissions/users/' . $staff->id);
    $matrix->assertOk();
    expect($matrix->json('data.overrides.assessments.view'))->toBeTrue();
    expect($matrix->json('data.effective.assessments.view'))->toBeTrue();
});
