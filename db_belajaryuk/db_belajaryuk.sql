-- =====================================================
-- DATABASE BELAJARYUK - FULL INSTALLATION
-- MySQL / phpMyAdmin
-- Database yang digunakan aplikasi: db_belajaryuk
-- =====================================================
--
-- CATATAN:
-- 1. Import file ini ke phpMyAdmin untuk membuat database
-- 2. Jalankan setup.php untuk membuat akun admin & user
--    dengan password yang benar (karena hashing password
--    tidak bisa dilakukan di pure SQL)
-- 3. Tabel orders & order_details ada untuk kompatibilitas
--    masa depan, namun aplikasi saat ini menggunakan tabel
--    transaksi sebagai tabel transaksi utama
-- =====================================================

CREATE DATABASE IF NOT EXISTS db_belajaryuk
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE db_belajaryuk;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. USERS
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. PRODUCTS / E-LEARNING
-- =====================================================
CREATE TABLE IF NOT EXISTS products (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_produk VARCHAR(150) NOT NULL,
    kategori VARCHAR(100) NOT NULL DEFAULT 'E-Learning',
    subkategori VARCHAR(100) DEFAULT NULL,
    deskripsi TEXT DEFAULT NULL,
    benefit TEXT DEFAULT NULL,
    harga DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    harga_promo DECIMAL(15,2) DEFAULT NULL,
    promo_aktif TINYINT(1) NOT NULL DEFAULT 0,
    durasi VARCHAR(50) DEFAULT NULL,
    jadwal VARCHAR(100) DEFAULT NULL,
    gambar VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_products_kategori (kategori),
    KEY idx_products_subkategori (subkategori),
    KEY idx_products_promo (promo_aktif),
    KEY idx_products_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. BOOTCAMP
-- =====================================================
CREATE TABLE IF NOT EXISTS bootcamp (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    judul VARCHAR(255) NOT NULL,
    kategori VARCHAR(100) NOT NULL,
    mentor VARCHAR(255) NOT NULL,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    harga_promo DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    promo_aktif TINYINT(1) NOT NULL DEFAULT 0,
    tanggal_mulai DATE NOT NULL,
    tanggal_berakhir DATE NOT NULL,
    kuota INT NOT NULL DEFAULT 0,
    gambar TEXT DEFAULT NULL,
    benefit TEXT DEFAULT NULL,
    deskripsi TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bootcamp_kategori (kategori),
    KEY idx_bootcamp_promo (promo_aktif),
    KEY idx_bootcamp_tanggal (tanggal_mulai, tanggal_berakhir)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. ORDERS (tidak digunakan aktif oleh aplikasi)
-- =====================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    total_harga DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    tanggal_order DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    PRIMARY KEY (id),
    KEY idx_orders_user (user_id),
    KEY idx_orders_tanggal (tanggal_order),
    KEY idx_orders_status (status),
    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. ORDER DETAILS (tidak digunakan aktif oleh aplikasi)
-- =====================================================
CREATE TABLE IF NOT EXISTS order_details (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    harga DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_order_details_order (order_id),
    KEY idx_order_details_product (product_id),
    CONSTRAINT fk_order_details_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_details_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. TRANSAKSI / MIDTRANS (tabel transaksi utama)
-- =====================================================
CREATE TABLE IF NOT EXISTS transaksi (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id VARCHAR(100) NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    jenis_produk ENUM('elearning','bootcamp') NOT NULL,
    produk_id INT UNSIGNED NOT NULL,
    nama_produk VARCHAR(255) NOT NULL,
    gross_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_type VARCHAR(100) DEFAULT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,
    transaction_status VARCHAR(50) NOT NULL DEFAULT 'pending',
    fraud_status VARCHAR(50) DEFAULT NULL,
    snap_token TEXT DEFAULT NULL,
    transaction_time DATETIME DEFAULT NULL,
    settlement_time DATETIME DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_transaksi_order_id (order_id),
    KEY idx_transaksi_user (user_id),
    KEY idx_transaksi_produk (jenis_produk, produk_id),
    KEY idx_transaksi_status (transaction_status),
    KEY idx_transaksi_transaction_id (transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA
-- =====================================================

-- -----------------------------------------------------
-- Produk E-Learning
-- -----------------------------------------------------
INSERT INTO products (nama_produk, kategori, subkategori, deskripsi, benefit, harga, harga_promo, promo_aktif, durasi, jadwal, status) VALUES
(
    'Pengembangan Web Full-Stack',
    'E-Learning',
    'Web Development',
    'Belajar HTML, CSS, JavaScript, PHP, MySQL dari nol',
    'Materi lengkap|Sertifikat|Live Session|Project Portfolio',
    299000.00,
    199000.00,
    1,
    '8 minggu',
    'Senin & Rabu, 19:00-21:00',
    'active'
),
(
    'Digital Marketing Mastery',
    'E-Learning',
    'Digital Marketing',
    'Kuasai SEO, SEM, Social Media Marketing',
    'Praktik langsung|Sertifikat|Konsultasi|Template Siap Pakai',
    249000.00,
    179000.00,
    1,
    '6 minggu',
    'Selasa & Kamis, 19:00-21:00',
    'active'
);

-- -----------------------------------------------------
-- Bootcamp
-- -----------------------------------------------------
INSERT INTO bootcamp (judul, kategori, mentor, harga, harga_promo, promo_aktif, tanggal_mulai, tanggal_berakhir, kuota, benefit, deskripsi) VALUES
(
    'Bootcamp UI/UX Design Intensive',
    'Design',
    'Rina Susanti',
    1500000.00,
    999000.00,
    1,
    '2026-10-01',
    '2026-10-30',
    20,
    'Figma Pro|Mentoring|Portfolio Review|Job Ready',
    'Bootcamp intensif UI/UX Design selama 30 hari dengan mentor berpengalaman'
);

-- -----------------------------------------------------
-- CATATAN: Akun admin & user perlu dibuat via setup.php
-- karena password harus di-hash dengan PHP password_hash()
--
-- Jalankan: php setup.php
-- Atau buat akun manual melalui halaman register
-- Default credentials setelah setup:
--   Admin: admin@belajaryuk.com / Admin123!
--   User:  user@belajaryuk.com  / User123!
-- -----------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- SELESAI
-- Setelah import, database yang digunakan adalah:
-- db_belajaryuk
-- =====================================================
