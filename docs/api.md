# API e-TLHT (`/api/v1`)

Semua endpoint default `Bearer <token>` (Sanctum). Respons JSON: `{ success, data, message }`. 
Setiap halaman dilindungi middleware `permission:{menu_code},{aksi}`; data tambahan dibatasi scope visibilitas.

## Auth & Profil (auth-only)
| Metode | Path | Keterangan |
|---|---|---|
| POST | `/auth/login` | rate-limited (5/menit) |
| GET | `/auth/me` | user + department + permissions + menus |
| POST | `/auth/logout` | cabut token |
| POST | `/auth/change-password` | |
| GET | `/lookups/departments?auditee=1` | departemen (auditee) |
| GET | `/lookups/staff?department_id=` | staff aktif per departemen |

## Temuan (`findings`)
`GET /findings` (filter status/fiscal_year/source/department_id/q) · `POST` · `GET /{id}` · `GET /{id}/tree` (AP, cache 120s) · `PUT` (edit; CLOSED hanya Kepala SPI, tercatat old/new) · `DELETE` (Draft saja) · `POST /{id}/register` · `POST /{id}/activate`.
Dokumen: `GET/POST /{id}/documents` (label wajib), `DELETE /documents/{document}`, unduh `GET /documents/{document}/download`.

## Action Plan (`action_plans`)
`GET /action-plans` (filter status/finding/department) · `POST` (multi-departemen, 1 formulir → 1 AP per dept) · `GET/PUT/DELETE /{id}` (PUT/DELETE hanya DRAFT) · `POST /send` ·
`POST /assign-pics` (manager) · `POST /submit-to-spi` (manager, syarat: semua TL SELESAI + bobot 100) · `POST /forward-to-pic` (setelah Revisi SPI) · `PUT /deadline` (admin_spi hanya) ·
Dokumen + unduh per-item. `GET /{id}/review-bundle` (data review SPI, cache 120s).

## Tindak Lanjut (`follow_ups`)
`GET /follow-ups` (filter status/action_plan/finding) · `POST /action-plans/{ap}/follow-ups` (batch) · `GET/PUT /{id}` (PUT hanya DRAFT/REVISI) · `POST /submit` ·
Keputusan manager: `POST /{id}/approve|revision|reject|return-to-revision|approve-completion|completion-revision`, `PATCH /{id}/weight` ·
PIC: `POST /{id}/progress` (multipart + dokumen berlabel, tidak bisa turun) · `GET /{id}/progress-reports` ·
Komentar: `GET /{id}/comments`, `POST /{id}/comments` (IA_COMMENT hanya TL disetujui; DISKUSI utk PIC/manager/pemantau) · `GET /{id}/reviews` (timeline keputusan).

## Review SPI (`spi_review`)
`GET /spi-reviews` (antrian DIAJUKAN_KE_SPI) · `POST /{ap}/spi-review` (penilaian per TL; catatan wajib utk REVISI) · `POST /{ap}/spi-complete` (Simpan & Selesaikan; naikkan revisi) ·
`GET /{ap}/review-bundle`.

## Status Eksternal (`external_status`)
`GET /external-status` (antrian MENUNGGU_STATUS_EKSTERNAL) · `GET /findings/{id}/external-status-records` ·
`POST /findings/{id}/external-status` (multipart dokumen; SSR→tutup, BSR/BD/TDTL→revisi AP pilihan) · unduh dokumen per-item.

## Dashboard, Laporan, Export
`GET /dashboard` (per role: counters, widget, tugas, notifikasi) ·
`GET /reports/departments|late|finding-age|risk` (filter fiscal_year/source/department_id/status; cache 300s) ·
`GET /exports/findings|action-plans|audit-trail|reports/departments|reports/late|reports/risk` (CSV, scope-safe).

## Inbox & Notifikasi
`GET /inbox` (tugas + received/opened/acted) · `POST /inbox/open` (catat first_opened) ·
`GET /notifications` · `GET /notifications/unread-count` · `POST /notifications/{id}/read` · `POST /notifications/read-all`.

## Audit
`GET /audit-trail` (filter action/entity/user/rentang tanggal) · `GET /audit-trail/actions`.

## Health
`GET /api/v1/health` (publik).