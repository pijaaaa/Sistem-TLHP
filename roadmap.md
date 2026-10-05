# Roadmap — Sistem Tindak Lanjut Temuan Audit Eksternal

Stack: Laravel 11 (API) + React SPA (Vite) + MySQL (Laragon). Tooling: opencode CLI (via 9router) di VS Code.
Cara pakai: kerjakan **berurutan**, satu prompt per sesi opencode. Setelah tiap milestone: jalankan test, commit, baru lanjut.

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

## 3. Prinsip teknis (berlaku semua milestone)
- Reusable component (FE) + Service/Resource/Policy layer (BE), tidak ada logika bisnis di controller.
- Cache: master data (departemen, karyawan, role) di-cache dengan invalidasi saat write; dashboard cache TTL pendek per role/user; React Query `staleTime` sesuai jenis data.
- Visibilitas data lewat satu global scope/policy terpusat (PIC hanya melihat temuan yang di-assign ke dirinya).
- Semua perubahan state tercatat di audit trail (dari M0 disiapkan helper, dilengkapi M10).

## 4. Daftar milestone
| # | Milestone | Hasil utama |
|---|---|---|
| M0 | Fondasi proyek | Repo, Laravel+React, AGENTS.md, cache helper, komponen dasar, struktur folder |
| M1 | Auth & RBAC | Login, 5 role, policy, route guard FE |
| M2 | Data master | Departemen (seed 13), karyawan, akun user, CRUD + cache |
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
