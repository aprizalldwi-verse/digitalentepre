-- =====================================================
-- UPDATE BOOTCAMP KATEGORI + TAMBAH BOOTCAMP BARU
-- 
-- File ini meng-update kategori bootcamp existing agar
-- sesuai dengan filter: Front-End, Back-End, UI/UX Design,
-- Data & AI, Mobile Dev
--
-- Dan menambahkan bootcamp baru agar setiap kategori
-- memiliki minimal 3 bootcamp.
--
-- Jalankan file ini SETELAH seed_dummy.sql
-- =====================================================

USE db_belajaryuk;

SET NAMES utf8mb4;

-- =====================================================
-- 1. UPDATE KATEGORI BOOTCAMP EXISTING
-- =====================================================

-- Full Stack Web Developer Bootcamp: Web Development → Front-End
UPDATE bootcamp SET kategori = 'Front-End' WHERE id = 1;

-- Digital Marketing Bootcamp: Digital Marketing → Front-End
UPDATE bootcamp SET kategori = 'Front-End' WHERE id = 2;

-- UI/UX Designer Bootcamp: Design → UI/UX Design
UPDATE bootcamp SET kategori = 'UI/UX Design' WHERE id = 3;

-- Data Analyst Bootcamp: Data Science → Data & AI
UPDATE bootcamp SET kategori = 'Data & AI' WHERE id = 4;

-- Digital Entrepreneur Bootcamp: Business → Data & AI
UPDATE bootcamp SET kategori = 'Data & AI' WHERE id = 5;

-- Content Creator Bootcamp: Content → UI/UX Design
UPDATE bootcamp SET kategori = 'UI/UX Design' WHERE id = 6;

-- Python Developer Bootcamp: Programming → Back-End
UPDATE bootcamp SET kategori = 'Back-End' WHERE id = 7;

-- Laravel Developer Bootcamp: Web Development → Back-End
UPDATE bootcamp SET kategori = 'Back-End' WHERE id = 8;


-- =====================================================
-- 2. TAMBAH BOOTCAMP BARU
-- =====================================================
-- Setiap kategori minimal 3 bootcamp
-- Front-End:   id 1,2 (existing) + 2 baru = 4
-- Back-End:    id 7,8 (existing) + 1 baru = 3
-- UI/UX Design: id 3,6 (existing) + 1 baru = 3
-- Data & AI:   id 4,5 (existing) + 1 baru = 3
-- Mobile Dev:  0 (existing) + 3 baru = 3
-- Total: 16 bootcamp

INSERT INTO bootcamp (judul, kategori, mentor, harga, harga_promo, promo_aktif, tanggal_mulai, tanggal_berakhir, kuota, gambar, benefit, deskripsi, created_at) VALUES

-- Front-End (2 baru)
('Frontend Developer Bootcamp', 'Front-End', 'Rizky Firmansyah',
2800000.00, 2000000.00, 1, '2026-10-20', '2026-12-30', 25,
'assets/bootcamp/bootcamp_frontend.png',
'HTML5/CSS3/JavaScript|React/Vue.js|Responsive Design|Portfolio Project|Job Ready',
'Bootcamp intensif frontend development selama 10 minggu. Kuasai HTML, CSS, JavaScript, dan framework modern.',
'2026-07-10 08:00:00'),

('React Front-End Bootcamp', 'Front-End', 'Dewi Kartika',
3000000.00, 2200000.00, 1, '2026-11-01', '2027-01-15', 20,
'assets/bootcamp/bootcamp_react.png',
'React 18+|Redux Toolkit|Next.js|TypeScript|Real Project',
'Bootcamp React.js intensif dari dasar hingga advanced. Bangun aplikasi production-ready.',
'2026-07-15 09:00:00'),

-- Back-End (1 baru)
('Node.js Backend Bootcamp', 'Back-End', 'Ahmad Fauzi',
3200000.00, 2400000.00, 1, '2026-11-05', '2027-01-20', 20,
'assets/bootcamp/bootcamp_nodejs.png',
'Node.js/Express|MongoDB/PostgreSQL|REST API|Authentication|Deployment',
'Bootcamp backend development dengan Node.js. Bangun API scalable dan microservices.',
'2026-07-20 10:00:00'),

