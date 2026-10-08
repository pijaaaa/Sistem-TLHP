<?php

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Models\ActionPlan;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\Finding;
use App\Models\User;
use App\Services\FindingService;

beforeEach(function () {
    $this->seed();
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->pic2 = User::where('username', 'pic_2_finance_ict')->first();
    $this->manager = User::where('username', 'mgr_finance_ict')->first();
});

function readyActionPlan(): ActionPlan
{
    $service = new FindingService();
    $finding = $service->createDraft([
        'title' => 'Temuan R4',
        'source' => 'BPK',
        'source_name' => null,
        'lhp_number' => 'LHP/R4/01',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Audit R4',
    ]);
    $finding->documents()->create([
        'label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'findings/lhp.pdf', 'mime' => 'application/pdf', 'size' => 1024,
    ]);
    $service->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);
    $service->activate($finding);

    $ap = test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans', [
        'finding_id' => $finding->id,
        'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
        'title' => 'Perbaikan R4',
        'risk' => 'TINGGI',
        'deadline' => '2026-11-30',
    ])->assertCreated()->json('data')[0];

    test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
    test()->actingAs(test()->manager, 'sanctum')
        ->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", ['user_ids' => [test()->pic->id]])
        ->assertOk()
        ->assertJsonPath('data.status', ActionPlanStatus::ProsesTindakLanjut->value);

    return ActionPlan::withoutGlobalScopes()->find($ap['id']);
}

function followUpsPayload(array $rows): array
{
    return ['rows' => $rows];
}

test('PIC dapat menyusun beberapa tindak lanjut sekaligus sebagai draft', function () {
    $ap = readyActionPlan();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'Buat SOP baru', 'target_date' => '2026-10-01', 'weight' => 40, 'pic_ids' => [$this->pic->id]],
        ['description' => 'Sosialisasi SOP', 'target_date' => '2026-10-15', 'weight' => 60, 'pic_ids' => [$this->pic->id]],
    ]))->assertCreated()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.status', FollowUpStatus::Draft->value)
        ->assertJsonPath('data.0.revision_no', 0)
        ->assertJsonPath('data.0.weight', 40);

    expect(FollowUp::withoutGlobalScopes()->where('action_plan_id', $ap->id)->count())->toBe(2);
});

test('bobot desimal dan bobot melebihi 100 ditolak', function () {
    $ap = readyActionPlan();

    // Desimal ditolak oleh validasi integer.
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'A', 'target_date' => '2026-10-01', 'weight' => 40.5, 'pic_ids' => [$this->pic->id]],
    ]))->assertStatus(422);

    // 60 + 50 = 110 > 100.
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'A', 'target_date' => '2026-10-01', 'weight' => 60, 'pic_ids' => [$this->pic->id]],
    ]))->assertCreated();

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'B', 'target_date' => '2026-10-05', 'weight' => 50, 'pic_ids' => [$this->pic->id]],
    ]))->assertStatus(422)
        ->assertJsonPath('message', 'Total bobot tindak lanjut aktif melebihi 100. Sisa bobot yang tersedia: 40.');
});

test('target date melewati deadline action plan ditolak', function () {
    $ap = readyActionPlan(); // deadline 2026-11-30

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'Terlambat', 'target_date' => '2026-12-15', 'weight' => 10, 'pic_ids' => [$this->pic->id]],
    ]))->assertStatus(422);
});

test('PIC non-assignee action plan ditolak sebagai PIC tindak lanjut', function () {
    $ap = readyActionPlan(); // assignee hanya pic_1_finance_ict

    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'A', 'target_date' => '2026-10-01', 'weight' => 50, 'pic_ids' => [$this->pic2->id]],
    ]))->assertStatus(422);
});

test('hanya PIC action plan yang dapat menyusun tindak lanjut', function () {
    $ap = readyActionPlan();
    $foreignPic = User::where('username', 'pic_1_eks')->first();

$this->actingAs($foreignPic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'A', 'target_date' => '2026-10-01', 'weight' => 50, 'pic_ids' => [$this->pic->id]],
    ]))->assertNotFound();
});

