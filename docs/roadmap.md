# Roadmap — Sistem Tindak Lanjut Temuan Audit Eksternal

Stack: Laravel 11 (API) + React SPA (Vite) + MySQL (Laragon). Tooling: opencode CLI (via 9router) di VS Code.
Cara pakai: **kerjakan berurutan**, satu prompt per sesi opencode. Setelah tiap milestone: jalankan test, commit, baru lanjut.

## 1. Role (5 role, 1 user = 1 role = 1 departemen)

| Role | Kode | Tugas utama |
|---|---|---|
| Admin SPI | `admin_spi` | Input temuan + dokumen audit, kirim ke IA |
| Manager IA | `manager_ia` | Distribusi temuan ke departemen (termasuk IA sendiri), assessment status |
| Manager Departemen | `manager_dept` | Assign PIC, review tindak lanjut & evidence, teruskan ke IA |
| Staff Departemen / PIC | `staff_dept` | Buat tindak lanjut, upload dokumen, upload evidence |
| Manager SPI | `manager_spi` | Input hasil pemeriksaan ulang auditor eksternal, closing |

Catatan: departemen IA juga punya staff (PIC). Manager IA bertindak sebagai Manager Dept untuk departemen IA.
Superadmin teknis (opsional, seeder) untuk kelola data master awal.

## 2. Alur & state machine

**Temuan (per ronde):**
`DRAFT` → (Admin SPI kirim) `DIKIRIM_KE_IA` → (Manager IA distribusi) `DIDISTRIBUSIKAN` → `DALAM_PROSES` → (semua departemen teruskan) `MENUNGGU_ASSESSMENT_IA` → (Manager IA tetapkan status) →
- **SSR** → `MENUNGGU_VERIFIKASI_SPI` → Manager SPI input hasil auditor eksternal → `CLOSED`
- **BSR** → ronde baru (`round + 1`), kembali ke `DIDISTRIBUSIKAN`, riwayat ronde lama tersimpan
- **Belum Ditindaklanjuti** → status default sebelum assessment; tidak memicu ronde baru
- **Tidak Dapat Ditindaklanjuti** → `CASE_CLOSED`, berhenti (alasan wajib)

**Per departemen per temuan (finding_department):** `DITERIMA` → `PIC_DITUGASKAN` → `DALAM_PROSES` → `SELESAI_100` → `DITERUSKAN_KE_IA`.

**Tindak lanjut (action plan):** `DRAFT` → `DIAJUKAN` → `DISETUJUI` | `DITOLAK` (final) | `REVISI` (kembali ke PIC, ajukan ulang tanpa batas) → setelah disetujui: `MENUNGGU_EVIDENCE` → evidence `DIAJUKAN` → `EVIDENCE_DISETUJUI` (selesai) | `EVIDENCE_REVISI`.

**Aturan bobot & progress:**
- Total bobot tindak lanjut aktif (bukan DITOLAK) per temuan per departemen per PIC-scope = 100%.
- PIC dan Manager Dept boleh ubah bobot; Manager override menang. Tidak bisa diajukan/disetujui bila total ≠ 100.
- Progress departemen = jumlah bobot tindak lanjut yang evidence-nya **sudah disetujui Manager**. Progress temuan = rata-rata progress departemen (tampilan); syarat teruskan ke IA: semua departemen 100%.
- Departemen baru bisa diteruskan ke IA saat 100%; Manager IA menilai setelah **semua** departemen meneruskan.

## 2b. Menu & izin halaman dinamis (dari database)

Menu/halaman disimpan di DB. Setiap halaman punya 4 izin: **lihat, tambah, ubah, hapus**. Izin bisa diatur per role (default) dan per karyawan/user (override). Contoh: karyawan X hanya boleh melihat halaman Tindak Lanjut (tanpa tambah/ubah/hapus).

Izin efektif = override user (jika ada) → kalau tidak ada, pakai default role. Nilai override null = ikut role.
Izin menu adalah lapisan akses halaman + CRUD. Aturan alur (siapa boleh approve, siapa boleh assess, dst) dan scope visibilitas data (PIC hanya melihat temuannya) tetap berlaku dan tidak bisa dilampaui oleh izin menu.
Hanya superadmin teknis yang mengelola izin (bisa dikonfigurasi). Ada proteksi agar tidak mengunci diri sendiri dari halaman Manajemen Akses.

Menu didaftarkan lewat seeder (kode menu tetap di kode, dipakai middleware); admin hanya mengatur nama, urutan, aktif/nonaktif, dan izin.
Setiap milestone fitur wajib mendaftarkan menu-nya dan memakai middleware `permission:{menu_code},{aksi}` di route.

## 3. Prinsip teknis (berlaku semua milestone)

- Reusable component (FE) + Service/Resource/Policy layer (BE), tidak ada logika bisnis di controller.
- Cache: master data (departemen, karyawan, role) di-cache dengan invalidasi saat write; dashboard cache TTL pendek per role/user; React Query `staleTime` sesuai jenis data.
- Visibilitas data lewat satu global scope/policy terpusat (PIC hanya melihat temuan yang di-assign ke dirinya).
- Semua perubahan state tercatat di audit trail (dari M0 disiapkan helper, dilengkapi M10).

## 4. Daftar milestone

| # | Milestone | Hasil utama |
|---|---|---|
| M0 | Fondasi proyek | Repo, Laravel+React, AGENTS.md, cache helper, komponen dasar, struktur folder |
| M1 | Auth, RBAC & Izin Menu Dinamis | Login, 5 role, tabel menu + izin per role/user, middleware, menu dinamis FE |

**M1 — Auth, RBAC & Izin Menu Dinamis**

