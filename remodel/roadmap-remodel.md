# Roadmap Remodel — e-TLHT (Tindak Lanjut Hasil Temuan)

Sumber: dokumen *e-TLHT Alur Bisnis* (PT. Bumi Siak Pusako, Oktober 2026).
Konteks: M0–M11 versi awal sudah selesai. Remodel ini mengerjakan ulang domain alur kerja di atas fondasi yang ada. Kerjakan **berurutan R0 → R11**, satu prompt per sesi opencode, test lalu commit tiap milestone.

## 1. Strategi remodel
- Git: beri tag `v1-m11` pada kode lama, kerjakan di branch `remodel/etlht`.
- **R0 membersihkan** modul alur kerja lama (distribusi, tindak lanjut lama, review, evidence, assessment IA, verifikasi SPI lama) dan merapikan migration (data development boleh direset, `migrate:fresh --seed`). Setelah itu R2–R8 membangun ulang alur kerja sesuai e-TLHT.
- **Dipertahankan:** auth Sanctum, menu + izin dinamis (lihat/tambah/ubah/hapus per role & per karyawan), data master departemen & karyawan, `CacheService`, `DocumentService` (dokumen polimorfik berlabel), `AuditLogger`/audit trail, komponen UI reusable, dashboard shell, exporter Excel.
- Peringatan nama: di kode lama `action_plans` berarti *tindak lanjut*. Di model baru `action_plans` = **Action Plan** (level atas) dan tindak lanjut = `follow_ups`. Karena itu R0 menghapus tabel lama dulu supaya tidak bentrok.

## 2. Peran (7 peran + superadmin teknis)
| Role | Kode | Departemen (master) | Tanggung jawab |
|---|---|---|---|
| Admin SPI | `admin_spi` | SPI | Registrasi temuan & action plan; review SPI per tindak lanjut (Sesuai/Revisi) |
| Internal Audit | `internal_audit` | IA (departemen IA juga auditee) | Dapat registrasi temuan & action plan; memantau & mengomentari tindak lanjut yang sudah disetujui manager (tanpa komentar = setuju) |
| Manager Departemen | `manager_dept` | departemen auditee | Tentukan PIC; setujui/revisi/tolak tindak lanjut; setujui penyelesaian; atur bobot; ajukan action plan 100% ke Admin SPI; teruskan revisi ke PIC |
| PIC Departemen | `staff_dept` | departemen auditee | Susun tindak lanjut (draft), ajukan, lapor progres + dokumen sampai 100% |
| Kepala SPI | `kepala_spi` | SPI | Catat status auditor eksternal (SSR/BSR/BD/TDTL); satu-satunya yang boleh mengubah temuan Closed |
| Manager Internal Audit | `manager_ia` | IA | Pantau seluruh temuan & laporan (baca saja) |

Catatan departemen IA: IA **juga bisa menjadi auditee**. Karena `manager_ia` baca saja, departemen IA sebagai auditee memakai `manager_dept` dan `staff_dept` tersendiri (akun terpisah dari `internal_audit`/`manager_ia`). SPI dan DIREKSI bukan auditee.
| Direksi | `direksi` | DIREKSI | Pantau seluruh temuan & laporan (baca saja) |

Aturan tetap: 1 user = 1 akun = 1 departemen = 1 jabatan. Akun punya tanggal **"Aktif sampai"**; setelah tanggal itu tidak bisa login.

