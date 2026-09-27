<?php
require_once __DIR__ . "/../config/koneksi.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (($_SESSION['role'] ?? null) !== 'admin') { http_response_code(401); exit('Akses admin diperlukan.'); }
require_once __DIR__ . '/report_data.php';

$report = adminLoadReport($conn, $_GET);
if ($report === null) { http_response_code(422); exit('Filter laporan tidak valid.'); }

$mode     = ($_GET['mode'] ?? 'sales') === 'finance' ? 'finance' : 'sales';
$format   = strtolower($_GET['format'] ?? 'csv');
$period   = adminReportPeriodLabel($report);
$financial = ($mode === 'finance');
$statusLabels = ['settlement' => 'Berhasil', 'pending' => 'Pending', 'expire' => 'Expired', 'cancel' => 'Dibatalkan', 'deny' => 'Ditolak'];
$records = [];
$headers = [];

if ($financial) {
    foreach (adminFinanceRows($report) as $row) {
        $records[] = [$row['type'], $row['date'], $row['user'], $row['email'], $row['product'], $row['category'], $row['amount'], $row['payment'], $row['status']];
    }
    $headers = ['Jenis Catatan', 'Tanggal', 'User', 'Email', 'Produk / Deskripsi', 'Kategori', 'Nominal', 'Metode Pembayaran', 'Status'];
} else {
    foreach ($report['transactions'] as $row) {
        $status = strtolower($row['transaction_status']);
        $records[] = [
            (int)$row['id'],
            (new DateTimeImmutable($row['transaction_date']))->format('d M Y'),
            $row['user_name'],
            $row['user_email'],
            $row['nama_produk'],
            $row['category'],
            $row['jenis_produk'] === 'elearning' ? 'E-Learning' : 'Bootcamp',
            (float)$row['gross_amount'],
            $row['payment_type'] ?: '-',
            $statusLabels[$status] ?? ucfirst($status),
        ];
    }
    $headers = ['ID', 'Tanggal', 'Pembeli', 'Email', 'Produk', 'Kategori', 'Jenis', 'Harga', 'Metode Pembayaran', 'Status'];
}

$summary = $report['summary'];
$filename = 'belajaryuk-' . ($financial ? 'rekap-keuangan' : 'laporan-penjualan') . '-' . date('Ymd-His');

/* ──────────── helpers ──────────── */
function esc($v) { return htmlspecialchars((string)$v, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }
function pdfStr($v) {
    $v = (string)$v;
    if (function_exists('iconv')) { $c = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $v); if ($c !== false) $v = $c; }
    $v = preg_replace('/[^\x20-\xFF]/', '?', $v);
    return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $v);
}
function fmtRupiah($v) { return number_format((float)$v, 0, ',', '.'); }
function xlsxCol($n) { $s = ''; while ($n > 0) { $n--; $s = chr(65 + ($n % 26)) . $s; $n = intdiv($n, 26); } return $s; }

/* ──────────── CSV ──────────── */
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['BELAJARYUK', $financial ? 'LAPORAN KEUANGAN' : 'LAPORAN PENJUALAN']);
    fputcsv($out, ['Periode', $period]);
    fputcsv($out, ['Total transaksi', $summary['total_transactions']]);
    if (!$financial) {
        fputcsv($out, ['E-Learning terjual', $summary['settled_elearning']]);
        fputcsv($out, ['Bootcamp terjual', $summary['settled_bootcamp']]);
    }
    fputcsv($out, ['Total pemasukan', $summary['income']]);
    fputcsv($out, ['Total pengeluaran', $summary['expenses']]);
    fputcsv($out, ['Profit bersih', $summary['profit']]);
    fputcsv($out, []);
    fputcsv($out, array_merge(['No'], $headers));
    foreach ($records as $i => $r) {
        $num = array_map(function ($v) { return is_float($v) || (is_int($v) && $v > 100) ? (string)(int)$v : $v; }, $r);
        fputcsv($out, array_merge([$i + 1], $num));
    }
    fclose($out);
    exit;
}

