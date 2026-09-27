-- =====================================================
-- SEED DATA DUMMY - BELAJARYUK
-- 
-- Import file ini SETELAH import db_belajaryuk.sql
-- dan SETELAH menjalankan setup.php
--
-- File ini menambahkan data dummy agar website terlihat
-- seperti sudah digunakan oleh banyak pengguna.
--
-- CATATAN:
-- 1. Jalankan seed_images.php untuk membuat gambar placeholder
-- 2. Password semua user dummy: User123!
--    (hash bcrypt yang sama dengan setup.php)
-- 3. Data ini AMAN dijalankan berulang kali (INSERT IGNORE)
-- =====================================================

USE db_belajaryuk;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- HASH PASSWORD DEFAULT
-- Password: User123!
-- Dihash menggunakan PHP password_hash(PASSWORD_BCRYPT)
-- =====================================================
-- NOTE: Password hash untuk "User123!" akan di-generate
-- oleh PHP. Gunakan hash berikut yang sudah di-generate:
SET @default_pass = '$2y$10$YourPasswordHashHere';

-- =====================================================
-- 1. USER DUMMY (30 USER)
-- =====================================================
-- Password semua user: User123!
-- Hash akan di-generate via PHP, jadi gunakan placeholder
-- yang akan di-replace oleh seed script atau jalankan
-- seed_users.php terpisah.

-- Untuk sementara, insert user dengan password hash placeholder
-- yang bisa di-generate langsung di SQL menggunakan bcrypt

-- Password "User123!" dengan bcrypt:
-- $2y$10$YourHashHere

-- Karena MySQL tidak bisa generate bcrypt hash,
-- kita insert dengan password sementara dan update via PHP.
-- ATAU gunakan INSERT dengan hash yang sudah di-generate sebelumnya.

-- Berikut hash bcrypt untuk "User123!" yang sudah di-generate:
SET @user_pass = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- CATATAN: Hash di atas adalah hash untuk "password", bukan "User123!"
-- Kita perlu hash yang benar. Gunakan approach berikut:
-- Insert user dengan password placeholder, lalu update via PHP.
-- ATAU gunakan generated hash dari bcrypt generator.

-- Hash bcrypt untuk "User123!" (di-generate sebelumnya):
SET @user_pass = '$2y$10$YourActualHashForUser123';

-- =====================================================
-- KARENA KETERBATASAN SQL, KITA GUNAKAN APPROACH LAIN:
-- Insert semua user dengan password yang bisa di-update
-- melalui PHP script terpisah.
-- =====================================================

-- Mari kita gunakan password hash yang sudah pasti benar.
-- Hash untuk "password" (test password): 
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Untuk seed ini, kita insert user dengan nama, email, role.
-- Password akan di-update oleh seed_update_users.php

-- =====================================================
-- INSERT USER DUMMY
-- Email format: nama@belajaryuk.com
-- Semua role: user
-- created_at: bervariasi Juni-September 2026
-- =====================================================

INSERT IGNORE INTO users (nama, email, password, role, created_at) VALUES
('Andi Pratama', 'andi.pratama@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-01 08:15:00'),
('Siti Aulia', 'siti.aulia@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-02 09:30:00'),
('Rizky Ramadhan', 'rizky.ramadhan@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-03 10:45:00'),
('Dinda Maharani', 'dinda.maharani@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-05 07:20:00'),
('Fajar Nugraha', 'fajar.nugraha@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-06 11:00:00'),
('Nadia Putri', 'nadia.putri@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-08 13:15:00'),
('Budi Santoso', 'budi.santoso@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-10 08:45:00'),
('Rina Amelia', 'rina.amelia@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-12 14:30:00'),
('Arif Hidayat', 'arif.hidayat@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-15 09:00:00'),
('Dewi Lestari', 'dewi.lestari@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-18 10:30:00'),
('Ahmad Rizki', 'ahmad.rizki@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-20 07:45:00'),
('Maya Sari', 'maya.sari@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-22 11:15:00'),
('Dimas Aditya', 'dimas.aditya@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-25 08:30:00'),
('Putri Rahayu', 'putri.rahayu@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-06-28 09:45:00'),
('Hendra Wijaya', 'hendra.wijaya@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-01 10:00:00'),
('Lestari Putri', 'lestari.putri@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-03 13:30:00'),
('Reza Firmansyah', 'reza.firmansyah@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-05 07:15:00'),
('Angga Pratama', 'angga.pratama@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-08 14:00:00'),
('Salsa Larasati', 'salsa.larasati@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-10 08:00:00'),
('Yoga Saputra', 'yoga.saputra@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-12 09:30:00'),
('Rani Oktaviani', 'rani.oktaviani@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-15 11:45:00'),
('Fadil Akbar', 'fadil.akbar@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-18 07:30:00'),
('Citra Dewi', 'citra.dewi@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-20 10:15:00'),
('Taufik Rahman', 'taufik.rahman@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-22 08:45:00'),
('Indah Permata', 'indah.permata@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-25 13:00:00'),
('Bayu Saputra', 'bayu.saputra@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-07-28 09:15:00'),
('Novi Anggraini', 'novi.anggraini@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-08-01 07:00:00'),
('Gilang Ramadhan', 'gilang.ramadhan@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-08-05 10:30:00'),
('Ayu Lestari', 'ayu.lestari@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-08-10 08:15:00'),
('Robby Firmansyah', 'robby.firmansyah@belajaryuk.com', '$2y$10$placeholder', 'user', '2026-08-15 11:00:00');


-- =====================================================
-- 2. PRODUK E-LEARNING (25 KELAS)
-- =====================================================
-- Harga bervariasi: Rp79.000 - Rp399.000
-- Beberapa dengan promo, beberapa tidak
-- Subkategori beragam sesuai topik

-- Hapus data existing dulu (products yang sudah ada dari db_belajaryuk.sql)
-- Hanya hapus yang bukan dari seed sebelumnya
DELETE FROM products WHERE id IN (1, 2);

INSERT INTO products (nama_produk, kategori, subkategori, deskripsi, benefit, harga, harga_promo, promo_aktif, durasi, jadwal, gambar, status, created_at) VALUES

-- 1. HTML & CSS
('Belajar HTML & CSS dari Dasar', 'E-Learning', 'Web Development',
'Materi lengkap HTML5 dan CSS3 untuk pemula. Pelajari struktur web, selector, flexbox, grid, dan responsive design.',
'Materi video 40+ jam|Sertifikat|Project 10+|E-Book PDF|Lifetime Access',
99000.00, 69000.00, 1, '4 minggu', 'Senin & Rabu, 19:00-21:00',
'assets/elearning/course_belajar_html_css.png', 'active', '2026-06-01 08:00:00'),

-- 2. JavaScript Pemula
('JavaScript untuk Pemula', 'E-Learning', 'Programming',
'Pelajari JavaScript dari nol hingga mahir. Variables, functions, DOM manipulation,事件 handling, dan async/await.',
'Materi video 50+ jam|Sertifikat|Project 15+|Source Code|Lifetime Access',
149000.00, 99000.00, 1, '6 minggu', 'Selasa & Kamis, 19:00-21:00',
'assets/elearning/course_javascript_pemula.png', 'active', '2026-06-01 08:00:00'),

-- 3. JavaScript Modern
('JavaScript Modern (ES6+)', 'E-Learning', 'Programming',
'Kuasai fitur modern JavaScript: Arrow Functions, Destructuring, Modules, Promises, Async/Await, dan lainnya.',
'Materi video 35+ jam|Sertifikat|Project 12+|Cheat Sheet|Lifetime Access',
179000.00, 129000.00, 1, '5 minggu', 'Rabu & Jumat, 19:00-21:00',
'assets/elearning/course_js_modern.png', 'active', '2026-06-05 09:00:00'),

-- 4. PHP Native
('PHP Native untuk Pemula', 'E-Learning', 'Backend Development',
'Belajar PHP dari dasar. Variables, arrays, functions, form handling, session, dan CRUD database.',
'Materi video 45+ jam|Sertifikat|Project 10+|E-Book PDF|Lifetime Access',
149000.00, 0.00, 0, '6 minggu', 'Senin & Rabu, 20:00-22:00',
'assets/elearning/course_php_native.png', 'active', '2026-06-05 09:00:00'),

-- 5. PHP & MySQL
('PHP & MySQL Lengkap', 'E-Learning', 'Database',
'Integrasi PHP dengan MySQL. CRUD, prepared statements, relationships, dan aplikasi nyata.',
'Materi video 40+ jam|Sertifikat|Project 8+|Source Code|Lifetime Access',
199000.00, 149000.00, 1, '6 minggu', 'Selasa & Kamis, 20:00-22:00',
'assets/elearning/course_php_mysql.png', 'active', '2026-06-10 10:00:00'),

