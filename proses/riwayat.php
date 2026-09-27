<?php

// ============================================================
// RIWAYAT TRANSAKSI USER
// BELAJARYUK
// ============================================================

session_start();

header(
    "Content-Type: application/json; charset=UTF-8"
);

require_once __DIR__ . "/midtrans_config.php";


// ============================================================
// HELPER RESPONSE
// ============================================================

function responseJson(
    $success,
    $message,
    $data = null,
    $httpCode = 200
) {

    http_response_code(
        $httpCode
    );

    echo json_encode(
        [
            "success" =>
                $success,

            "message" =>
                $message,

            "data" =>
                $data
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


// ============================================================
// CEK DATABASE
// ============================================================

if (
    !isset($conn) ||
    !($conn instanceof mysqli) ||
    $conn->connect_error
) {

    responseJson(
        false,
        "Koneksi database gagal.",
        null,
        500
    );

}


// ============================================================
// AMBIL USER ID SESSION
// ============================================================

$userId = 0;


// Format session yang mungkin dipakai project

if (
    isset($_SESSION["user_id"]) &&
    is_numeric($_SESSION["user_id"])
) {

    $userId =
        (int) $_SESSION["user_id"];

}

elseif (
    isset($_SESSION["id_user"]) &&
    is_numeric($_SESSION["id_user"])
) {

    $userId =
        (int) $_SESSION["id_user"];

}

elseif (
    isset($_SESSION["user"]) &&
    is_array($_SESSION["user"]) &&
    isset($_SESSION["user"]["id"]) &&
    is_numeric($_SESSION["user"]["id"])
) {

    $userId =
        (int) $_SESSION["user"]["id"];

}


// ============================================================
// WAJIB LOGIN
// ============================================================

if (
    $userId <= 0
) {

    responseJson(
        false,
        "Kamu belum login.",
        null,
        401
    );

}


// ============================================================
// KADALUARSAKAN TRANSAKSI PENDING > 5 MENIT
//
// Pending yang dibuat 5 menit atau lebih lalu akan menjadi
// expire.
//
// Settlement tetap aman karena hanya pending yang diubah.
//
// ============================================================

$expireSql = "

    UPDATE transaksi

    SET
        transaction_status = 'expire',
        updated_at = NOW()

    WHERE
        user_id = ?

        AND transaction_status = 'pending'

        AND created_at <=
            DATE_SUB(
                NOW(),
                INTERVAL 5 MINUTE
            )

";


$expireStmt =
    $conn->prepare(
        $expireSql
    );


if (
    $expireStmt
) {

    $expireStmt->bind_param(
        "i",
        $userId
    );


    $expireStmt->execute();


    $expireStmt->close();

}


// ============================================================
// HAPUS TRANSAKSI GAGAL / EXPIRE > 24 JAM
//
// Yang dihapus:
// deny
// cancel
// expire
//
// Yang tidak dihapus:
// pending
// settlement
// capture
// ============================================================

$deleteSql = "

    DELETE FROM transaksi

    WHERE
        user_id = ?

        AND transaction_status IN (
            'deny',
            'cancel',
            'expire'
        )

        AND updated_at <
            DATE_SUB(
                NOW(),
                INTERVAL 1 DAY
            )

";


$deleteStmt =
    $conn->prepare(
        $deleteSql
    );


if (
    $deleteStmt
) {

    $deleteStmt->bind_param(
        "i",
        $userId
    );


    $deleteStmt->execute();


    $deleteStmt->close();

}


// ============================================================
// AMBIL RIWAYAT
// ============================================================

$sql = "

    SELECT

        id,

        order_id,

        jenis_produk,

        produk_id,

        nama_produk,

        gross_amount,

        payment_type,

        transaction_id,

        transaction_status,

        fraud_status,

        transaction_time,

        settlement_time,

        created_at,

        updated_at

    FROM transaksi

    WHERE user_id = ?

    ORDER BY created_at DESC

";


$stmt =
    $conn->prepare(
        $sql
    );


if (
    !$stmt
) {

    responseJson(
        false,
        "Gagal menyiapkan query riwayat.",
        null,
        500
    );

}


$stmt->bind_param(
    "i",
    $userId
);


// ============================================================
// EKSEKUSI QUERY
// ============================================================

if (
    !$stmt->execute()
) {

    $stmt->close();


    responseJson(
        false,
        "Gagal mengambil riwayat transaksi.",
        null,
        500
    );

}


$result =
    $stmt->get_result();


$transactions = [];


// ============================================================
// FORMAT DATA TRANSAKSI
// ============================================================

while (
    $row =
    $result->fetch_assoc()
) {

    $status =
        strtolower(
            trim(
                (string)$row["transaction_status"]
            )
        );


    $transactions[] = [

        "id" =>
            (int) $row["id"],


        "order_id" =>
            $row["order_id"],


        "jenis_produk" =>
            $row["jenis_produk"],


        "produk_id" =>
            (int) $row["produk_id"],


        "nama_produk" =>
            $row["nama_produk"],


        "gross_amount" =>
            (float) $row["gross_amount"],


        "payment_type" =>
            $row["payment_type"],


        "transaction_id" =>
            $row["transaction_id"],


        "transaction_status" =>
            $status,


        "fraud_status" =>
            $row["fraud_status"],


        "transaction_time" =>
            $row["transaction_time"],


        "settlement_time" =>
            $row["settlement_time"],


        "created_at" =>
            $row["created_at"],


        "updated_at" =>
            $row["updated_at"]

    ];

}


$stmt->close();


// ============================================================
// RESPONSE
// ============================================================

responseJson(

    true,

    "Riwayat transaksi berhasil diambil.",

    $transactions

);

?>