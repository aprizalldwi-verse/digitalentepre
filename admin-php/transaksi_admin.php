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


/*
===================================================
METHOD: GET - Ambil semua transaksi
===================================================
*/

if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $transaksi = [];
    $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";
    $searchParam = "%" . $search . "%";
    $status = trim($_GET["status"] ?? "");
    $payment = trim($_GET["payment"] ?? "");
    $productId = trim($_GET["product_id"] ?? "");
    $userId = trim($_GET["user_id"] ?? "");
    $dateFrom = trim($_GET["date_from"] ?? "");
    $dateTo = trim($_GET["date_to"] ?? "");
    foreach (["date_from" => $dateFrom, "date_to" => $dateTo] as $label => $dateValue) {
        if ($dateValue !== "" && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue) || !checkdate((int)substr($dateValue, 5, 2), (int)substr($dateValue, 8, 2), (int)substr($dateValue, 0, 4)))) {
            echo json_encode(["success" => false, "message" => "Filter tanggal tidak valid: " . $label]);
            exit;
        }
    }
    if ($dateFrom !== "" && $dateTo !== "" && $dateFrom > $dateTo) {
        echo json_encode(["success" => false, "message" => "Tanggal awal harus sebelum atau sama dengan tanggal akhir."]);
        exit;
    }
    $productId = ctype_digit($productId) && (int)$productId > 0 ? $productId : "";
    $userId = ctype_digit($userId) && (int)$userId > 0 ? $userId : "";
        $sql = "SELECT t.id,t.order_id,t.user_id,t.jenis_produk,t.produk_id,t.nama_produk,t.gross_amount,t.payment_type,t.transaction_id,t.transaction_status,t.fraud_status,t.transaction_time,t.settlement_time,t.created_at,t.updated_at,COALESCE(u.nama,'User') AS nama_user,COALESCE(u.email,'-') AS email_user,
               COALESCE(p.kategori,b.kategori,CASE WHEN t.jenis_produk='elearning' THEN 'E-Learning' WHEN t.jenis_produk='bootcamp' THEN 'Bootcamp' ELSE 'Tidak diketahui' END) AS kategori_produk,
               COALESCE(t.transaction_time,t.created_at) AS tanggal_transaksi
            FROM transaksi t LEFT JOIN users u ON t.user_id=u.id
            LEFT JOIN products p ON t.jenis_produk='elearning' AND t.produk_id=p.id
            LEFT JOIN bootcamp b ON t.jenis_produk='bootcamp' AND t.produk_id=b.id
            WHERE (?='' OR t.order_id LIKE ? OR t.nama_produk LIKE ? OR u.nama LIKE ? OR u.email LIKE ?)
              AND (?='' OR t.transaction_status=?) AND (?='' OR COALESCE(t.payment_type,'')=?)
              AND (?='' OR t.produk_id=?) AND (?='' OR t.user_id=?)
                            AND (?='' OR DATE(COALESCE(t.transaction_time,t.created_at))>=?) AND (?='' OR DATE(COALESCE(t.transaction_time,t.created_at))<=?)
                        ORDER BY COALESCE(t.transaction_time,t.created_at) DESC,t.id DESC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssssssssssss", $search, $searchParam, $searchParam, $searchParam, $searchParam, $status, $status, $payment, $payment, $productId, $productId, $userId, $userId, $dateFrom, $dateFrom, $dateTo, $dateTo);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);


    /*
    AMBIL SEMUA DATA
    */

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $transaksi[] = [
                "id" => intval($row["id"]),
                "transaction_id" => intval($row["id"]),
                "order_id" => $row["order_id"],
                "user_id" => $row["user_id"] ? intval($row["user_id"]) : null,
                "jenis_produk" => $row["jenis_produk"],
                "produk_id" => intval($row["produk_id"]),
                "nama_produk" => $row["nama_produk"],
                "kategori_produk" => $row["kategori_produk"],
                "gross_amount" => floatval($row["gross_amount"]),
                "payment_type" => $row["payment_type"],
                "transaction_id" => $row["transaction_id"],
                "status" => $row["transaction_status"],
                "fraud_status" => $row["fraud_status"],
                "transaction_time" => $row["transaction_time"],
                "settlement_time" => $row["settlement_time"],
                "created_at" => $row["created_at"],
                "tanggal_transaksi" => $row["tanggal_transaksi"],
                "updated_at" => $row["updated_at"],
                "nama_user" => $row["nama_user"] ?? "User",
                "email_user" => $row["email_user"] ?? "-"
            ];
        }
    }

    mysqli_stmt_close($stmt);


    /*
    RESPONSE
    */

    echo json_encode([
        "success" => true,
        "message" => "Data transaksi berhasil dimuat",
        "data" => $transaksi
    ], JSON_PRETTY_PRINT);

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