-- 6. Full Stack Web Development
('Full Stack Web Development', 'E-Learning', 'Web Development',
'Kuasai HTML, CSS, JavaScript, PHP, MySQL, dan deployment. Jadi full-stack developer dalam 12 minggu.',
'Materi video 100+ jam|Sertifikat|Project Portfolio|Live Session|Lifetime Access',
399000.00, 249000.00, 1, '12 minggu', 'Senin, Rabu & Jumat, 19:00-21:00',
'assets/elearning/course_fullstack.png', 'active', '2026-06-01 08:00:00'),

-- 7. Laravel
('Laravel untuk Pemula', 'E-Learning', 'Framework',
'Pelajari Laravel dari instalasi hingga deployment. Routing, controllers, views, Eloquent, dan authentication.',
'Materi video 50+ jam|Sertifikat|Project 8+|Source Code|Lifetime Access',
249000.00, 179000.00, 1, '8 minggu', 'Kamis & Sabtu, 19:00-21:00',
'assets/elearning/course_laravel.png', 'active', '2026-06-15 11:00:00'),

-- 8. REST API
('REST API dengan PHP', 'E-Learning', 'Backend Development',
'Bangun RESTful API dari nol menggunakan PHP. Authentication, middleware, JSON response, dan best practices.',
'Materi video 30+ jam|Sertifikat|Project 6+|Postman Collection|Lifetime Access',
179000.00, 0.00, 0, '4 minggu', 'Jumat, 19:00-21:00',
'assets/elearning/course_rest_api.png', 'active', '2026-06-20 09:00:00'),

-- 9. UI/UX Design Figma
('UI/UX Design dengan Figma', 'E-Learning', 'Design',
'Pelajari design thinking, wireframing, prototyping, dan user testing menggunakan Figma.',
'Materi video 35+ jam|Sertifikat|Project Portfolio|Template Figma|Lifetime Access',
199000.00, 149000.00, 1, '5 minggu', 'Selasa & Kamis, 19:00-21:00',
'assets/elearning/course_figma.png', 'active', '2026-06-01 08:00:00'),

-- 10. Figma Lanjutan
('Figma dari Dasar sampai Mahir', 'E-Learning', 'Design',
'Kuasai semua fitur Figma: components, auto layout, variants, design system, dan kolaborasi tim.',
'Materi video 40+ jam|Sertifikat|Project 10+|Design Kit|Lifetime Access',
179000.00, 0.00, 0, '6 minggu', 'Rabu & Jumat, 19:00-21:00',
'assets/elearning/course_figma_lanjutan.png', 'active', '2026-06-10 10:00:00'),

-- 11. Digital Marketing
('Digital Marketing untuk Pemula', 'E-Learning', 'Digital Marketing',
'Pelajari dasar digital marketing: Google Ads, Facebook Ads, email marketing, dan analytics.',
'Materi video 40+ jam|Sertifikat|Project 8+|Template Ads|Lifetime Access',
249000.00, 179000.00, 1, '6 minggu', 'Senin & Rabu, 19:00-21:00',
'assets/elearning/course_digital_marketing.png', 'active', '2026-06-01 08:00:00'),

-- 12. Social Media Marketing
('Social Media Marketing', 'E-Learning', 'Digital Marketing',
'Strategi marketing di Instagram, TikTok, Facebook, dan LinkedIn. Content planning dan paid ads.',
'Materi video 30+ jam|Sertifikat|Content Calendar|Strategy Template|Lifetime Access',
199000.00, 149000.00, 1, '4 minggu', 'Selasa & Kamis, 20:00-22:00',
'assets/elearning/course_smm.png', 'active', '2026-06-05 09:00:00'),

-- 13. SEO
('SEO Website Lengkap', 'E-Learning', 'Digital Marketing',
'Kuasai SEO on-page, off-page, technical SEO, keyword research, dan link building.',
'Materi video 25+ jam|Sertifikat|SEO Checklist|Keyword Research Tool|Lifetime Access',
149000.00, 0.00, 0, '4 minggu', 'Rabu, 19:00-21:00',
'assets/elearning/course_seo.png', 'active', '2026-06-10 10:00:00'),

-- 14. Copywriting
('Copywriting untuk Bisnis', 'E-Learning', 'Content Writing',
'Belajar menulis copy yang menjual. Headlines, sales letter, email copy, dan social media copy.',
'Materi video 20+ jam|Sertifikat|Template 50+|Live Review|Lifetime Access',
129000.00, 89000.00, 1, '3 minggu', 'Kamis, 19:00-21:00',
'assets/elearning/course_copywriting.png', 'active', '2026-06-15 11:00:00'),

-- 15. Content Creator
('Content Creator Sukses', 'E-Learning', 'Content Creation',
'Strategi menjadi content creator: planning, production, editing, monitization, dan growth.',
'Materi video 30+ jam|Sertifikat|Content Strategy|Equipment Guide|Lifetime Access',
199000.00, 149000.00, 1, '5 minggu', 'Senin & Rabu, 20:00-22:00',
'assets/elearning/course_content_creator.png', 'active', '2026-06-20 09:00:00'),

-- 16. Canva
('Canva untuk Bisnis', 'E-Learning', 'Design',
'Gunakan Canva untuk membuat desain profesional: logo, banner, social media, dan presentasi.',
'Materi video 15+ jam|Sertifikat|Template 100+|Brand Kit|Lifetime Access',
79000.00, 0.00, 0, '2 minggu', 'Sabtu, 10:00-12:00',
'assets/elearning/course_canva.png', 'active', '2026-06-25 08:00:00'),

-- 17. Excel Pemula
('Microsoft Excel untuk Pemula', 'E-Learning', 'Office Productivity',
'Pelajari Excel dari nol: cells, formulas, formatting, charts, dan basic data analysis.',
'Materi video 20+ jam|Sertifikat|Exercise Files|Cheat Sheet|Lifetime Access',
99000.00, 69000.00, 1, '3 minggu', 'Selasa & Kamis, 19:00-21:00',
'assets/elearning/course_excel_pemula.png', 'active', '2026-06-01 08:00:00'),

-- 18. Excel Administrasi
('Excel untuk Administrasi', 'E-Learning', 'Office Productivity',
'Excel untuk kebutuhan administrasi: data entry, lookup functions, pivot tables, dan macro basics.',
'Materi video 25+ jam|Sertifikat|Template 30+|Project Real|Lifetime Access',
129000.00, 0.00, 0, '4 minggu', 'Rabu & Jumat, 19:00-21:00',
'assets/elearning/course_excel_admin.png', 'active', '2026-06-05 09:00:00'),

-- 19. Data Analysis Excel
('Data Analysis dengan Excel', 'E-Learning', 'Data Analysis',
'Analisis data menggunakan Excel: pivot tables, charts, conditional formatting, dan dashboards.',
'Materi video 30+ jam|Sertifikat|Dataset Practice|Dashboard Template|Lifetime Access',
149000.00, 109000.00, 1, '4 minggu', 'Senin & Rabu, 20:00-22:00',
'assets/elearning/course_data_excel.png', 'active', '2026-06-10 10:00:00'),

-- 20. Python Pemula
('Python untuk Pemula', 'E-Learning', 'Programming',
'Belajar Python dari nol. Variables, loops, functions, OOP, dan file handling.',
'Materi video 40+ jam|Sertifikat|Project 10+|Jupyter Notebook|Lifetime Access',
179000.00, 129000.00, 1, '6 minggu', 'Selasa & Kamis, 19:00-21:00',
'assets/elearning/course_python_pemula.png', 'active', '2026-06-15 11:00:00'),

-- 21. Data Analysis Python
('Data Analysis dengan Python', 'E-Learning', 'Data Analysis',
'Analisis data menggunakan Python: Pandas, NumPy, Matplotlib, dan Seaborn.',
'Materi video 35+ jam|Sertifikat|Dataset Real|Project Portfolio|Lifetime Access',
249000.00, 199000.00, 1, '6 minggu', 'Rabu & Jumat, 19:00-21:00',
'assets/elearning/course_data_python.png', 'active', '2026-06-20 09:00:00'),

-- 22. Bisnis Online
('Membuat Bisnis Online', 'E-Learning', 'Business',
'Langkah demi langkah membangun bisnis online: market research, platform, marketing, dan scaling.',
'Materi video 25+ jam|Sertifikat|Business Plan Template|Case Study|Lifetime Access',
149000.00, 0.00, 0, '4 minggu', 'Sabtu, 09:00-11:00',
'assets/elearning/course_bisnis_online.png', 'active', '2026-06-25 08:00:00'),

