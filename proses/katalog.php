<?php

/* =========================================================
   KATALOG PUBLIK — Frontend User BelajarYuk
   ---------------------------------------------------------
   Endpoint baca publik (TANPA login) untuk:
   - daftar & detail E-Learning (tabel products)
   - daftar & detail Bootcamp (tabel bootcamp)
   - kategori (products.subkategori)
   - instruktur (bootcamp.mentor)
   - statistik (users / products / bootcamp / transaksi)

   Semua query memakai PREPARED STATEMENT.
   Admin API (admin-php/*) tidak disentuh.
   ========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../config/koneksi.php";

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

function respond($success, $message, $data = null, $extra = [])
{
    $payload = ["success" => $success, "message" => $message];
    if ($data !== null) {
        $payload["data"] = $data;
    }
    foreach ($extra as $key => $value) {
        $payload[$key] = $value;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$type = isset($_GET["type"]) ? trim((string) $_GET["type"]) : "elearning";
$id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
$q = isset($_GET["q"]) ? trim((string) $_GET["q"]) : "";
$kategori = isset($_GET["kategori"]) ? trim((string) $_GET["kategori"]) : "";
$sort = isset($_GET["sort"]) ? trim((string) $_GET["sort"]) : "terbaru";
$limit = isset($_GET["limit"]) ? intval($_GET["limit"]) : 0;
$page = isset($_GET["page"]) ? max(1, intval($_GET["page"])) : 1;
$minHarga = isset($_GET["min_harga"]) && $_GET["min_harga"] !== "" ? floatval($_GET["min_harga"]) : null;
$maxHarga = isset($_GET["max_harga"]) && $_GET["max_harga"] !== "" ? floatval($_GET["max_harga"]) : null;

/* ---------------------------------------------------------
   Helper: eksekusi prepared statement
--------------------------------------------------------- */
function runPrepared($conn, $sql, $types, $values)
{
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        respond(false, "Query gagal: " . mysqli_error($conn));
    }
    if ($types !== "" && $values) {
        mysqli_stmt_bind_param($stmt, $types, ...$values);
    }
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        respond(false, "Query gagal: " . mysqli_error($conn));
    }
    return mysqli_stmt_get_result($stmt);
}

function parseBenefitList($benefit)
{
    if ($benefit === null || trim((string) $benefit) === "") {
        return [];
    }
    $parts = preg_split('/\||\r?\n/', (string) $benefit);
    $items = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== "") {
            $items[] = $part;
        }
    }
    return $items;
}

/* =========================================================
   TYPE: KATEGORI
   Sumber: products.subkategori (E-Learning) + bootcamp.kategori
========================================================= */
if ($type === "kategori") {
    $kategoriElearning = [];
    $result = runPrepared(
        $conn,
        "SELECT subkategori AS nama, COUNT(*) AS jumlah
         FROM products
         WHERE status = 'active' AND subkategori IS NOT NULL AND subkategori <> ''
         GROUP BY subkategori
         ORDER BY jumlah DESC, subkategori ASC",
        "",
        []
    );
    while ($row = mysqli_fetch_assoc($result)) {
        $row["tipe"] = "elearning";
        $kategoriElearning[] = $row;
    }

    $kategoriBootcamp = [];
    $result = runPrepared(
        $conn,
        "SELECT kategori AS nama, COUNT(*) AS jumlah
         FROM bootcamp
         WHERE kategori IS NOT NULL AND kategori <> ''
         GROUP BY kategori
         ORDER BY jumlah DESC, kategori ASC",
        "",
        []
    );
    while ($row = mysqli_fetch_assoc($result)) {
        $row["tipe"] = "bootcamp";
        $kategoriBootcamp[] = $row;
    }

    respond(true, "Kategori berhasil diambil.", [
        "elearning" => $kategoriElearning,
        "bootcamp" => $kategoriBootcamp
    ]);
}

/* =========================================================
   TYPE: INSTRUKTUR
   Sumber: bootcamp.mentor (satu-satunya data mentor di DB)
========================================================= */
if ($type === "instruktur") {
    $result = runPrepared(
        $conn,
        "SELECT mentor,
                COUNT(*) AS jumlah_bootcamp,
                GROUP_CONCAT(DISTINCT kategori ORDER BY kategori SEPARATOR ', ') AS keahlian,
                SUM(kuota) AS total_kuota
         FROM bootcamp
         WHERE mentor IS NOT NULL AND mentor <> ''
         GROUP BY mentor
         ORDER BY jumlah_bootcamp DESC, mentor ASC",
        "",
        []
    );

    $instruktur = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $instruktur[] = [
            "nama" => $row["mentor"],
            "keahlian" => $row["keahlian"],
            "jumlah_bootcamp" => intval($row["jumlah_bootcamp"]),
            "total_kuota" => intval($row["total_kuota"]),
            "inisial" => strtoupper(preg_replace('/[^A-Za-z ]/', "", substr($row["mentor"], 0, 1)))
        ];
    }

    respond(true, "Instruktur berhasil diambil.", $instruktur);
}