## 3. Struktur data
```
Temuan (findings)
 └─ Action Plan (action_plans)         — dijalankan SATU departemen; satu temuan bisa banyak AP (juga >1 AP per departemen)
     └─ Tindak Lanjut (follow_ups)     — disusun PIC; bobot, tanggal penyelesaian, PIC (>=1), progres, dokumen, diskusi
```
**findings**: registration_number (otomatis saat Registrasi), source (BPK/BPKP/KAP/Lainnya + nama), lhp_number, lhp_date, finding_date (boleh mundur), response_period_start/end (boleh mundur), fiscal_year (dari tahun `response_period_start`), scope (ruang lingkup), status, activated_at, closed_at/by. Pivot `finding_auditee_departments`. Dokumen LHP berlabel.
**action_plans**: finding_id, department_id, code, title, condition (kondisi), criteria (kriteria), cause (sebab), impact (dampak), risk (risiko: enum Rendah/Sedang/Tinggi/Kritis), deadline, loss_idr, loss_usd (nullable), status, current_revision (0 = Awal), progress (cache). Pivot `action_plan_assignees` (PIC). Dokumen berlabel.
**action_plan_revisions**: action_plan_id, revision_no, requested_by, requested_role, source (`SPI_REVIEW`/`EXTERNAL_STATUS`), reason, requested_at, forwarded_to_pic_at, new_deadline (nullable).
**follow_ups**: action_plan_id, revision_no, description, target_date, weight (integer), progress (0–100, tidak boleh turun), status, linked_follow_up_id (nullable), approved_by/at, completed_at. Pivot `follow_up_assignees` (PIC >=1).
**follow_up_reviews**: keputusan manager (Setujui/Revisi/Tolak + catatan) dan keputusan penyelesaian.
**follow_up_progress_reports**: nilai progres, catatan, reported_by/at; dokumen berlabel polimorfik.
**follow_up_comments**: `kind` = `DISKUSI` | `IA_COMMENT`, author, body.
**spi_reviews / spi_review_items**: per action plan per revisi; item per tindak lanjut: `SESUAI`/`REVISI` + catatan.
**external_status_records**: finding_id, status (`SSR`/`BSR`/`BD`/`TDTL`), note, recorded_by/at, dokumen berlabel; pivot `external_status_action_plans` (AP yang dipilih untuk direvisi).
**inbox_tasks**: recipient_id, task_type, subject (polimorfik), title, received_at, first_opened_at, acted_at.
**reminder_logs**: follow_up_id/task_id, type, sent_on (unik per hari agar idempotent). **notifications**: database notification Laravel.

## 4. Status
**Temuan:** `DRAFT` → `TERDAFTAR` (Registrasi, nomor otomatis) → `PROSES_TINDAK_LANJUT` (Aktifkan) → `REVIEW_SPI` → `MENUNGGU_STATUS_EKSTERNAL` → `CLOSED`.
Status setelah Proses dihitung ulang otomatis oleh `FindingStatusService::recompute`:
- ada AP yang bukan {`DIAJUKAN_KE_SPI`, `SESUAI`} → `PROSES_TINDAK_LANJUT`
- semua AP {`DIAJUKAN_KE_SPI`, `SESUAI`} dan ada minimal satu `DIAJUKAN_KE_SPI` → `REVIEW_SPI`
- semua AP `SESUAI` → `MENUNGGU_STATUS_EKSTERNAL`
- SSR → `CLOSED`

**Action Plan:** `DRAFT` → (Kirim) `MENUNGGU_PENENTUAN_PIC` → (Manager tentukan PIC) `PROSES_TINDAK_LANJUT` → (Manager ajukan, semua TL Selesai & bobot 100) `DIAJUKAN_KE_SPI` → Review SPI: `SESUAI` | `REVISI_SPI` (revisi ke-N; manager teruskan ke PIC → `PROSES_TINDAK_LANJUT`). SSR → `CLOSED`.

**Tindak Lanjut:** `DRAFT` → `DIAJUKAN` → `DISETUJUI` | `REVISI` | `DITOLAK`; `DISETUJUI` → (progres 100) `MENUNGGU_PERSETUJUAN_SELESAI` → `SELESAI` | kembali `DISETUJUI` (manager minta revisi penyelesaian). Manager dapat mengembalikan `DISETUJUI` ke `REVISI` bila Internal Audit memberi masukan.

## 5. Aturan bisnis penting
1. **Bobot:** bilangan bulat; total bobot tindak lanjut aktif (bukan `DITOLAK`/`REVISI` yang tidak dipakai) **per action plan per revisi ≤ 100**; wajib **tepat 100** saat action plan diajukan ke Admin SPI. PIC menetapkan, manager boleh mengubah (override).
2. **Tanggal:** `target_date` tindak lanjut tidak boleh melewati `deadline` action plan (validasi keras). Revisi dapat membawa `new_deadline` dari peminta revisi.
3. **Progres:** PIC melapor progres integer, tidak boleh turun; hanya untuk TL `DISETUJUI` ke atas. Lapor 100 → `MENUNGGU_PERSETUJUAN_SELESAI`. **Progres TL baru dihitung ke action plan bila TL berstatus `DISETUJUI`, `MENUNGGU_PERSETUJUAN_SELESAI`, atau `SELESAI`.**
   - Progres AP = Σ(bobot × progres) / Σ(bobot) atas TL yang dihitung, pada revisi berjalan. Progres temuan = rata-rata progres AP (AP `SESUAI`/`CLOSED` dihitung 100). Jadi progres temuan selalu bersumber dari progres AP, yang bersumber dari tindak lanjutnya.