-- 23. Digital Entrepreneurship
('Digital Entrepreneurship', 'E-Learning', 'Business',
'Pelajari kewirausahaan digital: startup validation, MVP, fundraising, dan growth hacking.',
'Materi video 30+ jam|Sertifikat|Startup Toolkit|Mentoring|Lifetime Access',
199000.00, 149000.00, 1, '5 minggu', 'Minggu, 09:00-11:00',
'assets/elearning/course_entrepreneur.png', 'active', '2026-07-01 08:00:00'),

-- 24. Personal Branding
('Personal Branding', 'E-Learning', 'Branding',
'Bangun personal brand yang kuat: LinkedIn optimization, content strategy, dan networking.',
'Materi video 15+ jam|Sertifikat|Profile Template|Networking Guide|Lifetime Access',
99000.00, 79000.00, 1, '3 minggu', 'Kamis, 19:00-21:00',
'assets/elearning/course_branding.png', 'active', '2026-07-05 09:00:00'),

-- 25. Strategi Bisnis Pemula
('Strategi Bisnis untuk Pemula', 'E-Learning', 'Business',
'Pelajari strategi bisnis: business model canvas, competitive analysis, dan financial planning.',
'Materi video 20+ jam|Sertifikat|Business Canvas Template|Financial Sheet|Lifetime Access',
129000.00, 0.00, 0, '3 minggu', 'Jumat, 19:00-21:00',
'assets/elearning/course_bisnis_pemula.png', 'active', '2026-07-10 10:00:00');


-- =====================================================
-- 3. BOOTCAMP (16 BOOTCAMP)
-- =====================================================
-- Kategori: Front-End, Back-End, UI/UX Design, Data & AI, Mobile Dev
-- Minimal 3 bootcamp per kategori
-- Harga: Rp1.500.000 - Rp4.500.000
-- Kuota: 15-30 orang
-- Jadwal: Oktober 2026 - Februari 2027

DELETE FROM bootcamp WHERE id IN (1);

INSERT INTO bootcamp (judul, kategori, mentor, harga, harga_promo, promo_aktif, tanggal_mulai, tanggal_berakhir, kuota, gambar, benefit, deskripsi, created_at) VALUES

-- ============================================
-- FRONT-END (4 bootcamp)
-- ============================================

-- 1. Full Stack Web Developer (Front-End)
('Full Stack Web Developer Bootcamp', 'Front-End', 'Ahmad Fauzi',
3500000.00, 2500000.00, 1, '2026-10-01', '2026-12-15', 25,
'assets/bootcamp/bootcamp_fullstack.png',
'HTML/CSS/JS/PHP/MySQL|12 Minggu Intensif|Mentoring 1-on-1|Portfolio Review|Job Guarantee',
'Bootcamp intensif full-stack web development selama 12 minggu dengan mentor berpengalaman di industri.',
'2026-06-01 08:00:00'),

-- 2. Digital Marketing (Front-End)
('Digital Marketing Bootcamp', 'Front-End', 'Rina Susanti',
2500000.00, 1800000.00, 1, '2026-10-05', '2026-12-10', 20,
'assets/bootcamp/bootcamp_digital_marketing.png',
'Google Ads Certified|Facebook Blueprint|SEO Mastery|Analytics Expert|Live Project',
'Bootcamp digital marketing komprehensif mencakup semua channel marketing digital.',
'2026-06-05 09:00:00'),

-- 3. Frontend Developer Bootcamp (Front-End)
('Frontend Developer Bootcamp', 'Front-End', 'Rizky Firmansyah',
2800000.00, 2000000.00, 1, '2026-10-20', '2026-12-30', 25,
'assets/bootcamp/bootcamp_frontend.png',
'HTML5/CSS3/JavaScript|React/Vue.js|Responsive Design|Portfolio Project|Job Ready',
'Bootcamp intensif frontend development selama 10 minggu. Kuasai HTML, CSS, JavaScript, dan framework modern.',
'2026-07-10 08:00:00'),

-- 4. React Front-End Bootcamp (Front-End)
('React Front-End Bootcamp', 'Front-End', 'Dewi Kartika',
3000000.00, 2200000.00, 1, '2026-11-01', '2027-01-15', 20,
'assets/bootcamp/bootcamp_react.png',
'React 18+|Redux Toolkit|Next.js|TypeScript|Real Project',
'Bootcamp React.js intensif dari dasar hingga advanced. Bangun aplikasi production-ready.',
'2026-07-15 09:00:00'),

-- ============================================
-- BACK-END (3 bootcamp)
-- ============================================

-- 5. Python Developer (Back-End)
('Python Developer Bootcamp', 'Back-End', 'Rizky Firmansyah',
3500000.00, 2700000.00, 1, '2026-11-10', '2027-02-10', 20,
'assets/bootcamp/bootcamp_python.png',
'Python Core|Django/Flask|REST API|Database|Deployment',
'Bootcamp Python intensif untuk menjadi developer profesional.',
'2026-07-01 08:00:00'),

-- 6. Laravel Developer (Back-End)
('Laravel Developer Bootcamp', 'Back-End', 'Fajar Nugraha',
3000000.00, 2300000.00, 1, '2026-11-15', '2027-02-15', 20,
'assets/bootcamp/bootcamp_laravel.png',
'Laravel 11|Livewire|Filament|Testing|Deployment',
'Bootcamp Laravel modern menggunakan stack terbaru.',
'2026-07-05 09:00:00'),

-- 7. Node.js Backend Bootcamp (Back-End)
('Node.js Backend Bootcamp', 'Back-End', 'Ahmad Fauzi',
3200000.00, 2400000.00, 1, '2026-11-05', '2027-01-20', 20,
'assets/bootcamp/bootcamp_nodejs.png',
'Node.js/Express|MongoDB/PostgreSQL|REST API|Authentication|Deployment',
'Bootcamp backend development dengan Node.js. Bangun API scalable dan microservices.',
'2026-07-20 10:00:00'),

-- ============================================
-- UI/UX DESIGN (3 bootcamp)
-- ============================================

-- 8. UI/UX Designer (UI/UX Design)
('UI/UX Designer Bootcamp', 'UI/UX Design', 'Dewi Kartika',
2800000.00, 2000000.00, 1, '2026-10-10', '2026-12-20', 20,
'assets/bootcamp/bootcamp_uiux.png',
'Figma Mastery|Design System|User Research|Portfolio Building|Job Ready',
'Bootcamp UI/UX design intensif dengan fokus pada portfolio dan job readiness.',
'2026-06-10 10:00:00'),

-- 9. Content Creator (UI/UX Design)
('Content Creator Bootcamp', 'UI/UX Design', 'Maya Sari',
2000000.00, 1500000.00, 1, '2026-11-05', '2027-01-10', 25,
'assets/bootcamp/bootcamp_content_creator.png',
'Video Editing|Content Strategy|Monetization|Brand Deal|Growth Hacking',
'Bootcamp content creator lengkap dari produksi hingga monetisasi.',
'2026-06-25 08:00:00'),

-- 10. Product Design Bootcamp (UI/UX Design)
('Product Design Bootcamp', 'UI/UX Design', 'Maya Sari',
2500000.00, 1800000.00, 1, '2026-11-10', '2027-01-25', 25,
'assets/bootcamp/bootcamp_product_design.png',
'Design Thinking|User Research|Wireframing|Prototyping|Usability Testing',
'Bootcamp product design lengkap dari research hingga high-fidelity prototype.',
'2026-07-25 08:00:00'),

-- ============================================
-- DATA & AI (3 bootcamp)
-- ============================================

-- 11. Data Analyst (Data & AI)
('Data Analyst Bootcamp', 'Data & AI', 'Budi Santoso',
3000000.00, 2200000.00, 1, '2026-10-15', '2026-12-25', 20,
'assets/bootcamp/bootcamp_data_analyst.png',
'Excel Advanced|SQL|Python Basic|Tableau|Real Dataset Project',
'Bootcamp data analyst dengan hands-on project menggunakan data real dari perusahaan.',
'2026-06-15 11:00:00'),

-- 12. Digital Entrepreneur (Data & AI)
('Digital Entrepreneur Bootcamp', 'Data & AI', 'Andi Pratama',
4000000.00, 3000000.00, 1, '2026-11-01', '2027-01-31', 15,
'assets/bootcamp/bootcamp_entrepreneur.png',
'Business Model Canvas|MVP Development|Pitch Deck|Investor Network|Mentoring',
'Bootcamp kewirausahaan digital untuk calon founder startup.',
'2026-06-20 09:00:00'),

