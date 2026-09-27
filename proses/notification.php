<?php

// ============================================================
// MIDTRANS PAYMENT NOTIFICATION
// BELAJARYUK
// ============================================================

header("Content-Type: application/json; charset=UTF-8");


// ============================================================
// CONFIG
// ============================================================

require_once __DIR__ . "/midtrans_config.php";


// ============================================================
// RESPONSE HELPER
// ============================================================

function responseJson(
    $success,
    $message,
    $data = null,
    $httpCode = 200
) {

    http_response_code($httpCode);

    echo json_encode(
        [
            "success" => $success,
            "message" => $message,
            "data" => $data
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
// AMBIL RAW BODY
// ============================================================

$rawBody =
    file_get_contents(
        "php://input"
    );


// ============================================================
// PARSE JSON
// ============================================================

$notification = [];

if (
    !empty($rawBody)
) {

    $notification =
        json_decode(
            $rawBody,
            true
        );

}


// ============================================================
// FALLBACK POST
// ============================================================

if (
    !is_array($notification) ||
    empty($notification)
) {

    $notification =
        $_POST;

}


// ============================================================
// CEK NOTIFICATION
// ============================================================

if (
    !is_array($notification) ||
    empty($notification)
) {

    responseJson(
        false,
        "Notification kosong.",
        null,
        400
    );

}


// ============================================================
// LOG NOTIFICATION
// ============================================================

error_log(
    "MIDTRANS NOTIFICATION: " .
    json_encode(
        $notification,
        JSON_UNESCAPED_UNICODE
    )
);


// ============================================================
// DATA MIDTRANS
// ============================================================

$orderId =
    trim(
        $notification["order_id"] ??
        ""
    );

$statusCode =
    trim(
        $notification["status_code"] ??
        ""
    );

$grossAmount =
    trim(
        $notification["gross_amount"] ??
        ""
    );

$transactionStatus =
    trim(
        strtolower(
            $notification["transaction_status"] ??
            ""
        )
    );

$paymentType =
    trim(
        $notification["payment_type"] ??
        ""
    );

$transactionId =
    trim(
        $notification["transaction_id"] ??
        ""
    );

$fraudStatus =
    trim(
        strtolower(
            $notification["fraud_status"] ??
            ""
        )
    );

$signatureKey =
    trim(
        $notification["signature_key"] ??
        ""
    );

$transactionTime =
    trim(
        $notification["transaction_time"] ??
        ""
    );


// ============================================================
// VALIDASI DATA WAJIB
// ============================================================

if (
    $orderId === "" ||
    $statusCode === "" ||
    $grossAmount === "" ||
    $transactionStatus === ""
) {

    responseJson(
        false,
        "Data notification Midtrans tidak lengkap.",
        null,
        400
    );

}


// ============================================================
// VALIDASI SIGNATURE KEY
//
// SHA512(
//     order_id +
//     status_code +
//     gross_amount +
//     ServerKey
// )
// ============================================================

$signatureString =
    $orderId .
    $statusCode .
    $grossAmount .
    MIDTRANS_SERVER_KEY;


$expectedSignature =
    hash(
        "sha512",
        $signatureString
    );


if (
    $signatureKey === "" ||
    !hash_equals(
        $expectedSignature,
        $signatureKey
    )
) {

    error_log(
        "MIDTRANS INVALID SIGNATURE: " .
        $orderId
    );


    responseJson(
        false,
        "Signature notification tidak valid.",
        null,
        403
    );

}


// ============================================================
// CARI TRANSAKSI
// ============================================================

$stmt =
    $conn->prepare(
        "
        SELECT
            id,
            order_id,
            user_id,
            jenis_produk,
            produk_id,
            nama_produk,
            gross_amount,
            transaction_status
        FROM transaksi
        WHERE order_id = ?
        LIMIT 1
        "
    );


if (
    !$stmt
) {

    responseJson(
        false,
        "Gagal menyiapkan query transaksi.",
        null,
        500
    );

}


$stmt->bind_param(
    "s",
    $orderId
);


$stmt->execute();


$result =
    $stmt->get_result();


$transaksi =
    $result->fetch_assoc();


$stmt->close();


// ============================================================
// TRANSAKSI TIDAK DITEMUKAN
// ============================================================

if (
    !$transaksi
) {

    error_log(
        "MIDTRANS ORDER TIDAK DITEMUKAN: " .
        $orderId
    );


    responseJson(
        false,
        "Order ID tidak ditemukan di database.",
        [
            "order_id" => $orderId
        ],
        404
    );

}


// ============================================================
// VALIDASI GROSS AMOUNT
// ============================================================

$dbGrossAmount =
    (float)
    $transaksi["gross_amount"];

$midtransGrossAmount =
    (float)
    $grossAmount;


// Bandingkan sampai 2 digit desimal

if (
    number_format(
        $dbGrossAmount,
        2,
        ".",
        ""
    )
    !==
    number_format(
        $midtransGrossAmount,
        2,
        ".",
        ""
    )
) {

    error_log(
        "MIDTRANS GROSS AMOUNT TIDAK SESUAI: " .
        $orderId
    );


    responseJson(
        false,
        "Gross amount tidak sesuai dengan transaksi.",
        [
            "order_id" => $orderId,
            "database_amount" => $dbGrossAmount,
            "midtrans_amount" => $midtransGrossAmount
        ],
        400
    );

}


// ============================================================
// VERIFIKASI STATUS KE MIDTRANS
//
// Selain signature, status transaksi dicek langsung ke API
// Midtrans agar status yang tersimpan sesuai sumber pembayaran.
// ============================================================

$statusUrl =
    "https://api.sandbox.midtrans.com/v2/" .
    rawurlencode(
        $orderId
    ) .
    "/status";


// ============================================================
// CURL
// ============================================================

$ch =
    curl_init(
        $statusUrl
    );


curl_setopt_array(
    $ch,
    [

        CURLOPT_RETURNTRANSFER =>
            true,

        CURLOPT_HTTPHEADER =>
            [
                "Accept: application/json"
            ],

        CURLOPT_USERPWD =>
            MIDTRANS_SERVER_KEY . ":",

        CURLOPT_HTTPAUTH =>
            CURLAUTH_BASIC,

        CURLOPT_TIMEOUT =>
            30,

        CURLOPT_CONNECTTIMEOUT =>
            10

    ]
);


$statusResponse =
    curl_exec(
        $ch
    );


$curlError =
    curl_error(
        $ch
    );

$statusHttpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close(
    $ch
);


// ============================================================
// CEK CURL
// ============================================================

if (
    $statusResponse === false ||
    $curlError
) {

    error_log(
        "MIDTRANS STATUS CURL ERROR: " .
        $curlError
    );


    responseJson(
        false,
        "Gagal menghubungi Midtrans untuk verifikasi status.",
        null,
        502
    );

}


// ============================================================
// PARSE STATUS RESPONSE
// ============================================================

$statusData =
    json_decode(
        $statusResponse,
        true
    );


if (
    !is_array($statusData)
) {

    error_log(
        "MIDTRANS STATUS RESPONSE INVALID: " .
        $statusResponse
    );


    responseJson(
        false,
        "Response status dari Midtrans tidak valid.",
        null,
        502
    );

}


// ============================================================
// HTTP ERROR DARI MIDTRANS
// ============================================================

if (
    $statusHttpCode < 200 ||
    $statusHttpCode >= 300
) {

    error_log(
        "MIDTRANS STATUS HTTP ERROR: " .
        $statusHttpCode .
        " | " .
        $statusResponse
    );


    responseJson(
        false,
        "Midtrans menolak verifikasi status transaksi.",
        [
            "http_code" =>
                $statusHttpCode,

            "midtrans_response" =>
                $statusData
        ],
        502
    );

}


// ============================================================
// AMBIL STATUS TERPERCAYA DARI MIDTRANS
// ============================================================

$verifiedTransactionStatus =
    strtolower(
        trim(
            $statusData["transaction_status"] ??
            $transactionStatus
        )
    );


$verifiedPaymentType =
    trim(
        $statusData["payment_type"] ??
        $paymentType
    );


$verifiedTransactionId =
    trim(
        $statusData["transaction_id"] ??
        $transactionId
    );


$verifiedFraudStatus =
    strtolower(
        trim(
            $statusData["fraud_status"] ??
            $fraudStatus
        )
    );


// ============================================================
// NORMALISASI STATUS
//
// capture + fraud accepted
// = settlement
//
// capture + challenge
// = challenge
// ============================================================

$finalStatus =
    $verifiedTransactionStatus;


if (
    $verifiedTransactionStatus ===
    "capture"
) {

    if (
        $verifiedFraudStatus ===
        "challenge"
    ) {

        $finalStatus =
            "challenge";

    }

    else {

        $finalStatus =
            "settlement";

    }

}


// ============================================================
// DATA WAKTU
// ============================================================

$transactionDateTime =
    null;


if (
    $transactionTime !== ""
) {

    $timestamp =
        strtotime(
            $transactionTime
        );


    if (
        $timestamp !== false
    ) {

        $transactionDateTime =
            date(
                "Y-m-d H:i:s",
                $timestamp
            );

    }

}


// ============================================================
// SETTLEMENT TIME
// ============================================================

$settlementDateTime =
    null;


if (
    $finalStatus ===
    "settlement"
) {

    $settlementDateTime =
        date(
            "Y-m-d H:i:s"
        );

}


// ============================================================
// UPDATE TRANSAKSI
// ============================================================

if (
    $settlementDateTime !== null
) {

    $stmt =
        $conn->prepare(
            "
            UPDATE transaksi
            SET
                payment_type = ?,
                transaction_id = ?,
                transaction_status = ?,
                fraud_status = ?,
                transaction_time = ?,
                settlement_time = ?,
                updated_at = NOW()
            WHERE order_id = ?
            "
        );


    if (
        !$stmt
    ) {

        responseJson(
            false,
            "Gagal menyiapkan update transaksi.",
            null,
            500
        );

    }


    $stmt->bind_param(
        "sssssss",
        $verifiedPaymentType,
        $verifiedTransactionId,
        $finalStatus,
        $verifiedFraudStatus,
        $transactionDateTime,
        $settlementDateTime,
        $orderId
    );

}

else {

    $stmt =
        $conn->prepare(
            "
            UPDATE transaksi
            SET
                payment_type = ?,
                transaction_id = ?,
                transaction_status = ?,
                fraud_status = ?,
                transaction_time = ?,
                updated_at = NOW()
            WHERE order_id = ?
            "
        );


    if (
        !$stmt
    ) {

        responseJson(
            false,
            "Gagal menyiapkan update transaksi.",
            null,
            500
        );

    }


    $stmt->bind_param(
        "ssssss",
        $verifiedPaymentType,
        $verifiedTransactionId,
        $finalStatus,
        $verifiedFraudStatus,
        $transactionDateTime,
        $orderId
    );

}


// ============================================================
// EXECUTE UPDATE
// ============================================================

if (
    !$stmt->execute()
) {

    $error =
        $stmt->error;


    $stmt->close();


    error_log(
        "MIDTRANS UPDATE TRANSAKSI ERROR: " .
        $error
    );


    responseJson(
        false,
        "Gagal memperbarui transaksi.",
        null,
        500
    );

}


$stmt->close();


// ============================================================
// LOG SUKSES
// ============================================================

error_log(
    "MIDTRANS TRANSAKSI UPDATED: " .
    $orderId .
    " | STATUS: " .
    $finalStatus
);


// ============================================================
// RESPONSE
// ============================================================

responseJson(
    true,
    "Notification Midtrans berhasil diproses.",
    [

        "order_id" =>
            $orderId,

        "transaction_status" =>
            $finalStatus,

        "payment_type" =>
            $verifiedPaymentType,

        "transaction_id" =>
            $verifiedTransactionId,

        "fraud_status" =>
            $verifiedFraudStatus,

        "jenis_produk" =>
            $transaksi["jenis_produk"],

        "produk_id" =>
            $transaksi["produk_id"]

    ]
);

?>