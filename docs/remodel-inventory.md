# R0 Inventory — Pemetaan Lama → Baru

Sumber: roadmap-remodel.md §9, codebase scan M11.

## Backend

### Models

| Lama | Baru | Status | Alasan |
|---|---|---|---|
| `ActionPlan` (tindak lanjut M0–M11) | — | **REMOVE** | Diganti dengan `FollowUp` di R4 |
| `FindingDepartment` (distribusi) | — | **REMOVE** | Alur distribusi dirombak; ganti dengan `ActionPlan` (R3) & `FollowUp` (R4) |
| `EvidenceSubmission`, `EvidenceFile` | — | **REMOVE** | Evidence workflow dihapus; laporan progres langsung di `FollowUpProgressReport` (R6) |
| `FindingVerification` | — | **REMOVE** | Verifikasi SPI lama; ganti `ExternalStatusRecord` (R8) |
| `Finding` | `Finding` | **REWORK** | Tambah: registration_number, source, lhp_number, lhp_date, response_period_start/end, fiscal_year, scope, activated_at, closed_at/by; **hapus**: current_round, assessment_status, assessment_note, assessed_by, assessed_at, is_active |
| `Audit` | `Audit` | **KEEP** | Audit trail infrastruktur |
| `User`, `Department`, `Employee` | — | **KEEP** | Master data, auth |
| `Menu`, `RoleMenuPermission`, `UserMenuPermission` | — | **KEEP** | Menu & izin dinamis |
| — | `ActionPlan` (baru) | **CREATE** | R3: Action Plan per departemen per temuan |
| — | `ActionPlanRevision` | **CREATE** | R7: Tracking revisi AP |
| — | `FollowUp` | **CREATE** | R4: Tindak lanjut PIC |
| — | `FollowUpReview` | **CREATE** | R5: Keputusan manager & penyelesaian |
| — | `FollowUpProgressReport` | **CREATE** | R6: Laporan progres + dokumen |
| — | `FollowUpComment` | **CREATE** | R6: Diskusi & IA comment |
| — | `SpiReview`, `SpiReviewItem` | **CREATE** | R7: Review SPI per AP |
| — | `ExternalStatusRecord` | **CREATE** | R8: Status eksternal (SSR/BSR/BD/TDTL) |
| — | `InboxTask` | **CREATE** | R9: Tracking tugas masuk |
| — | `ReminderLog` | **CREATE** | R9: Idempotent pengingat |

### Enums

| Lama | Baru | Status |
|---|---|---|
| `ActionPlanStatus` (Draft, Submitted, Approved, …) | — | **REMOVE** |
| `FindingDepartmentStatus` (Received, PicAssigned, …) | — | **REMOVE** |
| `EvidenceStatus` (Diajukan, Disetujui, Revisi) | — | **REMOVE** |
| `AssessmentStatus` (Ssr, Bsr, BelumDitindaklanjuti, TidakDapatDitindaklanjuti) | — | **REMOVE** |
| `FindingStatus` | `FindingStatus` | **REWORK** | Status baru: DRAFT → TERDAFTAR → PROSES_TINDAK_LANJUT → REVIEW_SPI → MENUNGGU_STATUS_EKSTERNAL → CLOSED |
| `Role` | `Role` | **REWORK** | Hapus ManagerSpi; tambah KepelaSpi, InternalAudit, Direksi |
| `AuditorConclusion` | — | **REMOVE** |

### Services

