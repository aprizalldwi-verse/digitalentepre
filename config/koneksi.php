<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "db_belajaryuk";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");

?>
