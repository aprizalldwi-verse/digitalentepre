<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . "/../config/koneksi.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (($_SESSION["role"] ?? null) !== "admin") {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Akses admin diperlukan."], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!$conn) {
    http_response_code(503);
    echo json_encode(["success" => false, "message" => "Database tidak tersedia."], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS expenses (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    expense_date DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), KEY idx_expenses_date (expense_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$conn->query("CREATE TABLE IF NOT EXISTS admin_settings (
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$conn->query("CREATE TABLE IF NOT EXISTS partnerships (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT '',
    contact VARCHAR(150) NOT NULL DEFAULT '',
    status VARCHAR(30) NOT NULL DEFAULT 'aktif',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id), KEY idx_partnerships_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

function sendJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function validDate($value) {
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return false;
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}
function dateRange($preset, $start, $end) {
    $today = new DateTimeImmutable('today');
    switch ($preset) {
        case 'today': return [$today->format('Y-m-d'), $today->format('Y-m-d')];
        case 'yesterday': $d = $today->modify('-1 day')->format('Y-m-d'); return [$d, $d];
        case 'last7': return [$today->modify('-6 days')->format('Y-m-d'), $today->format('Y-m-d')];
        case 'last30': return [$today->modify('-29 days')->format('Y-m-d'), $today->format('Y-m-d')];
        case 'thisMonth': return [$today->modify('first day of this month')->format('Y-m-d'), $today->format('Y-m-d')];
        case 'lastMonth': $m = $today->modify('first day of last month'); return [$m->format('Y-m-d'), $m->modify('last day of this month')->format('Y-m-d')];
        case 'thisYear': return [$today->setDate((int)$today->format('Y'), 1, 1)->format('Y-m-d'), $today->format('Y-m-d')];
        case 'custom': return validDate($start) && validDate($end) && $start <= $end ? [$start, $end] : null;
        case 'all': return [null, null];
        default: return [$today->format('Y-m-d'), $today->format('Y-m-d')];
    }
}
function readJsonInput() {
    $json = json_decode(file_get_contents('php://input'), true);
    return is_array($json) ? $json : $_POST;
}
function requireFields($data, $fields) {
    foreach ($fields as $field) if (!isset($data[$field]) || !is_scalar($data[$field]) || trim((string)$data[$field]) === '') return false;
    return true;
}

$action = trim($_GET['action'] ?? '');
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$input = readJsonInput();

if ($action === 'overview' && $method === 'GET') {
    $range = dateRange($_GET['range'] ?? 'today', $_GET['start'] ?? '', $_GET['end'] ?? '');
    if (!$range) sendJson(['success' => false, 'message' => 'Rentang tanggal tidak valid.'], 422);
    [$start, $end] = $range;
    $userSql = 'SELECT COUNT(*) total FROM users' . ($start ? ' WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)' : '');
    $userStmt = $conn->prepare($userSql);
    if ($start) $userStmt->bind_param('ss', $start, $end);
    $userStmt->execute(); $users = (int)$userStmt->get_result()->fetch_assoc()['total']; $userStmt->close();
    $txnSql = "SELECT COUNT(*) transactions, COALESCE(SUM(CASE WHEN transaction_status='settlement' THEN gross_amount ELSE 0 END),0) income FROM transaksi" . ($start ? ' WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)' : '');
    $txnStmt = $conn->prepare($txnSql);
    if ($start) $txnStmt->bind_param('ss', $start, $end);
    $txnStmt->execute(); $txn = $txnStmt->get_result()->fetch_assoc(); $txnStmt->close();
    $expenseSql = 'SELECT COALESCE(SUM(amount),0) total FROM expenses' . ($start ? ' WHERE expense_date >= ? AND expense_date < DATE_ADD(?, INTERVAL 1 DAY)' : '');
    $expenseStmt = $conn->prepare($expenseSql);
    if ($start) $expenseStmt->bind_param('ss', $start, $end);
    $expenseStmt->execute(); $expense = (float)$expenseStmt->get_result()->fetch_assoc()['total']; $expenseStmt->close();
    $courses = (int)$conn->query("SELECT (SELECT COUNT(*) FROM products) + (SELECT COUNT(*) FROM bootcamp) total")->fetch_assoc()['total'];
    $registrations = (int)$txn['transactions']; $income = (float)$txn['income'];
    $sales = $conn->query("SELECT
        COALESCE(SUM(CASE WHEN transaction_status='settlement' AND created_at >= CURDATE() AND created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN gross_amount ELSE 0 END),0) today,
        COALESCE(SUM(CASE WHEN transaction_status='settlement' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) THEN gross_amount ELSE 0 END),0) last7,
        COALESCE(SUM(CASE WHEN transaction_status='settlement' AND YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE()) THEN gross_amount ELSE 0 END),0) this_month,
        COALESCE(SUM(CASE WHEN transaction_status='settlement' AND YEAR(created_at)=YEAR(CURDATE()) THEN gross_amount ELSE 0 END),0) this_year,
        COALESCE(SUM(CASE WHEN transaction_status='settlement' THEN gross_amount ELSE 0 END),0) all_time
        FROM transaksi")->fetch_assoc();
    sendJson(['success' => true, 'range' => ['start' => $start, 'end' => $end], 'data' => [
        'users' => $users, 'courses' => $courses, 'transactions' => (int)$txn['transactions'],
        'registrations' => $registrations, 'income' => $income, 'expenses' => $expense, 'profit' => $income - $expense,
        'sales_today' => (float)$sales['today'], 'sales_7_days' => (float)$sales['last7'],
        'sales_this_month' => (float)$sales['this_month'], 'sales_this_year' => (float)$sales['this_year'], 'sales_total' => (float)$sales['all_time']
    ]]);
}

if ($action === 'registrations' && $method === 'GET') {
    $range = dateRange($_GET['range'] ?? 'all', $_GET['start'] ?? '', $_GET['end'] ?? '');
    if (!$range) sendJson(['success' => false, 'message' => 'Rentang tanggal tidak valid.'], 422);
    [$start, $end] = $range;
    $sql = "SELECT t.id, t.user_id, t.jenis_produk, t.produk_id, COALESCE(p.nama_produk,b.judul,t.nama_produk) nama_produk, t.gross_amount, t.transaction_status, COALESCE(t.transaction_time,t.created_at) registration_date, u.nama user_name, u.email FROM transaksi t LEFT JOIN users u ON u.id=t.user_id LEFT JOIN products p ON t.jenis_produk='elearning' AND p.id=t.produk_id LEFT JOIN bootcamp b ON t.jenis_produk='bootcamp' AND b.id=t.produk_id" . ($start ? ' WHERE COALESCE(t.transaction_time,t.created_at) >= ? AND COALESCE(t.transaction_time,t.created_at) < DATE_ADD(?, INTERVAL 1 DAY)' : '') . ' ORDER BY registration_date DESC';
    $stmt = $conn->prepare($sql); if ($start) $stmt->bind_param('ss', $start, $end);
    $stmt->execute(); $result = $stmt->get_result(); $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    $stmt->close(); sendJson(['success' => true, 'data' => $rows]);
}

if ($action === 'instructors' && $method === 'GET') {
    $result = $conn->query("SELECT mentor AS name, COUNT(*) AS bootcamp_count FROM bootcamp WHERE TRIM(mentor) <> '' GROUP BY mentor ORDER BY mentor");
    $rows = []; while ($row = $result->fetch_assoc()) $rows[] = $row;
    sendJson(['success' => true, 'data' => $rows]);
}

if ($action === 'charts' && $method === 'GET') {
    $range = dateRange($_GET['range'] ?? 'last30', $_GET['start'] ?? '', $_GET['end'] ?? '');
    if (!$range || !$range[0] || (new DateTimeImmutable($range[0]))->diff(new DateTimeImmutable($range[1]))->days > 365) sendJson(['success' => false, 'message' => 'Rentang grafik tidak valid atau melebihi satu tahun.'], 422);
    [$start, $end] = $range; $endExclusive = (new DateTimeImmutable($end))->modify('+1 day')->format('Y-m-d'); $days = [];
    for ($date = new DateTimeImmutable($start); $date < new DateTimeImmutable($endExclusive); $date = $date->modify('+1 day')) $days[$date->format('Y-m-d')] = ['sales' => 0.0, 'income' => 0.0, 'expenses' => 0.0];
    $stmt = $conn->prepare("SELECT DATE(created_at) day, COUNT(*) sales, COALESCE(SUM(CASE WHEN transaction_status='settlement' THEN gross_amount ELSE 0 END),0) income FROM transaksi WHERE created_at >= ? AND created_at < ? GROUP BY DATE(created_at)");
    $stmt->bind_param('ss', $start, $endExclusive); $stmt->execute(); $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) if (isset($days[$row['day']])) { $days[$row['day']]['sales'] = (int)$row['sales']; $days[$row['day']]['income'] = (float)$row['income']; }
    $stmt->close();
    $stmt = $conn->prepare('SELECT DATE(expense_date) day, COALESCE(SUM(amount),0) expenses FROM expenses WHERE expense_date >= ? AND expense_date < ? GROUP BY DATE(expense_date)');
    $stmt->bind_param('ss', $start, $endExclusive); $stmt->execute(); $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) if (isset($days[$row['day']])) $days[$row['day']]['expenses'] = (float)$row['expenses'];
    $stmt->close(); sendJson(['success' => true, 'data' => $days]);
}

if ($action === 'payments' && $method === 'GET') {
    $range = dateRange($_GET['range'] ?? 'last30', $_GET['start'] ?? '', $_GET['end'] ?? '');
    if (!$range) sendJson(['success' => false, 'message' => 'Rentang tanggal tidak valid.'], 422);
    [$start, $end] = $range;
    $sql = "SELECT COALESCE(payment_type,'Lainnya') method, COUNT(*) jumlah FROM transaksi WHERE transaction_status='settlement'" . ($start ? ' AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)' : '') . ' GROUP BY payment_type ORDER BY jumlah DESC';
    $stmt = $conn->prepare($sql);
    if ($start) $stmt->bind_param('ss', $start, $end);
    $stmt->execute(); $result = $stmt->get_result(); $rows = [];
    while ($row = $result->fetch_assoc()) $rows[] = $row;
    $stmt->close(); sendJson(['success' => true, 'data' => $rows]);
}

if ($action === 'calendar' && $method === 'GET') {
    $month = $_GET['month'] ?? date('Y-m');
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) sendJson(['success' => false, 'message' => 'Bulan tidak valid.'], 422);
    $monthStart = $month . '-01'; $monthEnd = (new DateTimeImmutable($monthStart))->modify('first day of next month')->format('Y-m-d');
    $daily = [];
    $stmt = $conn->prepare("SELECT DATE(COALESCE(transaction_time,created_at)) day, COUNT(*) registrations, SUM(transaction_status='settlement') transactions, COALESCE(SUM(CASE WHEN transaction_status='settlement' THEN gross_amount ELSE 0 END),0) income FROM transaksi WHERE COALESCE(transaction_time,created_at) >= ? AND COALESCE(transaction_time,created_at) < ? GROUP BY DATE(COALESCE(transaction_time,created_at))");
    $stmt->bind_param('ss', $monthStart, $monthEnd); $stmt->execute(); $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $daily[$row['day']] = ['registrations' => (int)$row['registrations'], 'transactions' => (int)$row['transactions'], 'income' => (float)$row['income'], 'expenses' => 0.0];
    $stmt->close();
    $stmt = $conn->prepare('SELECT DATE(expense_date) day, COALESCE(SUM(amount),0) total FROM expenses WHERE expense_date >= ? AND expense_date < ? GROUP BY DATE(expense_date)');
    $stmt->bind_param('ss', $monthStart, $monthEnd); $stmt->execute(); $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { if (!isset($daily[$row['day']])) $daily[$row['day']] = ['registrations' => 0, 'transactions' => 0, 'income' => 0.0, 'expenses' => 0.0]; $daily[$row['day']]['expenses'] = (float)$row['total']; }
    $stmt->close();
    foreach ($daily as &$item) $item['profit'] = $item['income'] - $item['expenses']; unset($item);
    sendJson(['success' => true, 'month' => $month, 'days' => $daily]);
}