4. **Pengajuan ke Admin SPI:** hanya bila semua TL revisi berjalan (yang aktif) `SELESAI` dan total bobot 100.
5. **Revisi (per action plan):** Awal → Revisi 1, 2, …; tiap revisi mencatat siapa, kapan, alasan. TL lama tersimpan lengkap sebagai riwayat; PIC menyusun TL baru (boleh ditautkan ke TL yang diperbaiki) dengan deadline & bobot baru. **Hanya AP yang direvisi yang berjalan; AP `SESUAI` dibekukan.**
6. **Review SPI:** satu halaman per AP; tiap TL dinilai Sesuai/Revisi (catatan wajib untuk Revisi); "Simpan & Selesaikan" hanya bila semua TL sudah dinilai. Ada satu Revisi → AP `REVISI_SPI`; semua Sesuai → AP `SESUAI`.
7. **Status eksternal (Kepala SPI)**, hanya saat temuan `MENUNGGU_STATUS_EKSTERNAL`: SSR → temuan dan semua AP `CLOSED` otomatis. **BSR / BD / TDTL** → Kepala SPI memilih ≥1 AP yang harus diperbaiki → AP itu masuk revisi berikutnya, alur berulang dari penentuan/penerusan ke PIC; temuan kembali `PROSES_TINDAK_LANJUT`.
8. **Temuan Closed:** hanya `kepala_spi` yang boleh mengubah; tiap perubahan dicatat nilai lama & baru.
9. **Umur temuan** = hari sejak `finding_date`, tidak pernah direset. **Keterlambatan** dihitung dari `target_date` tiap TL (belum `SELESAI` dan lewat tanggal).
10. **Dokumen** bisa dilampirkan di temuan, action plan, setiap laporan progres, dan status eksternal; semua berlabel. **Unduh satu per satu, tanpa unduhan massal/zip.**

## 6. Visibilitas (satu tempat terpusat: `Visibility` services)
- Departemen hanya melihat AP miliknya **setelah AP dikirim**. PIC hanya melihat AP tempat ia ditunjuk, dan **tidak lagi melihatnya setelah temuan Closed**; manager tetap bisa.
- `admin_spi`, `internal_audit`, `kepala_spi`, `manager_ia`, `direksi` melihat **seluruh temuan dan AP**, tetapi **tindak lanjut baru terlihat setelah disetujui manager** (`DISETUJUI`, `MENUNGGU_PERSETUJUAN_SELESAI`, `SELESAI`).
- Izin menu (lihat/tambah/ubah/hapus) tetap lapisan terluar dan tidak boleh menembus aturan di atas maupun aturan alur.

## 7. Notifikasi, pelacakan, pengingat (masuk scope, in-app saja)
- Lonceng notifikasi untuk setiap tugas dan keputusan. **Inbox Persetujuan**: per tugas tercatat kapan diterima, kapan pertama dibuka, kapan ditindaklanjuti.
- Scheduler harian: pengingat **H-30, H-14, H-7, H-3, H-1** sebelum `target_date` TL ke PIC; **terlambat** → eskalasi ke manager; **terlambat > 7 hari** → eskalasi ke Kepala SPI; tugas **belum dibuka > 3 hari** → pengingat (diulang tiap 3 hari selama belum dibuka). Idempotent lewat `reminder_logs`.
- Semua aktivitas masuk **Log Aktivitas** (audit trail).

## 8. Peta halaman (menu → route)
| Menu (kode) | Route | Role utama |
|---|---|---|
| Dashboard (`dashboard`) | `/dashboard` | semua |
| Temuan (`findings`) | `/temuan`, `/temuan/baru`, `/temuan/:id` (tampilan bertingkat Temuan > AP > TL, detail TL pop-up) | CRUD: admin_spi, internal_audit; lihat: lainnya sesuai visibilitas |
| Action Plan (`action_plans`) | `/action-plan`, `/action-plan/baru?temuan=:id` (multi-departemen), `/action-plan/:id` | buat/kirim: admin_spi, internal_audit; tentukan PIC & ajukan ke SPI: manager_dept |
| Tindak Lanjut (`follow_ups`) | `/tindak-lanjut`, `/action-plan/:id/tindak-lanjut/susun` (susun banyak sekaligus) | PIC (susun/ajukan/lapor progres), manager_dept (lihat/atur bobot) |
| Persetujuan Manager (`follow_up_reviews`) | `/persetujuan` (tab: Tindak Lanjut, Penyelesaian) | manager_dept |
| Pemantauan IA (`ia_monitoring`) | `/pemantauan` | internal_audit (komentar) |
| Review SPI (`spi_review`) | `/review-spi`, `/review-spi/:actionPlanId` (satu halaman) | admin_spi |
| Status Eksternal (`external_status`) | `/status-eksternal`, `/status-eksternal/:findingId` | kepala_spi |
| Inbox Persetujuan (`inbox`) | `/inbox` | semua yang punya tugas |
| Monitoring & Laporan (`reports`) | `/laporan` (rekap per departemen, keterlambatan, umur temuan, risiko, export Excel) | semua (sesuai visibilitas) |
| Log Aktivitas (`audit_trail`) | `/log-aktivitas` | admin_spi, internal_audit, kepala_spi, manager_ia, direksi, superadmin |
| Master & Akses | `/master/departemen`, `/master/karyawan`, `/akses` | superadmin |
Lonceng notifikasi ada di topbar (bukan menu).

