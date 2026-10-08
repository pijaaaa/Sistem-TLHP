# AGENTS.md — e-TLHT (versi remodel, berdiri sendiri; menggantikan AGENTS.md lama sepenuhnya)

## Proyek
Sistem e-TLHT (Tindak Lanjut Hasil Temuan audit eksternal). Backend: Laravel 11 API (`/api/v1`), MySQL. Frontend: React + Vite + TypeScript + TanStack Query + React Router + Tailwind. Dev di Laragon.
Alur bisnis, status, aturan bobot/progres/revisi, peta halaman: `docs/roadmap-remodel.md`. **Baca bagian yang relevan dengan milestone yang dikerjakan** (jangan memuat seluruh file bila tidak perlu).

## Glosarium (jangan campur istilah)
- **Temuan** (`findings`) → **Action Plan** (`action_plans`, dijalankan satu departemen) → **Tindak Lanjut** (`follow_ups`, disusun PIC).
- Di kode lama `action_plans` = tindak lanjut. Itu sudah DIHAPUS di R0; jangan mengacu ke model lama.
- Role: admin_spi, internal_audit, manager_dept, staff_dept (PIC), kepala_spi, manager_ia (baca saja), direksi, + superadmin teknis. 1 user = 1 akun = 1 departemen = 1 role.

## Aturan kerja
1. Kerjakan HANYA milestone yang diminta. Jangan membuat fitur milestone lain, jangan memulihkan kode modul lama.
2. Backend berlapis: Route → FormRequest → Controller tipis → Service (logika bisnis) → Model. Output lewat API Resource. Otorisasi lewat Policy. Status/role pakai PHP Enum.
3. Semua transisi status lewat Service dengan validasi; transisi ilegal → HTTP 422. Service domain men-dispatch Domain Event; listener notifikasi dibuat di R9.
4. Migration wajib punya foreign key, index, dan soft delete bila entitas bisnis.
5. Cache hanya lewat `App\Support\CacheService` (key berversi + invalidasi); dilarang `Cache::remember` liar; jangan bergantung pada cache tags (driver Laragon bisa `file`). Invalidasi saat progres, dashboard, izin, atau master berubah.
6. Frontend: wajib komponen reusable di `src/components/ui` dan `src/components/shared`; jangan menyalin markup tabel/form/modal. Data fetching hanya lewat hook di `src/hooks` (TanStack Query).
7. Visibilitas data hanya lewat `FindingVisibility`, `ActionPlanVisibility`, `FollowUpVisibility` (scope + policy); dilarang filter ad-hoc di controller. Role pemantau (admin_spi, internal_audit, kepala_spi, manager_ia, direksi) baru melihat tindak lanjut setelah manager menyetujui.
8. Temuan/AP `CLOSED` read-only kecuali oleh `kepala_spi`; setiap perubahan dicatat nilai lama & baru.
9. Dokumen: label wajib, simpan di disk `private`, validasi mime & ukuran dari config, unduh **satu per satu** lewat endpoint terotorisasi; DILARANG endpoint unduh massal/zip.
10. Bobot = integer. Progres = integer 0–100 dan tidak boleh turun. `target_date` tindak lanjut ≤ deadline action plan.
11. Menu/halaman dan izin (lihat/tambah/ubah/hapus) bersumber dari database: setiap halaman baru didaftarkan di `MenuSeeder`, route dilindungi `permission:{menu_code},{aksi}`, FE memakai `usePermission`/`<Can>`. Izin menu tidak boleh menembus visibilitas dan aturan alur.
12. Setiap fitur ada test (Pest/PHPUnit feature test untuk BE). Jalankan test sebelum selesai.
13. Bahasa UI: Indonesia. Kode, nama tabel, variabel: Inggris.
14. Akhiri tiap tugas dengan ringkasan: file dibuat/diubah, cara menjalankan, yang belum dikerjakan.
15. Jangan mengubah file di luar kebutuhan milestone, jangan menghapus test (kecuali yang diinstruksikan R0), jangan commit secret.