if ($action === 'expenses') {
    if ($method === 'GET') {
        $range = dateRange($_GET['range'] ?? 'all', $_GET['start'] ?? '', $_GET['end'] ?? '');
        if (!$range) sendJson(['success' => false, 'message' => 'Rentang tanggal tidak valid.'], 422);
        [$start, $end] = $range;
        $sql = 'SELECT id, category, description, amount, expense_date, created_at, updated_at FROM expenses' . ($start ? ' WHERE expense_date >= ? AND expense_date < DATE_ADD(?, INTERVAL 1 DAY)' : '') . ' ORDER BY expense_date DESC';
        $stmt = $conn->prepare($sql); if ($start) $stmt->bind_param('ss', $start, $end); $stmt->execute(); $result = $stmt->get_result(); $rows = [];
        while ($row = $result->fetch_assoc()) $rows[] = $row; $stmt->close(); sendJson(['success' => true, 'data' => $rows]);
    }
    if ($method === 'POST' || $method === 'PUT') {
        if (!requireFields($input, ['category', 'description', 'amount', 'expense_date']) || mb_strlen(trim($input['category'])) > 100 || mb_strlen(trim($input['description'])) > 4000 || !is_numeric($input['amount']) || (float)$input['amount'] <= 0 || (float)$input['amount'] > 9999999999999.99) sendJson(['success' => false, 'message' => 'Data pengeluaran tidak valid.'], 422);
        $expenseDate = str_replace('T', ' ', trim($input['expense_date']));
        $date = DateTime::createFromFormat('Y-m-d H:i', $expenseDate) ?: DateTime::createFromFormat('Y-m-d H:i:s', $expenseDate);
        if (!$date) sendJson(['success' => false, 'message' => 'Tanggal pengeluaran tidak valid.'], 422);
        $category = trim($input['category']); $description = trim($input['description']); $amount = (float)$input['amount']; $value = $date->format('Y-m-d H:i:s');
        if ($method === 'POST') { $stmt = $conn->prepare('INSERT INTO expenses (category,description,amount,expense_date) VALUES (?,?,?,?)'); $stmt->bind_param('ssds', $category, $description, $amount, $value); }
        else { $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) sendJson(['success' => false, 'message' => 'ID pengeluaran tidak valid.'], 422); $stmt = $conn->prepare('UPDATE expenses SET category=?,description=?,amount=?,expense_date=? WHERE id=?'); $stmt->bind_param('ssdsi', $category, $description, $amount, $value, $id); }
        $ok = $stmt->execute(); $id = $method === 'POST' ? $stmt->insert_id : (int)$input['id']; $stmt->close(); sendJson(['success' => $ok, 'id' => $id], $ok ? 200 : 500);
    }
    if ($method === 'DELETE') { $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT); if (!$id || $id < 1) sendJson(['success' => false, 'message' => 'ID pengeluaran tidak valid.'], 422); $stmt = $conn->prepare('DELETE FROM expenses WHERE id=?'); $stmt->bind_param('i', $id); $ok = $stmt->execute(); $stmt->close(); sendJson(['success' => $ok]); }
}

