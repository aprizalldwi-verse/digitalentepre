<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../proses/masuk.php');
    exit;
}

require_once __DIR__ . '/../config/koneksi.php';

$totalUsers = 0;
$totalProducts = 0;
$totalTransactions = 0;
$totalRevenue = 0;
$popularCourses = [];
$recentTransactions = [];
$recentActivities = [];
$paymentMethods = [];
$salesChartLabels = [];
$salesChartValues = [];
$dailySales = [];

function safeQuery($conn, $sql, $params = [], $types = '') {
    if (!isset($conn) || !$conn) return null;
    if (!empty($params)) {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) return null;
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    return mysqli_query($conn, $sql);
}

// Total Users
$r = safeQuery($conn, "SELECT COUNT(*) AS total FROM users");
if ($r && $row = mysqli_fetch_assoc($r)) $totalUsers = intval($row['total']);

// Total Products
$r = safeQuery($conn, "SELECT (SELECT COUNT(*) FROM products WHERE status='active') AS el, (SELECT COUNT(*) FROM bootcamp) AS bc");
if ($r && $row = mysqli_fetch_assoc($r)) $totalProducts = intval($row['el']) + intval($row['bc']);

// Total Transactions (settlement)
$r = safeQuery($conn, "SELECT COUNT(*) AS total FROM transaksi WHERE transaction_status='settlement'");
if ($r && $row = mysqli_fetch_assoc($r)) $totalTransactions = intval($row['total']);

// Total Revenue
$r = safeQuery($conn, "SELECT COALESCE(SUM(gross_amount),0) AS total FROM transaksi WHERE transaction_status='settlement'");
if ($r && $row = mysqli_fetch_assoc($r)) $totalRevenue = floatval($row['total']);

$thisMonth = date('m');
$thisYear = date('Y');

$r = safeQuery($conn, "SELECT COUNT(*) AS t FROM users WHERE MONTH(created_at)=? AND YEAR(created_at)=?", [$thisMonth, $thisYear], 'ii');
$tmU = ($r && $row = mysqli_fetch_assoc($r)) ? intval($row['t']) : 0;

$r = safeQuery($conn, "SELECT COUNT(*) AS t FROM transaksi WHERE transaction_status='settlement' AND MONTH(created_at)=? AND YEAR(created_at)=?", [$thisMonth, $thisYear], 'ii');
$tmT = ($r && $row = mysqli_fetch_assoc($r)) ? intval($row['t']) : 0;

$r = safeQuery($conn, "SELECT COALESCE(SUM(gross_amount),0) AS t FROM transaksi WHERE transaction_status='settlement' AND MONTH(created_at)=? AND YEAR(created_at)=?", [$thisMonth, $thisYear], 'ii');
$tmR = ($r && $row = mysqli_fetch_assoc($r)) ? floatval($row['t']) : 0;

// Popular Courses (top 5)
$r = safeQuery($conn, "SELECT t.nama_produk, t.jenis_produk, t.produk_id, COUNT(*) AS jumlah_peserta, SUM(t.gross_amount) AS total_pendapatan FROM transaksi t WHERE t.transaction_status='settlement' GROUP BY t.nama_produk, t.jenis_produk, t.produk_id ORDER BY jumlah_peserta DESC LIMIT 5");
if ($r) { while ($row = mysqli_fetch_assoc($r)) $popularCourses[] = $row; }

foreach ($popularCourses as &$course) {
    $course['gambar'] = '';
    if ($course['jenis_produk'] === 'elearning') {
        $r2 = safeQuery($conn, "SELECT gambar FROM products WHERE id=?", [$course['produk_id']], 'i');
        if ($r2 && $row2 = mysqli_fetch_assoc($r2)) $course['gambar'] = $row2['gambar'] ?? '';
    } else {
        $r2 = safeQuery($conn, "SELECT gambar FROM bootcamp WHERE id=?", [$course['produk_id']], 'i');
        if ($r2 && $row2 = mysqli_fetch_assoc($r2)) $course['gambar'] = $row2['gambar'] ?? '';
    }
}
unset($course);

