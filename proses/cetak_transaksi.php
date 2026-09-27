<?php

// ============================================================
// CETAK TRANSAKSI
// BELAJARYUK
// ============================================================

session_start();

require_once __DIR__ . "/midtrans_config.php";


// ============================================================
// AMBIL USER ID
// ============================================================

$userId = 0;

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
// CEK LOGIN
// ============================================================

if (
    $userId <= 0
) {

    die(
        "Kamu harus login untuk melihat transaksi."
    );

}


// ============================================================
// DATA USER
// ============================================================

$namaUser = "-";
$emailUser = "-";


if (
    isset($_SESSION["nama"]) &&
    $_SESSION["nama"]
) {

    $namaUser =
        $_SESSION["nama"];

}

elseif (
    isset($_SESSION["nama_user"]) &&
    $_SESSION["nama_user"]
) {

    $namaUser =
        $_SESSION["nama_user"];

}

elseif (
    isset($_SESSION["user"]) &&
    is_array($_SESSION["user"]) &&
    isset($_SESSION["user"]["nama"])
) {

    $namaUser =
        $_SESSION["user"]["nama"];

}


if (
    isset($_SESSION["email"]) &&
    $_SESSION["email"]
) {

    $emailUser =
        $_SESSION["email"];

}

elseif (
    isset($_SESSION["user_email"]) &&
    $_SESSION["user_email"]
) {

    $emailUser =
        $_SESSION["user_email"];

}

elseif (
    isset($_SESSION["user"]) &&
    is_array($_SESSION["user"]) &&
    isset($_SESSION["user"]["email"])
) {

    $emailUser =
        $_SESSION["user"]["email"];

}


// ============================================================
// ORDER ID
// ============================================================

$orderId =
    trim(
        $_GET["order_id"] ??
        ""
    );


if (
    $orderId === ""
) {

    die(
        "Kode transaksi tidak ditemukan."
    );

}


// ============================================================
// AMBIL TRANSAKSI
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
        transaction_time,
        settlement_time,
        created_at
    FROM transaksi
    WHERE order_id = ?
      AND user_id = ?
    LIMIT 1
";


$stmt =
    $conn->prepare(
        $sql
    );


if (
    !$stmt
) {

    die(
        "Query transaksi gagal."
    );

}


$stmt->bind_param(
    "si",
    $orderId,
    $userId
);


$stmt->execute();


$result =
    $stmt->get_result();


$transaction =
    $result->fetch_assoc();


$stmt->close();


// ============================================================
// CEK TRANSAKSI
// ============================================================

if (
    !$transaction
) {

    die(
        "Transaksi tidak ditemukan."
    );

}


// ============================================================
// HANYA TRANSAKSI BERHASIL YANG BISA DICETAK
// ============================================================

if (
    strtolower(
        $transaction["transaction_status"]
    ) !== "settlement"
) {

    die(
        "Bukti transaksi hanya tersedia untuk pembayaran yang berhasil."
    );

}


// ============================================================
// FORMAT HARGA
// ============================================================

function formatRupiah(
    $value
) {

    return "Rp " .
        number_format(
            (float) $value,
            0,
            ",",
            "."
        );

}


// ============================================================
// FORMAT TANGGAL
// ============================================================

function formatTanggal(
    $value
) {

    if (
        !$value
    ) {

        return "-";

    }


    $timestamp =
        strtotime(
            $value
        );


    if (
        $timestamp === false
    ) {

        return $value;

    }


    return date(
        "d F Y H:i",
        $timestamp
    );

}


// ============================================================
// JENIS PRODUK
// ============================================================

$jenisProduk =
    strtolower(
        $transaction["jenis_produk"]
    );


if (
    $jenisProduk ===
    "bootcamp"
) {

    $jenisLabel =
        "Bootcamp";

}

else {

    $jenisLabel =
        "E-Learning";

}


// ============================================================
// ESCAPE
// ============================================================

function e(
    $value
) {

    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );

}

?>