if ($action === 'partnerships') {
    if ($method === 'GET') { $result = $conn->query('SELECT id,name,category,contact,status,notes,created_at,updated_at FROM partnerships ORDER BY created_at DESC'); $rows=[]; while ($row=$result->fetch_assoc()) $rows[]=$row; sendJson(['success'=>true,'data'=>$rows]); }
    if ($method === 'POST') { if (!requireFields($input, ['name'])) sendJson(['success'=>false,'message'=>'Nama mitra wajib diisi.'],422); $name=trim($input['name']); $category=trim($input['category']??''); $contact=trim($input['contact']??''); $status=in_array($input['status']??'aktif',['aktif','nonaktif','proses'],true)?$input['status']:'aktif'; $notes=trim($input['notes']??''); $stmt=$conn->prepare('INSERT INTO partnerships (name,category,contact,status,notes) VALUES (?,?,?,?,?)'); $stmt->bind_param('sssss',$name,$category,$contact,$status,$notes); $ok=$stmt->execute(); $id=$stmt->insert_id; $stmt->close(); sendJson(['success'=>$ok,'id'=>$id],$ok?200:500); }
    if ($method === 'PUT') { $id=filter_var($input['id']??null,FILTER_VALIDATE_INT); if (!$id||$id<1||!requireFields($input,['name'])) sendJson(['success'=>false,'message'=>'Data mitra tidak valid.'],422); $name=trim($input['name']); $category=trim($input['category']??''); $contact=trim($input['contact']??''); $status=in_array($input['status']??'aktif',['aktif','nonaktif','proses'],true)?$input['status']:'aktif'; $notes=trim($input['notes']??''); $stmt=$conn->prepare('UPDATE partnerships SET name=?,category=?,contact=?,status=?,notes=? WHERE id=?'); $stmt->bind_param('sssssi',$name,$category,$contact,$status,$notes,$id); $ok=$stmt->execute(); $stmt->close(); sendJson(['success'=>$ok]); }
    if ($method === 'DELETE') { $id=filter_var($input['id']??null,FILTER_VALIDATE_INT); if (!$id||$id<1) sendJson(['success'=>false,'message'=>'ID mitra tidak valid.'],422); $stmt=$conn->prepare('DELETE FROM partnerships WHERE id=?'); $stmt->bind_param('i',$id); $ok=$stmt->execute(); $stmt->close(); sendJson(['success'=>$ok]); }
}