// Recent Transactions (10)
$r = safeQuery($conn, "SELECT t.id, t.nama_produk, t.gross_amount, t.transaction_status, t.payment_type, t.created_at, u.nama AS user_nama FROM transaksi t LEFT JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 10");
if ($r) { while ($row = mysqli_fetch_assoc($r)) $recentTransactions[] = $row; }

// Payment Methods
$r = safeQuery($conn, "SELECT COALESCE(payment_type,'Lainnya') AS method, COUNT(*) AS jumlah FROM transaksi WHERE transaction_status='settlement' GROUP BY payment_type ORDER BY jumlah DESC");
if ($r) { while ($row = mysqli_fetch_assoc($r)) $paymentMethods[] = $row; }

// Recent Activities
$r = safeQuery($conn, "SELECT nama, created_at FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 3");
$recentUsers = [];
if ($r) { while ($row = mysqli_fetch_assoc($r)) $recentUsers[] = $row; }

$r = safeQuery($conn, "SELECT nama_produk, jenis_produk, created_at FROM transaksi WHERE transaction_status='settlement' ORDER BY created_at DESC LIMIT 3");
$recentTx = [];
if ($r) { while ($row = mysqli_fetch_assoc($r)) $recentTx[] = $row; }

$r = safeQuery($conn, "SELECT nama_produk, created_at FROM products ORDER BY created_at DESC LIMIT 2");
$recentProd = [];
if ($r) { while ($row = mysqli_fetch_assoc($r)) $recentProd[] = $row; }

$activities = [];
foreach ($recentUsers as $u) {
    $activities[] = ['type'=>'user','title'=>'Pengguna baru mendaftar','detail'=>$u['nama'],'time'=>$u['created_at'],'icon'=>'👤','color'=>'blue'];
}
foreach ($recentTx as $tx) {
    $label = $tx['jenis_produk']==='bootcamp' ? 'Pembelian Bootcamp' : 'Pembelian Kelas';
    $activities[] = ['type'=>'transaction','title'=>'Transaksi berhasil','detail'=>$label.': '.$tx['nama_produk'],'time'=>$tx['created_at'],'icon'=>'💰','color'=>'green'];
}
foreach ($recentProd as $p) {
    $activities[] = ['type'=>'product','title'=>'Kelas baru ditambahkan','detail'=>$p['nama_produk'],'time'=>$p['created_at'],'icon'=>'📚','color'=>'purple'];
}
usort($activities, function($a,$b){ return strtotime($b['time'])-strtotime($a['time']); });
$activities = array_slice($activities, 0, 5);

// Daily sales chart (last 7 days)
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('d M', strtotime($date));
    $dailySales[$label] = 0;
}
$r = safeQuery($conn, "SELECT DATE(created_at) AS tgl, COALESCE(SUM(gross_amount),0) AS total FROM transaksi WHERE transaction_status='settlement' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY tgl ASC");
if ($r) { while ($row = mysqli_fetch_assoc($r)) { $label = date('d M', strtotime($row['tgl'])); if (isset($dailySales[$label])) $dailySales[$label] = floatval($row['total']); } }
$salesChartLabels = array_keys($dailySales);
$salesChartValues = array_values($dailySales);

