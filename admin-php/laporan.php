<?php

header("Content-Type: application/json; charset=UTF-8");

/*
====================================================
KONEKSI DATABASE
====================================================
*/

require_once __DIR__ . "/../config/koneksi.php";


/*
====================================================
CEK KONEKSI DATABASE
====================================================
*/

if (!isset($conn) || !$conn) {
    echo json_encode([
        "success" => false,
        "message" => "Database tidak terhubung"
    ]);
    exit;
}


/*
====================================================
SESSION CHECK
====================================================
*/

session_start();

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["role"])
) {
    echo json_encode([
        "success" => false,
        "message" => "Anda belum login"
    ]);
    exit;
}

if ($_SESSION["role"] !== "admin") {
    echo json_encode([
        "success" => false,
        "message" => "Akses ditolak. Hanya admin."
    ]);
    exit;
}

require_once __DIR__ . "/report_data.php";
$report = adminLoadReport($conn, $_GET);
if ($report === null) {
    http_response_code(422);
    echo json_encode(["success" => false, "message" => "Filter laporan tidak valid."], JSON_UNESCAPED_UNICODE);
    exit;
}
$formattedTransactions = [];
foreach ($report["transactions"] as $transaction) {
    $status = strtolower($transaction["transaction_status"]);
    $statusLabels = ["settlement" => "Berhasil", "pending" => "Pending", "expire" => "Expired", "cancel" => "Dibatalkan", "deny" => "Ditolak"];
    $formattedTransactions[] = [
        "id" => (int)$transaction["id"],
        "tanggal" => (new DateTimeImmutable($transaction["transaction_date"]))->format("d M Y"),
        "tanggal_raw" => $transaction["transaction_date"],
        "pembeli" => $transaction["user_name"],
        "email" => $transaction["user_email"],
        "produk" => $transaction["nama_produk"],
        "kategori" => $transaction["category"],
        "jenis" => $transaction["jenis_produk"] === "elearning" ? "E-Learning" : "Bootcamp",
        "harga" => (float)$transaction["gross_amount"],
        "status" => $statusLabels[$status] ?? ucfirst($status),
        "status_raw" => $status,
        "payment_type" => $transaction["payment_type"] ?: "-",
        "order_id" => $transaction["order_id"]
    ];
}
echo json_encode([
    "success" => true,
    "message" => "Data laporan berhasil dimuat",
    "filters" => ["periode" => $report["filters"]["range"], "jenis" => $report["filters"]["type"], "tgl_mulai" => $report["filters"]["start"], "tgl_akhir" => $report["filters"]["end"]],
    "summary" => ["total_transaksi" => $report["summary"]["total_transactions"], "total_elearning" => $report["summary"]["settled_elearning"], "total_bootcamp" => $report["summary"]["settled_bootcamp"], "total_pendapatan" => $report["summary"]["income"], "total_pengeluaran" => $report["summary"]["expenses"], "profit_bersih" => $report["summary"]["profit"]],
    "data" => $formattedTransactions
], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
exit;
