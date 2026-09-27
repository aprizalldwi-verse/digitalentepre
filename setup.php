<?php
/**
 * SETUP.PHP - Script Setup Akun Admin & User
 * 
 * Jalankan script ini SETelah import database SQL:
 *   php setup.php
 * Atau akses via browser:
 *   http://localhost/project-digital-entrepreneurship-fixed/setup.php
 * 
 * Script ini membuat akun:
 *   Admin: admin@belajaryuk.com / Admin123!
 *   User:  user@belajaryuk.com  / User123!
 */

require_once __DIR__ . '/config/koneksi.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== SETUP AKUN BELAJARYUK ===\n\n";

// -----------------------------------------------
// Data akun yang akan dibuat
// -----------------------------------------------
$accounts = [
    [
        'nama'   => 'Admin BelajarYuk',
        'email'  => 'admin@belajaryuk.com',
        'pass'   => 'Admin123!',
        'role'   => 'admin',
    ],
    [
        'nama'   => 'User BelajarYuk',
        'email'  => 'user@belajaryuk.com',
        'pass'   => 'User123!',
        'role'   => 'user',
    ],
];

$successCount = 0;
$errorCount   = 0;

foreach ($accounts as $acct) {
    // Cek apakah email sudah ada
    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($check, "s", $acct['email']);
    mysqli_stmt_execute($check);
    $result = mysqli_stmt_get_result($check);

    if (mysqli_num_rows($result) > 0) {
        echo "[SKIP] {$acct['email']} sudah ada di database.\n";
        mysqli_stmt_close($check);
        continue;
    }
    mysqli_stmt_close($check);

    // Hash password menggunakan PHP password_hash()
    $hashedPassword = password_hash($acct['pass'], PASSWORD_BCRYPT);

    // Insert user baru
    $insert = mysqli_prepare($conn, 
        "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)"
    );
    mysqli_stmt_bind_param($insert, "ssss", $acct['nama'], $acct['email'], $hashedPassword, $acct['role']);

    if (mysqli_stmt_execute($insert)) {
        echo "[OK]  Akun {$acct['role']} berhasil dibuat: {$acct['email']}\n";
        $successCount++;
    } else {
        echo "[ERR] Gagal membuat {$acct['email']}: " . mysqli_error($conn) . "\n";
        $errorCount++;
    }
    mysqli_stmt_close($insert);
}

echo "\n=== RINGKASAN ===\n";
echo "Berhasil : {$successCount}\n";
echo "Gagal    : {$errorCount}\n";
echo "Dilewati : " . (2 - $successCount - $errorCount) . "\n";
echo "\n=== LOGIN INFO ===\n";
echo "Admin  -> admin@belajaryuk.com / Admin123!\n";
echo "User   -> user@belajaryuk.com  / User123!\n";
echo "\n=============================\n";
echo "Setup selesai! Anda bisa menghapus file ini.\n";
