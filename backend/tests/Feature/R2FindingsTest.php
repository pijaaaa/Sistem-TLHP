<?php

use App\Enums\FindingStatus;
use App\Models\Department;
use App\Models\Finding;
use App\Models\User;
use App\Services\FindingService;
use App\Services\FindingNumberService;

beforeEach(function () {
    $this->seed();
});

test('finding number auto-generated per fiscal year', function () {
    $num1 = FindingNumberService::generate(2026);
    $num2 = FindingNumberService::generate(2026);
    $num3 = FindingNumberService::generate(2027);

    expect($num1)->toEqual('TLHT-2026-0001');
    expect($num2)->toEqual('TLHT-2026-0002');
    expect($num3)->toEqual('TLHT-2027-0001');
});

test('fiscal year calculated from response_period_start', function () {
    $service = new FindingService();
    $user = User::where('username', 'admin_spi')->first();
    $this->actingAs($user);

    $finding = $service->createDraft([
        'title' => 'Test',
        'response_period_start' => '2026-01-15',
    ]);

    expect($finding->fiscal_year)->toEqual(2026);
});

test('finding can be drafted, registered, and activated', function () {
    $service = new FindingService();
    $user = User::where('username', 'admin_spi')->first();
    $financeIct = Department::where('code', 'FINANCE_ICT')->first();
    $this->actingAs($user);

    $finding = $service->createDraft([
        'title' => 'Test Finding',
        'finding_date' => '2026-07-01',
        'response_period_start' => '2026-08-01',
        'response_period_end' => '2026-09-30',
        'lhp_number' => 'LHP/2026/01',
        'lhp_date' => '2026-07-10',
        'source' => 'BPK',
        'scope' => 'Test scope',
        'fiscal_year' => 2026,
    ]);

    expect($finding->status)->toEqual(FindingStatus::Draft);
    expect($finding->registration_number)->toBeNull();

    \App\Models\FindingDocument::create([
        'finding_id' => $finding->id,
        'label' => 'LHP',
        'filename' => 'test.pdf',
        'file_path' => '/docs/test.pdf',
        'mime_type' => 'application/pdf',
    ]);

    $finding = $service->register($finding, [$financeIct->id]);

    expect($finding->status)->toEqual(FindingStatus::Terdaftar);
    expect($finding->registration_number)->toEqual('TLHT-2026-0001');

    $finding = $service->activate($finding);

    expect($finding->status)->toEqual(FindingStatus::ProsessTindakLanjut);
    expect($finding->activated_at)->not->toBeNull();
});

test('register requires documents', function () {
    $service = new FindingService();
    $user = User::where('username', 'admin_spi')->first();
    $financeIct = Department::where('code', 'FINANCE_ICT')->first();
    $this->actingAs($user);

    $finding = $service->createDraft([
        'title' => 'Test',
        'fiscal_year' => 2026,
    ]);

    expect(function () use ($service, $finding, $financeIct) {
        $service->register($finding, [$financeIct->id]);
    })->toThrow(\Illuminate\Validation\ValidationException::class);
});

test('finding draft created without error', function () {
    $service = new FindingService();
    $user = User::where('username', 'admin_spi')->first();
    $this->actingAs($user);

    $finding = $service->createDraft(['title' => 'Test', 'fiscal_year' => 2026]);

    expect($finding)->not->toBeNull();
    expect($finding->status)->toEqual(FindingStatus::Draft);
});