/* =========================================================
   TYPE: STATISTIK
   Semua angka dihitung langsung dari tabel existing.
========================================================= */
if ($type === "statistik") {
    $result = runPrepared($conn, "SELECT COUNT(*) AS jumlah FROM users", "", []);
    $pengguna = intval(mysqli_fetch_assoc($result)["jumlah"]);

    $result = runPrepared(
        $conn,
        "SELECT COUNT(*) AS jumlah FROM products WHERE kategori = 'E-Learning' AND status = 'active'",
        "",
        []
    );
    $kelas = intval(mysqli_fetch_assoc($result)["jumlah"]);

    $result = runPrepared($conn, "SELECT COUNT(*) AS jumlah FROM bootcamp", "", []);
    $bootcamp = intval(mysqli_fetch_assoc($result)["jumlah"]);

    $result = runPrepared(
        $conn,
        "SELECT COUNT(*) AS jumlah FROM transaksi WHERE transaction_status IN ('settlement','capture')",
        "",
        []
    );
    $transaksiBerhasil = intval(mysqli_fetch_assoc($result)["jumlah"]);

    $result = runPrepared(
        $conn,
        "SELECT COUNT(DISTINCT user_id) AS jumlah FROM transaksi
         WHERE transaction_status IN ('settlement','capture') AND user_id IS NOT NULL",
        "",
        []
    );
    $peserta = intval(mysqli_fetch_assoc($result)["jumlah"]);

    respond(true, "Statistik berhasil diambil.", [
        "pengguna" => $pengguna,
        "kelas" => $kelas,
        "bootcamp" => $bootcamp,
        "kelas_dan_bootcamp" => $kelas + $bootcamp,
        "transaksi_berhasil" => $transaksiBerhasil,
        "peserta_aktif" => $peserta
    ]);
}

/* =========================================================
   TYPE: E-LEARNING (list / detail)
========================================================= */
if ($type === "elearning") {

    $select = "SELECT p.id, p.nama_produk, p.kategori, p.subkategori, p.deskripsi, p.harga,
                      p.harga_promo, p.promo_aktif, p.durasi, p.jadwal, p.benefit, p.gambar,
                      p.status, p.created_at,
                      COALESCE(t.peserta, 0) AS peserta,
                      COALESCE(t.transaksi, 0) AS transaksi
               FROM products p
               LEFT JOIN (
                   SELECT produk_id,
                          COUNT(DISTINCT CASE WHEN transaction_status IN ('settlement','capture')
                                              AND user_id IS NOT NULL THEN user_id END) AS peserta,
                          COUNT(*) AS transaksi
                   FROM transaksi
                   WHERE jenis_produk = 'elearning'
                   GROUP BY produk_id
               ) t ON t.produk_id = p.id";

    /* --- DETAIL --- */
    if ($id > 0) {
        $result = runPrepared(
            $conn,
            $select . " WHERE p.id = ? LIMIT 1",
            "i",
            [$id]
        );

        if (!$result || mysqli_num_rows($result) === 0) {
            respond(false, "Course tidak ditemukan.");
        }

        $course = mysqli_fetch_assoc($result);
        $course["benefit_list"] = parseBenefitList($course["benefit"]);
        respond(true, "Course berhasil diambil.", $course);
    }

    /* --- DAFTAR + FILTER + SEARCH + SORT --- */
    $types = "";
    $conditions = ["p.kategori = 'E-Learning'", "p.status = 'active'"];
    $values = [];

    if ($q !== "") {
        $conditions[] = "(p.nama_produk LIKE ? OR p.deskripsi LIKE ? OR p.subkategori LIKE ?
                          OR p.kategori LIKE ? OR p.benefit LIKE ? OR p.jadwal LIKE ?)";
        $like = "%" . $q . "%";
        $values = array_merge($values, [$like, $like, $like, $like, $like, $like]);
        $types .= str_repeat("s", 6);
    }

    if ($kategori !== "") {
        $conditions[] = "p.subkategori = ?";
        $values[] = $kategori;
        $types .= "s";
    }

    if ($minHarga !== null) {
        $conditions[] = "COALESCE(NULLIF(p.harga_promo,0), p.harga) >= ?";
        $values[] = $minHarga;
        $types .= "d";
    }

    if ($maxHarga !== null) {
        $conditions[] = "COALESCE(NULLIF(p.harga_promo,0), p.harga) <= ?";
        $values[] = $maxHarga;
        $types .= "d";
    }

    switch ($sort) {
        case "harga_asc":
            $orderBy = "ORDER BY COALESCE(NULLIF(p.harga_promo,0), p.harga) ASC";
            break;
        case "harga_desc":
            $orderBy = "ORDER BY COALESCE(NULLIF(p.harga_promo,0), p.harga) DESC";
            break;
        case "terpopuler":
            $orderBy = "ORDER BY COALESCE(t.peserta, 0) DESC, p.id DESC";
            break;
        case "termurah":
            $orderBy = "ORDER BY p.harga ASC";
            break;
        default:
            $orderBy = "ORDER BY p.id DESC";
    }

    $sql = $select . " WHERE " . implode(" AND ", $conditions) . " " . $orderBy;
    if ($limit > 0) {
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    }

    $result = runPrepared($conn, $sql, $types, $values);

    $courses = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row["benefit_list"] = parseBenefitList($row["benefit"]);
        $courses[] = $row;
    }

    respond(true, "Data course berhasil diambil.", $courses, ["total" => count($courses)]);
}

