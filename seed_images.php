<?php
/**
 * SEED IMAGES / THUMBNAIL GENERATOR
 *
 * File ini sebelumnya menulis konten SVG ke dalam file berekstensi .png.
 * Karena browser menolak gambar SVG yang dikirim sebagai image/png,
 * card course/bootcamp tampil "Gambar tidak tersedia".
 *
 * Sekarang file ini hanya menjadi wrapper dari generate_thumbnails.js
 * yang merender thumbnail menjadi PNG asli (800x450).
 *
 * Cara pakai:
 *   php seed_images.php
 *   ATAU
 *   node generate_thumbnails.js
 *
 * Output:
 *   assets/elearning/*.png  (thumbnail course)
 *   assets/bootcamp/*.png   (thumbnail bootcamp)
 *   assets/thumbnails.json  (judul, badge, tema untuk render ulang)
 */

$root = __DIR__;
$script = $root . '/generate_thumbnails.js';

echo "=== THUMBNAIL GENERATOR BELAJARYUK ===\n\n";

if (!file_exists($script)) {
    echo "[ERROR] generate_thumbnails.js tidak ditemukan.\n";
    exit(1);
}

$node = null;
$candidates = array('node');

$paths = explode(PATH_SEPARATOR, (string) getenv('PATH'));
foreach ($paths as $dir) {
    if ($dir === '') continue;
    $candidates[] = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . 'node.exe';
    $candidates[] = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . 'node';
}

foreach ($candidates as $bin) {
    $probe = (strpos($bin, DIRECTORY_SEPARATOR) !== false)
        ? 'where "' . $bin . '"'
        : 'where ' . $bin;
    $out = array();
    $code = 0;
    @exec($probe . ' 2>NUL', $out, $code);
    if ($code === 0 && !empty($out)) {
        $node = $bin;
        break;
    }
}

if ($node === null) {
    echo "[ERROR] Node.js tidak ditemukan di PATH.\n";
    echo "        Jalankan manual: node generate_thumbnails.js\n";
    exit(1);
}

echo "[OK] Node: {$node}\n";
echo "[OK] Script: generate_thumbnails.js\n\n";

$command = escapeshellarg($node) . ' ' . escapeshellarg($script);
passthru($command, $exitCode);

if ($exitCode !== 0) {
    echo "\n[ERROR] Generator gagal (exit code {$exitCode}).\n";
    exit($exitCode);
}

echo "\n[OK] Thumbnail PNG tersimpan di assets/elearning/ dan assets/bootcamp/\n";
echo "=== SEED IMAGES SELESAI ===\n";
