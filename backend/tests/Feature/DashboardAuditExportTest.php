<?php

use App\Enums\FindingStatus;
use App\Models\Audit;
use App\Models\Department;
use App\Models\Menu;
use App\Models\User;
use App\Models\UserMenuPermission;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class, \Database\Seeders\EmployeeUserSeeder::class]);
    Storage::fake(config('upload.disk'));

    $this->admin = User::where('username', 'admin_spi')->first();
    $this->managerIa = User::where('username', 'manager_ia')->first();
    $this->managerDept = User::where('username', 'mgr_finance_ict')->first();
    $this->pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->kepalaSpi = User::where('username', 'kepala_spi')->first();
});

test('setiap perubahan status tercatat di audit trail', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Audit test',
    ])->assertStatus(201);

    $audits = Audit::where('entity_type', 'finding')->get();

    expect($audits)->not->toBeEmpty();
    expect($audits->pluck('action'))->toContain('finding.created');
    expect($audits->first()->user_id)->toBe($this->admin->id);
    expect($audits->first()->created_at)->not->toBeNull();
});

test('audit menyimpan ip address dan deskripsi', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Audit ip',
    ])->assertStatus(201);

    $audit = Audit::where('action', 'finding.created')->first();

    expect($audit)->not->toBeNull();
    expect($audit->description)->not->toBeEmpty();
    expect($audit->ip_address)->not->toBeNull();
});

test('izin audit trail dihormati: override user mencabut akses', function () {
    $menu = Menu::where('code', 'audit_trail')->first();
    UserMenuPermission::create([
        'user_id' => $this->pic->id,
        'menu_id' => $menu->id,
        'can_view' => false,
    ]);

    $this->actingAs($this->pic, 'sanctum')->getJson('/api/v1/audit-trail')->assertStatus(403);
});

test('manager ia bisa melihat audit trail dan difilter per aksi', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Filter test',
    ])->assertStatus(201);

    $res = $this->actingAs($this->managerIa, 'sanctum')->getJson('/api/v1/audit-trail?action=finding.created');
    $res->assertOk();

    $actions = collect($res->json('data.data'))->pluck('action')->unique();
    expect($actions->all())->toBe(['finding.created']);
});

test('audit trail difilter per rentang tanggal', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Date test',
    ])->assertStatus(201);

    $res = $this->actingAs($this->managerIa, 'sanctum')
        ->getJson('/api/v1/audit-trail?from=' . now()->toDateString());
    $res->assertOk();

    $old = $this->actingAs($this->managerIa, 'sanctum')
        ->getJson('/api/v1/audit-trail?from=' . now()->addDay()->toDateString());
    $old->assertOk();
    expect(collect($old->json('data.data')))->toBeEmpty();
});

test('daftar aksi audit tersedia untuk filter UI', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'title' => 'Actions test',
    ])->assertStatus(201);

    $res = $this->actingAs($this->managerIa, 'sanctum')->getJson('/api/v1/audit-trail/actions');
    $res->assertOk();
    expect($res->json('data'))->toContain('finding.created');
});

test('dashboard admin menampilkan counter temuan', function () {
    $res = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/dashboard');
    $res->assertOk();

    $data = $res->json('data');
    expect($data['role'])->toBe('admin_spi');
    expect($data['counters'])->toHaveKeys([
        'total_temuan', 'draft', 'terdaftar', 'proses_tindak_lanjut', 'closed',
    ]);
    expect($data['findings_by_status'])->toBeArray();
});

test('dashboard staff menampilkan counter action plan milik PIC', function () {
    $res = $this->actingAs($this->pic, 'sanctum')->getJson('/api/v1/dashboard');
    $res->assertOk();

    expect($res->json('data.role'))->toBe('staff_dept');
    expect($res->json('data.counters'))->toHaveKeys([
        'total_action_plan', 'proses_tindak_lanjut', 'diajukan_ke_spi', 'closed',
    ]);
});

test('dashboard manager dept hanya menghitung departemennya', function () {
    $res = $this->actingAs($this->managerDept, 'sanctum')->getJson('/api/v1/dashboard');
    $res->assertOk();

    $data = $res->json('data');
    expect($data['role'])->toBe('manager_dept');
    expect($data['counters'])->toHaveKeys([
        'total_action_plan', 'menunggu_penentuan_pic', 'proses_tindak_lanjut', 'diajukan_ke_spi', 'closed',
    ]);
});

test('dashboard kepala spi menampilkan status eksternal dan closed', function () {
    $res = $this->actingAs($this->kepalaSpi, 'sanctum')->getJson('/api/v1/dashboard');
    $res->assertOk();

    expect($res->json('data.counters'))->toHaveKeys([
        'total_temuan', 'menunggu_status_eksternal', 'review_spi', 'closed',
    ]);
});

test('export temuan menghasilkan csv', function () {
    $this->actingAs($this->admin, 'sanctum');

    $service = new \App\Services\FindingService();
    $finding = $service->createDraft([
        'title' => 'Export test',
        'source' => 'BPK',
        'lhp_number' => 'LHP/EXP',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Ekspor',
    ]);
    $finding->documents()->create([
        'label' => 'LHP', 'name' => 'e.pdf', 'path' => 'findings/e.pdf', 'mime' => 'application/pdf', 'size' => 10,
    ]);
    $service->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);

    $res = $this->actingAs($this->managerIa, 'sanctum')->get('/api/v1/exports/findings');
    $res->assertOk();
    $res->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $res->streamedContent();
    expect($content)->toContain('No Registrasi')
        ->toContain($finding->registration_number);
});

test('export rencana aksi ada dan tidak bocor data user lain', function () {
    $res = $this->actingAs($this->kepalaSpi, 'sanctum')->get('/api/v1/exports/action-plans');
    $res->assertOk();

    $content = $res->streamedContent();
    expect($content)->toContain('Kode');
});

test('export ditolak 403 tanpa izin exports', function () {
    $this->actingAs($this->pic, 'sanctum')->get('/api/v1/exports/findings')->assertStatus(403);
});

test('export audit trail mengikuti izin audit_trail', function () {
    $menu = Menu::where('code', 'audit_trail')->first();
    UserMenuPermission::create([
        'user_id' => $this->managerIa->id,
        'menu_id' => $menu->id,
        'can_view' => false,
    ]);

    $this->actingAs($this->managerIa, 'sanctum')->get('/api/v1/exports/audit-trail')->assertStatus(403);

    $res = $this->actingAs($this->managerDept, 'sanctum')->get('/api/v1/exports/audit-trail');
    $res->assertOk();
});
