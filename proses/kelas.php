<?php

/* ============================================================
   KELAS SAYA - BELAJARYUK
   Menampilkan produk yang sudah dimiliki user
============================================================ */

session_start();

header(
    "Content-Type: application/json; charset=UTF-8"
);


/* ============================================================
   RESPONSE JSON
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
   HANYA GET
============================================================ */

if (
    $_SERVER["REQUEST_METHOD"] !== "GET"
) {

    responseJson(
        false,
        "Method tidak diizinkan.",
        null,
        405
    );

}


/* ============================================================
   AMBIL USER ID DARI SESSION
============================================================ */

$userId =
    null;


/* user_id */

if (
    isset(
        $_SESSION["user_id"]
    ) &&
    $_SESSION["user_id"] !== ""
) {

    $userId =
        (int)$_SESSION["user_id"];

}


/* id_user */

elseif (
    isset(
        $_SESSION["id_user"]
    ) &&
    $_SESSION["id_user"] !== ""
) {

    $userId =
        (int)$_SESSION["id_user"];

}


/* user array */

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
   DATABASE
============================================================ */

/*
   Database BELAJARYUK
   Sesuaikan username/password jika berbeda.
*/

$host =
    "localhost";


$username =
    "root";


$password =
    "";


$database =
    "db_belajaryuk";


$conn =
    new mysqli(
        $host,
        $username,
        $password,
        $database
    );


/* ============================================================
   CEK DATABASE
============================================================ */

if (
    $conn->connect_error
) {

    responseJson(
        false,
        "Koneksi database gagal: " .
        $conn->connect_error,
        null,
        500
    );

}


/* ============================================================
   UTF-8
============================================================ */

$conn->set_charset(
    "utf8mb4"
);


/* ============================================================
   QUERY
============================================================ */

/*
   Hanya transaksi yang sudah selesai:

   settlement
   capture

   Transaksi pending tidak dimasukkan.

   Transaksi expire / cancel / deny juga tidak dimasukkan.

   Jika user membeli course yang sama berkali-kali,
   hanya transaksi terbaru yang diambil.
*/

$sql = "

    SELECT

        t.id,

        t.order_id,

        t.user_id,

        t.jenis_produk,

        t.produk_id,

        t.nama_produk,

        t.gross_amount,

        t.payment_type,

        t.transaction_id,

        t.transaction_status,

        t.transaction_time,

        t.settlement_time,

        t.created_at,

        t.updated_at,


        /* ===========================
           E-LEARNING
        =========================== */

        p.kategori AS p_kategori,

        p.subkategori AS p_subkategori,

        p.deskripsi AS p_deskripsi,

        p.durasi AS p_durasi,

        p.jadwal AS p_jadwal,

        p.gambar AS p_gambar,


        /* ===========================
           BOOTCAMP
        =========================== */

        b.kategori AS b_kategori,

        b.deskripsi AS b_deskripsi,

        b.tanggal_mulai,

        b.tanggal_berakhir,

        b.gambar AS b_gambar


    FROM transaksi t


    /* ==============================
       PRODUCTS
    ============================== */

    LEFT JOIN products p

        ON t.jenis_produk = 'elearning'

        AND t.produk_id = p.id


    /* ==============================
       BOOTCAMP
    ============================== */

    LEFT JOIN bootcamp b

        ON t.jenis_produk = 'bootcamp'

        AND t.produk_id = b.id


    /* ==============================
       AMBIL TRANSAKSI TERBARU
       PER PRODUK
    ============================== */

    INNER JOIN (

        SELECT

            jenis_produk,

            produk_id,

            MAX(id) AS latest_id

        FROM transaksi

        WHERE

            user_id = ?

            AND transaction_status IN (
                'settlement',
                'capture'
            )

        GROUP BY

            jenis_produk,

            produk_id

    ) latest

        ON t.id = latest.latest_id


    WHERE

        t.user_id = ?


    ORDER BY

        t.created_at DESC

";


$stmt =
    $conn->prepare(
        $sql
    );


/* ============================================================
   CEK PREPARE
============================================================ */

if (
    !$stmt
) {

    $error =
        $conn->error;


    $conn->close();


    responseJson(
        false,
        "Gagal menyiapkan query kelas: " .
        $error,
        null,
        500
    );

}


/* ============================================================
   BIND USER ID
============================================================ */

$stmt->bind_param(
    "ii",
    $userId,
    $userId
);


/* ============================================================
   EXECUTE
============================================================ */

if (
    !$stmt->execute()
) {

    $error =
        $stmt->error;


    $stmt->close();

    $conn->close();


    responseJson(
        false,
        "Gagal mengambil kelas: " .
        $error,
        null,
        500
    );

}