<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Bukti Transaksi -
        <?= e($transaction["order_id"]) ?>
    </title>


    <style>

        * {
            box-sizing:border-box;
        }


        body {

            margin:0;

            padding:30px;

            background:#f1f5f9;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            color:#0f172a;

        }


        .receipt {

            width:100%;

            max-width:700px;

            margin:0 auto;

            background:#ffffff;

            padding:40px;

            border-radius:16px;

            box-shadow:
                0 10px 30px
                rgba(15,23,42,.08);

        }


        .brand {

            text-align:center;

            margin-bottom:6px;

            font-size:25px;

            font-weight:800;

            color:#4338ca;

        }


        .title {

            text-align:center;

            font-size:20px;

            font-weight:700;

            margin-bottom:5px;

        }


        .subtitle {

            text-align:center;

            color:#64748b;

            font-size:13px;

            margin-bottom:30px;

        }


        .status {

            text-align:center;

            margin-bottom:28px;

        }


        .status span {

            display:inline-block;

            padding:8px 16px;

            border-radius:999px;

            background:#dcfce7;

            color:#15803d;

            font-size:12px;

            font-weight:800;

        }


        .info {

            border-top:
                1px solid #e2e8f0;

            border-bottom:
                1px solid #e2e8f0;

            padding:20px 0;

        }


        .row {

            display:flex;

            justify-content:
                space-between;

            gap:20px;

            padding:9px 0;

            font-size:13px;

        }


        .label {

            color:#64748b;

        }


        .value {

            text-align:right;

            font-weight:600;

            max-width:60%;

        }


        .total {

            display:flex;

            justify-content:
                space-between;

            align-items:center;

            margin-top:20px;

            padding-top:20px;

            border-top:
                1px dashed #cbd5e1;

        }


        .total-label {

            color:#64748b;

            font-size:13px;

        }


        .total-value {

            font-size:21px;

            font-weight:800;

            color:#2563eb;

        }


        .code-box {

            margin-top:25px;

            padding:16px;

            background:#f8fafc;

            border:
                1px solid #e2e8f0;

            border-radius:12px;

            text-align:center;

        }


        .code-label {

            font-size:11px;

            color:#64748b;

            margin-bottom:7px;

        }


        .code {

            font-size:16px;

            font-weight:800;

            letter-spacing:.5px;

            color:#4338ca;

            word-break:break-all;

        }


        .footer {

            text-align:center;

            margin-top:30px;

            color:#64748b;

            font-size:11px;

            line-height:1.6;

        }


        .print-button {

            display:block;

            margin:25px auto 0;

            border:none;

            background:#4338ca;

            color:white;

            padding:11px 20px;

            border-radius:999px;

            cursor:pointer;

            font-size:13px;

            font-weight:700;

        }


        @media print {

            body {

                background:
                    #ffffff;

                padding:0;

            }


            .receipt {

                max-width:none;

                box-shadow:none;

                border-radius:0;

                padding:20px;

            }


            .print-button {

                display:none;

            }

        }


        @media (max-width:600px) {

            body {

                padding:10px;

            }


            .receipt {

                padding:25px 20px;

            }


            .row {

                font-size:12px;

            }


            .value {

                max-width:55%;

            }

        }

    </style>

</head>


<body>

    <div class="receipt">


        <div class="brand">
            BELAJARYUK
        </div>


        <div class="title">
            Bukti Transaksi
        </div>


        <div class="subtitle">
            Bukti pembayaran resmi
        </div>


        <div class="status">

            <span>
                PEMBAYARAN BERHASIL
            </span>

        </div>


        <div class="info">


            <div class="row">

                <div class="label">
                    Nama
                </div>

                <div class="value">
                    <?= e($namaUser) ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Email
                </div>

                <div class="value">
                    <?= e($emailUser) ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Produk
                </div>

                <div class="value">
                    <?= e($transaction["nama_produk"]) ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Jenis Produk
                </div>

                <div class="value">
                    <?= e($jenisLabel) ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Metode Pembayaran
                </div>

                <div class="value">
                    <?= e($transaction["payment_type"] ?: "-") ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Transaction ID Midtrans
                </div>

                <div class="value">
                    <?= e($transaction["transaction_id"] ?: "-") ?>
                </div>

            </div>


            <div class="row">

                <div class="label">
                    Tanggal Pembayaran
                </div>

                <div class="value">
                    <?= e(formatTanggal(
                        $transaction["settlement_time"] ||
                        $transaction["transaction_time"] ||
                        $transaction["created_at"]
                    )) ?>
                </div>

            </div>


            <div class="total">

                <div class="total-label">
                    Total Pembayaran
                </div>


                <div class="total-value">
                    <?= e(
                        formatRupiah(
                            $transaction["gross_amount"]
                        )
                    ) ?>
                </div>

            </div>

        </div>


        <div class="code-box">

            <div class="code-label">
                KODE TRANSAKSI
            </div>


            <div class="code">
                <?= e($transaction["order_id"]) ?>
            </div>

        </div>


        <div class="footer">

            Simpan bukti transaksi ini sebagai bukti pembayaran kamu.<br>

            BELAJARYUK

        </div>


        <button
            class="print-button"
            onclick="window.print()"
        >

            🖨️ Cetak Transaksi

        </button>


    </div>

</body>

</html>