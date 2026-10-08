# Deploy & Menjalankan Scheduler (Laragon / Windows)

## Menjalankan scheduler otomatis

Fitur pengingat (tenggat H-30/14/7/3/1, eskalasi keterlambatan, pengingat tugas belum dibuka)
dijalankan oleh perintah `php artisan reminders:daily`, dijadwalkan pukul **07:00** via `routes/console.php`.

Di Laragon/Windows gunakan **Task Scheduler** Windows untuk memanggil `schedule:run` setiap menit
(Laravel akan menjalankan perintah yang waktunya tiba; yang lain dilewati).

### Langkah
1. Buka **Task Scheduler** (Start → `taskschd.msc`) → *Create Task*.
2. Trigger: **Daily**, mulai pukul 07:00; centang *Repeat task every* → **1 minutes**, *for a duration of...* → **Indefinitely / 24 hours**.
3. Action → *Start a program*:
   - `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe` (sesuaikan versi PHP Laragon)
   - Arguments: `artisan schedule:run`
   - Start in: direktori backend, contoh `C:\Magang\Sistem TLHP\backend`
4. *Finish*.

Uji manual (idempoten, aman dijalankan dua kali di hari yang sama):
```
cd C:\Magang\Sistem TLHP\backend
php artisan schedule:test
php artisan reminders:daily
```

Catatan: pastikan `php.ini` mengaktifkan ekstensi yang dibutuhkan (pdo_sqlite/pdo_mysql) dan
`APP_ENV` di `.env` tidak `production` saat masih pengembangan.

## Env lain

- DB default dev: MySQL Laragon (`DB_CONNECTION=mysql` di `.env`). Test memakai SQLite in-memory.
- `php artisan migrate:fresh --seed` untuk reset data development.

## Menjalankan aplikasi

- Backend API: `cd backend && php artisan serve` (default `http://localhost:8000`).
- Frontend: `cd frontend && npm run dev` (Vite proxy `/api` → `:8000`, port 5173).

## Optimasi produksi

```bash
cd backend
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force   # tanpa fresh di produksi
```

Cache config/route perlu di-refresh setelah perubahan `.env` atau `routes/*` (`php artisan optimize:clear`).

## Storage & dokumen

Dokumen disimpan di disk `private` (sesuai `config/filesystems.php`, key `upload.disk`). Di produksi titik ke
`storage_path('app/private')` (atau penyimpanan objek S3 bila `upload.disk=s3`). Simbolik link:
`php artisan storage:link`. Dokumen hanya diunduh **satu per satu** lewat endpoint ber-token; tidak ada unduhan massal/zip.

## Backup

- Database: `mysqldump -u root etlht > backup_$(date +%F).sql` (atau fitur backup Laragon).
- Dokumen: salin folder `backend/storage/app/private`.
- Scheduler (Task Scheduler Windows) men-trigger `php artisan schedule:run` tiap menit sebagai prasyarat pengingat otomatis.