/* =========================================================
   TYPE: BOOTCAMP (list / detail)
========================================================= */
if ($type === "bootcamp") {

    $select = "SELECT b.id, b.judul, b.kategori, b.mentor, b.harga, b.harga_promo, b.promo_aktif,
                      b.tanggal_mulai, b.tanggal_berakhir, b.kuota, b.gambar, b.benefit,
                      b.deskripsi, b.created_at,
                      COALESCE(t.peserta, 0) AS peserta
               FROM bootcamp b
               LEFT JOIN (
                   SELECT produk_id,
                          COUNT(DISTINCT CASE WHEN transaction_status IN ('settlement','capture')
                                              AND user_id IS NOT NULL THEN user_id END) AS peserta
                   FROM transaksi
                   WHERE jenis_produk = 'bootcamp'
                   GROUP BY produk_id
               ) t ON t.produk_id = b.id";

    /* --- DETAIL --- */
    if ($id > 0) {
        $result = runPrepared(
            $conn,
            $select . " WHERE b.id = ? LIMIT 1",
            "i",
            [$id]
        );

        if (!$result || mysqli_num_rows($result) === 0) {
            respond(false, "Bootcamp tidak ditemukan.");
        }

        $bootcamp = mysqli_fetch_assoc($result);
        $bootcamp["benefit_list"] = parseBenefitList($bootcamp["benefit"]);
        respond(true, "Bootcamp berhasil diambil.", $bootcamp);
    }

    /* --- DAFTAR + FILTER + SEARCH + SORT --- */
    $conditions = ["1 = 1"];
    $types = "";
    $values = [];

    if ($q !== "") {
        $conditions[] = "(b.judul LIKE ? OR b.deskripsi LIKE ? OR b.kategori LIKE ?
                          OR b.mentor LIKE ? OR b.benefit LIKE ?)";
        $like = "%" . $q . "%";
        $values = array_merge($values, [$like, $like, $like, $like, $like]);
        $types .= str_repeat("s", 5);
    }

    if ($kategori !== "") {
        $conditions[] = "b.kategori = ?";
        $values[] = $kategori;
        $types .= "s";
    }

    if ($minHarga !== null) {
        $conditions[] = "COALESCE(NULLIF(b.harga_promo,0), b.harga) >= ?";
        $values[] = $minHarga;
        $types .= "d";
    }

    if ($maxHarga !== null) {
        $conditions[] = "COALESCE(NULLIF(b.harga_promo,0), b.harga) <= ?";
        $values[] = $maxHarga;
        $types .= "d";
    }

    switch ($sort) {
        case "harga_asc":
            $orderBy = "ORDER BY COALESCE(NULLIF(b.harga_promo,0), b.harga) ASC";
            break;
        case "harga_desc":
            $orderBy = "ORDER BY COALESCE(NULLIF(b.harga_promo,0), b.harga) DESC";
            break;
        case "terpopuler":
            $orderBy = "ORDER BY COALESCE(t.peserta, 0) DESC, b.id DESC";
            break;
        case "terdekat":
            $orderBy = "ORDER BY b.tanggal_mulai ASC";
            break;
        default:
            $orderBy = "ORDER BY b.id DESC";
    }

    $sql = $select . " WHERE " . implode(" AND ", $conditions) . " " . $orderBy;
    if ($limit > 0) {
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    }

    $result = runPrepared($conn, $sql, $types, $values);

    $bootcamps = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row["benefit_list"] = parseBenefitList($row["benefit"]);
        $bootcamps[] = $row;
    }

    respond(true, "Data bootcamp berhasil diambil.", $bootcamps, ["total" => count($bootcamps)]);
}

respond(false, "Type request tidak dikenali.");
