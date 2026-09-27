# BelajarYuk - Platform E-Learning

Platform e-learning lengkap dengan sistem pembayaran Midtrans, panel admin, dan manajemen kursus/Bootcamp.

## Requirements

- **XAMPP** (Apache + MySQL + PHP 7.4+)
- **Browser** (Chrome, Firefox, Edge, Safari)
- **PHP Extensions**: curl, mysqli, json, mbstring

## Installation

### 1. Setup Database

1. Start **Apache** dan **MySQL** di XAMPP Control Panel
2. Buka **phpMyAdmin** (http://localhost/phpmyadmin)
3. Import file `db_belajaryuk/db_belajaryuk.sql`
   - Klik tab **Import**
   - Pilih file `db_belajaryuk.sql`
   - Klik **Go**

### 2. Buat Akun Admin & User

Buka browser dan akses:

```
http://localhost/project-digital-entrepreneurship-fixed/setup.php
```

Halaman ini akan membuat akun demo:
- **Admin**: admin@belajaryuk.com / Admin123!
- **User**: user@belajaryuk.com / User123!

> Jalankan setup.php hanya sekali. Jika sudah dijalankan, jalankan lagi tidak akan membuat akun duplikat.

### 3. (Opsional) Tambah Data Dummy

Untuk membuat website terlihat seperti sudah digunakan oleh banyak pengguna:

1. Import seed data ke database:
   - Buka **phpMyAdmin**
   - Pilih database `db_belajaryuk`
   - Import file `db_belajaryuk/seed_dummy.sql`

2. Generate gambar placeholder:
   ```
   php seed_images.php
   ```

3. Update password user dummy:
   ```
   php seed_update_password.php
   ```

Data dummy yang ditambahkan:
- 30 user baru
- 25 kursus E-Learning
- 8 program Bootcamp
- 100+ transaksi (beragam status)

### 4. Akses Website

**User Website:**
```
http://localhost/project-digital-entrepreneurship-fixed/pages/index.html
```

**Admin Panel:**
```
http://localhost/project-digital-entrepreneurship-fixed/admin/
```

## Login Demo

### Admin
- Email: `admin@belajaryuk.com`
- Password: `Admin123!`

### User
- Email: `user@belajaryuk.com`
- Password: `User123!`

## Database

```
Database: db_belajaryuk
Host:     localhost
User:     root
Password: (kosong)
```

### Tables

| Table | Description |
|-------|-------------|
| `users` | Data user (admin & user) |
| `products` | Produk E-Learning |
| `bootcamp` | Program Bootcamp |
| `transaksi` | Transaksi pembayaran (Midtrans) |
| `orders` | Orders (legacy, unused) |
| `order_details` | Order details (legacy, unused) |
| `expenses` | Pengeluaran Admin, dibuat otomatis secara aditif saat panel dipakai |
| `admin_settings` | Kontak, tautan sosial, dan pengaturan Admin |
| `partnerships` | Data kerja sama Admin |

Pendaftaran pada panel Admin menggunakan record di `transaksi` (termasuk relasi `user_id`, `jenis_produk`, dan `produk_id`); project belum memiliki tabel pendaftaran khusus. Status user ditambahkan dengan default `active` secara aditif saat endpoint Admin User pertama kali dipakai. Tabel tambahan tidak diisi data dummy.

## Configuration

### Database
File: `config/koneksi.php`

```php
$host = "localhost";
$user = "root";
$password = "";
$database = "db_belajaryuk";
```

### Midtrans (Pembayaran)
File: `proses/midtrans_config.php`

Saat ini menggunakan **Midtrans Sandbox**. Untuk production, ganti:

```php
define('MIDTRANS_SERVER_KEY', 'Mid-server-XXXXX'); // Ganti dengan server key production
define('MIDTRANS_CLIENT_KEY', 'Mid-client-XXXXX'); // Ganti dengan client key production
define('MIDTRANS_API_URL', 'https://app.midtrans.com/snap/v1/transactions'); // URL production
```

Dapatkan key di: https://dashboard.midtrans.com/

## Fitur

### User
- Registrasi & Login
- Browse kelas E-Learning
- Browse Bootcamp
- Search & Filter kategori
- Detail produk
- Pembayaran via Midtrans (Transfer Bank, E-Wallet, QRIS, etc.)
- Kelas Saya (produk yang sudah dibeli)
- Riwayat Transaksi
- Cetak Bukti Transaksi
- Profil & Logout

### Admin
- Dashboard statistik (total penjualan, user, produk)
- Grafik penjualan bulanan
- CRUD E-Learning (Tambah, Edit, Hapus)
- CRUD Bootcamp (Tambah, Edit, Hapus)
- Kelola User (Lihat, Hapus)
- Lihat Semua Transaksi
- Filter & Search transaksi
- Rekap penjualan, pendaftaran, pengeluaran, dan profit bersih
- Kalender aktivitas dari transaksi dan pengeluaran
- Pencarian global dan pengaturan Admin berbasis database

## Struktur Folder

```
project-digital-entrepreneurship-fixed/
├── admin/                  # Halaman admin (HTML + PHP wrapper)
│   ├── index.php           # Redirect ke dashboard
│   ├── dashboard.php       # Dashboard admin (dengan session check)
│   ├── elearning_admin.php # Kelola E-Learning
│   ├── bootcamp_admin.php  # Kelola Bootcamp
│   ├── user_admin.php      # Kelola User
│   └── transaksi_admin.php # Kelola Transaksi
├── admin-php/              # API backend admin
│   ├── dashboard.php       # API dashboard
│   ├── elearning.php       # API CRUD E-Learning
│   ├── bootcamp.php        # API CRUD Bootcamp
│   ├── user.php            # API user management
│   └── transaksi_admin.php # API transaksi admin
├── assets/                 # Assets (gambar course)
├── config/                 # Konfigurasi database
│   └── koneksi.php
├── course/                 # Folder upload bootcamp images
├── css/                    # Stylesheets
│   ├── style.css           # CSS user
│   └── admin.css           # CSS admin
├── db_belajaryuk/          # Database SQL
│   └── db_belajaryuk.sql
├── js/                     # JavaScript
│   ├── script.js           # Main landing page
│   ├── elearning.js        # E-Learning listing
│   ├── detail.js           # Detail course
│   ├── bootcamp_user.js    # Bootcamp listing
│   ├── detail_bootcamp.js  # Detail bootcamp
│   ├── kelas.js            # Kelas Saya
│   ├── riwayat.js          # Riwayat transaksi
│   ├── komunitas.js        # Komunitas
│   ├── admin.js            # Dashboard admin
│   ├── elearning_admin.js  # CRUD E-Learning admin
│   ├── bootcamp_admin.js   # CRUD Bootcamp admin
│   └── theme.js            # Theme toggle
├── pages/                  # Halaman user
│   ├── index.html          # Beranda
│   ├── elearning.html      # Daftar E-Learning
│   ├── detail.html         # Detail E-Learning
│   ├── bootcamp.html       # Daftar Bootcamp
│   ├── detail_bootcamp.html# Detail Bootcamp
│   ├── kelas.html          # Kelas Saya
│   ├── riwayat.html        # Riwayat Transaksi
│   └── komunitas.html      # Komunitas
├── db_belajaryuk/          # Database SQL
│   ├── db_belajaryuk.sql
│   └── seed_dummy.sql      # Data dummy (opsional)
├── proses/                 # Backend processing
│   ├── daftar.php          # Halaman registrasi
│   ├── masuk.php           # Halaman login
│   ├── proses_daftar.php   # Proses registrasi
│   ├── proses_masuk.php    # Proses login
│   ├── logout.php          # Proses logout
│   ├── status_login.php    # Cek status login
│   ├── create_payment.php  # Buat pembayaran Midtrans
│   ├── notification.php    # Webhook notifikasi Midtrans
│   ├── kelas.php           # API Kelas Saya
│   ├── riwayat.php         # API Riwayat
│   ├── cetak_transaksi.php # Cetak bukti transaksi
│   ├── midtrans_config.php # Konfigurasi Midtrans
│   └── test_midtrans.php   # Test integrasi Midtrans
├── setup.php               # Setup akun admin & user
├── seed_images.php         # Generate gambar placeholder
├── seed_update_password.php # Update password user dummy
└── README.md
```

## Keamanan

- Password di-hash menggunakan `password_hash()` (bcrypt)
- Prepared statements untuk semua query database
- Session-based authentication
- Role-based access control (admin/user)
- Input validation di frontend & backend
- Output escaping untuk mencegah XSS
- CSRF-safe form submissions
- File upload validation (MIME type, extension, size)
- Admin pages dilindungi session check

## Payment Gateway (Midtrans)

Integrasi Midtrans Snap untuk pembayaran:
- Transfer Bank (BCA, BRI, Mandiri, BNI, etc.)
- E-Wallet (GoPay, OVO, DANA, ShopeePay, etc.)
- QRIS
- Kartu Kredit/Debit
- Convenience Store (Alfamart, Indomaret)

### Konfigurasi Midtrans

1. Daftar di https://dashboard.midtrans.com/
2. Ambil **Server Key** dan **Client Key** dari dashboard
3. Edit file `proses/midtrans_config.php`
4. Ganti nilai `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY`
5. Untuk production, ganti `MIDTRANS_API_URL` ke URL production Midtrans

## Development

Untuk development, Midtrans sandbox sudah dikonfigurasi. Test payment bisa dilakukan dengan kartu test Midtrans:

- **Kartu Berhasil**: 4811 1111 1111 1114, Exp: 12/25, CVV: 123
- **Kartu Gagal**: 4811 1111 1111 1118

## License

Projek ini dibuat untuk keperluan akademik/pembelajaran.