_Backend — Menu & izin_

4. Tabel `menus`: `id`, `code` (unik, tetap di kode), `name`, `path`, `icon`, `parent_id` (nullable, untuk grup), `sort_order`, `is_active`.
5. Tabel `role_menu_permissions`: `role`, `menu_id`, `can_view`, `can_create`, `can_update`, `can_delete` (unik role+menu).
6. Tabel `user_menu_permissions`: `user_id`, `menu_id`, `can_view`/`can_create`/`can_update`/`can_delete` nullable (null = ikut role; non-null = override), unik user+menu.
7. Aturan: `can_create`/`update`/`delete` hanya efektif bila `can_view` true. Superadmin selalu penuh dan tidak bisa dibatasi.
8. `PermissionService::effective(User)` mengembalikan peta `menu_code → {view, create, update, delete}` (override user ?? role). Cache lewat CacheService per user (invalidasi saat izin role/user berubah, saat role user berubah).
9. Middleware `permission:{menu_code},{aksi}` (aksi: view|create|update|delete) dan Gate/helper `can_menu($menu, $action)`. Route GET = view, POST = create, PUT/PATCH = update, DELETE = delete, kecuali ditentukan lain.
10. `MenuSeeder` (idempotent) mendaftarkan menu awal: `dashboard`, `master.departments`, `master.employees`, `access.permissions`, `audit_trail`, serta placeholder menu fitur: `findings.reports`, `findings.distribution`, `findings.list`, `action_plans`, `action_plan_reviews`, `evidence`, `assessments`, `verifications`, `exports`. Seeder default matriks izin per role (sesuai tugas role di roadmap).
11. `GET /auth/me` mengembalikan user, role, departemen, pohon menu yang boleh dilihat + izin efektif.
12. Seeder user development per role (password dari env) + superadmin.
13. Test: login sukses/gagal, user nonaktif, izin efektif (role saja, override user menambah, override user mencabut), create tanpa view tidak efektif, route ditolak 403 tanpa izin, cache terinvalidasi saat izin berubah.

_Frontend_

14. Login, AuthProvider, useAuth, ProtectedRoute.
15. Sidebar dibangun dari `me.menus` (bukan hard-code). Hook `usePermission(menuCode)` → `{canView, canCreate, canUpdate, canDelete}`; komponen reusable `<Can menu="..." action="create">` dan `<PermissionRoute menu="...">`.
16. Update DataTable/PageHeader agar tombol Tambah/Ubah/Hapus otomatis disembunyikan berdasarkan izin halaman (prop `permissionMenu`).
17. Halaman 403/404, auto-logout saat 401.

_Acceptance criteria_

- Memberi user X izin hanya `can_view` pada satu halaman: user X melihat halaman itu tanpa tombol tambah/ubah/hapus, dan API create/update/delete mengembalikan 403.
- Menu di sidebar hilang jika `can_view` false.
- Override user mengalahkan default role di kedua arah.
- Test BE lolos.

_Larangan_

- Jangan hard-code menu di FE.
- Jangan buat UI pengaturan izin (itu M2).
- Jangan biarkan izin menu menembus scope visibilitas/alur kerja.
- Jangan beri `Gate::before` akses bebas selain superadmin.
| M2 | Data master & manajemen akses | Departemen (seed 13), karyawan, akun user, UI atur izin menu per role/karyawan, CRUD + cache |
| M3 | Input temuan (Admin SPI) | Temuan, rekomendasi, action plan auditor, dokumen, kirim ke IA |
| M4 | Distribusi | Manager IA → departemen (multi), Manager Dept → PIC (multi), scoping visibilitas |
| M5 | Tindak lanjut PIC | CRUD tindak lanjut, dokumen berlabel, bobot, ajukan |
| M6 | Review tindak lanjut | Approve/tolak/revisi, override bobot, validasi 100% |
| M7 | Evidence & progress | Upload evidence, review, perhitungan progress, teruskan ke IA |
| M8 | Assessment IA & ronde | 4 status, BSR → ronde baru, TDTL → case closed |
| M9 | Verifikasi SPI & closing | Input hasil auditor eksternal, closing hanya SSR |
| M10 | Audit trail, dashboard, export | Audit trail viewer, dashboard per role, export Excel |
| M11 | Hardening & rilis | Optimasi cache/query, test end-to-end, keamanan upload, dokumentasi deploy |

## 5. Di luar scope

Notifikasi (in-app/email), deadline/jatuh tempo, SSO. Bisa ditambah setelah M11.

## 6. Asumsi yang saya tetapkan (koreksi bila salah)

1. Tolak = final; PIC wajib menyesuaikan bobot sisanya agar 100%.
2. Ronde baru (BSR): departemen dari ronde sebelumnya terbawa (Manager IA boleh ubah), tindak lanjut baru, PIC di-assign ulang oleh Manager Dept, riwayat lama read-only.
3. Jika auditor eksternal belum puas padahal IA menetapkan SSR, Manager SPI mencatat hasil dan mengembalikan temuan ke Manager IA (status menjadi BSR → ronde baru).
4. Evidence per tindak lanjut boleh banyak file berlabel, tapi satu pengajuan per tindak lanjut yang di-approve sekaligus.
5. Bobot dalam satuan persen bilangan bulat atau desimal 2 digit, total tepat 100.
6. Frontend memakai TypeScript, TanStack Query, React Router, Tailwind.
7. Izin menu bersifat override terhadap default role, tetapi tidak membuka akses di luar scope visibilitas dan aturan alur kerja.
8. Menu dan route baru wajib didaftarkan di seeder menu agar muncul di pengaturan izin.