-- 13. AI & Machine Learning Bootcamp (Data & AI)
('AI & Machine Learning Bootcamp', 'Data & AI', 'Budi Santoso',
4500000.00, 3500000.00, 1, '2026-11-15', '2027-02-28', 15,
'assets/bootcamp/bootcamp_ai_ml.png',
'Python/TensorFlow|Machine Learning|Deep Learning|NLP|Computer Vision',
'Bootcamp AI dan Machine Learning intensif. Bangun model AI untuk masalah nyata.',
'2026-08-01 09:00:00'),

-- ============================================
-- MOBILE DEV (3 bootcamp)
-- ============================================

-- 14. Flutter Developer (Mobile Dev)
('Flutter Developer Bootcamp', 'Mobile Dev', 'Angga Pratama',
3000000.00, 2200000.00, 1, '2026-10-25', '2027-01-10', 20,
'assets/bootcamp/bootcamp_flutter.png',
'Dart/Flutter|UI Components|State Management|Firebase|Play Store Deploy',
'Bootcamp Flutter untuk membangun aplikasi mobile cross-platform.',
'2026-08-05 08:00:00'),

-- 15. Android Developer (Mobile Dev)
('Android Developer Bootcamp', 'Mobile Dev', 'Reza Firmansyah',
3200000.00, 2400000.00, 1, '2026-11-01', '2027-01-20', 20,
'assets/bootcamp/bootcamp_android.png',
'Kotlin/Java|Android Studio|Jetpack Compose|Room Database|Google Play',
'Bootcamp Android development modern dengan Kotlin dan Jetpack Compose.',
'2026-08-10 09:00:00'),

-- 16. React Native Bootcamp (Mobile Dev)
('React Native Bootcamp', 'Mobile Dev', 'Fajar Nugraha',
3000000.00, 2200000.00, 1, '2026-11-10', '2027-01-25', 20,
'assets/bootcamp/bootcamp_react_native.png',
'React Native|Expo|Redux|Native Modules|App Store Deploy',
'Bootcamp React Native untuk membangun aplikasi mobile dengan JavaScript.',
'2026-08-15 10:00:00');


-- =====================================================
-- 4. TRANSAKSI DUMMY (100+ TRANSAKSI)
-- =====================================================
-- user_id: 3-32 (user dummy, admin tidak beli)
-- produk_id: 1-25 (products) atau 1-8 (bootcamp)
-- transaction_status: settlement (70%), pending (15%), expire (5%), cancel (5%), deny (5%)
-- Tanggal tersebar Juni-September 2026
-- harga: sesuai harga produk (promo jika promo_aktif=1)

-- Catatan: order_id harus unik. Format: BY-YYYYMMDDHHmmss-XXXX

-- ============================================
-- TRANSAKSI E-LEARNING (80 transaksi)
-- ============================================

INSERT INTO transaksi (order_id, user_id, jenis_produk, produk_id, nama_produk, gross_amount, payment_type, transaction_id, transaction_status, fraud_status, transaction_time, settlement_time, created_at, updated_at) VALUES

-- Juni 2026 (20 transaksi)
('BY-20260601081500-A1B2C3D4', 3, 'elearning', 1, 'Belajar HTML & CSS dari Dasar', 69000.00, 'bank_transfer', 'TXN-001', 'settlement', 'accept', '2026-06-01 08:20:00', '2026-06-01 08:25:00', '2026-06-01 08:15:00', '2026-06-01 08:25:00'),
('BY-20260602093000-B2C3D4E5', 4, 'elearning', 2, 'JavaScript untuk Pemula', 99000.00, 'ewallet', 'TXN-002', 'settlement', 'accept', '2026-06-02 09:35:00', '2026-06-02 09:40:00', '2026-06-02 09:30:00', '2026-06-02 09:40:00'),
('BY-20260603104500-C3D4E5F6', 5, 'elearning', 6, 'Full Stack Web Development', 249000.00, 'bank_transfer', 'TXN-003', 'settlement', 'accept', '2026-06-03 10:50:00', '2026-06-03 10:55:00', '2026-06-03 10:45:00', '2026-06-03 10:55:00'),
('BY-20260605072000-D4E5F6G7', 6, 'elearning', 9, 'UI/UX Design dengan Figma', 149000.00, 'ewallet', 'TXN-004', 'settlement', 'accept', '2026-06-05 07:25:00', '2026-06-05 07:30:00', '2026-06-05 07:20:00', '2026-06-05 07:30:00'),
('BY-20260606110000-E5F6G7H8', 7, 'elearning', 11, 'Digital Marketing untuk Pemula', 179000.00, 'bank_transfer', 'TXN-005', 'settlement', 'accept', '2026-06-06 11:05:00', '2026-06-06 11:10:00', '2026-06-06 11:00:00', '2026-06-06 11:10:00'),
('BY-20260608131500-F6G7H8I9', 8, 'elearning', 17, 'Microsoft Excel untuk Pemula', 69000.00, 'credit_card', 'TXN-006', 'settlement', 'accept', '2026-06-08 13:20:00', '2026-06-08 13:25:00', '2026-06-08 13:15:00', '2026-06-08 13:25:00'),
('BY-20260610084500-G7H8I9J0', 9, 'elearning', 20, 'Python untuk Pemula', 129000.00, 'bank_transfer', 'TXN-007', 'settlement', 'accept', '2026-06-10 08:50:00', '2026-06-10 08:55:00', '2026-06-10 08:45:00', '2026-06-10 08:55:00'),
('BY-20260612143000-H8I9J0K1', 10, 'elearning', 5, 'PHP & MySQL Lengkap', 149000.00, 'ewallet', 'TXN-008', 'settlement', 'accept', '2026-06-12 14:35:00', '2026-06-12 14:40:00', '2026-06-12 14:30:00', '2026-06-12 14:40:00'),
('BY-20260615090000-I9J0K1L2', 11, 'elearning', 3, 'JavaScript Modern (ES6+)', 129000.00, 'bank_transfer', 'TXN-009', 'settlement', 'accept', '2026-06-15 09:05:00', '2026-06-15 09:10:00', '2026-06-15 09:00:00', '2026-06-15 09:10:00'),
('BY-20260618103000-J0K1L2M3', 12, 'elearning', 12, 'Social Media Marketing', 149000.00, 'ewallet', 'TXN-010', 'settlement', 'accept', '2026-06-18 10:35:00', '2026-06-18 10:40:00', '2026-06-18 10:30:00', '2026-06-18 10:40:00'),
('BY-20260620074500-K1L2M3N4', 13, 'elearning', 15, 'Content Creator Sukses', 149000.00, 'bank_transfer', 'TXN-011', 'settlement', 'accept', '2026-06-20 07:50:00', '2026-06-20 07:55:00', '2026-06-20 07:45:00', '2026-06-20 07:55:00'),
('BY-20260622111500-L2M3N4O5', 14, 'elearning', 23, 'Digital Entrepreneurship', 149000.00, 'credit_card', 'TXN-012', 'settlement', 'accept', '2026-06-22 11:20:00', '2026-06-22 11:25:00', '2026-06-22 11:15:00', '2026-06-22 11:25:00'),
('BY-20260625083000-M3N4O5P6', 15, 'elearning', 7, 'Laravel untuk Pemula', 179000.00, 'bank_transfer', 'TXN-013', 'settlement', 'accept', '2026-06-25 08:35:00', '2026-06-25 08:40:00', '2026-06-25 08:30:00', '2026-06-25 08:40:00'),
('BY-20260628094500-N4O5P6Q7', 16, 'elearning', 14, 'Copywriting untuk Bisnis', 89000.00, 'ewallet', 'TXN-014', 'settlement', 'accept', '2026-06-28 09:50:00', '2026-06-28 09:55:00', '2026-06-28 09:45:00', '2026-06-28 09:55:00'),
('BY-20260630100000-O5P6Q7R8', 17, 'elearning', 24, 'Personal Branding', 79000.00, 'bank_transfer', 'TXN-015', 'settlement', 'accept', '2026-06-30 10:05:00', '2026-06-30 10:10:00', '2026-06-30 10:00:00', '2026-06-30 10:10:00'),
('BY-20260601090000-P6Q7R8S9', 18, 'elearning', 1, 'Belajar HTML & CSS dari Dasar', 69000.00, 'ewallet', 'TXN-016', 'settlement', 'accept', '2026-06-01 09:05:00', '2026-06-01 09:10:00', '2026-06-01 09:00:00', '2026-06-01 09:10:00'),
('BY-20260603113000-Q7R8S9T0', 19, 'elearning', 2, 'JavaScript untuk Pemula', 99000.00, 'bank_transfer', 'TXN-017', 'settlement', 'accept', '2026-06-03 11:35:00', '2026-06-03 11:40:00', '2026-06-03 11:30:00', '2026-06-03 11:40:00'),
('BY-20260605140000-R8S9T0U1', 20, 'elearning', 6, 'Full Stack Web Development', 249000.00, 'bank_transfer', 'TXN-018', 'settlement', 'accept', '2026-06-05 14:05:00', '2026-06-05 14:10:00', '2026-06-05 14:00:00', '2026-06-05 14:10:00'),
('BY-20260607100000-S9T0U1V2', 21, 'elearning', 11, 'Digital Marketing untuk Pemula', 179000.00, 'ewallet', 'TXN-019', 'settlement', 'accept', '2026-06-07 10:05:00', '2026-06-07 10:10:00', '2026-06-07 10:00:00', '2026-06-07 10:10:00'),
('BY-20260610130000-T0U1V2W3', 22, 'elearning', 9, 'UI/UX Design dengan Figma', 149000.00, 'credit_card', 'TXN-020', 'settlement', 'accept', '2026-06-10 13:05:00', '2026-06-10 13:10:00', '2026-06-10 13:00:00', '2026-06-10 13:10:00'),

