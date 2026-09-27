<?php

session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $cookie["path"], $cookie["domain"], $cookie["secure"], $cookie["httponly"]);
}
session_destroy();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$accept = $_SERVER["HTTP_ACCEPT"] ?? "";
if (strpos($accept, "application/json") !== false || strtolower($_SERVER["HTTP_X_REQUESTED_WITH"] ?? "") === "xmlhttprequest") {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode(["success" => true, "logged_in" => false]);
    exit;
}

header("Location: masuk.php?logout=1", true, 303);
exit;

?>