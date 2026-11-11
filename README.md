# Toko Parfum

Toko parfum online: Laravel 12, Livewire 3, Filament 3 (`/admin`), MySQL 8. Spesifikasi ada di [docs/PRD.md](docs/PRD.md), aturan kerja di [CLAUDE.md](CLAUDE.md), dan rencana per fase di [docs/plans](docs/plans).

## Menjalankan di lokal

Kebutuhan: PHP 8.2+ (ekstensi intl, pdo_mysql, gd, zip, mbstring, fileinfo), Composer, Node 20+, MySQL 8.

```bash
composer install
npm install && npm run build
cp .env.example .env   # isi DB_*, lalu:
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan app:create-admin
php artisan serve
```

- **Customer uji dari seeder:** `customer@tokoparfum.test` / `password`.
- **Admin:** buat lewat `php artisan app:create-admin`. Seeder sengaja tidak membuat admin.
- **Ongkir tanpa API key:** set `RAJAONGKIR_FAKE=true`. Ini hanya berlaku di `APP_ENV=local` dan memakai data wilayah serta tarif contoh.
- **Email:** di lokal `MAIL_MAILER=log`, jadi isi email tertulis di `storage/logs/laravel.log`. Email dikirim lewat queue, jadi jalankan `php artisan queue:work` atau `php artisan schedule:work`.

## Test

```bash
php artisan test
./vendor/bin/pint --test
```

Test memakai database MySQL `parfum_test` (lihat `phpunit.xml`).

## Produksi (shared hosting)

- Cukup satu cron: `* * * * * cd /path/app && php artisan schedule:run >> /dev/null 2>&1`. Scheduler membatalkan order yang lewat 24 jam (`orders:expire`) dan memproses queue email (`queue:work --stop-when-empty`).
- Isi `RAJAONGKIR_API_KEY`, lalu `RAJAONGKIR_ORIGIN` dengan ID kecamatan gudang. Pastikan `RAJAONGKIR_FAKE=false`.
- `APP_ENV=production`, `APP_DEBUG=false`, dan HTTPS wajib.

## Status fitur

Semua 14 fitur PRD sudah dikerjakan **kecuali pembayaran Midtrans (fitur 8)**, yang ditunda atas permintaan pemilik. Order dibuat dengan status Menunggu Bayar dan dibatalkan otomatis setelah 24 jam. `OrderService::markPaid()` sudah siap dipanggil dari webhook Midtrans. Langkah-langkah yang tersisa ada di [docs/plans/fase-4.md](docs/plans/fase-4.md).