test('tautan revisi hanya valid ke tindak lanjut action plan yang sama dari revisi sebelumnya', function () {
    $ap = readyActionPlan();

    $created = $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'Revisi dari TL lama', 'target_date' => '2026-10-01', 'weight' => 50, 'pic_ids' => [$this->pic->id]],
    ]))->assertCreated()->json('data');

    $oldId = $created[0]['id'];

    // Tautan ke TL revisi yang sama (revision 0 == current 0) ditolak.
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'B', 'target_date' => '2026-10-02', 'weight' => 50, 'pic_ids' => [$this->pic->id], 'linked_follow_up_id' => $oldId],
    ]))->assertStatus(422);

    // Tautan ke action plan lain ditolak.
    $ap2 = readyActionPlan();
    $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap2->id}/follow-ups", followUpsPayload([
        ['description' => 'C', 'target_date' => '2026-10-03', 'weight' => 50, 'pic_ids' => [$this->pic->id], 'linked_follow_up_id' => $oldId],
    ]))->assertStatus(422);
});

test('submit mengubah draft menjadi diajukan dan hanya oleh PIC', function () {
    $ap = readyActionPlan();
    $created = $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'A', 'target_date' => '2026-10-01', 'weight' => 100, 'pic_ids' => [$this->pic->id]],
    ]))->assertCreated()->json('data');

    $id = $created[0]['id'];

    $this->actingAs($this->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$id]])
        ->assertOk()
        ->assertJsonPath('data.0.status', FollowUpStatus::Diajukan->value);

    // Mengajukan ulang (sudah DIAJUKAN) ditolak.
    $this->actingAs($this->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$id]])
        ->assertStatus(422);
});

test('edit saat DIAJUKAN ditolak', function () {
    $ap = readyActionPlan();
    $created = $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'A', 'target_date' => '2026-10-01', 'weight' => 100, 'pic_ids' => [$this->pic->id]],
    ]))->assertCreated()->json('data');

    $id = $created[0]['id'];
    $this->actingAs($this->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$id]])->assertOk();

    $this->actingAs($this->pic, 'sanctum')
        ->putJson("/api/v1/follow-ups/{$id}", ['description' => 'Ubah'])
        ->assertStatus(422);
});

test('pemantau tidak melihat tindak lanjut sebelum disetujui manager', function () {
    $ap = readyActionPlan();
    $created = $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'A', 'target_date' => '2026-10-01', 'weight' => 100, 'pic_ids' => [$this->pic->id]],
    ]))->assertCreated()->json('data');

    $id = $created[0]['id'];
    $this->actingAs($this->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$id]])->assertOk();

    // Admin SPI (pemantau) hanya melihat DISETUJUI/MENUNGGU_PERSETUJUAN_SELESAI/SELESAI.
    $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()
        ->assertJsonCount(0, 'data.data');

    // Simulasi keputusan manager (R5) langsung di DB.
    FollowUp::withoutGlobalScopes()->whereKey($id)->update(['status' => FollowUpStatus::Disetujui->value]);

    $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()
        ->assertJsonCount(1, 'data.data')
        ->assertJsonPath('data.data.0.status', FollowUpStatus::Disetujui->value);
});

test('PIC kehilangan akses tindak lanjut setelah temuan CLOSED', function () {
    $ap = readyActionPlan();
    $created = $this->actingAs($this->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/follow-ups", followUpsPayload([
        ['description' => 'A', 'target_date' => '2026-10-01', 'weight' => 100, 'pic_ids' => [$this->pic->id]],
    ]))->assertCreated()->json('data');

    $this->actingAs($this->pic, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()
        ->assertJsonCount(1, 'data.data');

    $ap->finding->update(['status' => FindingStatus::Closed]);

    $this->actingAs($this->pic, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()
        ->assertJsonCount(0, 'data.data');

    // Manager tetap melihat.
    $this->actingAs($this->manager, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()
        ->assertJsonCount(1, 'data.data');
});