<?php

/* ============================================================
   CREATE PAYMENT - BELAJARYUK
   Midtrans Snap Sandbox
============================================================ */

session_start();

header("Content-Type: application/json; charset=UTF-8");


/* ============================================================
   LOAD CONFIG
============================================================ */

require_once __DIR__ . "/midtrans_config.php";


/* ============================================================
   FUNCTION RESPONSE JSON
============================================================ */

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
            "success" => $success,
            "message" => $message,
            "data" => $data
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;

}


/* ============================================================
   HANYA POST
============================================================ */

if (
    $_SERVER["REQUEST_METHOD"] !== "POST"
) {

    responseJson(
        false,
        "Method tidak diizinkan.",
        null,
        405
    );

}


/* ============================================================
   TERIMA JSON
============================================================ */

$rawInput =
    file_get_contents(
        "php://input"
    );


$dataInput =
    json_decode(
        $rawInput,
        true
    );


/* ============================================================
   FALLBACK KE $_POST
============================================================ */

if (
    !is_array(
        $dataInput
    )
) {

    $dataInput =
        $_POST;

}


/* ============================================================
   AMBIL DATA PRODUK
============================================================ */

$jenisProduk =
    isset(
        $dataInput["jenis_produk"]
    )
        ? trim(
            (string)$dataInput["jenis_produk"]
        )
        : "";


$produkId =
    isset(
        $dataInput["produk_id"]
    )
        ? (int)$dataInput["produk_id"]
        : 0;


/* ============================================================
   VALIDASI PRODUK
============================================================ */

if (
    $jenisProduk === ""
) {

    responseJson(
        false,
        "Jenis produk tidak ditemukan.",
        null,
        400
    );

}


if (
    !in_array(
        $jenisProduk,
        [
            "elearning",
            "bootcamp"
        ],
        true
    )
) {

    responseJson(
        false,
        "Jenis produk tidak valid.",
        null,
        400
    );

}


if (
    $produkId <= 0
) {

    responseJson(
        false,
        "ID produk tidak valid.",
        null,
        400
    );

}


/* ============================================================
   CEK DATABASE
============================================================ */

if (
    !isset(
        $conn
    ) ||
    !($conn instanceof mysqli)
) {

    responseJson(
        false,
        "Koneksi database tidak tersedia.",
        null,
        500
    );

}


/* ============================================================
   SESSION USER
============================================================ */

$userId =
    null;


$userName =
    "";


$userEmail =
    "";


/* ============================================================
   USER ID
============================================================ */

if (
    isset(
        $_SESSION["user_id"]
    ) &&
    $_SESSION["user_id"] !== ""
) {

    $userId =
        (int)$_SESSION["user_id"];

}

elseif (
    isset(
        $_SESSION["id_user"]
    ) &&
    $_SESSION["id_user"] !== ""
) {

    $userId =
        (int)$_SESSION["id_user"];

}

elseif (
    isset(
        $_SESSION["user"]
    ) &&
    is_array(
        $_SESSION["user"]
    ) &&
    isset(
        $_SESSION["user"]["id"]
    )
) {

    $userId =
        (int)$_SESSION["user"]["id"];

}


/* ============================================================
   NAMA USER
============================================================ */

if (
    isset(
        $_SESSION["nama"]
    ) &&
    $_SESSION["nama"] !== ""
) {

    $userName =
        trim(
            (string)$_SESSION["nama"]
        );

}

elseif (
    isset(
        $_SESSION["nama_user"]
    ) &&
    $_SESSION["nama_user"] !== ""
) {

    $userName =
        trim(
            (string)$_SESSION["nama_user"]
        );

}

elseif (
    isset(
        $_SESSION["user"]
    ) &&
    is_array(
        $_SESSION["user"]
    ) &&
    isset(
        $_SESSION["user"]["nama"]
    )
) {

    $userName =
        trim(
            (string)$_SESSION["user"]["nama"]
        );

}


/* ============================================================
   EMAIL USER
============================================================ */

