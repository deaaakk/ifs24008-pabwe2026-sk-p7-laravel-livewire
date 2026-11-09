# Stock Barang — Laravel 13 + Livewire 4 (Feature-First)

Studi kasus modul P7 (PABWE 2026). Paket ini adalah **overlay**: berisi semua file kode
yang harus ditimpa/ditambahkan ke proyek Laravel 13 baru.

## Pasang (ringkas)

```bash
laravel new ifs24008-pabwe2026-sk-p7-laravel-livewire --database=mysql --phpunit --no-node --no-boost --no-interaction
cd ifs24008-pabwe2026-sk-p7-laravel-livewire
composer require livewire/livewire phpoffice/phpspreadsheet

# salin isi paket ini ke root proyek (timpa file yang bentrok), lalu:
php scripts/apply-overlay.php
cp .env.example .env && php artisan key:generate

mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS pabwe_p7_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Akun seeder: `admin` / `admin` dan `member` / `member`.

## Uji

```bash
composer test     # butuh Xdebug (xdebug.mode=coverage) atau PCOV untuk laporan coverage
composer audit
```

## Struktur

Lihat bagian 3.4 modul. Kode: `app/Features/{Admin,Ai,Auth,Profile,Stock}`,
middleware `role` dan `owns.stock` di `bootstrap/app.php`, rate limiter `1000-per-second`
di `AppServiceProvider`, CI di `Jenkinsfile` (gate audit + coverage 100% via `scripts/check-coverage.php`).
