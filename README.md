# Taqreer

Sistem laporan bulanan santri: jurnal tahfidz, input modul penilaian (tahfidz, kesantrian, akademik, administrasi), rapor PDF, portal wali murid, dan analitik perkembangan.

Dibangun dengan Laravel 13 (PHP 8.5), Tailwind CSS v4, Alpine.js, Chart.js, dan dompdf.

## Menjalankan secara lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed   # mengisi data contoh; semua akun contoh berpassword "password"
composer run dev             # server Laravel + Vite
```

Tes dan format kode:

```bash
php artisan test --compact
vendor/bin/pint
```

## Production

| Komponen | Layanan (paket gratis) |
|---|---|
| Aplikasi | Vercel, runtime `vercel-php` (region Singapura) |
| Database | Supabase Postgres, lewat pooler |
| File upload (logo, stempel, tanda tangan) | Supabase Storage, bucket publik `taqreer` |
| CI/CD, backup, pemantauan | GitHub Actions |

Proyek Vercel **tidak** terhubung ke Git. Satu-satunya jalur deploy adalah workflow GitHub Actions, supaya tes dan migrasi selalu berjalan lebih dulu.

### Workflow GitHub Actions

| Workflow | Kapan | Isi |
|---|---|---|
| `deploy.yml` | Setiap push dan PR | Pint, tes di Postgres. Pada push ke `main`: migrasi Supabase, lalu deploy ke Vercel |
| `backup.yml` | Setiap hari 01:00 WIB | `pg_dump` skema `public` + isi bucket, dienkripsi AES-256, disimpan sebagai artifact 30 hari, lalu diverifikasi bisa didekripsi |
| `maintenance.yml` | Setiap hari 02:00 WIB | `model:prune` (log audit lama); sekaligus menjaga proyek Supabase tetap aktif |
| `uptime.yml` | Setiap 30 menit | Cek `/up`; membuka issue saat situs mati dan menutupnya saat pulih |

Workflow deploy, backup, dan maintenance hanya berjalan jika repository variable `DEPLOY_ENABLED` bernilai `true`.

### Konfigurasi

Repository variables: `DEPLOY_ENABLED`, `PRODUCTION_URL`.

Repository secrets:

| Secret | Keterangan |
|---|---|
| `VERCEL_TOKEN`, `VERCEL_ORG_ID`, `VERCEL_PROJECT_ID` | Deploy ke Vercel |
| `DATABASE_MIGRATION_URL` | URL Supabase **session pooler** (port 5432), untuk migrasi dan backup |
| `AWS_ENDPOINT`, `AWS_DEFAULT_REGION`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET` | Supabase Storage (S3), untuk backup file |
| `BACKUP_PASSPHRASE` | Kunci enkripsi backup. Simpan salinannya di password manager; secret GitHub tidak bisa dibaca ulang |

Environment variables di Vercel: `APP_NAME`, `APP_KEY`, `APP_URL`, `DB_URL` (Supabase **transaction pooler**, port 6543), `AWS_*`, dan `AWS_URL` (`https://<ref>.supabase.co/storage/v1/object/public/taqreer`). Nilai non-rahasia lainnya ada di [`api/index.php`](api/index.php).

### Membuat akun Super Admin

Seeder data contoh tidak pernah dijalankan di production. Akun pertama dibuat dari laptop:

```bash
DB_CONNECTION=pgsql DB_URL="<session pooler URL>" DB_SSLMODE=require CACHE_STORE=array \
  php artisan app:create-super-admin <username> --name="Nama Lengkap"
```

### Memulihkan backup

1. Unduh artifact dari halaman run **Daily Backup** di tab Actions.
2. Dekripsi dan ekstrak:
   ```bash
   gpg --decrypt taqreer-backup-YYYYMMDD-HHMM.tar.gz.gpg | tar -xz
   ```
3. Pulihkan database (hati-hati: menimpa data yang ada):
   ```bash
   pg_restore --clean --if-exists --no-owner --dbname "<session pooler URL>" database.dump
   ```
4. Unggah ulang isi folder `uploads/` ke bucket `taqreer` bila diperlukan.

### Batasan yang perlu diketahui

- Runtime PHP di Vercel tidak memiliki ekstensi GD, jadi dompdf hanya bisa menyematkan gambar JPEG. Form Profil Lembaga mengubah PNG/WebP menjadi JPEG di browser sebelum diunggah.
- Satu request dibatasi 60 detik (termasuk ekspor PDF massal).
- Guru, wali kelas, dan kesantrian hanya dapat melihat dan mengubah data santri di kelas yang ditugaskan kepadanya.
