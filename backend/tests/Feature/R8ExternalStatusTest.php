<?php

use App\Enums\ActionPlanStatus;
use App\Enums\FindingStatus;
use App\Enums\FollowUpStatus;
use App\Events\FindingClosed;
use App\Models\ActionPlan;
use App\Models\ActionPlanRevision;
use App\Models\Audit;
use App\Models\Department;
use App\Models\FollowUp;
use App\Models\User;
use App\Services\FindingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
    Storage::fake(config('upload.disk'));
    $this->admin = User::where('username', 'admin_spi')->first();
    $this->kepala = User::where('username', 'kepala_spi')->first();
    $this->pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->manager = User::where('username', 'mgr_finance_ict')->first();
});

function r8SettleSesuai(ActionPlan $ap): void
{
    $ids = FollowUp::withoutGlobalScopes()
        ->where('action_plan_id', $ap->id)
        ->where('status', '!=', FollowUpStatus::Ditolak->value)
        ->pluck('id')
        ->all();

    test()->actingAs(test()->admin, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-review", [
        'items' => collect($ids)->map(fn ($id) => ['follow_up_id' => $id, 'result' => 'SESUAI'])->all(),
    ])->assertOk();

    test()->actingAs(test()->admin, 'sanctum')->postJson("/api/v1/action-plans/{$ap->id}/spi-complete")->assertOk();
}

function r8ReadyFinding(int $apCount = 1, array $weight = [100]): \App\Models\Finding
{
    $service = new FindingService();
    $finding = $service->createDraft([
        'title' => 'Temuan R8',
        'source' => 'BPK',
        'source_name' => null,
        'lhp_number' => 'LHP/R8/01',
        'lhp_date' => '2026-06-01',
        'finding_date' => '2026-06-01',
        'response_period_start' => '2026-07-01',
        'response_period_end' => '2026-12-31',
        'scope' => 'Audit R8',
    ]);
    $finding->documents()->create([
        'label' => 'LHP', 'name' => 'lhp.pdf', 'path' => 'findings/lhp.pdf', 'mime' => 'application/pdf', 'size' => 1024,
    ]);
    $service->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]);
    $service->activate($finding);

    $aps = [];
    for ($i = 0; $i < $apCount; $i++) {
        $ap = test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans', [
            'finding_id' => $finding->id,
            'department_ids' => [Department::where('code', 'FINANCE_ICT')->first()->id],
            'title' => "Perbaikan R8-{$i}",
            'risk' => 'TINGGI',
            'deadline' => '2026-11-30',
        ])->assertCreated()->json('data')[0];

        test()->actingAs(test()->admin, 'sanctum')->postJson('/api/v1/action-plans/send', ['ids' => [$ap['id']]])->assertOk();
        test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/assign-pics", [
            'user_ids' => [test()->pic->id],
        ])->assertOk();

        $aps[] = $ap;
    }

    foreach ($aps as $ap) {
        foreach ($weight as $w) {
            $fu = test()->actingAs(test()->pic, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/follow-ups", [
                'rows' => [['description' => "TL {$w}", 'target_date' => '2026-10-01', 'weight' => $w, 'pic_ids' => [test()->pic->id]]],
            ])->assertCreated()->json('data')[0];
            test()->actingAs(test()->pic, 'sanctum')->postJson('/api/v1/follow-ups/submit', ['ids' => [$fu['id']]])->assertOk();
            test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/approve")->assertOk();
            test()->actingAs(test()->pic, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/progress", ['progress_value' => 100])->assertCreated();
            test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/follow-ups/{$fu['id']}/approve-completion")->assertOk();
        }

        test()->actingAs(test()->manager, 'sanctum')->postJson("/api/v1/action-plans/{$ap['id']}/submit-to-spi")->assertOk();
        r8SettleSesuai(ActionPlan::withoutGlobalScopes()->find($ap['id']));
    }

    return $finding->refresh();
}

function externalStatusPayload(?string $status, array $overrides = []): array
{
    return array_merge([
        'status' => $status,
        'note' => 'Catatan status',
        'document' => [
            ['file' => UploadedFile::fake()->create('hasil.pdf', 100, 'application/pdf'), 'label' => 'Hasil Auditor'],
        ],
    ], $overrides);
}

test('hanya kepala spi yang dapat mencatat status eksternal', function () {
    $finding = r8ReadyFinding();

    $this->actingAs($this->admin, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('SSR'))
        ->assertStatus(403);

    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('SSR'))
        ->assertCreated();
});