if (
    isset(
        $_SESSION["email"]
    ) &&
    $_SESSION["email"] !== ""
) {

    $userEmail =
        trim(
            (string)$_SESSION["email"]
        );

}

elseif (
    isset(
        $_SESSION["user_email"]
    ) &&
    $_SESSION["user_email"] !== ""
) {

    $userEmail =
        trim(
            (string)$_SESSION["user_email"]
        );

}

elseif (
    isset(
        $_SESSION["user"]
    ) &&
    is_array(
        $_SESSION["user"]
    ) &&
    isset(
        $_SESSION["user"]["email"]
    )
) {

    $userEmail =
        trim(
            (string)$_SESSION["user"]["email"]
        );

}


/* ============================================================
   VALIDASI LOGIN
============================================================ */

if (
    !$userId ||
    $userId <= 0
) {

    responseJson(
        false,
        "Session login tidak ditemukan. Silakan login kembali.",
        null,
        401
    );

}


/* ============================================================
   DEFAULT USERNAME
============================================================ */

if (
    $userName === ""
) {

    $userName =
        "Pengguna BELAJARYUK";

}


/* ============================================================
   VALIDASI EMAIL
============================================================ */

if (
    $userEmail === "" ||
    !filter_var(
        $userEmail,
        FILTER_VALIDATE_EMAIL
    )
) {

    responseJson(
        false,
        "Email akun tidak valid.",
        null,
        400
    );

}


/* ============================================================
   DATA PRODUK
============================================================ */

$namaProduk =
    "";


$hargaNormal =
    0;


$hargaPromo =
    0;


$promoAktif =
    0;


/* ============================================================
   E-LEARNING
   TABEL: products
============================================================ */

if (
    $jenisProduk === "elearning"
) {

    try {

        $sql =
            "
            SELECT
                id,
                nama_produk,
                harga,
                harga_promo,
                promo_aktif
            FROM products
            WHERE id = ?
            LIMIT 1
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
                "Gagal menyiapkan query products: " .
                $conn->error,
                null,
                500
            );

        }


        $stmt->bind_param(
            "i",
            $produkId
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        if (
            !$result ||
            $result->num_rows === 0
        ) {

            $stmt->close();


            responseJson(
                false,
                "Course tidak ditemukan.",
                null,
                404
            );

        }


        $product =
            $result->fetch_assoc();


        $stmt->close();


        $namaProduk =
            isset(
                $product["nama_produk"]
            )
                ? trim(
                    (string)$product["nama_produk"]
                )
                : "";


        $hargaNormal =
            isset(
                $product["harga"]
            )
                ? (float)$product["harga"]
                : 0;


        $hargaPromo =
            isset(
                $product["harga_promo"]
            )
                ? (float)$product["harga_promo"]
                : 0;


        $promoAktif =
            isset(
                $product["promo_aktif"]
            )
                ? (int)$product["promo_aktif"]
                : 0;

    }

    catch (
        Throwable $e
    ) {

        responseJson(
            false,
            "Gagal mengambil data E-Learning: " .
            $e->getMessage(),
            null,
            500
        );

    }

}


/* ============================================================
   BOOTCAMP
   TABEL: bootcamp
============================================================ */

