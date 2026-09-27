<?php

header("Content-Type: application/json; charset=UTF-8");

/*
====================================================
KONEKSI DATABASE
====================================================
*/

require_once __DIR__ . "/../config/koneksi.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SESSION["role"] ?? null) !== "admin") {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Akses admin diperlukan"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($conn) || !$conn) {
    echo json_encode([
        "success" => false,
        "message" => "Database tidak terhubung"
    ]);
    exit;
}


/*
====================================================
FILTER PERIODE
====================================================
*/

$period = isset($_GET["period"])
    ? intval($_GET["period"])
    : 7;

$chartOnly = isset($_GET["chart_only"]) && $_GET["chart_only"] == "1";


/*
====================================================
TANGGAL SEKARANG
====================================================
*/

$currentDate = date("Y-m-d");
$currentYear = date("Y");


/*
====================================================
DATA GRAFIK PENJUALAN
====================================================
*/

$chartLabels = [];
$chartValues = [];


if ($period == 7) {
    // 7 hari terakhir
    for ($i = 6; $i >= 0; $i--) {
        $date = date("Y-m-d", strtotime("-{$i} days"));
        $label = date("d M", strtotime($date));
        $chartLabels[] = $label;
        $chartValues[] = 0;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT DATE(created_at) AS tgl, COALESCE(SUM(gross_amount), 0) AS total
         FROM transaksi
         WHERE transaction_status = 'settlement'
         AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
         GROUP BY DATE(created_at)
         ORDER BY tgl ASC"
    );

    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $label = date("d M", strtotime($row["tgl"]));
                $idx = array_search($label, $chartLabels);
                if ($idx !== false) {
                    $chartValues[$idx] = floatval($row["total"]);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }

} else if ($period == 30) {
    // 30 hari terakhir
    for ($i = 29; $i >= 0; $i--) {
        $date = date("Y-m-d", strtotime("-{$i} days"));
        $label = date("d M", strtotime($date));
        $chartLabels[] = $label;
        $chartValues[] = 0;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT DATE(created_at) AS tgl, COALESCE(SUM(gross_amount), 0) AS total
         FROM transaksi
         WHERE transaction_status = 'settlement'
         AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at)
         ORDER BY tgl ASC"
    );

    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $label = date("d M", strtotime($row["tgl"]));
                $idx = array_search($label, $chartLabels);
                if ($idx !== false) {
                    $chartValues[$idx] = floatval($row["total"]);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }

} else if ($period == "month") {
    // Bulan ini - per minggu
    $daysInMonth = date("t");
    $weeks = ceil($daysInMonth / 7);
    for ($w = 0; $w < $weeks; $w++) {
        $startDay = ($w * 7) + 1;
        $endDay = min(($w + 1) * 7, $daysInMonth);
        $label = "Minggu " . ($w + 1);
        $chartLabels[] = $label;
        $chartValues[] = 0;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT DATE(created_at) AS tgl, COALESCE(SUM(gross_amount), 0) AS total
         FROM transaksi
         WHERE transaction_status = 'settlement'
         AND MONTH(created_at) = ?
         AND YEAR(created_at) = ?
         GROUP BY DATE(created_at)
         ORDER BY tgl ASC"
    );

    if ($stmt) {
        $month = date("m");
        $year = date("Y");
        mysqli_stmt_bind_param($stmt, "ii", $month, $year);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $day = date("d", strtotime($row["tgl"]));
                $weekIdx = min(intdiv($day - 1, 7), $weeks - 1);
                if (isset($chartValues[$weekIdx])) {
                    $chartValues[$weekIdx] += floatval($row["total"]);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }

} else {
    // Tahun ini - per bulan
    $months = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
    $chartLabels = $months;
    $chartValues = array_fill(0, 12, 0);

    $stmt = mysqli_prepare(
        $conn,
        "SELECT MONTH(created_at) AS bulan, COALESCE(SUM(gross_amount), 0) AS total
         FROM transaksi
         WHERE transaction_status = 'settlement'
         AND YEAR(created_at) = ?
         GROUP BY MONTH(created_at)
         ORDER BY bulan ASC"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $currentYear);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $bulan = intval($row["bulan"]) - 1;
                if (isset($chartValues[$bulan])) {
                    $chartValues[$bulan] = floatval($row["total"]);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }
}


/*
====================================================
FORMAT CHART DATA
====================================================
*/

$chartDataFormatted = [];
for ($i = 0; $i < count($chartLabels); $i++) {
    $chartDataFormatted[] = [
        "label" => $chartLabels[$i],
        "value" => $chartValues[$i]
    ];
}


/*
====================================================
RESPONSE JSON
====================================================
*/

echo json_encode(
    [
        "success" => true,
        "period" => $period,
        "chart" => $chartDataFormatted
    ],
    JSON_PRETTY_PRINT
);

?>