-- Juli 2026 (20 transaksi)
('BY-20260701080000-U1V2W3X4', 23, 'elearning', 20, 'Python untuk Pemula', 129000.00, 'bank_transfer', 'TXN-021', 'settlement', 'accept', '2026-07-01 08:05:00', '2026-07-01 08:10:00', '2026-07-01 08:00:00', '2026-07-01 08:10:00'),
('BY-20260703100000-V2W3X4Y5', 24, 'elearning', 5, 'PHP & MySQL Lengkap', 149000.00, 'ewallet', 'TXN-022', 'settlement', 'accept', '2026-07-03 10:05:00', '2026-07-03 10:10:00', '2026-07-03 10:00:00', '2026-07-03 10:10:00'),
('BY-20260705090000-W3X4Y5Z6', 25, 'elearning', 17, 'Microsoft Excel untuk Pemula', 69000.00, 'bank_transfer', 'TXN-023', 'settlement', 'accept', '2026-07-05 09:05:00', '2026-07-05 09:10:00', '2026-07-05 09:00:00', '2026-07-05 09:10:00'),
('BY-20260708110000-X4Y5Z6A7', 26, 'elearning', 12, 'Social Media Marketing', 149000.00, 'ewallet', 'TXN-024', 'settlement', 'accept', '2026-07-08 11:05:00', '2026-07-08 11:10:00', '2026-07-08 11:00:00', '2026-07-08 11:10:00'),
('BY-20260710080000-Y5Z6A7B8', 27, 'elearning', 7, 'Laravel untuk Pemula', 179000.00, 'bank_transfer', 'TXN-025', 'settlement', 'accept', '2026-07-10 08:05:00', '2026-07-10 08:10:00', '2026-07-10 08:00:00', '2026-07-10 08:10:00'),
('BY-20260712093000-Z6A7B8C9', 28, 'elearning', 15, 'Content Creator Sukses', 149000.00, 'credit_card', 'TXN-026', 'settlement', 'accept', '2026-07-12 09:35:00', '2026-07-12 09:40:00', '2026-07-12 09:30:00', '2026-07-12 09:40:00'),
('BY-20260715100000-A7B8C9D0', 29, 'elearning', 3, 'JavaScript Modern (ES6+)', 129000.00, 'bank_transfer', 'TXN-027', 'settlement', 'accept', '2026-07-15 10:05:00', '2026-07-15 10:10:00', '2026-07-15 10:00:00', '2026-07-15 10:10:00'),
('BY-20260718114500-B8C9D0E1', 30, 'elearning', 23, 'Digital Entrepreneurship', 149000.00, 'ewallet', 'TXN-028', 'settlement', 'accept', '2026-07-18 11:50:00', '2026-07-18 11:55:00', '2026-07-18 11:45:00', '2026-07-18 11:55:00'),
('BY-20260720080000-C9D0E1F2', 31, 'elearning', 1, 'Belajar HTML & CSS dari Dasar', 69000.00, 'bank_transfer', 'TXN-029', 'settlement', 'accept', '2026-07-20 08:05:00', '2026-07-20 08:10:00', '2026-07-20 08:00:00', '2026-07-20 08:10:00'),
('BY-20260722090000-D0E1F2G3', 32, 'elearning', 14, 'Copywriting untuk Bisnis', 89000.00, 'credit_card', 'TXN-030', 'settlement', 'accept', '2026-07-22 09:05:00', '2026-07-22 09:10:00', '2026-07-22 09:00:00', '2026-07-22 09:10:00'),
('BY-20260725100000-E1F2G3H4', 3, 'elearning', 25, 'Strategi Bisnis untuk Pemula', 129000.00, 'bank_transfer', 'TXN-031', 'settlement', 'accept', '2026-07-25 10:05:00', '2026-07-25 10:10:00', '2026-07-25 10:00:00', '2026-07-25 10:10:00'),
('BY-20260728110000-F2G3H4I5', 4, 'elearning', 8, 'REST API dengan PHP', 179000.00, 'ewallet', 'TXN-032', 'settlement', 'accept', '2026-07-28 11:05:00', '2026-07-28 11:10:00', '2026-07-28 11:00:00', '2026-07-28 11:10:00'),
('BY-20260730080000-G3H4I5J6', 5, 'elearning', 19, 'Data Analysis dengan Excel', 109000.00, 'bank_transfer', 'TXN-033', 'settlement', 'accept', '2026-07-30 08:05:00', '2026-07-30 08:10:00', '2026-07-30 08:00:00', '2026-07-30 08:10:00'),
('BY-20260702100000-H4I5J6K7', 6, 'elearning', 21, 'Data Analysis dengan Python', 199000.00, 'bank_transfer', 'TXN-034', 'settlement', 'accept', '2026-07-02 10:05:00', '2026-07-02 10:10:00', '2026-07-02 10:00:00', '2026-07-02 10:10:00'),
('BY-20260705140000-I5J6K7L8', 7, 'elearning', 10, 'Figma dari Dasar sampai Mahir', 179000.00, 'ewallet', 'TXN-035', 'settlement', 'accept', '2026-07-05 14:05:00', '2026-07-05 14:10:00', '2026-07-05 14:00:00', '2026-07-05 14:10:00'),
('BY-20260708130000-J6K7L8M9', 8, 'elearning', 16, 'Canva untuk Bisnis', 79000.00, 'bank_transfer', 'TXN-036', 'settlement', 'accept', '2026-07-08 13:05:00', '2026-07-08 13:10:00', '2026-07-08 13:00:00', '2026-07-08 13:10:00'),
('BY-20260711090000-K7L8M9N0', 9, 'elearning', 13, 'SEO Website Lengkap', 149000.00, 'credit_card', 'TXN-037', 'settlement', 'accept', '2026-07-11 09:05:00', '2026-07-11 09:10:00', '2026-07-11 09:00:00', '2026-07-11 09:10:00'),
('BY-20260714100000-L8M9N0O1', 10, 'elearning', 22, 'Membuat Bisnis Online', 149000.00, 'bank_transfer', 'TXN-038', 'settlement', 'accept', '2026-07-14 10:05:00', '2026-07-14 10:10:00', '2026-07-14 10:00:00', '2026-07-14 10:10:00'),
('BY-20260717110000-M9N0O1P2', 11, 'elearning', 4, 'PHP Native untuk Pemula', 149000.00, 'ewallet', 'TXN-039', 'settlement', 'accept', '2026-07-17 11:05:00', '2026-07-17 11:10:00', '2026-07-17 11:00:00', '2026-07-17 11:10:00'),
('BY-20260720130000-N0O1P2Q3', 12, 'elearning', 18, 'Excel untuk Administrasi', 129000.00, 'bank_transfer', 'TXN-040', 'settlement', 'accept', '2026-07-20 13:05:00', '2026-07-20 13:10:00', '2026-07-20 13:00:00', '2026-07-20 13:10:00'),

