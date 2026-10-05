# Sistem TLHP — Tindak Lanjut Temuan Audit Eksternal

Aplikasi web untuk managing siklus penuh tindak lanjut temuan audit eksternal:
input temuan → distribusi ke departemen → tindak lanjut PIC → review →
evidence & progress → assessment IA → verifikasi auditor → closing.

Alur dan state machine lengkap ada di [`docs/roadmap.md`](docs/roadmap.md).

## Stack

| Bagian | Teknologi |
|---|---|
| Backend | Laravel 11 (API, prefix `/api/v1`), Sanctum token auth, MySQL |
| Frontend | React + Vite + TypeScript, TanStack Query, React Router, Tailwind |
| Testing | Pest (feature test), SQLite in-memory |

## Menjalankan lokal (Laragon)

### 1. Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Isi `.env` minimal:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_tlhp
DB_USERNAME=root
DB_PASSWORD=

# Token Sanctum untuk API ( Sanctum:: expirationMinutes )
SANCTUM_STATEFUL_DOMAINS=localhost:5173

# Batas unggah
UPLOAD_MAX_SIZE_KB=10240
```

Buat database lalu jalankan:

```bash
php artisan migrate --seed
php artisan serve          # http://localhost:8000
```

Seeder membuat 13 departemen, seluruh menu + matriks izin per role, dan user
developer. Password semua akun mengikuti `DEV_USER_PASSWORD` (default
`password`):

| Role | Email | Departemen |
|---|---|---|
| Admin SPI | `admin_spi@example.com` | FINANCE & ICT |
| Manager IA | `manager_ia@example.com` | IA |
| Manager Departemen | `manager_finance@example.com` | FINANCE & ICT |
| Staff / PIC | `pic_finance@example.com` | FINANCE & ICT |
| Manager SPI | `manager_spi@example.com` | FINANCE & ICT |
| Super Admin | `superadmin@example.com` | FINANCE & ICT |

> Set `DEV_USER_PASSWORD` yang kuat, atau ganti password seluruh akun developer
> sebelum dipakai di lingkungan mana pun yang dapat diakses pengguna lain.

### 2. Frontend

```bash
cd frontend
npm install
cp .env.example .env        # VITE_API_URL=http://localhost:8000/api/v1
npm run dev                 # http://localhost:5173
```

## Menjalankan test

```bash
cd backend && php artisan test          # seluruh feature test
cd backend && php artisan test --filter=EndToEndTest   # alur penuh

cd frontend && npm run build            # cek TypeScript + bundling
```

## Deploy

### Backend

```bash
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan storage:link               # tidak wajib: dokumen memakai disk `private`
php artisan optimize
```

Wajib di produksi:

- `APP_DEBUG=false`
- `APP_ENV=production`
- `APP_URL` sesuai domain
- `CACHE_STORE=redis` atau `file` (driver `file` sudah didukung; jangan pakai
  cache tag)
- `SESSION_DRIVER=file`
- Kunci disk `private` tetap di dalam `storage/app/private` — **jangan** diarahkan
  ke folder web publik. Dokumen hanya bisa diunduh lewat endpoint terotorisasi.

### Frontend

```bash
npm ci
npm run build          # keluaran di dist/
```

Sajikan `dist/` lewat web server (nginx/Apache) dan arahkan `/api/*` ke backend.

### Checklist produksi

- [ ] HTTPS aktif; `SANCTUM_STATEFUL_DOMAINS` disesuaikan
- [ ] Password akun developer diganti
- [ ] Backup terjadwal untuk MySQL **dan** `storage/app/private`
- [ ] Login dibatasi 5 percobaan/menit per email+IP; unggahan dibatasi
      120 permintaan/menit per pengguna
- [ ] Login tidak lagi memakai session web: seluruh autentikasi via token, dan
      logout mencabut token di database

## Catatan arsitektur

- **Izin menu** tersimpan di DB (`menus`, `role_menu_permissions`,
  `user_menu_permissions`). Route memakai middleware `permission:{menu_code},{aksi}`.
  Izin menu mengatur akses halaman/CRUD, **tidak** melewati aturan alur kerja
  maupun scope visibilitas data (PIC hanya melihat temuan miliknya).
- **Cache** memakai `App\Support\CacheService` dengan key berversi + invalidasi
  eksplisit. Dilarang `Cache::remember` langsung.
- **Audit trail** mencatat setiap perubahan state ke tabel `audits`. Kegagalan
  pencatatan tidak pernah menggagalkan operasi bisnis.
- **Ronde temuan**: kolom `round` Attach to `findings`, `finding_departments`,
  `action_plans`, dan `evidence_submissions`. Ronde lama bersifat read-only.