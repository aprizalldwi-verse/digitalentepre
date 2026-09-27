<?php

session_start();

include "../config/koneksi.php";

/** @var mysqli $conn */

// Halaman login mengirimkan tujuan lewat input tersembunyi
if (!isset($_GET['redirect']) && !empty($_POST['redirect'])) {
    $_GET['redirect'] = (string) $_POST['redirect'];
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Input validation
$errors = [];

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Format email tidak valid.";
}

if (empty($password)) {
    $errors[] = "Password tidak boleh kosong.";
}

if (!empty($errors)) {
    $errorMsg = implode(" ", $errors);
    header("Location: masuk.php?error_login=" . urlencode(htmlspecialchars($errorMsg)));
    exit;
}

// Use prepared statement
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();

    // ONLY use password_verify - no plaintext comparison
    if (password_verify($password, $user['password'])) {

        if (($user['status'] ?? 'active') !== 'active') {
            $stmt->close();
            header("Location: masuk.php?error_login=" . urlencode("Akun ini sedang dinonaktifkan."));
            exit;
        }

        // Simpan data login
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        $stmt->close();

        // Jika ADMIN
        if ($user['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
            exit;
        }

        // Jika USER biasa → kembali ke halaman asal bila redirect valid
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

        header(
            "Location: ../pages/index.html?login=success&nama=" .
            urlencode($user['nama']) .
            "&email=" .
            urlencode($user['email'])
        );
        exit;

    } else {

        $stmt->close();
        header(
            "Location: masuk.php?error_login=" .
            urlencode(htmlspecialchars("Password salah!"))
        );
        exit;
    }

} else {

    $stmt->close();
    header(
        "Location: masuk.php?error_login=" .
        urlencode(htmlspecialchars("Email tidak ditemukan!"))
    );
    exit;
}

?>