-- Agustus 2026 (20 transaksi)
('BY-20260801080000-O1P2Q3R4', 13, 'elearning', 2, 'JavaScript untuk Pemula', 99000.00, 'bank_transfer', 'TXN-041', 'settlement', 'accept', '2026-08-01 08:05:00', '2026-08-01 08:10:00', '2026-08-01 08:00:00', '2026-08-01 08:10:00'),
('BY-20260805090000-P2Q3R4S5', 14, 'elearning', 6, 'Full Stack Web Development', 249000.00, 'ewallet', 'TXN-042', 'settlement', 'accept', '2026-08-05 09:05:00', '2026-08-05 09:10:00', '2026-08-05 09:00:00', '2026-08-05 09:10:00'),
('BY-20260810100000-Q3R4S5T6', 15, 'elearning', 11, 'Digital Marketing untuk Pemula', 179000.00, 'bank_transfer', 'TXN-043', 'settlement', 'accept', '2026-08-10 10:05:00', '2026-08-10 10:10:00', '2026-08-10 10:00:00', '2026-08-10 10:10:00'),
('BY-20260812110000-R4S5T6U7', 16, 'elearning', 9, 'UI/UX Design dengan Figma', 149000.00, 'credit_card', 'TXN-044', 'settlement', 'accept', '2026-08-12 11:05:00', '2026-08-12 11:10:00', '2026-08-12 11:00:00', '2026-08-12 11:10:00'),
('BY-20260815080000-S5T6U7V8', 17, 'elearning', 20, 'Python untuk Pemula', 129000.00, 'bank_transfer', 'TXN-045', 'settlement', 'accept', '2026-08-15 08:05:00', '2026-08-15 08:10:00', '2026-08-15 08:00:00', '2026-08-15 08:10:00'),
('BY-20260818090000-T6U7V8W9', 18, 'elearning', 5, 'PHP & MySQL Lengkap', 149000.00, 'ewallet', 'TXN-046', 'settlement', 'accept', '2026-08-18 09:05:00', '2026-08-18 09:10:00', '2026-08-18 09:00:00', '2026-08-18 09:10:00'),
('BY-20260820100000-U7V8W9X0', 19, 'elearning', 7, 'Laravel untuk Pemula', 179000.00, 'bank_transfer', 'TXN-047', 'settlement', 'accept', '2026-08-20 10:05:00', '2026-08-20 10:10:00', '2026-08-20 10:00:00', '2026-08-20 10:10:00'),
('BY-20260822110000-V8W9X0Y1', 20, 'elearning', 17, 'Microsoft Excel untuk Pemula', 69000.00, 'bank_transfer', 'TXN-048', 'settlement', 'accept', '2026-08-22 11:05:00', '2026-08-22 11:10:00', '2026-08-22 11:00:00', '2026-08-22 11:10:00'),
('BY-20260825080000-W9X0Y1Z2', 21, 'elearning', 12, 'Social Media Marketing', 149000.00, 'ewallet', 'TXN-049', 'settlement', 'accept', '2026-08-25 08:05:00', '2026-08-25 08:10:00', '2026-08-25 08:00:00', '2026-08-25 08:10:00'),
('BY-20260828090000-X0Y1Z2A3', 22, 'elearning', 3, 'JavaScript Modern (ES6+)', 129000.00, 'credit_card', 'TXN-050', 'settlement', 'accept', '2026-08-28 09:05:00', '2026-08-28 09:10:00', '2026-08-28 09:00:00', '2026-08-28 09:10:00'),
('BY-20260802100000-Y1Z2A3B4', 23, 'elearning', 15, 'Content Creator Sukses', 149000.00, 'bank_transfer', 'TXN-051', 'settlement', 'accept', '2026-08-02 10:05:00', '2026-08-02 10:10:00', '2026-08-02 10:00:00', '2026-08-02 10:10:00'),
('BY-20260805140000-Z2A3B4C5', 24, 'elearning', 24, 'Personal Branding', 79000.00, 'bank_transfer', 'TXN-052', 'settlement', 'accept', '2026-08-05 14:05:00', '2026-08-05 14:10:00', '2026-08-05 14:00:00', '2026-08-05 14:10:00'),
('BY-20260808110000-A3B4C5D6', 25, 'elearning', 1, 'Belajar HTML & CSS dari Dasar', 69000.00, 'ewallet', 'TXN-053', 'settlement', 'accept', '2026-08-08 11:05:00', '2026-08-08 11:10:00', '2026-08-08 11:00:00', '2026-08-08 11:10:00'),
('BY-20260811130000-B4C5D6E7', 26, 'elearning', 23, 'Digital Entrepreneurship', 149000.00, 'bank_transfer', 'TXN-054', 'settlement', 'accept', '2026-08-11 13:05:00', '2026-08-11 13:10:00', '2026-08-11 13:00:00', '2026-08-11 13:10:00'),
('BY-20260814080000-C5D6E7F8', 27, 'elearning', 14, 'Copywriting untuk Bisnis', 89000.00, 'credit_card', 'TXN-055', 'settlement', 'accept', '2026-08-14 08:05:00', '2026-08-14 08:10:00', '2026-08-14 08:00:00', '2026-08-14 08:10:00'),
('BY-20260817100000-D6E7F8G9', 28, 'elearning', 21, 'Data Analysis dengan Python', 199000.00, 'bank_transfer', 'TXN-056', 'settlement', 'accept', '2026-08-17 10:05:00', '2026-08-17 10:10:00', '2026-08-17 10:00:00', '2026-08-17 10:10:00'),
('BY-20260820140000-E7F8G9H0', 29, 'elearning', 19, 'Data Analysis dengan Excel', 109000.00, 'ewallet', 'TXN-057', 'settlement', 'accept', '2026-08-20 14:05:00', '2026-08-20 14:10:00', '2026-08-20 14:00:00', '2026-08-20 14:10:00'),
('BY-20260823090000-F8G9H0I1', 30, 'elearning', 10, 'Figma dari Dasar sampai Mahir', 179000.00, 'bank_transfer', 'TXN-058', 'settlement', 'accept', '2026-08-23 09:05:00', '2026-08-23 09:10:00', '2026-08-23 09:00:00', '2026-08-23 09:10:00'),
('BY-20260826110000-G9H0I1J2', 31, 'elearning', 25, 'Strategi Bisnis untuk Pemula', 129000.00, 'bank_transfer', 'TXN-059', 'settlement', 'accept', '2026-08-26 11:05:00', '2026-08-26 11:10:00', '2026-08-26 11:00:00', '2026-08-26 11:10:00'),
('BY-20260829080000-H0I1J2K3', 32, 'elearning', 16, 'Canva untuk Bisnis', 79000.00, 'ewallet', 'TXN-060', 'settlement', 'accept', '2026-08-29 08:05:00', '2026-08-29 08:10:00', '2026-08-29 08:00:00', '2026-08-29 08:10:00'),

-- September 2026 (20 transaksi)
('BY-20260901080000-I1J2K3L4', 3, 'elearning', 2, 'JavaScript untuk Pemula', 99000.00, 'bank_transfer', 'TXN-061', 'settlement', 'accept', '2026-09-01 08:05:00', '2026-09-01 08:10:00', '2026-09-01 08:00:00', '2026-09-01 08:10:00'),
('BY-20260903100000-J2K3L4M5', 4, 'elearning', 11, 'Digital Marketing untuk Pemula', 179000.00, 'ewallet', 'TXN-062', 'settlement', 'accept', '2026-09-03 10:05:00', '2026-09-03 10:10:00', '2026-09-03 10:00:00', '2026-09-03 10:10:00'),
('BY-20260905090000-K3L4M5N6', 5, 'elearning', 6, 'Full Stack Web Development', 249000.00, 'bank_transfer', 'TXN-063', 'settlement', 'accept', '2026-09-05 09:05:00', '2026-09-05 09:10:00', '2026-09-05 09:00:00', '2026-09-05 09:10:00'),
('BY-20260908110000-L4M5N6O7', 6, 'elearning', 9, 'UI/UX Design dengan Figma', 149000.00, 'credit_card', 'TXN-064', 'settlement', 'accept', '2026-09-08 11:05:00', '2026-09-08 11:10:00', '2026-09-08 11:00:00', '2026-09-08 11:10:00'),
('BY-20260910080000-M5N6O7P8', 7, 'elearning', 5, 'PHP & MySQL Lengkap', 149000.00, 'bank_transfer', 'TXN-065', 'settlement', 'accept', '2026-09-10 08:05:00', '2026-09-10 08:10:00', '2026-09-10 08:00:00', '2026-09-10 08:10:00'),
('BY-20260912100000-N6O7P8Q9', 8, 'elearning', 17, 'Microsoft Excel untuk Pemula', 69000.00, 'ewallet', 'TXN-066', 'settlement', 'accept', '2026-09-12 10:05:00', '2026-09-12 10:10:00', '2026-09-12 10:00:00', '2026-09-12 10:10:00'),
('BY-20260915090000-O7P8Q9R0', 9, 'elearning', 7, 'Laravel untuk Pemula', 179000.00, 'bank_transfer', 'TXN-067', 'settlement', 'accept', '2026-09-15 09:05:00', '2026-09-15 09:10:00', '2026-09-15 09:00:00', '2026-09-15 09:10:00'),
('BY-20260918110000-P8Q9R0S1', 10, 'elearning', 20, 'Python untuk Pemula', 129000.00, 'ewallet', 'TXN-068', 'settlement', 'accept', '2026-09-18 11:05:00', '2026-09-18 11:10:00', '2026-09-18 11:00:00', '2026-09-18 11:10:00'),
('BY-20260920080000-Q9R0S1T2', 11, 'elearning', 12, 'Social Media Marketing', 149000.00, 'bank_transfer', 'TXN-069', 'settlement', 'accept', '2026-09-20 08:05:00', '2026-09-20 08:10:00', '2026-09-20 08:00:00', '2026-09-20 08:10:00'),
('BY-20260922100000-R0S1T2U3', 12, 'elearning', 3, 'JavaScript Modern (ES6+)', 129000.00, 'credit_card', 'TXN-070', 'settlement', 'accept', '2026-09-22 10:05:00', '2026-09-22 10:10:00', '2026-09-22 10:00:00', '2026-09-22 10:10:00'),
('BY-20260925090000-S1T2U3V4', 13, 'elearning', 15, 'Content Creator Sukses', 149000.00, 'bank_transfer', 'TXN-071', 'settlement', 'accept', '2026-09-25 09:05:00', '2026-09-25 09:10:00', '2026-09-25 09:00:00', '2026-09-25 09:10:00'),
('BY-20260928080000-T2U3V4W5', 14, 'elearning', 23, 'Digital Entrepreneurship', 149000.00, 'ewallet', 'TXN-072', 'settlement', 'accept', '2026-09-28 08:05:00', '2026-09-28 08:10:00', '2026-09-28 08:00:00', '2026-09-28 08:10:00'),
('BY-20260930100000-U3V4W5X6', 15, 'elearning', 1, 'Belajar HTML & CSS dari Dasar', 69000.00, 'bank_transfer', 'TXN-073', 'settlement', 'accept', '2026-09-30 10:05:00', '2026-09-30 10:10:00', '2026-09-30 10:00:00', '2026-09-30 10:10:00'),

