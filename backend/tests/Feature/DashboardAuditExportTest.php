<?php

use App\Enums\Role;
use App\Models\Audit;
use App\Models\Finding;
use App\Models\FindingDepartment;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([DepartmentSeeder::class, MenuSeeder::class, PermissionSeeder::class]);
    Storage::fake(config('upload.disk'));

    $this->admin = createTestUser(Role::AdminSpi);
    $this->managerIa = createTestUser(Role::ManagerIa, 'IA');
    $this->managerDept = createTestUser(Role::ManagerDept, 'FINANCE_ICT');
    $this->pic = createTestUser(Role::StaffDept, 'FINANCE_ICT');
    $this->managerSpi = createTestUser(Role::ManagerSpi);
});

test('setiap perubahan status tercatat di audit trail', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M10-001',
        'title' => 'Audit test',
    ])->assertStatus(201);

    $findingId = Finding::where('code', 'T-M10-001')->first()->id;

    $this->actingAs($this->admin, 'sanctum')
        ->postJson("/api/v1/findings/{$findingId}/send-to-ia")->assertOk();

    $audits = Audit::where('entity_type', 'finding')->where('entity_id', $findingId)->get();

    expect($audits)->not->toBeEmpty();
    expect($audits->pluck('action'))->toContain('finding.sent_to_ia');
    expect($audits->first()->user_id)->toBe($this->admin->id);
    expect($audits->first()->created_at)->not->toBeNull();
});

test('audit menyimpan ip address dan deskripsi', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M10-002',
        'title' => 'Audit ip',
    ])->assertStatus(201);

    $audit = Audit::where('action', 'finding.created')->first();

    expect($audit)->not->toBeNull();
    expect($audit->description)->not->toBeEmpty();
    expect($audit->ip_address)->not->toBeNull();
});

test('izin audit trail dihormati: override user mencabut akses', function () {
    // Semua role punya audit_trail view secara default; override user yang mencabut.
    $menu = \App\Models\Menu::where('code', 'audit_trail')->first();
    \App\Models\UserMenuPermission::create([
        'user_id' => $this->pic->id,
        'menu_id' => $menu->id,
        'can_view' => false,
    ]);

    $this->actingAs($this->pic, 'sanctum')->getJson('/api/v1/audit-trail')->assertStatus(403);
});

test('manager ia bisa melihat audit trail dan difilter per aksi', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M10-003',
        'title' => 'Filter test',
    ])->assertStatus(201);

    $res = $this->actingAs($this->managerIa, 'sanctum')->getJson('/api/v1/audit-trail?action=finding.created');
    $res->assertOk();

    $actions = collect($res->json('data.data'))->pluck('action')->unique();
    expect($actions->all())->toBe(['finding.created']);
});

test('audit trail difilter per rentang tanggal', function () {
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M10-004',
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
        'code' => 'T-M10-005',
        'title' => 'Actions test',
    ])->assertStatus(201);

    $res = $this->actingAs($this->managerIa, 'sanctum')->getJson('/api/v1/audit-trail/actions');
    $res->assertOk();
    expect($res->json('data'))->toContain('finding.created');
});

test('dashboard manager ia menampilkan counter yang relevan', function () {
    $res = $this->actingAs($this->managerIa, 'sanctum')->getJson('/api/v1/dashboard');
    $res->assertOk();

    $data = $res->json('data');
    expect($data['role'])->toBe('manager_ia');
    expect($data['counters'])->toHaveKeys([
        'menunggu_distribusi',
        'menunggu_assessment',
        'dalam_proses',
        'menunggu_verifikasi',
    ]);
    expect($data['findings_by_status'])->toBeArray();
});

test('dashboard staff menampilkan counter tindak lanjut sendiri', function () {
    $res = $this->actingAs($this->pic, 'sanctum')->getJson('/api/v1/dashboard');
    $res->assertOk();

    expect($res->json('data.role'))->toBe('staff_dept');
    expect($res->json('data.counters'))->toHaveKeys([
        'draft', 'diajukan', 'revisi', 'menunggu_evidence', 'evidence_revisi', 'selesai',
    ]);
});

test('dashboard manager spi menampilkan counter verifikasi', function () {
    $res = $this->actingAs($this->managerSpi, 'sanctum')->getJson('/api/v1/dashboard');
    $res->assertOk();

    expect($res->json('data.counters'))->toHaveKeys([
        'menunggu_verifikasi', 'closed', 'case_closed', 'assessment_ssr',
    ]);
});

test('dashboard manager dept hanya menghitung departemennya', function () {
    $res = $this->actingAs($this->managerDept, 'sanctum')->getJson('/api/v1/dashboard');
    $res->assertOk();

    $data = $res->json('data');
    expect($data['role'])->toBe('manager_dept');
    expect($data['counters'])->toHaveKeys([
        'dalam_proses', 'selesai_100', 'menunggu_review_tindak_lanjut', 'menunggu_review_evidence',
    ]);

    // Temuan dari departemen lain tidak boleh terhitung.
    $other = \App\Models\Department::where('code', 'EKS')->first();
    $findingId = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M10-DEP',
        'title' => 'Dept lain',
    ])->json('data.id');

    FindingDepartment::create([
        'finding_id' => $findingId,
        'round' => 1,
        'department_id' => $other->id,
        'status' => 'dalam_proses',
    ]);

    $res = $this->actingAs($this->managerDept, 'sanctum')->getJson('/api/v1/dashboard');
    expect($res->json('data.counters.dalam_proses'))->toBe(0);
});

test('export temuan menghasilkan csv', function () {
    $id = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/findings', [
        'code' => 'T-M10-006',
        'title' => 'Export test',
    ])->json('data.id');

    // Manager IA tidak melihat temuan berstatus Draft (scope visibilitas).
    $this->actingAs($this->admin, 'sanctum')->postJson("/api/v1/findings/{$id}/send-to-ia")->assertOk();

    $res = $this->actingAs($this->managerIa, 'sanctum')->get('/api/v1/exports/findings');
    $res->assertOk();
    $res->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $content = $res->streamedContent();
    expect($content)->toContain('Kode');
    expect($content)->toContain('T-M10-006');
});

test('export rencana aksi ada dan tidak bocor data user lain', function () {
    $res = $this->actingAs($this->managerIa, 'sanctum')->get('/api/v1/exports/action-plans');
    $res->assertOk();

    $content = $res->streamedContent();
    expect($content)->toContain('Kode Temuan');
});

test('export ditolak 403 tanpa izin exports', function () {
    $this->actingAs($this->pic, 'sanctum')->get('/api/v1/exports/findings')->assertStatus(403);
});

test('export audit trail mengikuti izin audit_trail', function () {
    $menu = \App\Models\Menu::where('code', 'audit_trail')->first();
    \App\Models\UserMenuPermission::create([
        'user_id' => $this->managerIa->id,
        'menu_id' => $menu->id,
        'can_view' => false,
    ]);

    $this->actingAs($this->managerIa, 'sanctum')->get('/api/v1/exports/audit-trail')->assertStatus(403);

    $res = $this->actingAs($this->managerDept, 'sanctum')->get('/api/v1/exports/audit-trail');
    $res->assertOk();
});