<?php

use App\Enums\FollowUpStatus;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\FindingService;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->manager = User::where('username', 'mgr_finance_ict')->first();
    $this->picA = User::where('username', 'pic_1_finance_ict')->first();
    $this->picB = User::where('username', 'pic_2_finance_ict')->first();
    $this->managerEks = User::where('username', 'mgr_eks')->first();
    $this->ia = User::where('username', 'internal_audit')->first();
    $this->managerIa = User::where('username', 'manager_ia')->first();
});

test('semua route api v1 dilindungi permission atau daftar putih eksplisit', function () {
    $authOnlyUris = [
        'api/v1/notifications',
        'api/v1/notifications/unread-count',
        'api/v1/notifications/{notification}/read',
        'api/v1/notifications/read-all',
        'api/v1/follow-ups/{follow_up}/comments',
        'api/v1/auth/me',
        'api/v1/auth/logout',
        'api/v1/auth/change-password',
        'api/v1/lookups/departments',
        'api/v1/lookups/staff',
    ];
    $publicUris = [
        'api/v1/health',
        'api/v1/auth/login',
    ];

    $checked = 0;
    foreach (Route::getRoutes() as $route) {
        $uri = $route->uri();
        if (!str_starts_with($uri, 'api/v1/')) {
            continue;
        }

        $checked++;

        if (in_array($uri, $publicUris, true) || in_array($uri, $authOnlyUris, true)) {
            continue;
        }

        $middleware = implode(',', $route->gatherMiddleware());
        expect($middleware)->toContain('permission');
    }

    expect($checked)->toBeGreaterThan(25);
});

test('tidak ada endpoint unduhan massal atau zip', function () {
    foreach (Route::getRoutes() as $route) {
        $uri = $route->uri();
        if (!str_starts_with($uri, 'api/v1/')) {
            continue;
        }
        expect(strtolower($uri))->not->toMatch('/bulk|zip|download-all|export-all/');
    }

    expect(true)->toBeTrue();
});

test('visibilitas antar PIC dalam satu departemen dan PIC pinjam departemen lain', function () {
    $svc = new FindingService();
    $finding = $svc->createDraft([
        'title' => 'Temuan Matrix', 'source' => 'BPK', 'source_name' => null,
        'lhp_number' => 'LHP/M/1', 'lhp_date' => '2026-06-01', 'finding_date' => '2026-06-15',
        'response_period_start' => '2026-07-01', 'response_period_end' => '2026-12-31', 'scope' => 'Audit M',
    ]);
    $finding->documents()->create(['label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1]);
    $svc->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);
    $svc->activate($finding);

    $ap = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
        'title' => 'AP Matrix', 'risk' => 'SEDANG', 'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", [
        'user_ids' => [$this->picA->id, $this->picB->id],
    ])->assertOk();

    // Dua TL, PIC berbeda.
    $fuA = $this->actingAs($this->picA, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/follow-ups", [
        'rows' => [['description' => 'TL A', 'target_date' => '2026-10-01', 'weight' => 50, 'pic_ids' => [$this->picA->id]]],
    ])->assertCreated()->json('data')[0];
    $fuB = $this->actingAs($this->picB, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/follow-ups", [
        'rows' => [['description' => 'TL B', 'target_date' => '2026-10-02', 'weight' => 50, 'pic_ids' => [$this->picB->id]]],
    ])->assertCreated()->json('data')[0];

    $this->actingAs($this->picA, 'sanctum')->getJson("/api/v1/follow-ups?action_plan_id={$ap['id']}")->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.description', 'TL A');

    $this->actingAs($this->picA, 'sanctum')->getJson("/api/v1/follow-ups/{$fuB['id']}")->assertNotFound();

    // Manager departemen lain tidak melihat AP ini.
    $this->actingAs($this->managerEks, 'sanctum')->getJson("/api/v1/action-plans/{$ap['id']}")->assertNotFound();
});

test('pemantau tidak melihat tindak lanjut sebelum disetujui', function () {
    $svc = new FindingService();
    $finding = $svc->createDraft(['title' => 'P', 'source' => 'BPK', 'source_name' => null, 'lhp_number' => 'LHP/P', 'lhp_date' => '2026-06-01', 'finding_date' => '2026-06-15', 'response_period_start' => '2026-07-01', 'response_period_end' => '2026-12-31', 'scope' => 'x']);
    $finding->documents()->create(['label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1]);
    $svc->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);
    $svc->activate($finding);

    $ap = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id, 'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
        'title' => 'AP P', 'risk' => 'RENDAH', 'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [$this->picA->id]])->assertOk();

    $fu = $this->actingAs($this->picA, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/follow-ups", [
        'rows' => [['description' => 'TL P', 'target_date' => '2026-10-01', 'weight' => 100, 'pic_ids' => [$this->picA->id]]],
    ])->assertCreated()->json('data')[0];
    $this->actingAs($this->picA, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu['id']]])->assertOk();

    // Pemantau tidak melihat DIAJUKAN.
    $this->actingAs($this->ia, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()->assertJsonCount(0, 'data.data');

    // Setelah disetujui, pemantau melihat.
    $this->actingAs($this->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/approve")->assertOk();
    $this->actingAs($this->ia, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()->assertJsonCount(1, 'data.data');
});

test('departemen ia sebagai auditee: manager_dept ia mengelola, manager_ia baca saja', function () {
    $iaDept = Department::where('code', 'IA')->first();
    $managerIaAuditee = User::where('username', 'manager_ia_auditee')->first();
    $picIa = User::where('username', 'pic_ia')->first();

    $svc = new FindingService();
    $finding = $svc->createDraft(['title' => 'IA Auditee', 'source' => 'BPK', 'source_name' => null, 'lhp_number' => 'LHP/IA', 'lhp_date' => '2026-06-01', 'finding_date' => '2026-06-15', 'response_period_start' => '2026-07-01', 'response_period_end' => '2026-12-31', 'scope' => 'x']);
    $finding->documents()->create(['label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1]);
    $svc->register($finding, [$iaDept->id]);
    $svc->activate($finding);

    $ap = $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id, 'department_ids' => [$iaDept->id],
        'title' => 'AP IA', 'risk' => 'SEDANG', 'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();

    // Manager_dept IA menunjuk PIC IA.
    $this->actingAs($managerIaAuditee, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [$picIa->id]])
        ->assertOk();

    // manager_ia (baca saja) tidak boleh memutuskan.
    $this->actingAs($this->managerIa, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [$picIa->id]])
        ->assertStatus(403);
});