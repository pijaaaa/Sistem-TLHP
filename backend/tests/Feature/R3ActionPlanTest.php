<?php

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Events\ActionPlanSent;
use App\Events\PicAssigned;
use App\Models\Department;
use App\Models\Finding;
use App\Models\User;
use App\Services\FindingService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('username', 'admin_spi')->first();
});

function activeFinding(array $deptCodes = ['FINANCE_ICT']): Finding
{
    $service = new FindingService();
    $finding = $service->createDraft([
        'title' => 'Temuan R3',
        'source' => 'BPK',
        'source_name' => null,
        'lhp_number' => 'LHP/R3/01',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Audit R3',
    ]);

    $finding->documents()->create([
        'label' => 'LHP',
        'name' => 'lhp.pdf',
        'path' => 'findings/lhp.pdf',
        'mime' => 'application/pdf',
        'size' => 1024,
    ]);

    $deptIds = Department::whereIn('code', $deptCodes)->pluck('id')->all();
    $finding = $service->register($finding, $deptIds);

    return $service->activate($finding);
}

function apPayload(array $departmentIds): array
{
    return [
        'finding_id' => $GLOBALS['current_finding_id'] ?? 0,
        'department_ids' => $departmentIds,
        'title' => 'Tindak perbaikan',
        'condition' => 'Kondisi awal',
        'criteria' => 'Kriteria pemenuhan',
        'cause' => 'Akar masalah',
        'impact' => 'Dampak',
        'risk' => 'TINGGI',
        'deadline' => '2026-11-30',
        'loss_idr' => 1000000,
        'loss_usd' => 100,
    ];
}

function createApViaApi(Finding $finding, array $deptCodes): array
{
    $deptIds = Department::whereIn('code', $deptCodes)->pluck('id')->all();

    return test()->actingAs(test()->admin, 'sanctum')
        ->postJson('/api/v1/action-plans', [
            'finding_id' => $finding->id,
            'department_ids' => $deptIds,
            'title' => 'Tindak perbaikan',
            'condition' => 'Kondisi awal',
            'criteria' => 'Kriteria',
            'cause' => 'Sebab',
            'impact' => 'Dampak',
            'risk' => 'TINGGI',
            'deadline' => '2026-11-30',
            'loss_idr' => 1000000,
            'loss_usd' => 100,
        ])
        ->assertCreated()
        ->json('data');
}

test('satu formulir membuat action plan untuk beberapa departemen', function () {
    $finding = activeFinding(['FINANCE_ICT', 'EKS']);
    $data = createApViaApi($finding, ['FINANCE_ICT', 'EKS']);

    expect($data)->toHaveCount(2);
    expect(collect($data)->pluck('title')->unique()->all())->toBe(['Tindak perbaikan']);
    expect(collect($data)->pluck('code')->all())->each->toContain('/AP-');
    expect(collect($data)->pluck('department_id')->sort()->values()->all())
        ->toEqual(Department::whereIn('code', ['FINANCE_ICT', 'EKS'])->pluck('id')->sort()->values()->all());
});

test('departemen non-auditee ditolak', function () {
    $finding = activeFinding();
    $spi = Department::where('code', 'SPI')->first();

    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [$spi->id],
        'title' => 'Ditolak',
    ])->assertStatus(422);
});

test('action plan hanya boleh dibuat untuk temuan terdaftar atau proses', function () {
    $finding = Finding::first();
    expect($finding->status)->toEqual(FindingStatus::Draft);

    $dept = Department::where('code', 'FINANCE_ICT')->first();

    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [$dept->id],
        'title' => 'Belum boleh',
    ])->assertStatus(422);
});

test('departemen melihat action plan hanya setelah dikirim', function () {
    $finding = activeFinding();
    $data = createApViaApi($finding, ['FINANCE_ICT']);
    $apId = $data[0]['id'];
    $manager = User::where('username', 'mgr_finance_ict')->first();

    $this->actingAs($manager, 'sanctum')->getJson('/api/v1/action-plans')->assertOk()
        ->assertJsonCount(0, 'data.data');

    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$apId]])
        ->assertOk()
        ->assertJsonPath('data.0.status', ActionPlanStatus::MenungguPenentuanPic->value);

    $this->actingAs($manager, 'sanctum')->getJson('/api/v1/action-plans')->assertOk()
        ->assertJsonCount(1, 'data.data');
});

test('PIC hanya melihat action plan tempat ia ditunjuk', function () {
    $finding = activeFinding(['FINANCE_ICT', 'EKS']);
    $aps = createApViaApi($finding, ['FINANCE_ICT', 'EKS']);
    $ids = collect($aps)->pluck('id');

    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => $ids->all()])->assertOk();

    $managerFinance = User::where('username', 'mgr_finance_ict')->first();
    $managerEks = User::where('username', 'mgr_eks')->first();
    $picFinance = User::where('username', 'pic_1_finance_ict')->first();
    $picEks = User::where('username', 'pic_1_eks')->first();

    $apFinance = $aps[0]['department_id'] === $managerFinance->department_id ? $aps[0] : $aps[1];
    $apEks = $aps[0]['department_id'] === $managerEks->department_id ? $aps[0] : $aps[1];

    $this->actingAs($managerFinance, 'sanctum')->postJson("/api/v1/action-plans/{$apFinance['id']}/assign-pics", [
        'user_ids' => [$picFinance->id],
    ])->assertOk();

    $this->actingAs($managerEks, 'sanctum')->postJson("/api/v1/action-plans/{$apEks['id']}/assign-pics", [
        'user_ids' => [$picEks->id],
    ])->assertOk();

    // PIC A hanya melihat AP miliknya.
    $this->actingAs($picFinance, 'sanctum')->getJson('/api/v1/action-plans')->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.id', $apFinance['id']);

    $this->actingAs($picFinance, 'sanctum')->getJson("/api/v1/action-plans/{$apEks['id']}")->assertNotFound();
    $this->actingAs($picFinance, 'sanctum')->getJson("/api/v1/action-plans/{$apEks['id']}/documents")->assertNotFound();

    // Departemen lain tidak melihat AP finance.
    $this->actingAs($managerEks, 'sanctum')->getJson("/api/v1/action-plans/{$apFinance['id']}")->assertNotFound();
});

