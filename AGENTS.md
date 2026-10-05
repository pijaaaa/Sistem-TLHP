# AGENTS.md — taruh di root repo (opencode membacanya otomatis). Tempel juga di awal sesi bila perlu.

## Proyek
Sistem tindak lanjut temuan audit eksternal. Backend: Laravel 11 API (`/api/v1`), MySQL. Frontend: React + Vite + JavaScript + TanStack Query + React Router + Tailwind. Dev di Laragon.
Role: admin_spi, manager_ia, manager_dept, staff_dept (PIC), manager_spi. 1 user = 1 role = 1 departemen.
Alur lengkap ada di `docs/roadmap.md` — baca sebelum mengerjakan milestone.

## Aturan kerja
1. Kerjakan HANYA milestone yang diminta. Jangan membuat fitur milestone lain.
2. Backend berlapis: Route → FormRequest → Controller tipis → Service (logika bisnis) → Model. Output lewat API Resource. Otorisasi lewat Policy. Status/role pakai PHP Enum.
3. Semua transisi status lewat Service dengan validasi; tolak transisi ilegal dengan HTTP 422.
4. Migration wajib punya foreign key, index, dan soft delete bila entitas bisnis.
5. Cache: gunakan `App\Support\CacheService` (key berversi + invalidasi). Dilarang `Cache::remember` liar. Driver Laragon bisa `file`, jadi jangan bergantung pada cache tags.
6. Frontend: wajib pakai komponen reusable di `src/components/ui` dan `src/components/shared`. Dilarang menyalin markup tabel/form/modal. Data fetching hanya lewat hook di `src/hooks` memakai TanStack Query.
7. Visibilitas data (terutama PIC) hanya lewat scope/policy terpusat, bukan filter ad-hoc di controller.
8. Upload file: validasi mime & ukuran dari config, simpan di disk `private`, unduh lewat endpoint terotorisasi.
9. Tiap fitur ada test (Pest/PHPUnit feature test untuk BE). Jalankan test sebelum selesai.
10. Bahasa UI: Indonesia. Kode, nama tabel, nama variabel: Inggris.
11. Akhiri tiap tugas dengan ringkasan: file yang dibuat/diubah, perintah menjalankan, apa yang belum dikerjakan.
12. Jangan mengubah file di luar kebutuhan milestone, jangan menghapus test, jangan commit secret.