if ($action === 'settings') {
    if ($method === 'GET') { $result=$conn->query('SELECT setting_key,setting_value,updated_at FROM admin_settings ORDER BY setting_key'); $settings=[]; while($row=$result->fetch_assoc()) $settings[$row['setting_key']]=$row['setting_value']; sendJson(['success'=>true,'data'=>$settings]); }
    if ($method === 'POST') { $allowed=['contact_email','contact_phone','whatsapp_url','telegram_url','instagram_url','site_name']; $stmt=$conn->prepare('INSERT INTO admin_settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)'); foreach($allowed as $key){ if(!array_key_exists($key,$input)) continue; if(!is_scalar($input[$key])) { $stmt->close(); sendJson(['success'=>false,'message'=>'Nilai pengaturan tidak valid.'],422); } $value=trim((string)$input[$key]); if($key==='contact_email' && $value!=='' && !filter_var($value,FILTER_VALIDATE_EMAIL)) { $stmt->close(); sendJson(['success'=>false,'message'=>'Email kontak tidak valid.'],422); } if(substr($key,-4)==='_url' && $value!=='' && !filter_var($value,FILTER_VALIDATE_URL)) { $stmt->close(); sendJson(['success'=>false,'message'=>'URL sosial media tidak valid.'],422); } if(strlen($value)>255) { $stmt->close(); sendJson(['success'=>false,'message'=>'Nilai pengaturan terlalu panjang.'],422); } $stmt->bind_param('ss',$key,$value); if(!$stmt->execute()) { $stmt->close(); sendJson(['success'=>false,'message'=>'Pengaturan gagal disimpan.'],500); } } $stmt->close(); sendJson(['success'=>true,'message'=>'Pengaturan tersimpan.']); }
}

