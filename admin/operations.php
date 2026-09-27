<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
    header('Location: ../proses/masuk.php');
    exit;
}
$sections = [
    'instructors' => ['Guru / Instruktur', 'Data mentor yang tercatat pada program bootcamp.'],
    'sales' => ['Penjualan', 'Ringkasan dan tren penjualan dari transaksi database.'],
    'registrations' => ['Pendaftaran', 'Pendaftaran diturunkan dari transaksi yang tercatat.'],
    'finance' => ['Rekap Keuangan', 'Pemasukan transaksi, pengeluaran, dan profit bersih.'],
    'calendar' => ['Kalender', 'Aktivitas pendaftaran, transaksi, pemasukan, dan pengeluaran.'],
    'expenses' => ['Pengeluaran', 'Catat dan kelola pengeluaran operasional.'],
    'partnerships' => ['Kerjasama', 'Kelola data mitra kerja sama.'],
    'contacts' => ['Kontak & Sosial Media', 'Kelola kontak dan tautan sosial media.'],
    'settings' => ['Pengaturan', 'Kelola informasi dasar panel BelajarYuk.']
];
$section = $_GET['section'] ?? 'sales';
if (!isset($sections[$section])) $section = 'sales';
[$title, $description] = $sections[$section];
function navActive($current, $expected) { return $current === $expected ? ' active' : ''; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> | Admin BelajarYuk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="portal-body">
<div class="admin-dashboard portal-dashboard" data-section="<?= htmlspecialchars($section, ENT_QUOTES, 'UTF-8') ?>">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="sidebar-brand" href="dashboard.php"><span class="brand-icon"><i class="fas fa-graduation-cap"></i></span><span class="brand-text"><span class="brand-name">BelajarYuk</span><span class="brand-role">Admin Panel</span></span></a>
        <nav class="sidebar-menu">
            <a class="menu-item" href="dashboard.php"><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            <div class="menu-group"><span class="menu-group-label">Manajemen Kelas</span>
                <a class="menu-item" href="elearning_admin.php"><i class="fas fa-graduation-cap"></i><span>E-Learning</span></a>
                <a class="menu-item" href="bootcamp_admin.php"><i class="fas fa-laptop-code"></i><span>Bootcamp</span></a>
            </div>
            <a class="menu-item" href="transaksi_admin.php"><i class="fas fa-receipt"></i><span>Transaksi</span></a>
            <a class="menu-item" href="user_admin.php"><i class="fas fa-users"></i><span>User</span></a>
            <a class="menu-item<?= navActive($section, 'instructors') ?>" href="operations.php?section=instructors"><i class="fas fa-chalkboard-teacher"></i><span>Guru / Instruktur</span></a>
            <div class="menu-group"><span class="menu-group-label">Laporan</span>
                <a class="menu-item<?= navActive($section, 'sales') ?>" href="operations.php?section=sales"><i class="fas fa-chart-line"></i><span>Penjualan</span></a>
                <a class="menu-item<?= navActive($section, 'registrations') ?>" href="operations.php?section=registrations"><i class="fas fa-user-check"></i><span>Pendaftaran</span></a>
                <a class="menu-item<?= navActive($section, 'finance') ?>" href="operations.php?section=finance"><i class="fas fa-file-invoice-dollar"></i><span>Rekap Keuangan</span></a>
            </div>
            <a class="menu-item<?= navActive($section, 'calendar') ?>" href="operations.php?section=calendar"><i class="fas fa-calendar-alt"></i><span>Kalender</span></a>
            <a class="menu-item<?= navActive($section, 'expenses') ?>" href="operations.php?section=expenses"><i class="fas fa-wallet"></i><span>Pengeluaran</span></a>
            <a class="menu-item<?= navActive($section, 'partnerships') ?>" href="operations.php?section=partnerships"><i class="fas fa-handshake"></i><span>Kerjasama</span></a>
            <a class="menu-item<?= navActive($section, 'contacts') ?>" href="operations.php?section=contacts"><i class="fas fa-share-alt"></i><span>Kontak & Sosial Media</span></a>
            <a class="menu-item<?= navActive($section, 'settings') ?>" href="operations.php?section=settings"><i class="fas fa-cog"></i><span>Pengaturan</span></a>
        </nav>
        <a class="portal-logout" href="../proses/logout.php"><i class="fas fa-right-from-bracket"></i> Keluar</a>
    </aside>
    <div class="admin-overlay" id="adminOverlay" onclick="closeSidebar()"></div>
    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left"><button class="hamburger-btn" onclick="toggleSidebar()" aria-label="Buka menu"><i class="fas fa-bars"></i></button>
                <label class="topbar-search"><i class="fas fa-search"></i><input id="globalSearch" type="search" placeholder="Cari kelas, user, transaksi..." autocomplete="off"><div id="globalSearchResults" class="global-search-results" hidden></div></label>
            </div>
            <div class="topbar-right"><div class="topbar-profile"><div class="profile-avatar">A</div><div class="profile-info"><span class="profile-name">Admin</span><span class="profile-role">Administrator</span></div></div></div>
        </header>
        <section class="admin-content portal-content">
            <div class="portal-heading"><div><span class="portal-eyebrow">ADMIN PANEL</span><h1><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p></div>
                <div class="portal-date-filter" data-filterable="<?= in_array($section, ['sales','registrations','finance','expenses'], true) ? '1' : '0' ?>">
                    <label for="portalRange">Periode</label><select id="portalRange">
                        <option value="today">Hari Ini</option><option value="yesterday">Kemarin</option><option value="last7">7 Hari Terakhir</option><option value="last30" selected>30 Hari Terakhir</option><option value="thisMonth">Bulan Ini</option><option value="lastMonth">Bulan Lalu</option><option value="thisYear">Tahun Ini</option><option value="custom">Rentang Khusus</option>
                    </select><input type="date" id="portalStart" aria-label="Tanggal mulai" hidden><input type="date" id="portalEnd" aria-label="Tanggal akhir" hidden><button class="portal-button" id="applyRange" type="button" title="Terapkan filter"><i class="fas fa-filter"></i></button>
                </div>
            </div>
            <div class="portal-metrics" id="portalMetrics"></div>
            <div id="portalContent" class="portal-panel" aria-live="polite"><div class="portal-loading">Memuat data dari database...</div></div>
        </section>
    </main>
</div>
<script src="../js/image_helper.js"></script>
<script src="../js/admin_portal.js"></script>
</body>
</html>
