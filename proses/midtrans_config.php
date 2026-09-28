<?php

/*
|--------------------------------------------------------------------------
   LOAD .env FILE (if exists) — tanpa dependency luar
|--------------------------------------------------------------------------
*/
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        if (!getenv($key)) {
            putenv("$key=$val");
        }
    }
}

/*
|--------------------------------------------------------------------------
| MIDTRANS KEYS
|--------------------------------------------------------------------------
| Key sumber prioritas:
|   1. Environment variable : MIDTRANS_SERVER_KEY / MIDTRANS_CLIENT_KEY
|   2. File lokal           : proses/midtrans_keys.php  (tidak ikut di-commit)
|
| Contoh isi proses/midtrans_keys.php:
|   <?php
|   return [
|       'server_key' => 'Mid-server-xxxxxxxx',
|       'client_key' => 'Mid-client-xxxxxxxx',
|   ];
|
| Untuk production, ganti API URL di bawah ke:
|   https://app.midtrans.com/snap/v1/transactions
| https://dashboard.midtrans.com/
*/

$midtransKeys = [];
$midtransKeysFile = __DIR__ . '/midtrans_keys.php';

if (is_file($midtransKeysFile)) {
    $loadedKeys = include $midtransKeysFile;

    if (is_array($loadedKeys)) {
        $midtransKeys = $loadedKeys;
    }
}

define(
    'MIDTRANS_SERVER_KEY',
    getenv('MIDTRANS_SERVER_KEY')
        ?: (isset($midtransKeys['server_key']) ? $midtransKeys['server_key'] : '')
);

define(
    'MIDTRANS_CLIENT_KEY',
    getenv('MIDTRANS_CLIENT_KEY')
        ?: (isset($midtransKeys['client_key']) ? $midtransKeys['client_key'] : '')
);


/*
|--------------------------------------------------------------------------
| SANDBOX API URL
|--------------------------------------------------------------------------
| For production, change to: https://app.midtrans.com/snap/v1/transactions
*/

define(
    'MIDTRANS_API_URL',
    'https://app.sandbox.midtrans.com/snap/v1/transactions'
);


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
| Sesuaikan dengan kredensial database pada server tujuan.
*/

define(
    'DB_HOST',
    getenv('DB_HOST') ?: 'localhost'
);

define(
    'DB_USER',
    getenv('DB_USER') ?: 'root'
);

define(
    'DB_PASS',
    getenv('DB_PASS') ?: ''
);

define(
    'DB_NAME',
    getenv('DB_NAME') ?: 'db_belajaryuk'
);


/*
|--------------------------------------------------------------------------
| CONNECTION
|--------------------------------------------------------------------------
*/

$conn = new mysqli(
    DB_HOST,
    DB_USER,
    DB_PASS,
    DB_NAME
);

if ($conn->connect_error) {

    die(
        'Koneksi database gagal: ' .
        $conn->connect_error
    );

}

$conn->set_charset('utf8mb4');