function formatRupiah($n) { return 'Rp '.number_format($n,0,',','.'); }
function formatNumber($n) { return number_format($n,0,',','.'); }
function timeAgo($dt) {
    $now = new DateTime(); $ago = new DateTime($dt); $diff = $now->diff($ago);
    if ($diff->d > 0) return $diff->d.' hari yang lalu';
    if ($diff->h > 0) return $diff->h.' jam yang lalu';
    if ($diff->i > 0) return $diff->i.' menit yang lalu';
    return 'Baru saja';
}
function formatDate($dt) { return (new DateTime($dt))->format('d M Y'); }
function getStatusBadge($s) {
    $s = strtolower($s);
    if ($s==='settlement') return '<span class="badge badge-success">Berhasil</span>';
    if ($s==='pending') return '<span class="badge badge-warning">Pending</span>';
    if ($s==='expire') return '<span class="badge badge-neutral">Expired</span>';
    if ($s==='cancel') return '<span class="badge badge-danger">Dibatalkan</span>';
    if ($s==='deny') return '<span class="badge badge-danger">Ditolak</span>';
    return '<span class="badge badge-neutral">'.htmlspecialchars($s).'</span>';
}
function getCourseImage($gambar, $jenis) {
    if (empty($gambar)) return $jenis==='bootcamp' ? '../assets/bootcamp/bootcamp_fullstack.png' : '../assets/elearning/course_fullstack.png';
    if (strpos($gambar,'http')===0) return $gambar;
    $gambar = str_replace(
        array('course/assets/images/bootcamp/','assets/course/bootcamp/'),
        'assets/bootcamp/',
        $gambar
    );
    if (strpos($gambar,'assets/course/')===0) {
        $gambar = 'assets/elearning/' . basename($gambar);
    }
    $gambar = ltrim($gambar,'/');
    return '../'.$gambar;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - BelajarYuk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<div class="admin-dashboard">

    <!-- LEFT SIDEBAR -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">🎓</span>
            <div class="brand-text">
                <span class="brand-name">BelajarYuk</span>
                <span class="brand-role">Admin Panel</span>
            </div>
        </div>

        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>

            <div class="menu-group">
                <span class="menu-group-label">Manajemen Kelas</span>
                <a href="elearning_admin.php" class="menu-item">
                    <i class="fas fa-graduation-cap"></i>
                    <span>E-Learning</span>
                </a>
                <a href="bootcamp_admin.php" class="menu-item">
                    <i class="fas fa-laptop-code"></i>
                    <span>Bootcamp</span>
                </a>
            </div>

            <a href="transaksi_admin.php" class="menu-item">
                <i class="fas fa-receipt"></i>
                <span>Transaksi</span>
            </a>

            <div class="menu-group">
                <span class="menu-group-label">Pengguna</span>
                <a href="user_admin.php" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>User</span>
                </a>
                <a href="operations.php?section=instructors" class="menu-item">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Guru / Instruktur</span>
                </a>
            </div>

            <div class="menu-group">
                <span class="menu-group-label">Laporan</span>
                <a href="operations.php?section=sales" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Penjualan</span>
                </a>
                <a href="operations.php?section=registrations" class="menu-item">
                    <i class="fas fa-user-check"></i>
                    <span>Pendaftaran</span>
                </a>
                <a href="operations.php?section=finance" class="menu-item">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Rekap Keuangan</span>
                </a>
            </div>

            <a href="operations.php?section=calendar" class="menu-item">
                <i class="fas fa-calendar-alt"></i>
                <span>Kalender</span>
            </a>
            <a href="operations.php?section=expenses" class="menu-item">
                <i class="fas fa-wallet"></i>
                <span>Pengeluaran</span>
            </a>
            <a href="operations.php?section=partnerships" class="menu-item">
                <i class="fas fa-handshake"></i>
                <span>Kerjasama</span>
            </a>

            <a href="operations.php?section=contacts" class="menu-item">
                <i class="fas fa-phone-alt"></i>
                <span>Kontak & Sosial Media</span>
            </a>

            <a href="operations.php?section=settings" class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Pengaturan</span>
            </a>
            <a href="../proses/logout.php" class="menu-item">
                <i class="fas fa-right-from-bracket"></i>
                <span>Logout Admin</span>
            </a>
        </nav>

        <!-- Promo Card at bottom -->
        <div class="sidebar-promo">
            <div class="promo-illustration">📊</div>
            <div class="promo-text">
                <strong>Belajar lebih mudah, bersama BelajarYuk!</strong>
                <p>Tingkatkan skill dan raih masa depan yang lebih baik.</p>
            </div>
        </div>
    </aside>

    <!-- OVERLAY -->
    <div class="admin-overlay" id="adminOverlay" onclick="toggleSidebar()"></div>

    <!-- MAIN CONTENT -->
    <div class="admin-main">

        <!-- TOPBAR -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
                <label class="topbar-search">
                    <i class="fas fa-search"></i>
                    <input id="globalSearch" type="search" placeholder="Cari kelas, user, transaksi..." autocomplete="off">
                    <div id="globalSearchResults" class="global-search-results" hidden></div>
                </label>
            </div>
            <div class="topbar-right">
                <button class="topbar-icon-btn" title="Notifikasi">
                    <i class="fas fa-bell"></i>
                </button>
                <div class="topbar-profile">
                    <div class="profile-avatar">A</div>
                    <div class="profile-info">
                        <span class="profile-name">Admin</span>
                        <span class="profile-role">Administrator</span>
                    </div>
                    <i class="fas fa-chevron-down profile-arrow"></i>
                </div>
            </div>
        </header>

        <!-- CONTENT AREA - 3 COLUMN LAYOUT -->
        <div class="admin-content">
            <div class="content-three-col">

                <!-- MAIN COLUMN -->
                <div class="content-main">

                    <!-- WELCOME -->
                    <section class="welcome-section">
                        <div class="welcome-text">
                            <h1>Selamat Datang, Admin 👋</h1>
                            <p>Berikut ringkasan aktivitas dan statistik platform BelajarYuk hari ini.</p>
                        </div>
                        <label class="dashboard-range-filter">Periode
                            <select id="dashboardDateRange">
                                <option value="today">Hari Ini</option>
                                <option value="yesterday">Kemarin</option>
                                <option value="last7">7 Hari Terakhir</option>
                                <option value="last30" selected>30 Hari Terakhir</option>
                                <option value="thisMonth">Bulan Ini</option>
                                <option value="lastMonth">Bulan Lalu</option>
                                <option value="thisYear">Tahun Ini</option>
                                <option value="custom">Rentang Khusus</option>
                            </select>
                            <input id="dashboardRangeStart" type="date" aria-label="Tanggal mulai" hidden>
                            <input id="dashboardRangeEnd" type="date" aria-label="Tanggal akhir" hidden>
                        </label>
                    </section>

                    <!-- STATISTICS -->
                    <section class="stats-grid">
                        <div class="stat-card stat-blue">
                            <div class="stat-card-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-card-info">
                                <span class="stat-card-label">Total Pengguna</span>
                                <span class="stat-card-value" id="totalUsers"><?= formatNumber($totalUsers) ?></span>
                                <span class="stat-card-trend"><?= formatNumber($tmU) ?> pengguna terdaftar bulan ini</span>
                            </div>
                        </div>
                        <div class="stat-card stat-indigo">
                            <div class="stat-card-icon"><i class="fas fa-book-open"></i></div>
                            <div class="stat-card-info">
                                <span class="stat-card-label">Total Kelas</span>
                                <span class="stat-card-value" id="totalProducts"><?= formatNumber($totalProducts) ?></span>
                                <span class="stat-card-trend">Kelas dari database</span>
                            </div>
                        </div>
                        <div class="stat-card stat-green">
                            <div class="stat-card-icon"><i class="fas fa-shopping-cart"></i></div>
                            <div class="stat-card-info">
                                <span class="stat-card-label">Total Transaksi</span>
                                <span class="stat-card-value" id="totalTransactions"><?= formatNumber($totalTransactions) ?></span>
                                <span class="stat-card-trend"><?= formatNumber($tmT) ?> transaksi berhasil bulan ini</span>
                            </div>
                        </div>
                        <div class="stat-card stat-orange">
                            <div class="stat-card-icon"><i class="fas fa-dollar-sign"></i></div>
                            <div class="stat-card-info">
                                <span class="stat-card-label">Total Pendapatan</span>
                                <span class="stat-card-value" id="totalRevenue"><?= formatRupiah($totalRevenue) ?></span>
                                <span class="stat-card-trend"><?= formatRupiah($tmR) ?> pemasukan bulan ini</span>
                            </div>
                        </div>
                        <div class="stat-card stat-blue">
                            <div class="stat-card-icon"><i class="fas fa-user-check"></i></div>
                            <div class="stat-card-info">
                                <span class="stat-card-label">Total Pendaftaran</span>
                                <span class="stat-card-value" id="totalRegistrations">0</span>
                                <span class="stat-card-trend">Sumber: transaksi database</span>
                            </div>
                        </div>
                        <div class="stat-card stat-orange">
                            <div class="stat-card-icon"><i class="fas fa-wallet"></i></div>
                            <div class="stat-card-info">
                                <span class="stat-card-label">Total Pengeluaran</span>
                                <span class="stat-card-value" id="totalExpenses">Rp 0</span>
                                <span class="stat-card-trend">Dari catatan pengeluaran</span>
                            </div>
                        </div>
                        <div class="stat-card stat-green">
                            <div class="stat-card-icon"><i class="fas fa-scale-balanced"></i></div>
                            <div class="stat-card-info">
                                <span class="stat-card-label">Profit Bersih</span>
                                <span class="stat-card-value" id="netProfit">Rp 0</span>
                                <span class="stat-card-trend">Pemasukan dikurangi pengeluaran</span>
                            </div>
                        </div>
                    </section>

                    <!-- CHARTS -->
                    <section class="charts-row">
                        <div class="content-card chart-card">
                            <div class="card-header">
                                <h2>Grafik Penjualan</h2>
                                <select id="periodFilter" class="period-filter" onchange="changeChartPeriod()">
                                    <option value="7">7 Hari Terakhir</option>
                                    <option value="30">30 Hari</option>
                                    <option value="month">Bulan Ini</option>
                                    <option value="year">Tahun Ini</option>
                                </select>
                            </div>
                            <div class="chart-container">
                                <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                        <div class="content-card payment-card">
                            <div class="card-header">
                                <h2>Metode Pembayaran</h2>
                            </div>
                            <div class="payment-chart-container">
                                <canvas id="paymentChart"></canvas>
                            </div>
                            <div class="payment-legend" id="paymentLegend">
                                <?php if (empty($paymentMethods)): ?>
                                    <div class="empty-state-small">Belum ada data</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <!-- POPULAR COURSES + TRANSACTIONS -->
                    <section class="tables-row">
                        <div class="content-card">
                            <div class="card-header">
                                <h2>Kelas Terpopuler</h2>
                                <a href="elearning_admin.php" class="view-all-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr><th>No</th><th>Gambar</th><th>Nama Kelas</th><th>Kategori</th><th>Peserta</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($popularCourses)): ?>
                                            <tr><td colspan="5" class="empty-state">Belum ada data kelas populer.</td></tr>
                                        <?php else: foreach ($popularCourses as $idx => $course): ?>
                                        <tr>
                                            <td><?= $idx+1 ?></td>
                                            <td><img src="<?= getCourseImage($course['gambar'],$course['jenis_produk']) ?>" alt="" class="course-thumb" onerror="this.src='https://via.placeholder.com/40x40?text=IMG'"></td>
                                            <td class="course-name"><?= htmlspecialchars($course['nama_produk']) ?></td>
                                            <td><span class="badge <?= $course['jenis_produk']==='bootcamp'?'badge-purple':'badge-blue' ?>"><?= $course['jenis_produk']==='bootcamp'?'Bootcamp':'E-Learning' ?></span></td>
                                            <td><?= formatNumber($course['jumlah_peserta']) ?></td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="content-card">
                            <div class="card-header">
                                <h2>Transaksi Terbaru</h2>
                                <a href="transaksi_admin.php" class="view-all-link">Lihat Semua <i class="fas fa-arrow-right"></i></a>
                            </div>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr><th>No</th><th>User</th><th>Kelas</th><th>Jumlah</th><th>Status</th><th>Tanggal</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recentTransactions)): ?>
                                            <tr><td colspan="6" class="empty-state">Belum ada transaksi.</td></tr>
                                        <?php else: foreach ($recentTransactions as $idx => $tx): ?>
                                        <tr>
                                            <td><?= $idx+1 ?></td>
                                            <td><?= htmlspecialchars($tx['user_nama']??'User') ?></td>
                                            <td class="course-name"><?= htmlspecialchars($tx['nama_produk']) ?></td>
                                            <td class="text-nowrap"><?= formatRupiah($tx['gross_amount']) ?></td>
                                            <td><?= getStatusBadge($tx['transaction_status']) ?></td>
                                            <td class="text-nowrap"><?= formatDate($tx['created_at']) ?></td>
                                        </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>

                    <!-- COOPERATION BANNER -->
                    <section class="cooperation-banner">
                        <div class="coop-content">
                            <div class="coop-icon">📢</div>
                            <div class="coop-text">
                                <h3>Promosi & Kerjasama</h3>
                                <p>Tingkatkan jangkauan kelas Anda dengan fitur kerjasama guru panggilan dan integrasi sosial media.</p>
                            </div>
                        </div>
                        <a href="operations.php?section=partnerships" class="coop-btn">Kelola Sekarang</a>
                    </section>

                </div>

                <!-- RIGHT SIDEBAR -->
                <div class="content-right">

                    <!-- Aktivitas Terbaru -->
                    <div class="content-card">
                        <div class="card-header">
                            <h2>Aktivitas Terbaru</h2>
                            <a href="#" class="view-all-link">Lihat Semua</a>
                        </div>
                        <div class="activity-list">
                            <?php if (empty($activities)): ?>
                                <div class="empty-state">Belum ada aktivitas terbaru.</div>
                            <?php else: foreach ($activities as $act): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?= $act['color'] ?>"><?= $act['icon'] ?></div>
                                <div class="activity-content">
                                    <div class="activity-title"><?= htmlspecialchars($act['title']) ?></div>
                                    <div class="activity-detail"><?= htmlspecialchars($act['detail']) ?></div>
                                    <div class="activity-time"><?= timeAgo($act['time']) ?></div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <!-- Quick Action -->
                    <div class="content-card">
                        <div class="card-header">
                            <h2>Quick Action</h2>
                        </div>
                        <div class="quick-actions">
                            <a href="elearning_admin.php" class="quick-action-btn primary">
                                <i class="fas fa-plus"></i>
                                <span>Tambah Kelas</span>
                            </a>
                            <a href="transaksi_admin.php" class="quick-action-btn outline">
                                <i class="fas fa-receipt"></i>
                                <span>Lihat Semua Transaksi</span>
                            </a>
                            <a href="user_admin.php" class="quick-action-btn outline">
                                <i class="fas fa-users"></i>
                                <span>Kelola Pengguna</span>
                            </a>
                            <a href="laporan_admin.php" class="quick-action-btn outline">
                                <i class="fas fa-file-alt"></i>
                                <span>Buat Laporan</span>
                            </a>
                        </div>
                    </div>

                    <!-- Integrasi Sosial Media -->
                    <div class="content-card">
                        <div class="card-header">
                            <h2>Integrasi Sosial Media</h2>
                        </div>
                        <div class="social-links-vertical">
                            <a href="#" id="dashboardWhatsappLink" class="social-link-item whatsapp">
                                <i class="fab fa-whatsapp"></i>
                                <span>WhatsApp</span>
                                <i class="fas fa-chevron-right social-arrow"></i>
                            </a>
                            <a href="#" id="dashboardTelegramLink" class="social-link-item telegram">
                                <i class="fab fa-telegram-plane"></i>
                                <span>Telegram</span>
                                <i class="fas fa-chevron-right social-arrow"></i>
                            </a>
                            <a href="#" id="dashboardInstagramLink" class="social-link-item instagram">
                                <i class="fab fa-instagram"></i>
                                <span>Instagram</span>
                                <i class="fas fa-chevron-right social-arrow"></i>
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>
</div>

<script>
    var SALES_CHART_DATA = <?= json_encode(['labels'=>$salesChartLabels,'values'=>$salesChartValues]) ?>;
    var PAYMENT_DATA = <?= json_encode($paymentMethods) ?>;
</script>
<script src="../js/image_helper.js"></script>
<script src="../js/admin.js"></script>
<script src="../js/admin_dashboard_filters.js"></script>

</body>
</html>