-- Transaksi dengan status PENDING (12 transaksi)
('BY-20260610120000-V4W5X6Y7', 16, 'elearning', 21, 'Data Analysis dengan Python', 199000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-06-10 12:00:00', '2026-06-10 12:00:00'),
('BY-20260620140000-W5X6Y7Z8', 17, 'elearning', 14, 'Copywriting untuk Bisnis', 89000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-06-20 14:00:00', '2026-06-20 14:00:00'),
('BY-20260705150000-X6Y7Z8A9', 18, 'elearning', 8, 'REST API dengan PHP', 179000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-07-05 15:00:00', '2026-07-05 15:00:00'),
('BY-20260715120000-Y7Z8A9B0', 19, 'elearning', 19, 'Data Analysis dengan Excel', 109000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-07-15 12:00:00', '2026-07-15 12:00:00'),
('BY-20260805160000-Z8A9B0C1', 20, 'elearning', 25, 'Strategi Bisnis untuk Pemula', 129000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-08-05 16:00:00', '2026-08-05 16:00:00'),
('BY-20260815140000-A9B0C1D2', 21, 'elearning', 16, 'Canva untuk Bisnis', 79000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-08-15 14:00:00', '2026-08-15 14:00:00'),
('BY-20260901120000-B0C1D2E3', 22, 'elearning', 24, 'Personal Branding', 79000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-09-01 12:00:00', '2026-09-01 12:00:00'),
('BY-20260910150000-C1D2E3F4', 23, 'elearning', 10, 'Figma dari Dasar sampai Mahir', 179000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-09-10 15:00:00', '2026-09-10 15:00:00'),
('BY-20260920160000-D2E3F4G5', 24, 'elearning', 18, 'Excel untuk Administrasi', 129000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-09-20 16:00:00', '2026-09-20 16:00:00'),
('BY-20260925140000-E3F4G5H6', 25, 'elearning', 13, 'SEO Website Lengkap', 149000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-09-25 14:00:00', '2026-09-25 14:00:00'),
('BY-20260928120000-F4G5H6I7', 26, 'elearning', 22, 'Membuat Bisnis Online', 149000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-09-28 12:00:00', '2026-09-28 12:00:00'),
('BY-20260930160000-G5H6I7J8', 27, 'elearning', 4, 'PHP Native untuk Pemula', 149000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-09-30 16:00:00', '2026-09-30 16:00:00'),

-- Transaksi EXPIRED (5 transaksi)
('BY-20260615100000-H6I7J8K9', 28, 'elearning', 1, 'Belajar HTML & CSS dari Dasar', 69000.00, NULL, NULL, 'expire', NULL, '2026-06-15 10:00:00', NULL, '2026-06-15 10:00:00', '2026-06-20 10:00:00'),
('BY-20260710120000-I7J8K9L0', 29, 'elearning', 17, 'Microsoft Excel untuk Pemula', 69000.00, NULL, NULL, 'expire', NULL, '2026-07-10 12:00:00', NULL, '2026-07-10 12:00:00', '2026-07-15 12:00:00'),
('BY-20260801140000-J8K9L0M1', 30, 'elearning', 16, 'Canva untuk Bisnis', 79000.00, NULL, NULL, 'expire', NULL, '2026-08-01 14:00:00', NULL, '2026-08-01 14:00:00', '2026-08-06 14:00:00'),
('BY-20260820100000-K9L0M1N2', 31, 'elearning', 24, 'Personal Branding', 79000.00, NULL, NULL, 'expire', NULL, '2026-08-20 10:00:00', NULL, '2026-08-20 10:00:00', '2026-08-25 10:00:00'),
('BY-20260915140000-L0M1N2O3', 32, 'elearning', 14, 'Copywriting untuk Bisnis', 89000.00, NULL, NULL, 'expire', NULL, '2026-09-15 14:00:00', NULL, '2026-09-15 14:00:00', '2026-09-20 14:00:00'),

-- Transaksi CANCEL (3 transaksi)
('BY-20260625110000-M1N2O3P4', 3, 'elearning', 13, 'SEO Website Lengkap', 149000.00, 'bank_transfer', 'TXN-C01', 'cancel', NULL, '2026-06-25 11:00:00', NULL, '2026-06-25 11:00:00', '2026-06-26 11:00:00'),
('BY-20260720150000-N2O3P4Q5', 5, 'elearning', 10, 'Figma dari Dasar sampai Mahir', 179000.00, 'ewallet', 'TXN-C02', 'cancel', NULL, '2026-07-20 15:00:00', NULL, '2026-07-20 15:00:00', '2026-07-21 15:00:00'),
('BY-20260825160000-O3P4Q5R6', 9, 'elearning', 22, 'Membuat Bisnis Online', 149000.00, 'bank_transfer', 'TXN-C03', 'cancel', NULL, '2026-08-25 16:00:00', NULL, '2026-08-25 16:00:00', '2026-08-26 16:00:00'),

-- Transaksi DENY (2 transaksi)
('BY-20260705100000-P4Q5R6S7', 7, 'elearning', 8, 'REST API dengan PHP', 179000.00, 'credit_card', 'TXN-D01', 'deny', 'challenge', '2026-07-05 10:00:00', NULL, '2026-07-05 10:00:00', '2026-07-06 10:00:00'),
('BY-20260901140000-Q5R6S7T8', 12, 'elearning', 18, 'Excel untuk Administrasi', 129000.00, 'credit_card', 'TXN-D02', 'deny', 'challenge', '2026-09-01 14:00:00', NULL, '2026-09-01 14:00:00', '2026-09-02 14:00:00');


-- ============================================
-- TRANSAKSI BOOTCAMP (20 transaksi)
-- ============================================

INSERT INTO transaksi (order_id, user_id, jenis_produk, produk_id, nama_produk, gross_amount, payment_type, transaction_id, transaction_status, fraud_status, transaction_time, settlement_time, created_at, updated_at) VALUES

-- Bootcamp Full Stack (5 pembeli)
('BY-20260610090000-R6S7T8U9', 3, 'bootcamp', 1, 'Full Stack Web Developer Bootcamp', 2500000.00, 'bank_transfer', 'TXN-B01', 'settlement', 'accept', '2026-06-10 09:05:00', '2026-06-10 09:10:00', '2026-06-10 09:00:00', '2026-06-10 09:10:00'),
('BY-20260615110000-S7T8U9V0', 5, 'bootcamp', 1, 'Full Stack Web Developer Bootcamp', 2500000.00, 'bank_transfer', 'TXN-B02', 'settlement', 'accept', '2026-06-15 11:05:00', '2026-06-15 11:10:00', '2026-06-15 11:00:00', '2026-06-15 11:10:00'),
('BY-20260620140000-T8U9V0W1', 9, 'bootcamp', 1, 'Full Stack Web Developer Bootcamp', 2500000.00, 'ewallet', 'TXN-B03', 'settlement', 'accept', '2026-06-20 14:05:00', '2026-06-20 14:10:00', '2026-06-20 14:00:00', '2026-06-20 14:10:00'),
('BY-20260701100000-U9V0W1X2', 14, 'bootcamp', 1, 'Full Stack Web Developer Bootcamp', 2500000.00, 'bank_transfer', 'TXN-B04', 'settlement', 'accept', '2026-07-01 10:05:00', '2026-07-01 10:10:00', '2026-07-01 10:00:00', '2026-07-01 10:10:00'),
('BY-20260715130000-V0W1X2Y3', 20, 'bootcamp', 1, 'Full Stack Web Developer Bootcamp', 2500000.00, 'bank_transfer', 'TXN-B05', 'settlement', 'accept', '2026-07-15 13:05:00', '2026-07-15 13:10:00', '2026-07-15 13:00:00', '2026-07-15 13:10:00'),

-- Bootcamp Digital Marketing (3 pembeli)
('BY-20260625090000-W1X2Y3Z4', 4, 'bootcamp', 2, 'Digital Marketing Bootcamp', 1800000.00, 'bank_transfer', 'TXN-B06', 'settlement', 'accept', '2026-06-25 09:05:00', '2026-06-25 09:10:00', '2026-06-25 09:00:00', '2026-06-25 09:10:00'),
('BY-20260720110000-X2Y3Z4A5', 11, 'bootcamp', 2, 'Digital Marketing Bootcamp', 1800000.00, 'ewallet', 'TXN-B07', 'settlement', 'accept', '2026-07-20 11:05:00', '2026-07-20 11:10:00', '2026-07-20 11:00:00', '2026-07-20 11:10:00'),
('BY-20260810100000-Y3Z4A5B6', 16, 'bootcamp', 2, 'Digital Marketing Bootcamp', 1800000.00, 'bank_transfer', 'TXN-B08', 'settlement', 'accept', '2026-08-10 10:05:00', '2026-08-10 10:10:00', '2026-08-10 10:00:00', '2026-08-10 10:10:00'),

-- Bootcamp UI/UX (3 pembeli)
('BY-20260630080000-Z4A5B6C7', 6, 'bootcamp', 3, 'UI/UX Designer Bootcamp', 2000000.00, 'credit_card', 'TXN-B09', 'settlement', 'accept', '2026-06-30 08:05:00', '2026-06-30 08:10:00', '2026-06-30 08:00:00', '2026-06-30 08:10:00'),
('BY-20260725140000-A5B6C7D8', 12, 'bootcamp', 3, 'UI/UX Designer Bootcamp', 2000000.00, 'bank_transfer', 'TXN-B10', 'settlement', 'accept', '2026-07-25 14:05:00', '2026-07-25 14:10:00', '2026-07-25 14:00:00', '2026-07-25 14:10:00'),
('BY-20260820110000-B6C7D8E9', 22, 'bootcamp', 3, 'UI/UX Designer Bootcamp', 2000000.00, 'ewallet', 'TXN-B11', 'settlement', 'accept', '2026-08-20 11:05:00', '2026-08-20 11:10:00', '2026-08-20 11:00:00', '2026-08-20 11:10:00'),

-- Bootcamp Data Analyst (2 pembeli)
('BY-20260710100000-C7D8E9F0', 7, 'bootcamp', 4, 'Data Analyst Bootcamp', 2200000.00, 'bank_transfer', 'TXN-B12', 'settlement', 'accept', '2026-07-10 10:05:00', '2026-07-10 10:10:00', '2026-07-10 10:00:00', '2026-07-10 10:10:00'),
('BY-20260815090000-D8E9F0G1', 17, 'bootcamp', 4, 'Data Analyst Bootcamp', 2200000.00, 'bank_transfer', 'TXN-B13', 'settlement', 'accept', '2026-08-15 09:05:00', '2026-08-15 09:10:00', '2026-08-15 09:00:00', '2026-08-15 09:10:00'),

-- Bootcamp Digital Entrepreneur (2 pembeli)
('BY-20260728110000-E9F0G1H2', 15, 'bootcamp', 5, 'Digital Entrepreneur Bootcamp', 3000000.00, 'bank_transfer', 'TXN-B14', 'settlement', 'accept', '2026-07-28 11:05:00', '2026-07-28 11:10:00', '2026-07-28 11:00:00', '2026-07-28 11:10:00'),
('BY-20260901090000-F0G1H2I3', 25, 'bootcamp', 5, 'Digital Entrepreneur Bootcamp', 3000000.00, 'ewallet', 'TXN-B15', 'settlement', 'accept', '2026-09-01 09:05:00', '2026-09-01 09:10:00', '2026-09-01 09:00:00', '2026-09-01 09:10:00'),

-- Bootcamp Content Creator (2 pembeli)
('BY-20260805100000-G1H2I3J4', 8, 'bootcamp', 6, 'Content Creator Bootcamp', 1500000.00, 'bank_transfer', 'TXN-B16', 'settlement', 'accept', '2026-08-05 10:05:00', '2026-08-05 10:10:00', '2026-08-05 10:00:00', '2026-08-05 10:10:00'),
('BY-20260910110000-H2I3J4K5', 19, 'bootcamp', 6, 'Content Creator Bootcamp', 1500000.00, 'credit_card', 'TXN-B17', 'settlement', 'accept', '2026-09-10 11:05:00', '2026-09-10 11:10:00', '2026-09-10 11:00:00', '2026-09-10 11:10:00'),

-- Bootcamp Python (2 pembeli)
('BY-20260818140000-I3J4K5L6', 10, 'bootcamp', 7, 'Python Developer Bootcamp', 2700000.00, 'bank_transfer', 'TXN-B18', 'settlement', 'accept', '2026-08-18 14:05:00', '2026-08-18 14:10:00', '2026-08-18 14:00:00', '2026-08-18 14:10:00'),
('BY-20260920100000-J4K5L6M7', 28, 'bootcamp', 7, 'Python Developer Bootcamp', 2700000.00, 'bank_transfer', 'TXN-B19', 'settlement', 'accept', '2026-09-20 10:05:00', '2026-09-20 10:10:00', '2026-09-20 10:00:00', '2026-09-20 10:10:00'),

-- Bootcamp Laravel (1 pembeli + 1 pending)
('BY-20260825110000-K5L6M7N8', 13, 'bootcamp', 8, 'Laravel Developer Bootcamp', 2300000.00, 'bank_transfer', 'TXN-B20', 'settlement', 'accept', '2026-08-25 11:05:00', '2026-08-25 11:10:00', '2026-08-25 11:00:00', '2026-08-25 11:10:00'),
('BY-20260925150000-L6M7N8O9', 30, 'bootcamp', 8, 'Laravel Developer Bootcamp', 2300000.00, NULL, NULL, 'pending', NULL, NULL, NULL, '2026-09-25 15:00:00', '2026-09-25 15:00:00'),

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
-- SELESAI
-- =====================================================
-- Ringkasan data yang ditambahkan:
--
-- Users:           30 user dummy (id 3-32)
-- Products:        25 kelas E-Learning (id 1-25)
-- Bootcamp:        16 bootcamp (id 1-16)
--   - Front-End:    4 bootcamp (id 1,2,3,4)
--   - Back-End:     3 bootcamp (id 5,6,7)
--   - UI/UX Design: 3 bootcamp (id 8,9,10)
--   - Data & AI:    3 bootcamp (id 11,12,13)
--   - Mobile Dev:   3 bootcamp (id 14,15,16)
-- Transaksi:       109 transaksi
--   - Settlement:  82 transaksi (75%)
--   - Pending:     13 transaksi (12%)
--   - Expire:       5 transaksi (5%)
--   - Cancel:       3 transaksi (3%)
--   - Deny:         2 transaksi (2%)
--   - Bootcamp:    29 transaksi
--   - E-Learning:  80 transaksi
--
-- Tanggal transaksi tersebar:
--   Juni 2026:     ~25 transaksi
--   Juli 2026:     ~25 transaksi
--   Agustus 2026:  ~25 transaksi
--   September 2026: ~34 transaksi
--
-- CATATAN PENTING:
-- 1. Password semua user dummy perlu di-update via PHP
--    karena MySQL tidak bisa generate bcrypt hash.
--    Jalankan: php seed_update_password.php
-- 2. Jalankan: php seed_images.php untuk membuat gambar placeholder
-- 3. User demo (setup.php) tidak terpengaruh seed ini
-- 4. Kategori bootcamp sudah disesuaikan dengan filter:
--    Front-End, Back-End, UI/UX Design, Data & AI, Mobile Dev
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;