elseif (
    $jenisProduk === "bootcamp"
) {

    try {

        $sql =
            "
            SELECT
                id,
                judul,
                kategori,
                mentor,
                harga,
                tanggal_mulai,
                tanggal_berakhir,
                kuota,
                gambar,
                deskripsi,
                created_at,
                harga_promo,
                promo_aktif,
                benefit
            FROM bootcamp
            WHERE id = ?
            LIMIT 1
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
                "Gagal menyiapkan query bootcamp: " .
                $conn->error,
                null,
                500
            );

        }


        $stmt->bind_param(
            "i",
            $produkId
        );


        $stmt->execute();


        $result =
            $stmt->get_result();


        if (
            !$result ||
            $result->num_rows === 0
        ) {

            $stmt->close();


            responseJson(
                false,
                "Bootcamp tidak ditemukan.",
                null,
                404
            );

        }


        $bootcamp =
            $result->fetch_assoc();


        $stmt->close();


        $namaProduk =
            isset(
                $bootcamp["judul"]
            )
                ? trim(
                    (string)$bootcamp["judul"]
                )
                : "";


        $hargaNormal =
            isset(
                $bootcamp["harga"]
            )
                ? (float)$bootcamp["harga"]
                : 0;


        $hargaPromo =
            isset(
                $bootcamp["harga_promo"]
            )
                ? (float)$bootcamp["harga_promo"]
                : 0;


        $promoAktif =
            isset(
                $bootcamp["promo_aktif"]
            )
                ? (int)$bootcamp["promo_aktif"]
                : 0;

    }

    catch (
        Throwable $e
    ) {

        responseJson(
            false,
            "Gagal mengambil data Bootcamp: " .
            $e->getMessage(),
            null,
            500
        );

    }

}


/* ============================================================
   VALIDASI NAMA PRODUK
============================================================ */

if (
    $namaProduk === ""
) {

    responseJson(
        false,
        "Nama produk tidak ditemukan.",
        null,
        400
    );

}


/* ============================================================
   CEK PRODUK GRATIS
============================================================ */

/*
   GRATIS apabila:

   1. Harga normal = 0

   ATAU

   2. Promo aktif DAN harga promo = 0

   Contoh:

   harga = 0
   => GRATIS

   harga = 100000
   harga_promo = 0
   promo_aktif = 1
   => GRATIS
*/

$isFree =
    false;


if (
    $hargaNormal <= 0
) {

    $isFree =
        true;

}

elseif (
    $promoAktif === 1 &&
    $hargaPromo <= 0
) {

    $isFree =
        true;

}


/* ============================================================
   PRODUK GRATIS
============================================================ */

if (
    $isFree
) {

    /* ========================================================
       ORDER ID GRATIS
    ======================================================== */

    $orderId =
        "FREE-" .
        date(
            "YmdHis"
        ) .
        "-" .
        strtoupper(
            bin2hex(
                random_bytes(
                    4
                )
            )
        );


    /* ========================================================
       TRANSACTION ID GRATIS
    ======================================================== */

    $freeTransactionId =
        "FREE-" .
        strtoupper(
            bin2hex(
                random_bytes(
                    5
                )
            )
        );


    /* ========================================================
       SIMPAN LANGSUNG SEBAGAI LUNAS
    ======================================================== */

    try {

        $sqlInsert =
            "
            INSERT INTO transaksi
            (
                order_id,
                user_id,
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
                snap_token,
                created_at,
                updated_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                0,
                'free',
                ?,
                'settlement',
                'accept',
                NOW(),
                NOW(),
                NULL,
                NOW(),
                NOW()
            )
            ";


        $insert =
            $conn->prepare(
                $sqlInsert
            );


        if (
            !$insert
        ) {

            responseJson(
                false,
                "Gagal menyiapkan transaksi gratis: " .
                $conn->error,
                null,
                500
            );

        }


        /* ====================================================
           PERBAIKAN:
           Ada 6 variabel:
           1. orderId          = s
           2. userId           = i
           3. jenisProduk      = s
           4. produkId         = i
           5. namaProduk       = s
           6. freeTransactionId= s
        ==================================================== */

        $insert->bind_param(
            "sisiss",
            $orderId,
            $userId,
            $jenisProduk,
            $produkId,
            $namaProduk,
            $freeTransactionId
        );


        if (
            !$insert->execute()
        ) {

            $error =
                $insert->error;


            $insert->close();


            responseJson(
                false,
                "Gagal menyimpan transaksi gratis: " .
                $error,
                null,
                500
            );

        }


        $transactionDbId =
            $insert->insert_id;


        $insert->close();

    }

    catch (
        Throwable $e
    ) {

        responseJson(
            false,
            "Gagal membuat transaksi gratis: " .
            $e->getMessage(),
            null,
            500
        );

    }


    /* ========================================================
       RESPONSE GRATIS
    ======================================================== */

    responseJson(

        true,

        "Produk gratis berhasil ditambahkan ke akun.",

        [

            "transaction_id" =>
                $transactionDbId,

            "order_id" =>
                $orderId,

            "snap_token" =>
                null,

            "redirect_url" =>
                null,

            "gross_amount" =>
                0,

            "nama_produk" =>
                $namaProduk,

            "jenis_produk" =>
                $jenisProduk,

            "produk_id" =>
                $produkId,

            "is_free" =>
                true,

            "transaction_status" =>
                "settlement"

        ],

        200

    );

}


