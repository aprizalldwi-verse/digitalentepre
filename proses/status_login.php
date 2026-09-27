<?php

session_start();

header("Content-Type: application/json; charset=UTF-8");

// Default
$response = [
    "logged_in" => false,
    "nama" => "",
    "email" => "",
    "role" => ""
];


// ==============================
// CEK SESSION LOGIN
// ==============================

if (isset($_SESSION["user_id"])) {

    $response["logged_in"] = true;

    // Ambil nama jika ada
    if (isset($_SESSION["nama"])) {
        $response["nama"] = $_SESSION["nama"];
    }

    // Ambil email jika ada
    if (isset($_SESSION["email"])) {
        $response["email"] = $_SESSION["email"];
    }

    // Role user (user/admin)
    if (isset($_SESSION["role"])) {
        $response["role"] = $_SESSION["role"];
    } else {
        $response["role"] = "user";
    }
}


// ==============================
// KIRIM JSON
// ==============================

echo json_encode(
    $response,
    JSON_UNESCAPED_UNICODE
);

exit;

?>