| Lama | Baru | Status |
|---|---|---|
| `ActionPlanService` | — | **REMOVE** |
| `FindingDepartmentService` | — | **REMOVE** |
| `EvidenceSubmission` ops | — | **REMOVE** |
| `AssessmentService` | — | **REMOVE** |
| `VerificationService` | — | **REMOVE** |
| `FindingService` | `FindingService` | **REWORK** |
| `DashboardService` | `DashboardService` | **KEEP** (update widget untuk R10) |
| `PermissionService`, `UserService`, `DepartmentService`, `EmployeeService` | — | **KEEP** |
| — | `FindingStatusService` | **CREATE** | R2: Recompute status temuan |
| — | `ActionPlanService` (baru) | **CREATE** | R3: Alur AP |
| — | `FollowUpService` | **CREATE** | R4: Alur TL |
| — | `ExternalStatusService` | **CREATE** | R8: Status eksternal |
| — | `FindingVisibility`, `ActionPlanVisibility`, `FollowUpVisibility` | **CREATE** | R3: Scope/policy terpusat |

### Controllers

| Lama | Baru | Status |
|---|---|---|
| `ActionPlanController` (tindak lanjut M11) | — | **REMOVE** |
| `FindingDepartmentController` | — | **REMOVE** |
| `EvidenceController` | — | **REMOVE** |
| `AssessmentController` | — | **REMOVE** |
| `VerificationController` | — | **REMOVE** |
| `FindingController` | `FindingController` | **REWORK** |
| `FindingDistributionController` | — | **REMOVE** |
| `DashboardController`, `AuthController`, `AuditController`, `ExportController`, Master/* | — | **KEEP** |
| — | `ActionPlanController` (baru) | **CREATE** |
| — | `FollowUpController` | **CREATE** |
| — | `SpiReviewController` | **CREATE** |
| — | `ExternalStatusController` | **CREATE** |
| — | `InboxController` | **CREATE** |

### Policies

| Lama | Baru | Status |
|---|---|---|
| `ActionPlanPolicy` (old) | — | **REMOVE** |
| `FindingDepartmentPolicy` | — | **REMOVE** |
| `EvidenceFilePolicy` | — | **REMOVE** |
| `FindingPolicy`, `FindingDocumentPolicy`, User/Dept/Emp policies | — | **KEEP** |
| — | `ActionPlanPolicy` (baru) | **CREATE** |
| — | `FollowUpPolicy` | **CREATE** |
| — | `SpiReviewPolicy` | **CREATE** |

### Routes (routes/api.php)

**REMOVE:**
- `/findings/distribution` (FindingDistributionController)
- `/finding-departments/*` (FindingDepartmentController)
- `/action-plans/*` old (ActionPlanController lama)
- `/evidence/*` (EvidenceController)
- `/assessments/*` (AssessmentController)
- `/verifications/*` (VerificationController)

**KEEP:**
- `/auth/*`, `/master/*`, `/access/*`, `/dashboard`, `/audit-trail`, `/exports/*`
- `/findings/*` core (FindingController: index, store, show, update, delete, documents)

**CREATE (R2–R9):**
- `/action-plans/*` new (ActionPlanController baru)
- `/follow-ups/*` (FollowUpController)
- `/spi-reviews/*` (SpiReviewController)
- `/external-status/*` (ExternalStatusController)
- `/inbox/*` (InboxController)

### Migrations

**REMOVE (tabel lama; jangan di-roll-back, drop di migration baru R0):**
- `2026_10_06_010000_create_finding_departments_table.php` (+ finding_department_pics)
- `2026_10_06_015000_create_action_plans_table.php` (+ action_plan_documents) — KEEP dokumen field hanya
- `2026_10_06_020000_create_evidence_submissions_table.php` (+ evidence_files)
- `2026_10_06_040000_create_finding_verifications_table.php`
- `2026_10_06_030000_add_round_and_assessment_to_findings.php` — DROP fields saja, jangan tabel

**MODIFY:**
- `2026_10_05_120000_create_findings_table.php` — Rework: tambah registration_number, source, lhp_*, response_period_*, fiscal_year, scope, activated_at, closed_at, closed_by; hapus code, severity, recommendation, auditor_action_plan, is_active

**KEEP:**
- Semua infrastructure (users, departments, employees, menus, permissions, cache, jobs, tokens)

**CREATE (R0–R8):**
- Migration R0: Drop tabel lama + alter findings
- Migration R1: Role enum baru, user_expires_at
- Migration R2–R8: Tabel & kolom baru per milestone

### Tests

**REMOVE:**
- ActionPlanTest.php (old)
- FindingDistributionTest.php
- EvidenceTest.php
- AssessmentTest.php
- VerificationTest.php
- ActionPlanReviewTest.php

**KEEP:**
- AuthApiTest.php, MasterTest.php, PermissionApiTest.php, HealthTest.php, CacheServiceTest.php

**CREATE (R2–R11):**
- FindingTest.php (R2)
- ActionPlanTest.php (R3), FollowUpTest.php (R4), dst.

---

## Frontend

### Pages

| Lama | Baru | Status |
|---|---|---|
| `FindingsPage` (old: daftar distribusi) | — | **REMOVE** |
| `FindingsReportsPage` | — | **REMOVE** |
| `FindingsDistributionPage` | — | **REMOVE** |
| `FindingDepartmentsPage` | — | **REMOVE** |
| `ActionPlansPage` (old: tindak lanjut) | — | **REMOVE** |
| `ActionPlanReviewsPage` | — | **REMOVE** |
| `EvidencePage` | — | **REMOVE** |
| `AssessmentsPage` | — | **REMOVE** |
| `VerificationsPage` | — | **REMOVE** |
| `HomePage`, `LoginPage`, `AuditTrailPage`, `master/*` | — | **KEEP** |
| — | `FindingsPage` (baru: tree + detail TL pop-up) | **CREATE** (R2) |
| — | `ActionPlansPage` (baru) | **CREATE** (R3) |
| — | `FollowUpsPage` | **CREATE** (R4) |
| — | `FollowUpReviewsPage` | **CREATE** (R5) |
| — | `IaMonitoringPage` | **CREATE** (R5) |
| — | `SpiReviewPage` | **CREATE** (R7) |
| — | `ExternalStatusPage` | **CREATE** (R8) |
| — | `InboxPage` | **CREATE** (R9) |
| — | `ReportsPage` | **CREATE** (R10) |

### Hooks

| Lama | Baru | Status |
|---|---|---|
| `useActionPlans` (old) | — | **REMOVE** |
| `useFindingDistribution` | — | **REMOVE** |
| `useEvidence` | — | **REMOVE** |
| `useFindings`, `useDepartments`, `useEmployees`, `useDashboard`, `usePermission`, `usePermissions`, `useHealth` | — | **KEEP** |
| — | `useActionPlans` (baru) | **CREATE** |
| — | `useFollowUps` | **CREATE** |
| — | `useSpiReviews` | **CREATE** |
| — | `useExternalStatus` | **CREATE** |
| — | `useInbox` | **CREATE** |

### Components

| Lama | Baru | Status |
|---|---|---|
| `ActionPlanForm` (old) | — | **REMOVE** |
| `DataTable`, `FileUploader`, `WeightMeter`, `Can` (can.tsx), UI/* | — | **KEEP** |
| — | `ActionPlanForm` (baru) | **CREATE** |
| — | `FollowUpForm` | **CREATE** |
| — | `FindingTree` | **CREATE** |

### Routes (App.tsx)

**REMOVE:**
- `/findings/reports`, `/findings/distribution`, `/findings` old, `/action-plans` old, `/action-plan-reviews`, `/evidence`, `/assessments`, `/verifications`

**KEEP:**
- `/`, `/login`, `/master/*`, `/access/*`, `/audit-trail`, `/dashboard`

**CREATE:**
- `/temuan` (Findings), `/action-plan`, `/tindak-lanjut`, `/persetujuan`, `/pemantauan`, `/review-spi`, `/status-eksternal`, `/inbox`, `/laporan`

---

## Ringkasan Jumlah

| Kategori | KEEP | REMOVE | CREATE | Rework |
|---|---|---|---|---|
| BE Models | 7 | 5 | 11 | 1 |
| BE Enums | 1 | 4 | 1 | 1 |
| BE Services | 5 | 5 | 3 | 1 |
| BE Controllers | 6 | 6 | 5 | 1 |
| BE Policies | 6 | 3 | 3 | 1 |
| BE Routes | 5 | 6 | 5 | — |
| BE Tests | 4 | 6 | 7 | — |
| FE Pages | 7 | 9 | 8 | — |
| FE Hooks | 7 | 3 | 5 | — |
| FE Components | 4 | 1 | 3 | — |

---

## File yang Akan Dihapus di R0

### Backend
- `app/Models/ActionPlan.php`, `ActionPlanDocument.php`
- `app/Models/FindingDepartment.php`
- `app/Models/EvidenceSubmission.php`, `EvidenceFile.php`
- `app/Models/FindingVerification.php`
- `app/Enums/ActionPlanStatus.php`, `FindingDepartmentStatus.php`, `EvidenceStatus.php`, `AssessmentStatus.php`, `AuditorConclusion.php`
- `app/Services/ActionPlanService.php`, `FindingDepartmentService.php`, `AssessmentService.php`, `VerificationService.php`
- `app/Http/Controllers/Api/ActionPlanController.php`, `FindingDepartmentController.php`, `FindingDistributionController.php`, `EvidenceController.php`, `AssessmentController.php`, `VerificationController.php`
- `app/Policies/ActionPlanPolicy.php`, `ActionPlanDocumentPolicy.php`, `FindingDepartmentPolicy.php`, `EvidenceFilePolicy.php`
- `app/Http/Requests/*` untuk modul hapus
- `app/Http/Resources/ActionPlanResource.php`, `ActionPlanDocumentResource.php`, `FindingDepartmentResource.php`, `EvidenceSubmissionResource.php`, `EvidenceFileResource.php`, `FindingVerificationResource.php`
- Migration: `2026_10_06_010000_*`, `2026_10_06_015000_*`, `2026_10_06_020000_*`, `2026_10_06_040000_*`
- Tests: `ActionPlanTest.php`, `FindingDistributionTest.php`, `EvidenceTest.php`, `AssessmentTest.php`, `VerificationTest.php`, `ActionPlanReviewTest.php`

### Frontend
- `src/pages/findings/FindingsPage.tsx`, `FindingsReportsPage.tsx`, `FindingsDistributionPage.tsx`, `FindingDepartmentsPage.tsx`
- `src/pages/findings/ActionPlansPage.tsx`, `ActionPlanReviewsPage.tsx`, `EvidencePage.tsx`, `AssessmentsPage.tsx`, `VerificationsPage.tsx`
- `src/hooks/useActionPlans.ts`, `useFindingDistribution.ts`, `useEvidence.ts`
- `src/components/ActionPlanForm.tsx` (old)
- Routes di `App.tsx` untuk halaman hapus

---

## Menu & Izin (MenuSeeder, PermissionSeeder)

**REMOVE:**
- findings.reports, findings.distribution, findings.list (ganti findings saja)
- action_plans, action_plan_reviews (ganti action_plans baru)
- evidence, assessments, verifications (hapus total, bukan scope R0)

**REWORK/CREATE:**
- Master menu group
- findings (core: temuan)
- action_plans (baru: action plan)
- follow_ups (tindak lanjut)
- follow_up_reviews (persetujuan)
- ia_monitoring (pemantauan IA)
- spi_review (review SPI)
- external_status (status eksternal)
- inbox (persetujuan masuk)
- reports (laporan)
- audit_trail (tetap)

---

## Status Seed Data

Seeder lama (EmployeeUserSeeder, DummyDataSeeder) membuat dummy finding + action plan lama. Data dapat direset dengan `migrate:fresh --seed`.
