<?php

use App\Enums\FindingSource;
use App\Enums\FindingStatus;
use App\Events\FindingActivated;
use App\Events\FindingRegistered;
use App\Models\Department;
use App\Models\Finding;
use App\Models\User;
use App\Services\FindingNumberService;
use App\Services\FindingService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed();
    Storage::fake(config('upload.disk'));

    $this->admin = User::where('username', 'admin_spi')->first();
    $this->payload = [
        'title' => 'Kelemahan kontrol procurement',
        'source' => FindingSource::Bpk->value,
        'lhp_number' => 'LHP/2026/01',
        'lhp_date' => '2026-07-10',
        'finding_date' => '2026-07-01',
        'response_period_start' => '2026-08-01',
        'response_period_end' => '2026-09-30',
        'scope' => 'Pengadaan barang dan jasa',
    ];
});

function makeDraft(array $payload): Finding
{
    return (new FindingService())->createDraft($payload);
}

function uploadLhp(Finding $finding): void
{
    test()->actingAs(test()->admin, 'sanctum')->postJson("/api/v1/findings/{$finding->id}/documents", [
        'label' => 'LHP',
        'document' => UploadedFile::fake()->create('lhp.pdf', 100, 'application/pdf'),
    ])->assertCreated();
}

test('finding number auto-generated per fiscal year', function () {
    expect(FindingNumberService::generate(2026))->toEqual('TLHT-2026-0001')
        ->and(FindingNumberService::generate(2026))->toEqual('TLHT-2026-0002')
        ->and(FindingNumberService::generate(2027))->toEqual('TLHT-2027-0001');
});

test('fiscal year calculated from response_period_start and recalculated on change', function () {
    $this->actingAs($this->admin);

    $finding = makeDraft(['title' => 'Test', 'response_period_start' => '2026-01-15']);
    expect($finding->fiscal_year)->toEqual(2026);

    (new FindingService())->update($finding, ['response_period_start' => '2027-01-01']);
    expect($finding->refresh()->fiscal_year)->toEqual(2027);
});

test('backdate finding_date and response period is allowed', function () {
    $this->actingAs($this->admin);

    $finding = makeDraft(array_merge($this->payload, [
        'finding_date' => '2025-12-01',
        'response_period_start' => '2025-12-15',
        'response_period_end' => '2026-01-15',
    ]));

    expect($finding->finding_date->toDateString())->toEqual('2025-12-01')
        ->and($finding->fiscal_year)->toEqual(2025);
});

test('register requires complete data, auditee departments and document', function () {
    $this->actingAs($this->admin);

    $finding = makeDraft(['title' => 'Belum lengkap']);
    uploadLhp($finding);

    expect(fn () => (new FindingService())->register($finding, [Department::where('code', 'FINANCE_ICT')->first()->id]))
        ->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('register rejects non-auditee department', function () {
    $this->actingAs($this->admin);

    $finding = makeDraft($this->payload);
    uploadLhp($finding);

    $spi = Department::where('code', 'SPI')->first();

    $this->postJson("/api/v1/findings/{$finding->id}/register", ['department_ids' => [$spi->id]])
        ->assertStatus(422);

    expect($finding->refresh()->status)->toEqual(FindingStatus::Draft);
});

test('draft to registered to activated flow dispatches events', function () {
    Event::fake([FindingRegistered::class, FindingActivated::class]);

    $this->actingAs($this->admin);
    $finding = makeDraft($this->payload);
    uploadLhp($finding);

    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $this->postJson("/api/v1/findings/{$finding->id}/register", ['department_ids' => [$dept->id]])
        ->assertOk()
        ->assertJsonPath('data.registration_number', 'TLHT-2026-0001')
        ->assertJsonPath('data.status', FindingStatus::Terdaftar->value);

    Event::assertDispatched(FindingRegistered::class);

    $this->postJson("/api/v1/findings/{$finding->id}/activate")->assertOk()
        ->assertJsonPath('data.status', FindingStatus::ProsessTindakLanjut->value);

    Event::assertDispatched(FindingActivated::class);
    expect($finding->refresh()->activated_at)->not->toBeNull();
});

test('after register only descriptive fields can be updated', function () {
    $this->actingAs($this->admin);
    $finding = makeDraft($this->payload);
    uploadLhp($finding);
    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $this->postJson("/api/v1/findings/{$finding->id}/register", ['department_ids' => [$dept->id]])->assertOk();

    $this->putJson("/api/v1/findings/{$finding->id}", ['title' => 'Judul baru'])->assertOk();

    $this->putJson("/api/v1/findings/{$finding->id}", ['lhp_number' => 'LHP-BARU'])
        ->assertStatus(422);
});

test('role without write permission cannot create or register findings', function () {
    $staff = User::where('role', 'staff_dept')->firstOrFail();

    $this->actingAs($staff, 'sanctum')->postJson('/api/v1/findings', $this->payload)->assertStatus(403);

    $this->actingAs($this->admin);
    $finding = makeDraft($this->payload);

    $this->actingAs($staff, 'sanctum')
        ->postJson("/api/v1/findings/{$finding->id}/register", ['department_ids' => [1]])
        ->assertNotFound();
});

test('only draft findings can be deleted', function () {
    $this->actingAs($this->admin);
    $finding = makeDraft($this->payload);
    uploadLhp($finding);
    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $this->postJson("/api/v1/findings/{$finding->id}/register", ['department_ids' => [$dept->id]])->assertOk();

    $this->deleteJson("/api/v1/findings/{$finding->id}")->assertStatus(403);
});

test('finding list can be filtered by status and department', function () {
    $this->actingAs($this->admin);
    $finding = makeDraft($this->payload);
    uploadLhp($finding);
    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $this->postJson("/api/v1/findings/{$finding->id}/register", ['department_ids' => [$dept->id]])->assertOk();

    $this->getJson('/api/v1/findings?status=TERDAFTAR')->assertOk()
        ->assertJsonPath('data.data.0.id', $finding->id);

    $this->getJson("/api/v1/findings?department_id={$dept->id}")->assertOk()
        ->assertJsonPath('data.data.0.id', $finding->id);

    $drafts = $this->getJson('/api/v1/findings?status=DRAFT')->assertOk()->json('data.data');
    expect(collect($drafts)->pluck('id'))->not->toContain($finding->id);
});

test('closed finding cannot be edited by admin_spi', function () {
    $this->actingAs($this->admin);
    $finding = makeDraft($this->payload);
    $finding->update(['status' => FindingStatus::Closed]);

    $this->putJson("/api/v1/findings/{$finding->id}", ['title' => 'Ubah closed'])->assertStatus(403);
});
