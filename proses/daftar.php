<?php
// login.php / auth.php (Default tampilan: Daftar Akun)
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar & Masuk — Belajaryuk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: #cbd5e1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        /* CONTAINER 3D BUKU */
        .book-wrapper {
            width: 100%;
            max-width: 900px;
            height: 560px;
            perspective: 2000px;
            position: relative;
        }

        .real-book {
            width: 100%;
            height: 100%;
            display: flex;
            position: relative;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 
                0 25px 50px -12px rgba(15, 23, 42, 0.25),
                0 10px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* TULANG TENGAH BUKU (SPINE) */
        .book-spine {
            position: absolute;
            left: 50%;
            top: 0;
            bottom: 0;
            width: 30px;
            transform: translateX(-50%);
            background: linear-gradient(to right, 
                rgba(0, 0, 0, 0.15) 0%, 
                rgba(0, 0, 0, 0.02) 40%, 
                rgba(0, 0, 0, 0.02) 60%, 
                rgba(0, 0, 0, 0.15) 100%);
            z-index: 20;
            pointer-events: none;
        }

        /* HALAMAN KIRI STABIL (INFO DAFTAR) */
        .page-left {
            width: 50%;
            height: 100%;
            padding: 40px;
            background: linear-gradient(135deg, #4338ca 0%, #312e81 100%);
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* HALAMAN KANAN STABIL (BASE FORM MASUK) */
        .page-right {
            width: 50%;
            height: 100%;
            padding: 40px;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* LEMBARAN KERTAS YANG DIBALIK */
        .flipper-page {
            position: absolute;
            right: 0;
            top: 0;
            width: 50%;
            height: 100%;
            transform-origin: left center;
            transform-style: preserve-3d;
            transition: transform 0.9s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 10;
        }

        /* SAAT DI-FLIP: MENAMPILKAN FORM MASUK */
        .real-book.flipped .flipper-page {
            transform: rotateY(-180deg);
        }

        /* MUKA DEPAN (FORM DAFTAR) & BELAKANG (COVER INFO MASUK) */
        .flipper-front, .flipper-back {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            padding: 40px;
            backface-visibility: hidden;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-sizing: border-box;
        }

        .flipper-front {
            background: #ffffff;
            box-shadow: inset 15px 0 25px -10px rgba(0, 0, 0, 0.08);
        }

        .flipper-back {
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
            color: #ffffff;
            transform: rotateY(180deg);
            justify-content: space-between;
            box-shadow: inset -15px 0 25px -10px rgba(0, 0, 0, 0.2);
        }

        /* STYLES UI FORM */
        .brand {
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            text-decoration: none;
            display: inline-block;
        }
        .brand span { color: #818cf8; }

        .banner-text h2 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 10px;
        }
        .banner-text p {
            font-size: 13px;
            color: #c7d2fe;
            line-height: 1.6;
        }

        .form-header h3 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .form-header p {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 16px;
        }

        .form-group {
            margin-bottom: 14px;
        }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .form-input {
            width: 100%;
            padding: 11px 14px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
            outline: none;
            background: #f8fafc;
        }
        .form-input:focus {
            border-color: #4338ca;
            background: #ffffff;
        }

        .btn-submit {
            width: 100%;
            background: #4338ca;
            color: #ffffff;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn-flip {
            background: none;
            border: none;
            color: #4338ca;
            font-weight: 700;
            cursor: pointer;
            font-size: 13px;
            text-decoration: underline;
            margin-left: 4px;
        }

        .switch-prompt {
            margin-top: 14px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }

        .btn-back-home {
            display: block;
            text-align: center;
            margin-top: 12px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .btn-back-home:hover {
            color: #4338ca;
        }

        .alert-message {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 12px;
            text-align: center;
        }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #dcfce7; color: #166534; }

        @media (max-width: 768px) {
            .book-spine, .page-left { display: none; }
            .page-right, .flipper-page { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="book-wrapper">
        <div class="real-book" id="myBook">
            
            <!-- SPINNER TENGAH BUKU -->
            <div class="book-spine"></div>

            <!-- 1. HALAMAN KIRI STABIL (INFO DAFTAR) -->
            <div class="page-left">
                <a href="../pages/index.html" class="brand"><span>BELAJAR</span>YUK</a>
                <div class="banner-text">
                    <h2>Bergabung Gratis!</h2>
                    <p>Dapatkan pengalaman belajar interaktif, sertifikat resmi, dan jaringan luas bersama talenta digital lainnya.</p>
                </div>
                <div style="font-size:12px; color:#818cf8;">🚀 Akses Kapan Saja</div>
            </div>

            <!-- 2. LEMBARAN KERTAS DIBALIK (FLIPPER) -->
            <div class="flipper-page">
                
                <!-- SISI DEPAN LEMBARAN: FORM DAFTAR (DEFAULT VIEW) -->
                <div class="flipper-front">
                    <div class="form-header">
                        <h3>Buat Akun Baru</h3>
                        <p>Lengkapi data di bawah untuk mendaftar.</p>
                    </div>

                    <?php if (isset($_GET['error_register'])): ?>
                        <div class="alert-message alert-error">
                            <?php echo htmlspecialchars($_GET['error_register']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="../proses/proses_daftar.php" method="POST">
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">
                        <div class="form-group">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-input" placeholder="Nama kamu" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" placeholder="nama@email.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kata Sandi</label>
                            <input type="password" name="password" class="form-input" placeholder="Minimal 8 karakter" required>
                        </div>
                        <button type="submit" class="btn-submit">Daftar Sekarang</button>
                    </form>

                    <div class="switch-prompt">
                        Sudah punya akun?
                        <button type="button" class="btn-flip" onclick="openLoginPage()">Masuk ke Akun</button>
                    </div>

                    <a href="../pages/index.html" class="btn-back-home">← Kembali ke Beranda</a>
                </div>

                <!-- SISI BELAKANG LEMBARAN: COVER MASUK (TAMPIL SAAT DI-FLIP) -->
                <div class="flipper-back">
                    <a href="../pages/index.html" class="brand"><span>BELAJAR</span>YUK</a>
                    <div class="banner-text">
                        <h2>Selamat Datang Kembali!</h2>
                        <p>Buka lembaran barumu hari ini. Akses kembali materi eksklusif dan lanjutkan progres belajarmu.</p>
                    </div>
                    <div style="font-size:12px; color:#818cf8;">⚡ E-Learning Platform</div>
                </div>

            </div>

            <!-- 3. HALAMAN KANAN STABIL (FORM MASUK) -->
            <div class="page-right">
                <div class="form-header">
                    <h3>Masuk ke Akun</h3>
                    <p>Silakan isi email dan kata sandi kamu.</p>
                </div>

                <?php if (isset($_GET['error_login'])): ?>
                    <div class="alert-message alert-error">
                        <?php echo htmlspecialchars($_GET['error_login']); ?>
                    </div>
                <?php endif; ?>

                <form action="../proses/proses_masuk.php" method="POST">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="nama@email.com" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kata Sandi</label>
                        <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                    </div>
                    <button type="submit" class="btn-submit">Masuk</button>
                </form>

                <div class="switch-prompt">
                    Belum punya akun?
                    <button type="button" class="btn-flip" onclick="openRegisterPage()">Buka Halaman Daftar</button>
                </div>

                <a href="../pages/index.html" class="btn-back-home">← Kembali ke Beranda</a>
            </div>

        </div>
    </div>

    <script>
        const myBook = document.getElementById('myBook');

        function openLoginPage() {
            myBook.classList.add('flipped');
        }

        function openRegisterPage() {
            myBook.classList.remove('flipped');
        }

        // Buka form masuk secara otomatis jika terdapat parameter eror dari proses_masuk.php
        <?php if (isset($_GET['error_login'])): ?>
            openLoginPage();
        <?php endif; ?>
    </script>
</body>
</html>