/* ──────────── PDF ──────────── */
if ($format === 'pdf') {
    $pdf = buildPdf($report, $headers, $records, $period, $financial, $summary);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

/* ──────────── XLSX ──────────── */
if ($format === 'xlsx') {
    $xlsx = buildXlsx($report, $headers, $records, $period, $financial, $summary);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Content-Length: ' . strlen($xlsx));
    echo $xlsx;
    exit;
}

/* ──────────── PRINT ──────────── */
if ($format === 'print') {
    header('Content-Type: text/html; charset=UTF-8');
    $elearningCount = $summary['settled_elearning'] ?? 0;
    $bootcampCount  = $summary['settled_bootcamp']  ?? 0;
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><title>Laporan BelajarYuk</title>';
    echo '<style>body{font:12px Arial;color:#111;margin:24px}h1,h2,p{text-align:center;margin:4px}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #aaa;padding:6px;text-align:left;font-size:10px}th{background:#eee}.summary{display:flex;justify-content:space-between;flex-wrap:wrap;margin:18px 0;border:1px solid #aaa;padding:10px;gap:8px}.no-print{margin-bottom:16px}@media print{.no-print{display:none}body{margin:12mm}}</style>';
    echo '<button class="no-print" onclick="window.print()">Print</button>';
    echo '<h1>BELAJARYUK</h1>';
    echo '<h2>' . ($financial ? 'LAPORAN KEUANGAN' : 'LAPORAN PENJUALAN') . '</h2>';
    echo '<p>Periode: ' . esc($period) . '</p>';
    echo '<div class="summary">';
    echo '<span>Total transaksi: ' . $summary['total_transactions'] . '</span>';
    if (!$financial) {
        echo '<span>E-Learning: ' . $elearningCount . '</span>';
        echo '<span>Bootcamp: ' . $bootcampCount . '</span>';
    }
    echo '<span>Pemasukan: Rp ' . fmtRupiah($summary['income']) . '</span>';
    echo '<span>Pengeluaran: Rp ' . fmtRupiah($summary['expenses']) . '</span>';
    echo '<span>Profit: Rp ' . fmtRupiah($summary['profit']) . '</span>';
    echo '</div>';
    echo '<table><thead><tr><th>No</th>';
    foreach ($headers as $h) echo '<th>' . esc($h) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($records as $i => $r) {
        echo '<tr><td>' . ($i + 1) . '</td>';
        foreach ($r as $v) {
            $display = (is_float($v) || (is_int($v) && $v > 100)) ? 'Rp ' . fmtRupiah($v) : esc((string)$v);
            echo '<td>' . $display . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<script>window.addEventListener("load",function(){window.print()})</script></html>';
    exit;
}

http_response_code(400);
exit('Format export tidak didukung.');


/* ══════════════════════════════════════════════════════════════
   PDF builder — A4 landscape, table borders, repeat headers
   ══════════════════════════════════════════════════════════════ */
function buildPdf(array $report, array $headers, array $records, $period, $financial, array $summary)
{
    $colCount  = count($headers);
    $pageW     = 842;
    $pageH     = 595;
    $marginLeft   = 36;
    $marginRight  = 36;
    $marginTop    = 36;
    $marginBottom = 36;
    $usableW = $pageW - $marginLeft - $marginRight;

    /* Column widths: proportional to fit A4 landscape
       ID:7%, Tanggal:11%, Pembeli:14%, Email:16%, Produk:16%, Kategori:8%, Jenis:8%, Harga:10%, Metode:6%, Status:4% */
    $colPct  = $financial
        ? [12, 12, 14, 16, 16, 10, 8, 8, 4]
        : [6, 11, 14, 16, 16, 8, 8, 10, 6, 5];
    $colW = [];
    foreach ($colPct as $p) $colW[] = (int)($usableW * $p / 100);
    $colX = [$marginLeft];
    for ($i = 1; $i < count($colW); $i++) $colX[] = $colX[$i - 1] + $colW[$i - 1];

    $objects = [];
    $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
    $pageRefs = [];

    $rowsPerPage = 30;
    $pages = array_chunk($records, $rowsPerPage);
    if (!$pages) $pages = [[]];

    $elearningCount = $summary['settled_elearning'] ?? 0;
    $bootcampCount  = $summary['settled_bootcamp']  ?? 0;

    foreach ($pages as $pgIdx => $pageRows) {
        $pageObj     = 4 + $pgIdx * 2;
        $contentObj  = $pageObj + 1;
        $pageRefs[]  = $pageObj . ' 0 R';

        $content = '';

        /* ── Header block (on every page) ── */
        $y = $pageH - $marginTop;
        $content .= "BT /F1 14 Tf {$marginLeft} {$y} Td (" . pdfStr('BELAJARYUK') . ") Tj ET\n";
        $y -= 18;
        $content .= "BT /F1 9 Tf {$marginLeft} {$y} Td (" . pdfStr($financial ? 'LAPORAN KEUANGAN' : 'LAPORAN PENJUALAN') . ") Tj ET\n";
        $y -= 13;
        $content .= "BT /F1 7 Tf {$marginLeft} {$y} Td (" . pdfStr('Periode: ' . $period) . ") Tj ET\n";
        $y -= 13;
        $content .= "BT /F1 7 Tf {$marginLeft} {$y} Td (" . pdfStr('Dicetak: ' . date('d M Y H:i')) . ") Tj ET\n";
        $y -= 16;

        /* ── Summary block (first page only) ── */
        if ($pgIdx === 0) {
            $content .= "BT /F1 7 Tf {$marginLeft} {$y} Td (" . pdfStr('Total transaksi: ' . $summary['total_transactions']) . ") Tj ET\n";
            $y -= 11;
            if (!$financial) {
                $content .= "BT /F1 7 Tf {$marginLeft} {$y} Td (" . pdfStr('E-Learning terjual: ' . $elearningCount . '    Bootcamp terjual: ' . $bootcampCount) . ") Tj ET\n";
                $y -= 11;
            }
            $content .= "BT /F1 7 Tf {$marginLeft} {$y} Td (" . pdfStr('Pemasukan: Rp ' . fmtRupiah($summary['income']) . '    Pengeluaran: Rp ' . fmtRupiah($summary['expenses']) . '    Profit: Rp ' . fmtRupiah($summary['profit'])) . ") Tj ET\n";
            $y -= 16;
        }

        /* ── Table header ── */
        $content .= "0.6 w {$colX[0]} {$y} m " . ($colX[0] + $usableW) . " {$y} l S\n";
        $thY = $y - 4;
        $content .= "BT /F1 6.5 Tf ";
        foreach ($headers as $ci => $hdr) {
            $cx = $colX[$ci] + 3;
            $content .= "1 0 0 1 {$cx} {$thY} Tm (" . pdfStr($hdr) . ") Tj ";
        }
        $content .= "ET\n";
        $content .= "0.6 w {$colX[0]} {$y} m " . ($colX[0] + $usableW) . " {$y} l S\n";
        $y -= 12;
        $content .= "0.6 w {$colX[0]} {$y} m " . ($colX[0] + $usableW) . " {$y} l S\n";
        $y -= 2;

        /* ── Data rows ── */
        foreach ($pageRows as $ri => $row) {
            $globalRow = $pgIdx * $rowsPerPage + $ri + 1;
            $lineY = $y;
            $content .= "BT /F1 6 Tf ";
            $cellData = array_merge([$globalRow], $row);
            for ($ci = 0; $ci < min(count($cellData), count($colX)); $ci++) {
                $val = $cellData[$ci];
                if ($ci === 0 || $ci === 7) {
                    $display = is_numeric($val) ? 'Rp ' . fmtRupiah($val) : (string)$val;
                } else {
                    $display = (string)$val;
                }
                $cx = $colX[$ci] + 3;
                $content .= "1 0 0 1 {$cx} {$lineY} Tm (" . pdfStr(mb_substr($display, 0, 26)) . ") Tj ";
            }
            $content .= "ET\n";
            $y -= 3;
            $content .= "0.25 w {$colX[0]} {$y} m " . ($colX[0] + $usableW) . " {$y} l S\n";
            $y -= 10;
        }

        /* ── Page footer ── */
        $content .= "BT /F1 7 Tf " . ($pageW - $marginRight - 60) . " {$marginBottom} Td (" . pdfStr('Hal. ' . ($pgIdx + 1) . ' dari ' . count($pages)) . ") Tj ET\n";

        /* ── Vertical column separators ── */
        $tableTop = $pageH - $marginTop - ($pgIdx === 0 ? 70 : 50);
        $tableBottom = max($y + 4, $marginBottom + 14);
        $content .= "0.25 w ";
        for ($ci = 1; $ci < count($colX); $ci++) {
            $content .= "{$colX[$ci]} {$tableTop} m {$colX[$ci]} {$tableBottom} l S ";
        }
        $content .= "\n";

        /* ── Outer border ── */
        $content .= "0.5 w {$colX[0]} {$tableTop} m " . ($colX[0] + $usableW) . " {$tableTop} l S\n";
        $content .= "0.5 w {$colX[0]} {$tableBottom} m " . ($colX[0] + $usableW) . " {$tableBottom} l S\n";

        $objects[$pageObj]    = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $pageW . ' ' . $pageH . '] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentObj . ' 0 R >>';
        $objects[$contentObj] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
    }

    $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pages) . ' >>';
    $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

    ksort($objects);
    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $id => $obj) {
        $offsets[$id] = strlen($pdf);
        $pdf .= $id . " 0 obj\n" . $obj . "\nendobj\n";
    }
    $xref = strlen($pdf);
    $max = max(array_keys($objects));
    $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
    for ($i = 1; $i <= $max; $i++) $pdf .= sprintf('%010d 00000 n ', $offsets[$i] ?? 0) . "\n";
    $pdf .= 'trailer << /Size ' . ($max + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
    return $pdf;
}


/* ══════════════════════════════════════════════════════════════
   XLSX builder — styles.xml (bold, borders, colors, number fmt)
   ══════════════════════════════════════════════════════════════ */
function buildXlsx(array $report, array $headers, array $records, $period, $financial, array $summary)
{
    $elearningCount = $summary['settled_elearning'] ?? 0;
    $bootcampCount  = $summary['settled_bootcamp']  ?? 0;

    /* ── Summary rows ── */
    $rows = [];
    $rows[] = ['r' => 'BELAJARYUK',        's' => 'title'];
    $rows[] = ['r' => ($financial ? 'LAPORAN KEUANGAN' : 'LAPORAN PENJUALAN'), 's' => 'subtitle'];
    $rows[] = ['r' => ['Periode', $period], 's' => 'kv'];
    $rows[] = ['r' => ['Total transaksi', $summary['total_transactions']], 's' => 'kv'];
    if (!$financial) {
        $rows[] = ['r' => ['E-Learning terjual', $elearningCount], 's' => 'kv'];
        $rows[] = ['r' => ['Bootcamp terjual', $bootcampCount], 's' => 'kv'];
    }
    $rows[] = ['r' => ['Total pemasukan', $summary['income']], 's' => 'kv'];
    $rows[] = ['r' => ['Total pengeluaran', $summary['expenses']], 's' => 'kv'];
    $rows[] = ['r' => ['Profit bersih', $summary['profit']], 's' => 'kv'];
    $rows[] = ['r' => [], 's' => ''];
    $headerRow = ['r' => array_merge(['No'], $headers), 's' => 'header'];
    $rows[] = $headerRow;

    /* ── Data rows ── */
    foreach ($records as $i => $r) {
        $rows[] = ['r' => array_merge([$i + 1], $r), 's' => 'data'];
    }

    /* ── XML build ── */
    $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $sheet .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $sheet .= '<sheetViews><sheetView tabSelected="1" workbookViewId="0"><pane ySplit="12" topLeftCell="A13" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>';
    $sheet .= '<cols>';
    $colWidths = $financial ? [10, 14, 16, 22, 22, 14, 10, 10, 16] : [8, 14, 16, 22, 22, 12, 12, 14, 14, 14];
    foreach ($colWidths as $i => $w) {
        $sheet .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
    }
    $sheet .= '</cols>';
    $sheet .= '<sheetData>';

    $rowNum = 1;
    foreach ($rows as $rowEntry) {
        $r   = $rowEntry['r'];
        $sty = $rowEntry['s'];
        if (is_string($r) && $r !== '') {
            /* title / subtitle */
            $sheet .= '<row r="' . $rowNum . '">';
            $sheet .= '<c r="A' . $rowNum . '" t="inlineStr" s="' . ($sty === 'title' ? '1' : '2') . '"><is><t>' . esc($r) . '</t></is></c>';
            $sheet .= '</row>';
            $rowNum++;
            continue;
        }
        if (is_array($r) && empty($r)) { $rowNum++; continue; }
        if (is_array($r)) {
            $sheet .= '<row r="' . $rowNum . '">';
            foreach ($r as $ci => $val) {
                $cellRef = xlsxCol($ci + 1) . $rowNum;
                if ($sty === 'kv') {
                    if ($ci === 0) {
                        $sheet .= '<c r="' . $cellRef . '" t="inlineStr" s="3"><is><t>' . esc($val) . '</t></is></c>';
                    } else {
                        if (is_numeric($val)) {
                            $sheet .= '<c r="' . $cellRef . '" s="4"><v>' . (0 + $val) . '</v></c>';
                        } else {
                            $sheet .= '<c r="' . $cellRef . '" t="inlineStr" s="3"><is><t>' . esc($val) . '</t></is></c>';
                        }
                    }
                } elseif ($sty === 'header') {
                    $sheet .= '<c r="' . $cellRef . '" t="inlineStr" s="5"><is><t>' . esc($val) . '</t></is></c>';
                } elseif ($sty === 'data') {
                    /* Harga column index in data = 7 for sales, 6 for finance */
                    $hargaCol = $financial ? 6 : 7;
                    if ($ci === $hargaCol && is_numeric($val)) {
                        $sheet .= '<c r="' . $cellRef . '" s="6"><v>' . (0 + $val) . '</v></c>';
                    } elseif (is_numeric($val) && !is_string($val)) {
                        $sheet .= '<c r="' . $cellRef . '" s="0"><v>' . (0 + $val) . '</v></c>';
                    } else {
                        $sheet .= '<c r="' . $cellRef . '" t="inlineStr" s="0"><is><t xml:space="preserve">' . esc($val) . '</t></is></c>';
                    }
                } else {
                    if (is_numeric($val)) {
                        $sheet .= '<c r="' . $cellRef . '" s="3"><v>' . (0 + $val) . '</v></c>';
                    } else {
                        $sheet .= '<c r="' . $cellRef . '" t="inlineStr" s="3"><is><t>' . esc($val) . '</t></is></c>';
                    }
                }
            }
            $sheet .= '</row>';
            $rowNum++;
            continue;
        }
        $rowNum++;
    }

    $sheet .= '</sheetData>';
    $sheet .= '<autoFilter ref="A12:' . xlsxCol(count($headers) + 1) . ($rowNum - 1) . '"/>';
    $sheet .= '</worksheet>';

    /* ── styles.xml ── */
    $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $styles .= '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
    $styles .= '<fonts count="3">';
    $styles .= '<font><sz val="10"/><name val="Calibri"/></font>';
    $styles .= '<font><b/><sz val="14"/><name val="Calibri"/></font>';
    $styles .= '<font><b/><sz val="12"/><name val="Calibri"/></font>';
    $styles .= '</fonts>';
    $styles .= '<fills count="4">';
    $styles .= '<fill><patternFill patternType="none"/></fill>';
    $styles .= '<fill><patternFill patternType="gray125"/></fill>';
    $styles .= '<fill><patternFill patternType="solid"><fgColor rgb="FF4338CA"/></patternFill></fill>';
    $styles .= '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>';
    $styles .= '</fills>';
    $styles .= '<borders count="2">';
    $styles .= '<border><left/><right/><top/><bottom/><diagonal/></border>';
    $styles .= '<border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border>';
    $styles .= '</borders>';
    $styles .= '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>';
    $styles .= '<cellXfs count="7">';
    $styles .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>';
    $styles .= '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>';
    $styles .= '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>';
    $styles .= '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyBorder="1"/>';
    $styles .= '<xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>';
    $styles .= '<xf numFmtId="0" fontId="0" fillId="2" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>';
    $styles .= '<xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1" applyAlignment="1"><alignment horizontal="right"/></xf>';
    $styles .= '</cellXfs>';
    $styles .= '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>';
    $styles .= '</styleSheet>';

    /* ── content types ── */
    $ct = '<?xml version="1.0" encoding="UTF-8"?>';
    $ct .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
    $ct .= '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
    $ct .= '<Default Extension="xml" ContentType="application/xml"/>';
    $ct .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
    $ct .= '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
    $ct .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
    $ct .= '</Types>';

    /* ── workbook.xml ── */
    $wb = '<?xml version="1.0" encoding="UTF-8"?>';
    $wb .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $wb .= '<sheets><sheet name="Laporan" sheetId="1" r:id="rId1"/></sheets>';
    $wb .= '</workbook>';

    /* ── rels ── */
    $rels = '<?xml version="1.0" encoding="UTF-8"?>';
    $rels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $rels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
    $rels .= '</Relationships>';

    $wbRels = '<?xml version="1.0" encoding="UTF-8"?>';
    $wbRels .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
    $wbRels .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>';
    $wbRels .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
    $wbRels .= '</Relationships>';

    /* ── ZIP ── */
    $files = [
        '[Content_Types].xml'       => $ct,
        '_rels/.rels'               => $rels,
        'xl/workbook.xml'           => $wb,
        'xl/_rels/workbook.xml.rels'=> $wbRels,
        'xl/worksheets/sheet1.xml'  => $sheet,
        'xl/styles.xml'             => $styles,
    ];

    return xlsxZip($files);
}

/* ── ZIP builder (no external libs) ── */
function xlsxZip(array $files)
{
    $body = '';
    $directory = '';
    $offset = 0;
    $dosTime = (int)(date('G') << 11 | date('i') << 5 | (int)(date('s') / 2));
    $dosDate = (int)((date('Y') - 1980) << 9 | date('n') << 5 | date('j'));

    foreach ($files as $name => $data) {
        $compressed  = gzdeflate($data, 6);
        $crc         = crc32($data);
        $compSize    = strlen($compressed);
        $uncompSize  = strlen($data);
        $nameBytes   = $name;

        $local  = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 8, $dosTime, $dosDate, $crc, $compSize, $uncompSize, strlen($nameBytes), 0);
        $local .= $nameBytes . $compressed;
        $body  .= $local;

        $directory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 8, $dosTime, $dosDate, $crc, $compSize, $uncompSize, strlen($nameBytes), 0, 0, 0, 0, 0, $offset);
        $directory .= $nameBytes;
        $offset += strlen($local);
    }

    $dirOffset  = strlen($body);
    $dirSize    = strlen($directory);
    $count      = count($files);
    $endRecord  = pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $dirSize, $dirOffset, 0);

    return $body . $directory . $endRecord;
}
