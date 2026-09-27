<?php

/* =========================================================
   PROFIL USER — Frontend User BelajarYuk
   ---------------------------------------------------------
   GET  : ambil profil user yang sedang login
   POST : simpan perubahan profil (nama, email, password)

   - Tabel yang dipakai: users (existing)
   - Prepared statement + validasi input + sanitasi output
   - Tidak membuat tabel / database baru
   ========================================================= */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . "/../config/koneksi.php";

header("Content-Type: application/json; charset=UTF-8");

function respond($success, $message, $data = null)
{
    $payload = ["success" => $success, "message" => $message];
    if ($data !== null) {
        $payload["data"] = $data;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = intval($_SESSION["user_id"] ?? 0);

if ($userId <= 0 || empty($_SESSION["role"])) {
    http_response_code(401);
    respond(false, "Session login tidak ditemukan. Silakan login kembali.");
}

/* ---------------------------------------------------------
   Ambil data profil
--------------------------------------------------------- */
function fetchProfile($conn, $userId)
{
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, nama, email, role, status, created_at
         FROM users WHERE id = ? LIMIT 1"
    );
    if (!$stmt) {
        respond(false, "Query profil gagal: " . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) === 0) {
        respond(false, "Data user tidak ditemukan.");
    }

    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $user;
}

/* =========================================================
   GET — tampilkan profil
========================================================= */
if (($_SERVER["REQUEST_METHOD"] ?? "GET") === "GET") {
    respond(true, "Profil berhasil diambil.", fetchProfile($conn, $userId));
}

/* =========================================================
   POST — simpan perubahan profil
========================================================= */
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {

    if (!empty($_POST)) {
        $input = $_POST;
    } else {
        $raw = file_get_contents("php://input");
        $decoded = json_decode($raw, true);
        $input = is_array($decoded) ? $decoded : [];
    }

    $action = isset($input["action"]) ? trim((string) $input["action"]) : "update";

    /* --- Ganti password --- */
    if ($action === "password") {

        $passwordLama = isset($input["password_lama"]) ? (string) $input["password_lama"] : "";
        $passwordBaru = isset($input["password_baru"]) ? (string) $input["password_baru"] : "";
        $passwordUlang = isset($input["password_ulang"]) ? (string) $input["password_ulang"] : "";

        if ($passwordLama === "" || $passwordBaru === "") {
            respond(false, "Password lama dan password baru wajib diisi.");
        }

        if (strlen($passwordBaru) < 8) {
            respond(false, "Password baru minimal 8 karakter.");
        }

        if ($passwordBaru !== $passwordUlang) {
            respond(false, "Konfirmasi password baru tidak sama.");
        }

        $stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$row || !password_verify($passwordLama, $row["password"])) {
            respond(false, "Password lama salah.");
        }

        $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "si", $hash, $userId);

        if (!mysqli_stmt_execute($stmt)) {
            respond(false, "Gagal memperbarui password.");
        }
        mysqli_stmt_close($stmt);

        respond(true, "Password berhasil diperbarui.");
    }

    /* --- Update nama / email --- */
    $nama = isset($input["nama"]) ? trim((string) $input["nama"]) : "";
    $email = isset($input["email"]) ? trim((string) $input["email"]) : "";

    if ($nama === "" || strlen($nama) > 100) {
        respond(false, "Nama wajib diisi dan maksimal 100 karakter.");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        respond(false, "Format email tidak valid.");
    }

    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "si", $email, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        respond(false, "Email sudah digunakan oleh akun lain.");
    }
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "UPDATE users SET nama = ?, email = ? WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ssi", $nama, $email, $userId);

    if (!mysqli_stmt_execute($stmt)) {
        respond(false, "Gagal menyimpan profil: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    $_SESSION["nama"] = $nama;
    $_SESSION["email"] = $email;

    respond(true, "Profil berhasil disimpan.", fetchProfile($conn, $userId));
}

http_response_code(405);
respond(false, "Method request tidak didukung.");
