<?php

use App\Enums\FindingStatus;
use App\Models\ActionPlan;
use App\Models\Finding;
use App\Models\Department;
use App\Models\User;
use App\Services\ActionPlanService;
use App\Services\FindingService;
use App\Services\FindingNumberService;

beforeEach(function () {
    $this->seed();
});

test('staff_dept cannot see draft action plan', function () {
    $admin = User::where('username', 'admin_spi')->first();
    $this->actingAs($admin);

    $finding = Finding::first();
    $finding->update(['status' => FindingStatus::Terdaftar->value, 'fiscal_year' => 2026]);
    $finding->registration_number = FindingNumberService::generate(2026);
    $finding->save();
    
    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $finding->auditee_departments()->sync([$dept->id]);

    $service = new ActionPlanService();
    $aps = $service->createForDepartments($finding, [
        'title' => 'Test AP',
        'condition' => 'Ada masalah',
    ], [$dept->id]);

    $this->assertCount(1, $aps);
    expect($aps[0]->status->value)->toBe('DRAFT');

    $pic = User::where('username', 'pic_ia')->first();
    $this->actingAs($pic);

    $visible = ActionPlan::all();
    expect($visible->count())->toEqual(0);
});

test('staff_dept sees action plan after sent', function () {
    $admin = User::where('username', 'admin_spi')->first();
    $this->actingAs($admin);

    $finding = Finding::first();
    $finding->update(['status' => FindingStatus::Terdaftar->value, 'fiscal_year' => 2026]);
    $finding->registration_number = FindingNumberService::generate(2026);
    $finding->save();
    
    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $finding->auditee_departments()->sync([$dept->id]);

    $service = new ActionPlanService();
    $aps = $service->createForDepartments($finding, [
        'title' => 'Test AP',
        'condition' => 'Ada masalah',
    ], [$dept->id]);

    $ap = $aps[0];
    $service->send([$ap->id]);

    $pic = User::where('username', 'pic_1_finance_ict')->first();
    $this->actingAs($pic);

    $visible = ActionPlan::all();
    expect($visible->count())->toEqual(0);

    $admin = User::where('username', 'admin_spi')->first();
    $this->actingAs($admin);
    $service->assignPics($ap, [$pic->id]);

    $this->actingAs($pic);
    $visible = ActionPlan::all();
    expect($visible->count())->toEqual(1);
});

test('manager_dept sees ap after sent', function () {
    $admin = User::where('username', 'admin_spi')->first();
    $this->actingAs($admin);

    $finding = Finding::first();
    $finding->update(['status' => FindingStatus::Terdaftar->value, 'fiscal_year' => 2026]);
    $finding->registration_number = FindingNumberService::generate(2026);
    $finding->save();
    
    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $finding->auditee_departments()->sync([$dept->id]);

    $service = new ActionPlanService();
    $aps = $service->createForDepartments($finding, [
        'title' => 'Test AP',
    ], [$dept->id]);

    $ap = $aps[0];
    $service->send([$ap->id]);

    $manager = User::where('username', 'mgr_finance_ict')->first();
    $this->actingAs($manager);

    $visible = ActionPlan::all();
    expect($visible->count())->toEqual(1);
});

test('admin_spi and internal_audit see all ap', function () {
    $admin = User::where('username', 'admin_spi')->first();
    $this->actingAs($admin);

    $finding = Finding::first();
    $finding->update(['status' => FindingStatus::Terdaftar->value, 'fiscal_year' => 2026]);
    $finding->registration_number = FindingNumberService::generate(2026);
    $finding->save();
    
    $dept = Department::where('code', 'FINANCE_ICT')->first();
    $finding->auditee_departments()->sync([$dept->id]);

    $service = new ActionPlanService();
    $aps = $service->createForDepartments($finding, [
        'title' => 'Test AP',
    ], [$dept->id]);

    $visible = ActionPlan::all();
    expect($visible->count())->toEqual(1);
});
