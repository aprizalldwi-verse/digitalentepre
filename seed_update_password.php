<?php
/**
 * Seed Update Password Script
 * 
 * Script ini mengupdate semua password user dummy (selain admin)
 * menjadi bcrypt hash dari "User123!"
 * 
 * Jalankan SETELAH import seed_dummy.sql:
 * php seed_update_password.php
 */

require_once 'config/koneksi.php';

echo "=== Update User Passwords ===\n\n";

$password = 'User123!';
$hash = password_hash($password, PASSWORD_BCRYPT);

// Update semua user dengan role 'user'
// Password placeholder '$2y$placeholder' tidak valid, jadi update semua
$sql = "UPDATE users SET password = ? WHERE role = 'user'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hash);
$result = $stmt->execute();

if ($result) {
    $affected = $stmt->affected_rows;
    echo "✓ Berhasil update password untuk $affected user\n";
    echo "  Password: $password\n";
    echo "  Hash: $hash\n\n";
} else {
    echo "✗ Gagal update password: " . $conn->error . "\n\n";
}

$stmt->close();

// Verifikasi
echo "=== Verifikasi User ===\n";
$result = $conn->query("SELECT id, nama, email, role, LEFT(password, 20) as pass_prefix FROM users ORDER BY id");
while ($row = $result->fetch_assoc()) {
    echo sprintf("  ID: %d | %-25s | %-30s | %-6s | %s...\n", 
        $row['id'], $row['nama'], $row['email'], $row['role'], $row['pass_prefix']);
}

echo "\n=== Selesai ===\n";
echo "Semua user dummy sekarang bisa login dengan password: $password\n";

$conn->close();
?>