-- UI/UX Design (1 baru)
('Product Design Bootcamp', 'UI/UX Design', 'Maya Sari',
2500000.00, 1800000.00, 1, '2026-11-10', '2027-01-25', 25,
'assets/bootcamp/bootcamp_product_design.png',
'Design Thinking|User Research|Wireframing|Prototyping|Usability Testing',
'Bootcamp product design lengkap dari research hingga high-fidelity prototype.',
'2026-07-25 08:00:00'),

-- Data & AI (1 baru)
('AI & Machine Learning Bootcamp', 'Data & AI', 'Budi Santoso',
4500000.00, 3500000.00, 1, '2026-11-15', '2027-02-28', 15,
'assets/bootcamp/bootcamp_ai_ml.png',
'Python/TensorFlow|Machine Learning|Deep Learning|NLP|Computer Vision',
'Bootcamp AI dan Machine Learning intensif. Bangun model AI untuk masalah nyata.',
'2026-08-01 09:00:00'),

-- Mobile Dev (3 baru)
('Flutter Developer Bootcamp', 'Mobile Dev', 'Angga Pratama',
3000000.00, 2200000.00, 1, '2026-10-25', '2027-01-10', 20,
'assets/bootcamp/bootcamp_flutter.png',
'Dart/Flutter|UI Components|State Management|Firebase|Play Store Deploy',
'Bootcamp Flutter untuk membangun aplikasi mobile cross-platform.',
'2026-08-05 08:00:00'),

('Android Developer Bootcamp', 'Mobile Dev', 'Reza Firmansyah',
3200000.00, 2400000.00, 1, '2026-11-01', '2027-01-20', 20,
'assets/bootcamp/bootcamp_android.png',
'Kotlin/Java|Android Studio|Jetpack Compose|Room Database|Google Play',
'Bootcamp Android development modern dengan Kotlin dan Jetpack Compose.',
'2026-08-10 09:00:00'),

('React Native Bootcamp', 'Mobile Dev', 'Fajar Nugraha',
3000000.00, 2200000.00, 1, '2026-11-10', '2027-01-25', 20,
'assets/bootcamp/bootcamp_react_native.png',
'React Native|Expo|Redux|Native Modules|App Store Deploy',
'Bootcamp React Native untuk membangun aplikasi mobile dengan JavaScript.',
'2026-08-15 10:00:00');


-- =====================================================
-- 3. TAMBAH TRANSAKSI BOOTCAMP BARU
-- =====================================================
-- Transaksi untuk bootcamp baru (id 9-16)
-- Minimal beberapa transaksi per bootcamp baru

INSERT INTO transaksi (order_id, user_id, jenis_produk, produk_id, nama_produk, gross_amount, payment_type, transaction_id, transaction_status, fraud_status, transaction_time, settlement_time, created_at, updated_at) VALUES

-- Bootcamp Frontend Developer (id=9) - 2 pembeli
('BY-20260901120000-M7N8O9P0', 6, 'bootcamp', 9, 'Frontend Developer Bootcamp', 2000000.00, 'bank_transfer', 'TXN-B21', 'settlement', 'accept', '2026-09-01 12:05:00', '2026-09-01 12:10:00', '2026-09-01 12:00:00', '2026-09-01 12:10:00'),
('BY-20260915140000-N8O9P0Q1', 11, 'bootcamp', 9, 'Frontend Developer Bootcamp', 2000000.00, 'ewallet', 'TXN-B22', 'settlement', 'accept', '2026-09-15 14:05:00', '2026-09-15 14:10:00', '2026-09-15 14:00:00', '2026-09-15 14:10:00'),

-- Bootcamp React Front-End (id=10) - 1 pembeli
('BY-20260910110000-O9P0Q1R2', 18, 'bootcamp', 10, 'React Front-End Bootcamp', 2200000.00, 'bank_transfer', 'TXN-B23', 'settlement', 'accept', '2026-09-10 11:05:00', '2026-09-10 11:10:00', '2026-09-10 11:00:00', '2026-09-10 11:10:00'),