## 9. Pemetaan kode lama → baru
| Lama (M0–M11) | Baru |
|---|---|
| `audit_reports` + `findings` | `findings` (field LHP lengkap; laporan dilebur ke temuan) |
| `finding_departments` | **`action_plans`** (restruktur total) |
| `finding_assignees` | `action_plan_assignees` |
| `action_plans` (lama = tindak lanjut) | **`follow_ups`** |
| `action_plan_reviews` | `follow_up_reviews` |
| `evidence_submissions` | `follow_up_progress_reports` (+ dokumen) |
| `finding_rounds`, `AssessmentService` (Manager IA 4 status) | `action_plan_revisions` (revisi per AP); assessment IA **dihapus** |
| `external_verifications`, `ClosingService` | `external_status_records`, `ExternalStatusService` (Kepala SPI) |
| Role `manager_ia` (distribusi), `manager_spi` | `manager_ia` (baca saja), `kepala_spi` |
| Status `CASE_CLOSED`, TDTL = case closed | **dihapus** (TDTL memicu revisi) |
| Evidence di-approve per upload | **dihapus** (progres langsung; manager setujui saat 100%) |

## 10. Milestone
| # | Milestone | Hasil |
|---|---|---|
| R0 | Inventaris & pembersihan | Tag/branch, hapus modul alur lama, migration dirapikan, AGENTS.md baru |
| R1 | Peran, departemen, akun | 7 peran, SPI/IA/DIREKSI, "Aktif sampai", menu & izin baru |
| R2 | Temuan | Registrasi LHP, nomor otomatis, Draft→Terdaftar→Proses, tanggal mundur |
| R3 | Action plan | Buat multi-departemen, kirim, penentuan PIC, visibilitas |
| R4 | Tindak lanjut (PIC) | Susun banyak sekaligus, bobot bulat, ajukan |
| R5 | Keputusan manager & pemantauan IA | Setujui/revisi/tolak, komentar IA, kembalikan ke revisi |
| R6 | Progres & penyelesaian | Laporan progres, diskusi, setujui selesai, ajukan ke Admin SPI |
| R7 | Review SPI & revisi | Satu halaman, Sesuai/Revisi, Revisi 1/2/… per AP |
| R8 | Status eksternal & closing | SSR/BSR/BD/TDTL, penguncian temuan Closed |
| R9 | Notifikasi, inbox, pengingat | Lonceng, Inbox Persetujuan, pelacakan, scheduler |
| R10 | Tampilan, monitoring, laporan | Tree temuan + pop-up, dashboard, rekap, export |
| R11 | Hardening & rilis | Regresi, test alur penuh & visibilitas, performa, dokumen |

## 11. Asumsi saya (koreksi bila salah)
1. Flag `is_auditee`: SPI dan DIREKSI = false, **IA = true** (keputusan Anda). Departemen IA berisi 4 jenis akun: `internal_audit`, `manager_ia`, `manager_dept`, `staff_dept`. Flag bisa diubah di master.
2. Permintaan revisi penyelesaian oleh manager mengembalikan TL ke `DISETUJUI` (progres tetap 100); PIC menambah laporan/dokumen lalu melapor 100 lagi untuk mengajukan ulang.
3. Manager mengembalikan TL `DISETUJUI` ke `REVISI` atas masukan IA: progres tersimpan, PIC boleh ubah uraian/tanggal/bobot lalu mengajukan ulang.
4. Satu formulir action plan untuk banyak departemen membuat satu AP per departemen dengan isi sama; deadline per departemen boleh dioverride.
5. Format nomor registrasi: `TLHT-{tahun_buku}-{urut 4 digit}`, urutan per tahun buku, aman terhadap race condition.
6. Pengingat unopened diulang tiap 3 hari selama belum dibuka; eskalasi keterlambatan dikirim sekali per hari selama masih terlambat.
7. Event domain didispatch mulai R3 (listener no-op sampai R9) supaya notifikasi tidak perlu merombak service lama.