/* ============================================================
   RESULT
============================================================ */

$result =
    $stmt->get_result();


$data =
    [];


/* ============================================================
   LOOP DATA
============================================================ */

while (
    $row =
        $result->fetch_assoc()
) {


    /* ========================================================
       DEFAULT
    ======================================================== */

    $jenisProduk =
        strtolower(
            trim(
                (string)(
                    $row["jenis_produk"] ??
                    ""
                )
            )
        );


    $namaProduk =
        trim(
            (string)(
                $row["nama_produk"] ??
                ""
            )
        );


    $kategori =
        "";


    $subkategori =
        "";


    $deskripsi =
        "";


    $gambar =
        "";


    $durasi =
        "";


    $jadwal =
        "";


    $tanggalMulai =
        null;


    $tanggalBerakhir =
        null;


    /* ========================================================
       E-LEARNING
    ======================================================== */

    if (
        $jenisProduk ===
        "elearning"
    ) {

        $kategori =
            trim(
                (string)(
                    $row["p_kategori"] ??
                    "E-Learning"
                )
            );


        $subkategori =
            trim(
                (string)(
                    $row["p_subkategori"] ??
                    ""
                )
            );


        $deskripsi =
            trim(
                (string)(
                    $row["p_deskripsi"] ??
                    ""
                )
            );


        $gambar =
            trim(
                (string)(
                    $row["p_gambar"] ??
                    ""
                )
            );


        $durasi =
            trim(
                (string)(
                    $row["p_durasi"] ??
                    ""
                )
            );


        $jadwal =
            trim(
                (string)(
                    $row["p_jadwal"] ??
                    ""
                )
            );

    }


    /* ========================================================
       BOOTCAMP
    ======================================================== */

    elseif (
        $jenisProduk ===
        "bootcamp"
    ) {

        $kategori =
            trim(
                (string)(
                    $row["b_kategori"] ??
                    "Bootcamp"
                )
            );


        $deskripsi =
            trim(
                (string)(
                    $row["b_deskripsi"] ??
                    ""
                )
            );


        $gambar =
            trim(
                (string)(
                    $row["b_gambar"] ??
                    ""
                )
            );


        $tanggalMulai =
            $row["tanggal_mulai"] ??
            null;


        $tanggalBerakhir =
            $row["tanggal_berakhir"] ??
            null;

    }


    /* ========================================================
       FALLBACK DESKRIPSI
    ======================================================== */

    if (
        $deskripsi === ""
    ) {

        $deskripsi =
            $jenisProduk ===
            "elearning"

                ? "Course pembelajaran digital Belajaryuk."

                : "Program bootcamp Belajaryuk.";

    }


    /* ========================================================
       FALLBACK KATEGORI
    ======================================================== */

    if (
        $kategori === ""
    ) {

        $kategori =
            $jenisProduk ===
            "elearning"

                ? "E-Learning"

                : "Bootcamp";

    }


    /* ========================================================
       PUSH DATA
    ======================================================== */

    $data[] = [

        "id" =>
            (int)$row["id"],


        "order_id" =>
            $row["order_id"],


        "user_id" =>
            (int)$row["user_id"],


        "jenis_produk" =>
            $jenisProduk,


        "produk_id" =>
            (int)$row["produk_id"],


        "nama_produk" =>
            $namaProduk,


        "gross_amount" =>
            (float)(
                $row["gross_amount"] ??
                0
            ),


        "payment_type" =>
            $row["payment_type"],


        "transaction_id" =>
            $row["transaction_id"],


        "transaction_status" =>
            $row["transaction_status"],


        "transaction_time" =>
            $row["transaction_time"],


        "settlement_time" =>
            $row["settlement_time"],


        "created_at" =>
            $row["created_at"],


        "updated_at" =>
            $row["updated_at"],


        "kategori" =>
            $kategori,


        "subkategori" =>
            $subkategori,


        "deskripsi" =>
            $deskripsi,


        "gambar" =>
            $gambar,


        "durasi" =>
            $durasi,


        "jadwal" =>
            $jadwal,


        "tanggal_mulai" =>
            $tanggalMulai,


        "tanggal_berakhir" =>
            $tanggalBerakhir,


        "sudah_beli" =>
            true

    ];

}


/* ============================================================
   CLOSE
============================================================ */

$stmt->close();

$conn->close();


/* ============================================================
   RESPONSE
============================================================ */

responseJson(

    true,

    "Data Kelas Saya berhasil diambil.",

    $data,

    200

);

?>