test('status salah dan dokumen wajib ditolak', function () {
    $finding = r8ReadyFinding();

    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('XYZ'))
        ->assertStatus(422);

    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", [
        'status' => 'BSR',
        'action_plan_ids' => [$finding->action_plans()->first()->id],
    ])->assertStatus(422);
});

test('ssr menutup temuan dan semua action plan', function () {
    Event::fake([FindingClosed::class]);
    $finding = r8ReadyFinding(2);

    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('SSR'))
        ->assertCreated();

    Event::assertDispatched(FindingClosed::class);

    expect($finding->refresh()->status)->toEqual(FindingStatus::Closed)
        ->and($finding->closed_by)->toBe($this->kepala->id)
        ->and($finding->closed_at)->not->toBeNull();

    $aps = ActionPlan::withoutGlobalScopes()->where('finding_id', $finding->id)->get();
    expect($aps->pluck('status')->unique()->all())->toBe([ActionPlanStatus::Closed]);
});

test('bsr/bd/tdtl wajib memilih action plan dan hanya yang terpilih direvisi', function () {
    $finding = r8ReadyFinding(2);
    $aps = ActionPlan::withoutGlobalScopes()->where('finding_id', $finding->id)->orderBy('id')->get();
    $selected = $aps->first();

    // Tanpa pilihan AP → ditolak.
    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('BSR'))
        ->assertStatus(422);

    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('BSR', [
        'action_plan_ids' => [$selected->id],
    ]))->assertCreated();

    expect($selected->refresh()->status)->toEqual(ActionPlanStatus::RevisiSpi)
        ->and($selected->current_revision)->toBe(1);

    $revision = ActionPlanRevision::where('action_plan_id', $selected->id)->first();
    expect($revision->source->value)->toBe('EXTERNAL_STATUS')
        ->and($revision->requested_by)->toBe($this->kepala->id)
        ->and($revision->reason)->toContain('Catatan status');

    // AP tak terpilih tetap SESUAI (beku).
    $unselected = $aps->last();
    expect($unselected->refresh()->status)->toEqual(ActionPlanStatus::Sesuai);

    // Temuan kembali ke PROSES karena ada AP yang direvisi.
    expect($finding->refresh()->status)->toEqual(FindingStatus::ProsessTindakLanjut);
});

test('tindak lanjut di action plan yang tidak dipilih tetap beku', function () {
    $finding = r8ReadyFinding(2);
    $aps = ActionPlan::withoutGlobalScopes()->where('finding_id', $finding->id)->orderBy('id')->get();
    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('TDTL', [
        'action_plan_ids' => [$aps->first()->id],
    ]))->assertCreated();

    $fu = FollowUp::withoutGlobalScopes()->where('action_plan_id', $aps->last()->id)->first();
    $this->actingAs($this->pic, 'sanctum')->putJson("/api/v1/follow-ups/{$fu->id}", ['description' => 'ubah'])
        ->assertStatus(422);
});

test('temuan closed hanya dapat diubah kepala spi dan tercatat lama-baru', function () {
    $finding = r8ReadyFinding();
    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('SSR'))->assertCreated();

    // Non-kepala ditolak.
    $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/findings/{$finding->id}", ['title' => 'Ubah admin'])
        ->assertStatus(403);

    // Kepala boleh ubah & tercatat old/new.
    $this->actingAs($this->kepala, 'sanctum')->putJson("/api/v1/findings/{$finding->id}", ['title' => 'Revisi kepala SSPR'])
        ->assertOk();

    $audit = Audit::where('action', 'finding.updated')
        ->where('entity_id', $finding->id)
        ->latest()
        ->first();

    expect($audit)->not->toBeNull();
    $payload = $audit->payload;
    expect($payload['old']['title'] ?? null)->toBe('Temuan R8')
        ->and($payload['new']['title'] ?? null)->toBe('Revisi kepala SSPR');
});

test('pic kehilangan akses tindak lanjut setelah temuan closed, manager tetap', function () {
    $finding = r8ReadyFinding();
    $this->actingAs($this->kepala, 'sanctum')->post("/api/v1/findings/{$finding->id}/external-status", externalStatusPayload('SSR'))->assertCreated();

    $this->actingAs($this->pic, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()
        ->assertJsonCount(0, 'data.data');

    $this->actingAs($this->manager, 'sanctum')->getJson('/api/v1/follow-ups')->assertOk()
        ->assertJsonCount(1, 'data.data');
});