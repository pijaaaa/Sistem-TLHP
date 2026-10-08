<?php

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;

beforeEach(function () {
    $this->seed();
});

test('seeder creates 7 roles with correct departments', function () {
    $spi = Department::where('code', 'SPI')->first();
    $ia = Department::where('code', 'IA')->first();
    $direksi = Department::where('code', 'DIREKSI')->first();
    $financeIct = Department::where('code', 'FINANCE_ICT')->first();

    expect($spi)->not->toBeNull();
    expect($ia)->not->toBeNull();
    expect($direksi)->not->toBeNull();
    expect($financeIct)->not->toBeNull();

    expect($spi->is_auditee)->toBeFalse();
    expect($ia->is_auditee)->toBeTrue();
    expect($direksi->is_auditee)->toBeFalse();
    expect($financeIct->is_auditee)->toBeTrue();
});

test('login rejected after active_until date', function () {
    $user = User::where('username', 'admin_spi')->first();
    $user->update(['active_until' => now()->subDay()]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(403);
    $response->assertJsonPath('message', 'Akun Anda telah kadaluarsa.');
});

test('login accepted before active_until date', function () {
    $user = User::where('username', 'admin_spi')->first();
    $user->update(['active_until' => now()->addDay()]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['data' => ['token']]);
});

test('superadmin not limited by active_until', function () {
    $user = User::where('username', 'superadmin')->first();
    $user->update(['active_until' => now()->subDay()->toDateString()]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200);
});

test('lookup departments returns only auditee', function () {
    $user = User::where('username', 'admin_spi')->first();

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/lookups/departments?auditee=1');

    $response->assertStatus(200);
    $depts = $response->json('data.data');

    $codes = collect($depts)->pluck('code')->all();
    expect($codes)->toContain('IA', 'FINANCE_ICT');
    expect($codes)->not->toContain('SPI', 'DIREKSI');
});

test('manager_ia and direksi have read-only permissions', function () {
    expect(Role::ManagerIa->isReadOnlyMonitor())->toBeTrue();
    expect(Role::Direksi->isReadOnlyMonitor())->toBeTrue();
    expect(Role::AdminSpi->isReadOnlyMonitor())->toBeFalse();
});

test('monitor roles include correct roles', function () {
    expect(Role::AdminSpi->isMonitor())->toBeTrue();
    expect(Role::InternalAudit->isMonitor())->toBeTrue();
    expect(Role::Kepala_spi->isMonitor())->toBeTrue();
    expect(Role::ManagerIa->isMonitor())->toBeTrue();
    expect(Role::Direksi->isMonitor())->toBeTrue();
    expect(Role::ManagerDept->isMonitor())->toBeFalse();
    expect(Role::StaffDept->isMonitor())->toBeFalse();
});

test('auth/me returns active_until', function () {
    $user = User::where('username', 'admin_spi')->first();
    $user->update(['active_until' => '2026-12-31']);

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/auth/me');

    $response->assertStatus(200);
    expect($response->json('data.active_until'))->toEqual('2026-12-31');
});