/* ============================================================
   TENTUKAN HARGA AKHIR
============================================================ */

$grossAmount =
    $hargaNormal;


if (
    $promoAktif === 1 &&
    $hargaPromo > 0 &&
    $hargaPromo < $hargaNormal
) {

    $grossAmount =
        $hargaPromo;

}


/* ============================================================
   MIDTRANS BUTUH INTEGER
============================================================ */

$grossAmount =
    (int)round(
        $grossAmount
    );


/* ============================================================
   VALIDASI HARGA
============================================================ */

if (
    $grossAmount <= 0
) {

    responseJson(
        false,
        "Harga produk tidak valid.",
        null,
        400
    );

}


/* ============================================================
   BUAT ORDER ID
============================================================ */

$orderId =
    "BY-" .
    date(
        "YmdHis"
    ) .
    "-" .
    strtoupper(
        bin2hex(
            random_bytes(
                4
            )
        )
    );


/* ============================================================
   WAKTU EXPIRY 5 MENIT
============================================================ */

$expiryStartTime =
    date(
        "Y-m-d H:i:s O"
    );


/* ============================================================
   DATA MIDTRANS
============================================================ */

$midtransPayload =
    [

        "transaction_details" => [

            "order_id" =>
                $orderId,

            "gross_amount" =>
                $grossAmount

        ],


        "expiry" => [

            "start_time" =>
                $expiryStartTime,

            "unit" =>
                "minutes",

            "duration" =>
                5

        ],


        "item_details" => [

            [

                "id" =>
                    $jenisProduk .
                    "-" .
                    $produkId,

                "price" =>
                    $grossAmount,

                "quantity" =>
                    1,

                "name" =>
                    $namaProduk

            ]

        ],


        "customer_details" => [

            "first_name" =>
                $userName,

            "email" =>
                $userEmail

        ]

    ];


/* ============================================================
   JSON MIDTRANS
============================================================ */

$jsonPayload =
    json_encode(
        $midtransPayload,
        JSON_UNESCAPED_UNICODE
    );


if (
    $jsonPayload === false
) {

    responseJson(
        false,
        "Gagal membuat data pembayaran.",
        null,
        500
    );

}


/* ============================================================
   CURL MIDTRANS
============================================================ */

$ch =
    curl_init(
        MIDTRANS_API_URL
    );


curl_setopt(
    $ch,
    CURLOPT_RETURNTRANSFER,
    true
);


curl_setopt(
    $ch,
    CURLOPT_POST,
    true
);


curl_setopt(
    $ch,
    CURLOPT_POSTFIELDS,
    $jsonPayload
);


curl_setopt(
    $ch,
    CURLOPT_HTTPHEADER,
    [

        "Content-Type: application/json",

        "Accept: application/json",

        "Authorization: Basic " .
        base64_encode(
            MIDTRANS_SERVER_KEY . ":"
        )

    ]
);


/* ============================================================
   CURL TIMEOUT
============================================================ */

curl_setopt(
    $ch,
    CURLOPT_CONNECTTIMEOUT,
    15
);


curl_setopt(
    $ch,
    CURLOPT_TIMEOUT,
    30
);


/* ============================================================
   EKSEKUSI
============================================================ */

$midtransResponse =
    curl_exec(
        $ch
    );


$curlError =
    curl_error(
        $ch
    );


$curlErrno =
    curl_errno(
        $ch
    );


$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close(
    $ch
);


/* ============================================================
   CEK CURL
============================================================ */

