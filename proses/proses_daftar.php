<?php

session_start();

include "../config/koneksi.php";

/** @var mysqli $conn */

// Halaman daftar mengirimkan tujuan lewat input tersembunyi
if (!isset($_GET['redirect']) && !empty($_POST['redirect'])) {
    $_GET['redirect'] = (string) $_POST['redirect'];
}

$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Input validation
$errors = [];

if (empty($nama) || strlen($nama) > 100) {
    $errors[] = "Nama harus diisi dan maksimal 100 karakter.";
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Format email tidak valid.";
}

if (strlen($password) < 8) {
    $errors[] = "Password harus minimal 8 karakter.";
}

// Check duplicate email
if (empty($errors)) {
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = "Email sudah terdaftar.";
    }
    $check->close();
}

if (!empty($errors)) {
    $errorMsg = implode(" ", $errors);
    header("Location: daftar.php?error_register=" . urlencode(htmlspecialchars($errorMsg)));
    exit;
}

// Insert new user with prepared statement
$password_hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $nama, $email, $password_hash);

if ($stmt->execute()) {
    // Auto-login: get the new user ID and set session
    $new_user_id = $stmt->insert_id;
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['nama'] = $nama;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = 'user';

    $stmt->close();

    // Kembali ke halaman asal bila redirect valid
    $redirectRaw = isset($_GET['redirect']) ? (string) $_GET['redirect'] : '';
    if ($redirectRaw !== '' && strpbrk($redirectRaw, "\r\n") === false) {
        $redirectTarget = $redirectRaw;
        if (preg_match('#^https?://#i', $redirectRaw)) {
            $parts = parse_url($redirectRaw);
            $redirectTarget = ($parts['path'] ?? '/') .
                (isset($parts['query']) ? '?' . $parts['query'] : '');
        }
        if (strpos($redirectTarget, '/') === 0 && strpos($redirectTarget, '//') !== 0) {
            header("Location: " . $redirectTarget);
            exit;
        }
    }

    header("Location: ../pages/index.html?login=success&nama=" . urlencode($nama) . "&email=" . urlencode($email));
    exit;
} else {
    $stmt->close();
    header("Location: daftar.php?error_register=" . urlencode(htmlspecialchars("Pendaftaran gagal: " . $conn->error)));
    exit;
}

?>