if ($action === 'search' && $method === 'GET') {
    $term=trim($_GET['q']??''); if (mb_strlen($term)<2) sendJson(['success'=>true,'data'=>[]]); $like='%'.$term.'%'; $rows=[];
    $stmt=$conn->prepare('SELECT id,nama AS label,email AS detail,"user" AS type FROM users WHERE nama LIKE ? OR email LIKE ? ORDER BY nama LIMIT 8'); $stmt->bind_param('ss',$like,$like); $stmt->execute(); $result=$stmt->get_result(); while($r=$result->fetch_assoc()) $rows[]=$r; $stmt->close();
    $stmt=$conn->prepare('SELECT id,nama_produk AS label,kategori AS detail,"elearning" AS type FROM products WHERE nama_produk LIKE ? OR kategori LIKE ? ORDER BY nama_produk LIMIT 8'); $stmt->bind_param('ss',$like,$like); $stmt->execute(); $result=$stmt->get_result(); while($r=$result->fetch_assoc()) $rows[]=$r; $stmt->close();
    $stmt=$conn->prepare('SELECT id,judul AS label,mentor AS detail,"bootcamp" AS type FROM bootcamp WHERE judul LIKE ? OR mentor LIKE ? ORDER BY judul LIMIT 8'); $stmt->bind_param('ss',$like,$like); $stmt->execute(); $result=$stmt->get_result(); while($r=$result->fetch_assoc()) $rows[]=$r; $stmt->close();
    $stmt=$conn->prepare('SELECT t.id,t.order_id AS label,CONCAT(COALESCE(u.nama,"User")," · ",t.nama_produk) AS detail,"transaction" AS type FROM transaksi t LEFT JOIN users u ON u.id=t.user_id WHERE t.order_id LIKE ? OR t.nama_produk LIKE ? OR u.nama LIKE ? OR u.email LIKE ? ORDER BY t.created_at DESC LIMIT 12'); $stmt->bind_param('ssss',$like,$like,$like,$like); $stmt->execute(); $result=$stmt->get_result(); while($r=$result->fetch_assoc()) $rows[]=$r; $stmt->close(); sendJson(['success'=>true,'data'=>$rows]);
}

if ($action === 'finance-report' && $method === 'GET') {
    require_once __DIR__ . '/report_data.php';
    $report = adminLoadReport($conn, $_GET);
    if ($report === null) sendJson(['success' => false, 'message' => 'Filter laporan tidak valid.'], 422);
    sendJson(['success' => true, 'summary' => $report['summary'], 'data' => adminFinanceRows($report)]);
}

sendJson(['success' => false, 'message' => 'Aksi tidak ditemukan.'], 404);