if (
    $curlErrno !== 0
) {

    responseJson(
        false,
        "Gagal terhubung ke Midtrans: " .
        $curlError,
        null,
        502
    );

}


/* ============================================================
   RESPONSE MIDTRANS
============================================================ */

$midtransData =
    json_decode(
        $midtransResponse,
        true
    );


/* ============================================================
   HTTP MIDTRANS ERROR
============================================================ */

if (
    $httpCode < 200 ||
    $httpCode >= 300
) {

    $pesanMidtrans =
        "Midtrans menolak transaksi.";


    if (
        is_array(
            $midtransData
        ) &&
        isset(
            $midtransData["error_messages"]
        ) &&
        is_array(
            $midtransData["error_messages"]
        )
    ) {

        $pesanMidtrans =
            implode(
                " ",
                $midtransData["error_messages"]
            );

    }

    elseif (
        is_array(
            $midtransData
        ) &&
        isset(
            $midtransData["status_message"]
        )
    ) {

        $pesanMidtrans =
            $midtransData["status_message"];

    }

    elseif (
        is_array(
            $midtransData
        ) &&
        isset(
            $midtransData["message"]
        )
    ) {

        $pesanMidtrans =
            $midtransData["message"];

    }


    responseJson(
        false,
        $pesanMidtrans,
        [

            "http_code" =>
                $httpCode,

            "midtrans_response" =>
                $midtransData

        ],
        400
    );

}


/* ============================================================
   SNAP TOKEN
============================================================ */

$snapToken =
    "";


if (
    is_array(
        $midtransData
    ) &&
    isset(
        $midtransData["token"]
    )
) {

    $snapToken =
        trim(
            (string)$midtransData["token"]
        );

}


if (
    $snapToken === ""
) {

    responseJson(
        false,
        "Snap Token tidak diberikan oleh Midtrans.",
        [

            "midtrans_response" =>
                $midtransData

        ],
        500
    );

}


/* ============================================================
   SIMPAN TRANSAKSI PENDING
============================================================ */

try {

    $sqlInsert =
        "
        INSERT INTO transaksi
        (
            order_id,
            user_id,
            jenis_produk,
            produk_id,
            nama_produk,
            gross_amount,
            snap_token,
            transaction_status,
            created_at,
            updated_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'pending',
            NOW(),
            NOW()
        )
        ";


    $insert =
        $conn->prepare(
            $sqlInsert
        );


    if (
        !$insert
    ) {

        responseJson(
            false,
            "Gagal menyiapkan penyimpanan transaksi: " .
            $conn->error,
            null,
            500
        );

    }


    $insert->bind_param(
        "sisisds",
        $orderId,
        $userId,
        $jenisProduk,
        $produkId,
        $namaProduk,
        $grossAmount,
        $snapToken
    );


    if (
        !$insert->execute()
    ) {

        $error =
            $insert->error;


        $insert->close();


        responseJson(
            false,
            "Gagal menyimpan transaksi: " .
            $error,
            null,
            500
        );

    }


    $transactionDbId =
        $insert->insert_id;


    $insert->close();

}

catch (
    Throwable $e
) {

    responseJson(
        false,
        "Gagal menyimpan transaksi: " .
        $e->getMessage(),
        null,
        500
    );

}


/* ============================================================
   RESPONSE PEMBAYARAN NORMAL
============================================================ */

responseJson(

    true,

    "Pembayaran berhasil dibuat.",

    [

        "transaction_id" =>
            $transactionDbId,

        "order_id" =>
            $orderId,

        "snap_token" =>
            $snapToken,

        "redirect_url" =>
            "https://app.sandbox.midtrans.com/snap/v2/vtweb/" .
            $snapToken,

        "gross_amount" =>
            $grossAmount,

        "nama_produk" =>
            $namaProduk,

        "jenis_produk" =>
            $jenisProduk,

        "produk_id" =>
            $produkId,

        "is_free" =>
            false,

        "transaction_status" =>
            "pending"

    ],

    200

);

?>