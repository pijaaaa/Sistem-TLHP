# e-TLHT (Remodel)

Sistem **Tindak Lanjut Hasil Temuan** audit eksternal. 
Backend: **Laravel 11 API** (`/api/v1`) + MySQL (dev di Laragon). Frontend: **React + Vite + TypeScript** + TanStack Query + React Router + Tailwind.

Alur domain: **Temuan** (`findings`) → **Action Plan** (`action_plans`, per departemen) → **Tindak Lanjut** (`follow_ups`, oleh PIC).

> Dokumentasi lengkap: `docs/roadmap-remodel.md` (alur/status/visibilitas/milestone), `docs/api.md` (endpoint), `docs/deploy.md` (deploy & scheduler).

## Prasyarat (Laragon)

- PHP 8.3 (Laragon `php.ini`: aktifkan `pdo_mysql`, `pdo_sqlite`, `fileinfo`).
- Composer, Node.js 20+, MySQL (atau SQLite untuk test).
- Ekstensi `zip` (opsional), `gd` tidak wajib.

## Setup dari nol

```bash
# 1. Backend
cd backend
composer install
copy .env.example .env            # atur DB_DATABASE=etlht, DB_USERNAME=root, DB_PASSWORD=
php artisan key:generate
php artisan migrate:fresh --seed   # struktur + menu/izin + akun dev
php artisan serve                 # http://localhost:8000

# 2. Frontend
cd ../frontend
npm install                        # vite proxy /api → http://localhost:8000
npm run dev                        # http://localhost:5173
```

Akun dev dibuat oleh `EmployeeUserSeeder` (password dari `DEV_USER_PASSWORD`, default `password`):

| Username | Peran |
|---|---|
| `admin_spi` | Admin SPI |
| `internal_audit` | Internal Audit (IA) |
| `manager_ia` | Manager IA (baca saja) |
| `kepala_spi` | Kepala SPI |
| `mgr_<kode_dept>` | Manager Departemen |
| `pic_<n>_<kode_dept>` | PIC / Staff Departemen |
| `manager_ia_auditee` / `pic_ia` | Departemen IA sebagai auditee |
| `superadmin` | Super Admin teknis |

## Data demo

```bash
cd backend
php artisan demo:reset   # migrate:fresh --seed + data lintas status (draft s/d SSR closed) + notifikasi contoh
```

## Menjalankan test

```bash
cd backend
php artisan test        # Pest/PHPUnit feature test (SQLite in-memory), termasuk alur penuh & matriks visibilitas
```

Frontend: `npm run build` (produksi) / `npx oxlint .` (lint).

## Scheduler & pengingat

`php artisan reminders:daily` (jadwal 07:00 di `routes/console.php`): pengingat H-30/14/7/3/1, eskalasi keterlambatan, pengingat tugas belum dibuka — idempoten via `reminder_logs`. Di Windows gunakan Task Scheduler memanggil `php artisan schedule:run` tiap menit (lihat `docs/deploy.md`).

## Struktur ringkas

```
backend/app/
  Enums/        Status & role (FindingStatus, ActionPlanStatus, FollowUpStatus, ...)
  Services/     Logika bisnis berlapis (Finding*, ActionPlan, FollowUp, Review, SPI, External Status, Progress, TaskDispatcher, Reports)
  Scopes/       FindingVisibility, ActionPlanVisibility, FollowUpVisibility
  Policies/     Otorisasi; CacheService & AuditLogger di app/Support
frontend/src/
  pages/        Halaman alur (temuan, action-plan, tindak-lanjut, persetujuan, pemantauan, review-spi, status-eksternal, inbox, laporan, log-aktivitas)
  components/shared/  Komponen reusable (DataTable, Tabs, Folder*Select, FollowUpDetailModal, ReviewActionBar, ...)
  hooks/        TanStack Query per entitas
  lib/links.ts  Route helper terpusat notifikasi/inbox
```

## Catatan penting

- Visibilitas data hanya lewat scope global (temuan/AP/TL); role pemantau melihat tindak lanjut setelah disetujui manager.
- Temuan/AP **CLOSED** read-only kecuali Kepala SPI; setiap perubahan dicatat nilai lama & baru.
- Bobot TL integer, total aktif ≤100 (wajib 100 saat ajukan ke SPI); `target_date` ≤ deadline AP; progres tidak bisa turun.
- Dilarang endpoint unduh massal/zip; dokumen diunduh satu per satu lewat endpoint terotorisasi.
- Cache hanya melalui `App\Support\CacheService` (group berversi; invalidasi otomatis saat data berubah).