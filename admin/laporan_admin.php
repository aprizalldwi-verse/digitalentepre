<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../proses/masuk.php');
    exit;
}
readfile(__DIR__ . '/laporan_admin.html');
exit;
?>