test('PIC kehilangan akses setelah temuan CLOSED sementara manager tetap', function () {
    $finding = activeFinding();
    $ap = createApViaApi($finding, ['FINANCE_ICT'])[0];

    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();

    $manager = User::where('username', 'mgr_finance_ict')->first();
    $pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->actingAs($manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", [
        'user_ids' => [$pic->id],
    ])->assertOk();

    $this->actingAs($pic, 'sanctum')->getJson('/api/v1/action-plans')->assertOk()
        ->assertJsonCount(1, 'data.data');

    $finding->update(['status' => FindingStatus::Closed]);

    $this->actingAs($pic, 'sanctum')->getJson('/api/v1/action-plans')->assertOk()
        ->assertJsonCount(0, 'data.data');

    $this->actingAs($manager, 'sanctum')->getJson('/api/v1/action-plans')->assertOk()
        ->assertJsonCount(1, 'data.data');
});

test('hanya action plan draft yang boleh diubah dan dihapus', function () {
    $finding = activeFinding();
    $ap = createApViaApi($finding, ['FINANCE_ICT'])[0];

    $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/v1/action-plans/{$ap['id']}", ['title' => 'Draft diubah'])
        ->assertOk()
        ->assertJsonPath('data.title', 'Draft diubah');

    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();

    $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/v1/action-plans/{$ap['id']}", ['title' => 'Tidak boleh'])
        ->assertStatus(403);

    $this->actingAs($this->admin, 'sanctum')
        ->deleteJson("/api/v1/action-plans/{$ap['id']}")
        ->assertStatus(403);
});

test('action plan draft dapat dihapus', function () {
    $finding = activeFinding();
    $ap = createApViaApi($finding, ['FINANCE_ICT'])[0];

    $this->actingAs($this->admin, 'sanctum')->deleteJson("/api/v1/action-plans/{$ap['id']}")->assertOk();

    $this->actingAs($this->admin, 'sanctum')->getJson("/api/v1/action-plans/{$ap['id']}")->assertNotFound();
});

test('setelah terkirim hanya admin_spi yang boleh mengubah deadline', function () {
    $finding = activeFinding();
    $ap = createApViaApi($finding, ['FINANCE_ICT'])[0];
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();

    $manager = User::where('username', 'mgr_finance_ict')->first();
    $this->actingAs($manager, 'sanctum')
        ->putJson("/api/v1/action-plans/{$ap['id']}/deadline", ['deadline' => '2026-12-31'])
        ->assertStatus(403);

    $this->actingAs($this->admin, 'sanctum')
        ->putJson("/api/v1/action-plans/{$ap['id']}/deadline", ['deadline' => '2026-12-31'])
        ->assertOk()
        ->assertJsonPath('data.deadline', '2026-12-31');
});

test('penunjukan PIC hanya oleh manager departemen terkait dan PIC harus staff aktif satu departemen', function () {
    $finding = activeFinding(['FINANCE_ICT', 'EKS']);
    $aps = createApViaApi($finding, ['FINANCE_ICT', 'EKS']);
    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', [
        'ids' => collect($aps)->pluck('id')->all(),
    ])->assertOk();

    $managerEks = User::where('username', 'mgr_eks')->first();
    $managerFinance = User::where('username', 'mgr_finance_ict')->first();
    $picFinance = User::where('username', 'pic_1_finance_ict')->first();
    $picEks = User::where('username', 'pic_1_eks')->first();

    $apFinance = collect($aps)->firstWhere('department_id', $managerFinance->department_id);

    // Manager departemen lain tidak boleh menunjuk PIC (AP tersembunyi oleh visibilitas).
    $this->actingAs($managerEks, 'sanctum')
        ->postJson("/api/v1/action-plans/{$apFinance['id']}/assign-pics", ['user_ids' => [$picEks->id]])
        ->assertNotFound();

    // PIC dari departemen lain ditolak.
    $this->actingAs($managerFinance, 'sanctum')
        ->postJson("/api/v1/action-plans/{$apFinance['id']}/assign-pics", ['user_ids' => [$picEks->id]])
        ->assertStatus(422);

    // PIC sah → status berubah ke Proses Tindak Lanjut.
    $this->actingAs($managerFinance, 'sanctum')
        ->postJson("/api/v1/action-plans/{$apFinance['id']}/assign-pics", ['user_ids' => [$picFinance->id]])
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::ProsesTindakLanjut->value);
});

test('event didispatch pada kirim dan penunjukan PIC', function () {
    Event::fake([ActionPlanSent::class, PicAssigned::class]);

    $finding = activeFinding();
    $ap = createApViaApi($finding, ['FINANCE_ICT'])[0];

    $this->actingAs($this->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    Event::assertDispatched(ActionPlanSent::class);

    $manager = User::where('username', 'mgr_finance_ict')->first();
    $pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->actingAs($manager, 'sanctum')
        ->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [$pic->id]])
        ->assertOk();

    Event::assertDispatched(PicAssigned::class, fn ($event) => $event->userIds === [$pic->id]);
});
