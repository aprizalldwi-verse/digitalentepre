<?php
function adminReportRange($query)
{
    $today = new DateTimeImmutable('today');
    $preset = strtolower(trim($query['range'] ?? $query['periode'] ?? 'bulan_ini'));
    $customStart = $query['start'] ?? $query['tgl_mulai'] ?? '';
    $customEnd = $query['end'] ?? $query['tgl_akhir'] ?? '';
    switch ($preset) {
        case 'today': case 'hari_ini': $start = $today; $end = $today; break;
        case 'yesterday': case 'kemarin': $start = $end = $today->modify('-1 day'); break;
        case 'last7': case '7_hari': $start = $today->modify('-6 days'); $end = $today; break;
        case 'last30': case '30_hari': $start = $today->modify('-29 days'); $end = $today; break;
        case 'minggu_ini': $start = $today->modify('-' . ((int)$today->format('N') - 1) . ' days'); $end = $today; break;
        case 'bulan_ini': case 'thismonth': $start = $today->modify('first day of this month'); $end = $today; break;
        case 'lastmonth': case 'bulan_lalu': $start = $today->modify('first day of last month'); $end = $today->modify('last day of last month'); break;
        case 'tahun_ini': case 'thisyear': $start = $today->setDate((int)$today->format('Y'), 1, 1); $end = $today; break;
        case 'custom':
            $startDate = DateTimeImmutable::createFromFormat('!Y-m-d', $customStart);
            $endDate = DateTimeImmutable::createFromFormat('!Y-m-d', $customEnd);
            if (!$startDate || !$endDate || $startDate->format('Y-m-d') !== $customStart || $endDate->format('Y-m-d') !== $customEnd || $customStart > $customEnd) return null;
            $start = $startDate; $end = $endDate; break;
        case 'all': case 'semua': $start = $end = null; break;
        default: return null;
    }
    return ['preset' => $preset, 'start' => $start ? $start->format('Y-m-d') : null, 'end' => $end ? $end->format('Y-m-d') : null];
}

function adminLoadReport(mysqli $conn, array $query)
{
    $range = adminReportRange($query);
    if ($range === null) return null;
    $type = strtolower(trim($query['jenis'] ?? $query['type'] ?? 'semua'));
    if (!in_array($type, ['semua', 'elearning', 'bootcamp'], true)) $type = 'semua';
    $where = [];
    $params = [];
    $bindTypes = '';
    if ($range['start'] !== null) {
        $where[] = 'COALESCE(t.transaction_time,t.created_at) >= ? AND COALESCE(t.transaction_time,t.created_at) < DATE_ADD(?, INTERVAL 1 DAY)';
        $params[] = $range['start']; $params[] = $range['end']; $bindTypes .= 'ss';
    }
    if ($type !== 'semua') { $where[] = 't.jenis_produk = ?'; $params[] = $type; $bindTypes .= 's'; }
    $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT t.id,t.order_id,t.user_id,t.produk_id,t.jenis_produk,t.nama_produk,t.gross_amount,t.payment_type,t.transaction_status,COALESCE(t.transaction_time,t.created_at) AS transaction_date,
        COALESCE(u.nama,'User') AS user_name,COALESCE(u.email,'-') AS user_email,
        COALESCE(p.kategori,b.kategori,CASE WHEN t.jenis_produk='elearning' THEN 'E-Learning' WHEN t.jenis_produk='bootcamp' THEN 'Bootcamp' ELSE 'Tidak diketahui' END) AS category
        FROM transaksi t LEFT JOIN users u ON u.id=t.user_id
        LEFT JOIN products p ON t.jenis_produk='elearning' AND p.id=t.produk_id
        LEFT JOIN bootcamp b ON t.jenis_produk='bootcamp' AND b.id=t.produk_id" . $whereSql . ' ORDER BY transaction_date DESC,t.id DESC';
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new RuntimeException('Query laporan gagal disiapkan.');
    if ($bindTypes !== '') $stmt->bind_param($bindTypes, ...$params);
    $stmt->execute(); $result = $stmt->get_result(); $transactions = [];
    while ($row = $result->fetch_assoc()) $transactions[] = $row;
    $stmt->close();

    $expenseSql = 'SELECT id,category,description,amount,expense_date FROM expenses' . ($range['start'] !== null ? ' WHERE expense_date >= ? AND expense_date < DATE_ADD(?, INTERVAL 1 DAY)' : '') . ' ORDER BY expense_date DESC,id DESC';
    $expenseStmt = $conn->prepare($expenseSql);
    if ($range['start'] !== null) $expenseStmt->bind_param('ss', $range['start'], $range['end']);
    $expenseStmt->execute(); $expenseResult = $expenseStmt->get_result(); $expenses = [];
    while ($row = $expenseResult->fetch_assoc()) $expenses[] = $row;
    $expenseStmt->close();

    $settledIncome = 0.0; $settledElearning = 0; $settledBootcamp = 0;
    foreach ($transactions as $row) {
        if (strtolower($row['transaction_status']) !== 'settlement') continue;
        $settledIncome += (float)$row['gross_amount'];
        if ($row['jenis_produk'] === 'elearning') $settledElearning++;
        if ($row['jenis_produk'] === 'bootcamp') $settledBootcamp++;
    }
    $expenseTotal = array_sum(array_map(function ($row) { return (float)$row['amount']; }, $expenses));
    return [
        'filters' => ['range' => $range['preset'], 'type' => $type, 'start' => $range['start'], 'end' => $range['end']],
        'transactions' => $transactions, 'expenses' => $expenses,
        'summary' => ['total_transactions' => count($transactions), 'settled_elearning' => $settledElearning, 'settled_bootcamp' => $settledBootcamp, 'income' => $settledIncome, 'expenses' => $expenseTotal, 'profit' => $settledIncome - $expenseTotal]
    ];
}

function adminReportPeriodLabel(array $report)
{
    $start = $report['filters']['start']; $end = $report['filters']['end'];
    if ($start === null) return 'Semua Periode';
    $format = function ($date) { return (new DateTimeImmutable($date))->format('d/m/Y'); };
    return $start === $end ? $format($start) : $format($start) . ' - ' . $format($end);
}

function adminFinanceRows(array $report)
{
    $rows = [];
    foreach ($report['transactions'] as $t) {
        if (strtolower($t['transaction_status']) !== 'settlement') continue;
        $rows[] = ['type'=>'Pemasukan','date'=>$t['transaction_date'],'user'=>$t['user_name'],'email'=>$t['user_email'],'product'=>$t['nama_produk'],'category'=>$t['category'],'amount'=>(float)$t['gross_amount'],'payment'=>$t['payment_type'] ?: '-','status'=>$t['transaction_status'],'id'=>$t['id']];
    }
    foreach ($report['expenses'] as $e) $rows[] = ['type'=>'Pengeluaran','date'=>$e['expense_date'],'user'=>'-','email'=>'-','product'=>$e['description'],'category'=>$e['category'],'amount'=>(float)$e['amount'],'payment'=>'-','status'=>'Tercatat','id'=>$e['id']];
    usort($rows, function ($a, $b) { return strcmp($b['date'], $a['date']); });
    return $rows;
}
