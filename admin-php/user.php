<?php

header("Content-Type: application/json; charset=UTF-8");

/*
===================================================
KONEKSI DATABASE
===================================================
*/

require_once __DIR__ . "/../config/koneksi.php";


/*
===================================================
CEK KONEKSI DATABASE
===================================================
*/

if (!$conn) {
    echo json_encode([
        "success" => false,
        "message" => "Database tidak terhubung"
    ]);
    exit;
}


/*
===================================================
SESSION CHECK
===================================================
*/

session_start();

if (!isset($_SESSION["user_id"]) || !isset($_SESSION["role"])) {
    echo json_encode([
        "success" => false,
        "message" => "Anda belum login"
    ]);
    exit;
}

if ($_SESSION["role"] !== "admin") {
    echo json_encode([
        "success" => false,
        "message" => "Akses ditolak. Hanya admin yang dapat mengakses."
    ]);
    exit;
}

$session_user_id = $_SESSION["user_id"];

$statusColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'status'");
if ($statusColumn && mysqli_num_rows($statusColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active'");
}


/*
===================================================
METHOD: GET - Ambil semua user
===================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $users = [];
    $search = trim($_GET["search"] ?? "");
    $role = in_array($_GET["role"] ?? "all", ["all", "user", "admin"], true) ? ($_GET["role"] ?? "all") : "all";
    $status = in_array($_GET["status"] ?? "all", ["all", "active", "inactive"], true) ? ($_GET["status"] ?? "all") : "all";
    $page = max(1, filter_var($_GET["page"] ?? 1, FILTER_VALIDATE_INT) ?: 1);
    $perPage = min(100, max(1, filter_var($_GET["per_page"] ?? 20, FILTER_VALIDATE_INT) ?: 20));
    $userColumns = [];
    $columnResult = mysqli_query($conn, "SHOW COLUMNS FROM users");
    if ($columnResult) while ($column = mysqli_fetch_assoc($columnResult)) $userColumns[$column["Field"]] = true;
    $phoneColumn = null;
    foreach (["phone", "phone_number", "nomor_hp", "no_hp"] as $candidate) if (isset($userColumns[$candidate])) { $phoneColumn = $candidate; break; }
    $loginColumn = null;
    foreach (["last_login", "last_login_at"] as $candidate) if (isset($userColumns[$candidate])) { $loginColumn = $candidate; break; }
    $phoneSelect = $phoneColumn ? "`" . $phoneColumn . "`" : "NULL";
    $loginSelect = $loginColumn ? "`" . $loginColumn . "`" : "NULL";
    $selectColumns = "id,nama,email,role,status,created_at,{$phoneSelect} AS phone,{$loginSelect} AS last_login";

    if (isset($_GET["id"])) {
        $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            echo json_encode(["success" => false, "message" => "ID pengguna tidak valid"]);
            exit;
        }
        $detailStmt = mysqli_prepare($conn, "SELECT {$selectColumns} FROM users WHERE id = ?");
        mysqli_stmt_bind_param($detailStmt, "i", $id);
        mysqli_stmt_execute($detailStmt);
        $detail = mysqli_stmt_get_result($detailStmt);
        $user = $detail ? mysqli_fetch_assoc($detail) : null;
        mysqli_stmt_close($detailStmt);
        echo json_encode(["success" => (bool)$user, "data" => $user, "message" => $user ? "Data pengguna ditemukan" : "Pengguna tidak ditemukan"], JSON_PRETTY_PRINT);
        exit;
    }

    $where = [];
    $params = [];
    $types = "";
    if ($search !== "") { $where[] = "(nama LIKE ? OR email LIKE ?)"; $like = "%" . $search . "%"; $params[] = $like; $params[] = $like; $types .= "ss"; }
    if ($role !== "all") { $where[] = "role = ?"; $params[] = $role; $types .= "s"; }
    if ($status !== "all") { $where[] = "status = ?"; $params[] = $status; $types .= "s"; }
    $whereSQL = $where ? " WHERE " . implode(" AND ", $where) : "";
    $countStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM users" . $whereSQL);
    if ($types !== "") mysqli_stmt_bind_param($countStmt, $types, ...$params);
    mysqli_stmt_execute($countStmt);
    $filteredTotal = (int)mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))["total"];
    mysqli_stmt_close($countStmt);
    $offset = ($page - 1) * $perPage;
    $listStmt = mysqli_prepare($conn, "SELECT {$selectColumns} FROM users" . $whereSQL . " ORDER BY created_at DESC,id DESC LIMIT ? OFFSET ?");
    $listParams = $params;
    $listTypes = $types . "ii";
    $listParams[] = $perPage;
    $listParams[] = $offset;
    mysqli_stmt_bind_param($listStmt, $listTypes, ...$listParams);
    $stmt = $listStmt;
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = [
                "id" => intval($row["id"]),
                "nama" => $row["nama"],
                "email" => $row["email"],
                "role" => $row["role"],
                "status" => $row["status"],
                "phone" => $row["phone"],
                "last_login" => $row["last_login"],
                "created_at" => $row["created_at"]
            ];
        }
    }
    mysqli_stmt_close($stmt);

    $summaryResult = mysqli_query($conn, "SELECT COUNT(*) AS total, SUM(role='admin') AS admins, SUM(role='user') AS users FROM users");
    $summary = $summaryResult ? mysqli_fetch_assoc($summaryResult) : ["total" => 0, "admins" => 0, "users" => 0];

    echo json_encode([
        "success" => true,
        "message" => "Data pengguna berhasil dimuat",
        "data" => $users,
        "summary" => ["total" => (int)$summary["total"], "admins" => (int)$summary["admins"], "users" => (int)$summary["users"]],
        "pagination" => ["page" => $page, "per_page" => $perPage, "total" => $filteredTotal, "pages" => max(1, (int)ceil($filteredTotal / $perPage))],
        "fields" => ["phone" => $phoneColumn !== null, "last_login" => $loginColumn !== null],
        "session_user" => [
            "id" => intval($session_user_id)
        ]
    ], JSON_PRETTY_PRINT);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) $input = $_POST;
    if (!is_string($input["nama"] ?? null) || !is_string($input["email"] ?? null) || !is_string($input["password"] ?? null)) {
        echo json_encode(["success" => false, "message" => "Data tidak valid."]);
        exit;
    }
    $nama = trim($input["nama"]);
    $email = trim($input["email"]);
    $role = $input["role"] ?? "user";
    $status = $input["status"] ?? "active";
    $password = $input["password"];
    if ($nama === "" || mb_strlen($nama) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150 || !in_array($role, ["user", "admin"], true) || !in_array($status, ["active", "inactive"], true) || strlen($password) < 8) {
        echo json_encode(["success" => false, "message" => "Data tidak valid. Password minimal 8 karakter."]);
        exit;
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (nama,email,password,role,status) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, "sssss", $nama, $email, $hash, $role, $status);
    $success = mysqli_stmt_execute($stmt);
    $message = $success ? "Pengguna berhasil ditambahkan" : "Gagal menambahkan pengguna. Email mungkin sudah digunakan.";
    mysqli_stmt_close($stmt);
    echo json_encode(["success" => $success, "message" => $message]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input) || !is_string($input["nama"] ?? null) || !is_string($input["email"] ?? null)) {
        echo json_encode(["success" => false, "message" => "Data pengguna tidak valid"]);
        exit;
    }
    $id = filter_var($input["id"] ?? null, FILTER_VALIDATE_INT);
    $nama = trim($input["nama"]);
    $email = trim($input["email"]);
    $role = $input["role"] ?? "user";
    $status = $input["status"] ?? "active";
    if (!$id || $id < 1 || $nama === "" || mb_strlen($nama) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150 || !in_array($role, ["user", "admin"], true) || !in_array($status, ["active", "inactive"], true)) {
        echo json_encode(["success" => false, "message" => "Data pengguna tidak valid"]);
        exit;
    }
    if ($id === intval($session_user_id) && ($role !== "admin" || $status !== "active")) {
        echo json_encode(["success" => false, "message" => "Role dan status akun sendiri tidak dapat dinonaktifkan"]);
        exit;
    }
    $currentStmt = mysqli_prepare($conn, "SELECT role, status FROM users WHERE id = ?");
    mysqli_stmt_bind_param($currentStmt, "i", $id);
    mysqli_stmt_execute($currentStmt);
    $currentUser = mysqli_fetch_assoc(mysqli_stmt_get_result($currentStmt));
    mysqli_stmt_close($currentStmt);
    if ($currentUser && $currentUser["role"] === "admin" && $currentUser["status"] === "active" && ($role !== "admin" || $status !== "active")) {
        $activeAdmins = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role='admin' AND status='active'");
        $activeAdminCount = $activeAdmins ? (int)mysqli_fetch_assoc($activeAdmins)["total"] : 0;
        if ($activeAdminCount <= 1) {
            echo json_encode(["success" => false, "message" => "Tidak dapat menonaktifkan admin aktif terakhir"]);
            exit;
        }
    }
    $stmt = mysqli_prepare($conn, "UPDATE users SET nama=?, email=?, role=?, status=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "ssssi", $nama, $email, $role, $status, $id);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(["success" => $success, "message" => $success ? "Pengguna berhasil diperbarui" : "Gagal memperbarui pengguna"]);
    exit;
}


/*
===================================================
METHOD: DELETE - Hapus user
===================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    $input = json_decode(file_get_contents("php://input"), true);

    if (!$input || !isset($input["id"])) {
        echo json_encode([
            "success" => false,
            "message" => "ID pengguna tidak valid"
        ]);
        exit;
    }

    $user_id = intval($input["id"]);


    /*
    CEK: TIDAK BOLEH HAPUS AKUN SENDIRI
    */

    if ($user_id === intval($session_user_id)) {
        echo json_encode([
            "success" => false,
            "message" => "Tidak dapat menghapus akun sendiri"
        ]);
        exit;
    }


    /*
    CEK: APAKAH USER ADA
    */

    $checkStmt = mysqli_prepare(
        $conn,
        "SELECT id, nama, role FROM users WHERE id = ?"
    );

    mysqli_stmt_bind_param($checkStmt, "i", $user_id);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
        mysqli_stmt_close($checkStmt);
        echo json_encode([
            "success" => false,
            "message" => "Pengguna tidak ditemukan"
        ]);
        exit;
    }

    $checkRow = mysqli_fetch_assoc($checkResult);
    mysqli_stmt_close($checkStmt);


    /*
    CEK: JIKA USER ADALAH ADMIN TERAKHIR, TOLAK
    */

    if ($checkRow["role"] === "admin") {
        $countAdmin = mysqli_query(
            $conn,
            "SELECT COUNT(*) AS total FROM users WHERE role = 'admin'"
        );
        $rowAdmin = mysqli_fetch_assoc($countAdmin);

        if ($rowAdmin && intval($rowAdmin["total"]) <= 1) {
            echo json_encode([
                "success" => false,
                "message" => "Tidak dapat menghapus admin terakhir"
            ]);
            exit;
        }
    }


    /*
    HAPUS USER
    */

    $deleteStmt = mysqli_prepare(
        $conn,
        "DELETE FROM users WHERE id = ?"
    );

    mysqli_stmt_bind_param($deleteStmt, "i", $user_id);
    $success = mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);

    if ($success) {
        echo json_encode([
            "success" => true,
            "message" => "Pengguna \"" . addslashes($checkRow["nama"]) . "\" berhasil dihapus",
            "data" => null
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Gagal menghapus pengguna"
        ]);
    }

    exit;
}


/*
===================================================
METHOD TIDAK DIDUKUNG
===================================================
*/

echo json_encode([
    "success" => false,
    "message" => "Method tidak didukung"
]);

?>
