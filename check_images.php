<?php
/**
 * VALIDASI GAMBAR COURSE & BOOTCAMP
 *
 * Alur pengecekan:
 *   DATABASE PATH -> NORMALISASI PATH -> FILE EXISTS -> IMAGE URL -> <img>
 *
 * Jalankan:
 *   php check_images.php
 *   ATAU akses:
 *   http://localhost/project-digital-entrepreneurship-fixed/check_images.php
 */

require_once __DIR__ . '/config/koneksi.php';

header('Content-Type: text/plain; charset=utf-8');

function normalizeImagePath($path)
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^(https?:)?//#i', $path) || strpos($path, 'data:') === 0) {
        return $path;
    }

    $path = ltrim($path, '/');
    $path = preg_replace('#^(\.\./)+#', '', $path);

    if (strpos($path, 'course/assets/images/bootcamp/') === 0) {
        return 'assets/bootcamp/' . substr($path, strlen('course/assets/images/bootcamp/'));
    }
    if (strpos($path, 'assets/course/bootcamp/') === 0) {
        return 'assets/bootcamp/' . substr($path, strlen('assets/course/bootcamp/'));
    }
    if (strpos($path, 'assets/course/') === 0) {
        return 'assets/elearning/' . substr($path, strlen('assets/course/'));
    }

    return $path;
}

$rows = array();

$result = mysqli_query($conn, "SELECT id, nama_produk, gambar FROM products ORDER BY id");
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = array('tipe' => 'E-Learning', 'id' => $row['id'], 'nama' => $row['nama_produk'], 'gambar' => $row['gambar']);
}

$result = mysqli_query($conn, "SELECT id, judul, gambar FROM bootcamp ORDER BY id");
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = array('tipe' => 'Bootcamp', 'id' => $row['id'], 'nama' => $row['judul'], 'gambar' => $row['gambar']);
}

$total = count($rows);
$ok = 0;
$fail = 0;

echo "=== VALIDASI GAMBAR BELAJARYUK ===\n";
echo "Total data : {$total}\n\n";

foreach ($rows as $row) {
    $normalized = normalizeImagePath($row['gambar']);
    $file = __DIR__ . '/' . $normalized;
    $exists = ($normalized !== '' && is_file($file));
    $size = $exists ? filesize($file) : 0;
    $isPng = false;

    if ($exists) {
        $fh = fopen($file, 'rb');
        $head = fread($fh, 8);
        fclose($fh);
        $isPng = (substr($head, 0, 4) === "\x89PNG");
    }

    $status = ($exists && $isPng && $size > 0) ? 'OK  ' : 'FAIL';
    $status === 'OK  ' ? $ok++ : $fail++;

    printf(
        "%s | %-11s | id=%-3s | %-40s | %s%s\n",
        $status,
        $row['tipe'],
        $row['id'],
        mb_substr($row['nama'], 0, 40),
        $normalized,
        $exists ? '' : '  (FILE TIDAK ADA)'
    );
}

echo "\n=== RINGKASAN ===\n";
echo "OK   : {$ok}\n";
echo "FAIL : {$fail}\n";
echo $fail === 0 ? "SEMUA GAMBAR VALID\n" : "ADA GAMBAR BERMASALAH\n";
