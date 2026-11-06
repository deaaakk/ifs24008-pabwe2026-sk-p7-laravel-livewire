<?php

/**
 * Menyesuaikan proyek Laravel 13 baru agar cocok dengan kode overlay ini.
 * Jalankan SEKALI dari root proyek:  php scripts/apply-overlay.php
 * (aman dijalankan ulang / idempotent)
 */
$root = dirname(__DIR__);
chdir($root);

// 1. config/app.php -> tambah 'force_https'
$appConfig = 'config/app.php';
if (is_file($appConfig)) {
    $code = file_get_contents($appConfig);
    if (! str_contains($code, "'force_https'")) {
        $patched = preg_replace(
            "/(\s*'url'\s*=>\s*env\('APP_URL'[^\n]*\n)/",
            "$1\n    'force_https' => (bool) env('FORCE_HTTPS', false),\n",
            $code,
            1,
            $count
        );
        if ($count === 1) {
            file_put_contents($appConfig, $patched);
            echo "[ok] config/app.php: force_https ditambahkan\n";
        } else {
            echo "[!!] config/app.php: baris 'url' tidak ditemukan. Tambahkan manual:\n"
                ."     'force_https' => (bool) env('FORCE_HTTPS', false),\n";
        }
    } else {
        echo "[--] config/app.php: force_https sudah ada\n";
    }
}

// 2. composer.json -> script test
$composerFile = 'composer.json';
if (is_file($composerFile)) {
    $json = json_decode(file_get_contents($composerFile), true);
    $json['scripts']['test'] = [
        '@php artisan config:clear --ansi',
        '@php -d xdebug.mode=coverage vendor/bin/phpunit --coverage-text',
    ];
    file_put_contents(
        $composerFile,
        json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL
    );
    echo "[ok] composer.json: script test diperbarui\n";
}

// 3. .env.example -> DB & variabel baru
$envFile = '.env.example';
if (is_file($envFile)) {
    $env = file_get_contents($envFile);
    $env = preg_replace('/^#\s*(DB_(HOST|PORT|DATABASE|USERNAME|PASSWORD)=)/m', '$1', $env);
    $env = preg_replace('/^DB_DATABASE=.*/m', 'DB_DATABASE=pabwe_p7_stock', $env);
    $env = preg_replace('/^APP_NAME=.*/m', 'APP_NAME="Stock Barang"', $env);

    foreach ([
        'FORCE_HTTPS' => 'false',
        'OPENAI_API_KEY' => '',
        'OPENAI_MODEL' => 'gpt-4o-mini',
        'OPENAI_BASE_URL' => 'https://api.openai.com/v1',
    ] as $key => $value) {
        if (! preg_match('/^'.$key.'=/m', $env)) {
            $env = rtrim($env).PHP_EOL.$key.'='.$value.PHP_EOL;
        }
    }
    file_put_contents($envFile, $env);
    echo "[ok] .env.example diperbarui (DB_DATABASE=pabwe_p7_stock, FORCE_HTTPS, OPENAI_*)\n";
}

echo "\nSelesai. Langkah berikutnya: salin .env.example ke .env, buat database, lalu php artisan migrate:fresh --seed\n";