-- Bootcamp Node.js Backend (id=11) - 1 pembeli
('BY-20260920100000-P0Q1R2S3', 24, 'bootcamp', 11, 'Node.js Backend Bootcamp', 2400000.00, 'bank_transfer', 'TXN-B24', 'settlement', 'accept', '2026-09-20 10:05:00', '2026-09-20 10:10:00', '2026-09-20 10:00:00', '2026-09-20 10:10:00'),

-- Bootcamp Product Design (id=12) - 1 pembeli
('BY-20260925090000-Q1R2S3T4', 27, 'bootcamp', 12, 'Product Design Bootcamp', 1800000.00, 'ewallet', 'TXN-B25', 'settlement', 'accept', '2026-09-25 09:05:00', '2026-09-25 09:10:00', '2026-09-25 09:00:00', '2026-09-25 09:10:00'),

-- Bootcamp AI & ML (id=13) - 1 pembeli
('BY-20260928100000-R2S3T4U5', 29, 'bootcamp', 13, 'AI & Machine Learning Bootcamp', 3500000.00, 'bank_transfer', 'TXN-B26', 'settlement', 'accept', '2026-09-28 10:05:00', '2026-09-28 10:10:00', '2026-09-28 10:00:00', '2026-09-28 10:10:00'),

-- Bootcamp Flutter (id=14) - 1 pembeli
('BY-20260930080000-S3T4U5V6', 31, 'bootcamp', 14, 'Flutter Developer Bootcamp', 2200000.00, 'bank_transfer', 'TXN-B27', 'settlement', 'accept', '2026-09-30 08:05:00', '2026-09-30 08:10:00', '2026-09-30 08:00:00', '2026-09-30 08:10:00'),

-- Bootcamp Android (id=15) - 1 pembeli
('BY-20260930140000-T4U5V6W7', 32, 'bootcamp', 15, 'Android Developer Bootcamp', 2400000.00, 'ewallet', 'TXN-B28', 'settlement', 'accept', '2026-09-30 14:05:00', '2026-09-30 14:10:00', '2026-09-30 14:00:00', '2026-09-30 14:10:00'),

-- Bootcamp React Native (id=16) - 1 pembeli
('BY-20260930160000-U5V6W7X8', 3, 'bootcamp', 16, 'React Native Bootcamp', 2200000.00, 'bank_transfer', 'TXN-B29', 'settlement', 'accept', '2026-09-30 16:05:00', '2026-09-30 16:10:00', '2026-09-30 16:00:00', '2026-09-30 16:10:00');


-- =====================================================
-- RINGKASAN PERUBAHAN
-- =====================================================
-- Kategori bootcamp yang diupdate:
--   id 1: Web Development → Front-End
--   id 2: Digital Marketing → Front-End
--   id 3: Design → UI/UX Design
--   id 4: Data Science → Data & AI
--   id 5: Business → Data & AI
--   id 6: Content → UI/UX Design
--   id 7: Programming → Back-End
--   id 8: Web Development → Back-End
--
-- Bootcamp baru ditambahkan:
--   id 9:  Frontend Developer Bootcamp (Front-End)
--   id 10: React Front-End Bootcamp (Front-End)
--   id 11: Node.js Backend Bootcamp (Back-End)
--   id 12: Product Design Bootcamp (UI/UX Design)
--   id 13: AI & Machine Learning Bootcamp (Data & AI)
--   id 14: Flutter Developer Bootcamp (Mobile Dev)
--   id 15: Android Developer Bootcamp (Mobile Dev)
--   id 16: React Native Bootcamp (Mobile Dev)
--
-- Transaksi baru: 9 transaksi settlement
--
-- Total setelah update:
--   Bootcamp: 16
--   Transaksi: 109 (100 existing + 9 baru)
--   Settlement: 82 (73 existing + 9 baru)
--
-- Jumlah per kategori:
--   Front-End:   4 (id 1,2,9,10)
--   Back-End:    3 (id 7,8,11)
--   UI/UX Design: 3 (id 3,6,12)
--   Data & AI:   3 (id 4,5,13)
--   Mobile Dev:  3 (id 14,15,16)
-- =====================================================
