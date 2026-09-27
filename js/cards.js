/* =========================================================
   BELAJARYUK — KOMPONEN CARD (Frontend User)
   ---------------------------------------------------------
   Dipakai bersama oleh: home.js, elearning.js,
   bootcamp_user.js, kelas.js, dashboard.js
   ========================================================= */

(function () {
    "use strict";

    function esc(value) {
        if (window.UserApp && typeof window.UserApp.escapeHTML === "function") {
            return window.UserApp.escapeHTML(value);
        }
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;")
            .replace(/>/g, "&gt;").replace(/"/g, "&quot;");
    }

    function rupiah(value) {
        if (window.UserApp && typeof window.UserApp.formatRupiah === "function") {
            return window.UserApp.formatRupiah(value);
        }
        return "Rp " + Number(value || 0).toLocaleString("id-ID");
    }

    function src(path) {
        if (window.UserApp && typeof window.UserApp.imgSrc === "function") {
            return window.UserApp.imgSrc(path);
        }
        if (!path) return "";
        if (typeof window.resolveImagePath === "function") return window.resolveImagePath(path);
        var value = String(path);
        if (/^(https?:)?\/\//i.test(value) || value.charAt(0) === "/") return value;
        return "../" + value.replace(/^(\.\.\/)+/, "");
    }

    function priceInfo(item) {
        var normal = Number(item.harga || 0);
        var promo = Number(item.harga_promo || 0);
        var aktif = String(item.promo_aktif) === "1" || item.promo_aktif === 1 || item.promo_aktif === true;
        var current = aktif && promo > 0 && promo < normal ? promo : normal;
        return {
            current: current,
            normal: normal,
            showOld: aktif && promo > 0 && promo < normal,
            isPromo: aktif && promo > 0 && promo < normal
        };
    }

    function priceHTML(item) {
        var info = priceInfo(item);
        return (
            '<div class="user-price">' +
            (info.showOld ? '<span class="user-price-old">' + esc(rupiah(info.normal)) + "</span>" : "") +
            '<span class="user-price-now">' + esc(rupiah(info.current)) + "</span>" +
            "</div>"
        );
    }

    function placeholderHTML() {
        return '<div style="width:100%;height:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;background:#F1F5F9;color:#94A3B8;gap:6px">' +
            '<span style="font-size:26px">📚</span>' +
            '<span style="font-size:12px">Belum ada gambar</span></div>';
    }

    function mediaImage(path, alt, onerrorExtra) {
        var resolved = src(path);
        if (!resolved) {
            return placeholderHTML();
        }
        var handler = "this.onerror=null;this.style.display='none';this.parentNode.insertAdjacentHTML('beforeend', window.UserCards.placeholder());";
        if (onerrorExtra) {
            handler += onerrorExtra;
        }
        return '<img src="' + esc(resolved) + '" alt="' + esc(alt || "") + '" loading="lazy" onerror="' + esc(handler) + '">';
    }

    /* ---------------------------------------------------------
       CARD E-LEARNING
    --------------------------------------------------------- */
    function courseCard(course) {
        var nama = course.nama_produk || course.nama || "Kelas";
        var kategori = course.subkategori || course.kategori || "E-Learning";
        var deskripsi = course.deskripsi || "";
        var peserta = Number(course.peserta || course.participant_count || 0);
        var durasi = course.durasi || "";
        var status = course.status || "active";

        var metaItems = [];
        if (peserta > 0) {
            metaItems.push("<span>👥 " + esc(peserta) + " peserta</span>");
        } else {
            metaItems.push("<span>✨ Kelas baru</span>");
        }
        if (course.jadwal) {
            metaItems.push("<span>🗓 " + esc(course.jadwal) + "</span>");
        }

        return (
            '<article class="user-course-card">' +
                '<div class="user-card-media">' +
                    mediaImage(course.gambar, nama) +
                    '<span class="user-card-badge">E-LEARNING</span>' +
                    (priceInfo(course).isPromo ? '<span class="user-card-badge user-card-badge-promo">PROMO</span>' : "") +
                "</div>" +
                '<div class="user-card-body">' +
                    '<div class="user-card-tags">' +
                        '<span class="user-tag">' + esc(kategori) + "</span>" +
                        (durasi ? '<span class="user-tag user-tag-muted">🕒 ' + esc(durasi) + "</span>" : "") +
                        (status === "active" ? "" : '<span class="user-tag user-tag-muted">Nonaktif</span>') +
                    "</div>" +
                    '<h3 class="user-card-title">' + esc(nama) + "</h3>" +
                    (deskripsi ? '<p class="user-card-desc">' + esc(deskripsi) + "</p>" : "") +
                    '<div class="user-card-meta">' + metaItems.join("") + "</div>" +
                    '<div class="user-card-footer">' +
                        priceHTML(course) +
                        '<a class="user-btn user-btn-primary" href="detail.html?id=' + esc(course.id) + '">Lihat Kelas</a>' +
                    "</div>" +
                "</div>" +
            "</article>"
        );
    }

    /* ---------------------------------------------------------
       CARD BOOTCAMP
    --------------------------------------------------------- */
    function formatDateID(value) {
        if (!value) return "";
        var date = new Date(value);
        if (isNaN(date.getTime())) return value;
        return date.toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" });
    }

    function bootcampCard(item) {
        var nama = item.judul || item.nama_bootcamp || item.nama || "Bootcamp";
        var mentor = item.mentor || "-";
        var kuota = Number(item.kuota || 0);
        var peserta = Number(item.peserta || 0);
        var sisa = Math.max(0, kuota - peserta);
        var kategori = item.kategori || "Bootcamp";
        var deskripsi = item.deskripsi || "";

        var tanggal = "";
        if (item.tanggal_mulai && item.tanggal_berakhir) {
            tanggal = formatDateID(item.tanggal_mulai) + " — " + formatDateID(item.tanggal_berakhir);
        }

        return (
            '<article class="user-bootcamp-card">' +
                '<div class="user-card-media">' +
                    mediaImage(item.gambar, nama) +
                    '<span class="user-card-badge">BOOTCAMP</span>' +
                    (priceInfo(item).isPromo ? '<span class="user-card-badge user-card-badge-promo">PROMO</span>' : "") +
                "</div>" +
                '<div class="user-bootcamp-body">' +
                    '<div class="user-card-tags">' +
                        '<span class="user-tag">' + esc(kategori) + "</span>" +
                        (tanggal ? '<span class="user-tag user-tag-muted">🗓 ' + esc(tanggal) + "</span>" : "") +
                    "</div>" +
                    '<h3 class="user-bootcamp-title">' + esc(nama) + "</h3>" +
                    (deskripsi ? '<p class="user-bootcamp-desc">' + esc(deskripsi) + "</p>" : "") +
                    '<div class="user-bootcamp-chips">' +
                        '<span class="user-chip">👥 ' + esc(peserta) + "/" + esc(kuota) + " peserta</span>" +
                        (sisa > 0 ? '<span class="user-chip">🎯 Sisa kuota ' + esc(sisa) + "</span>" : '<span class="user-chip">Kuota penuh</span>') +
                    "</div>" +
                    '<div class="user-bootcamp-mentor">' +
                        '<span class="user-avatar">' + esc(inisial(mentor)) + "</span>" +
                        "<div><strong>" + esc(mentor) + "</strong><span>Mentor Bootcamp</span></div>" +
                    "</div>" +
                    '<div class="user-bootcamp-footer">' +
                        priceHTML(item) +
                        '<a class="user-btn user-btn-primary" href="detail_bootcamp.html?id=' + esc(item.id) + '">Lihat Detail</a>' +
                    "</div>" +
                "</div>" +
            "</article>"
        );
    }

    function inisial(name) {
        var parts = String(name || "?").trim().split(/\s+/);
        var a = parts[0] ? parts[0].charAt(0) : "?";
        var b = parts.length > 1 ? parts[parts.length - 1].charAt(0) : "";
        return (a + b).toUpperCase();
    }

    /* ---------------------------------------------------------
       STATE HELPERS
    --------------------------------------------------------- */
    function loaderHTML(text) {
        return '<div class="user-loader"><div class="user-spinner"></div>' + esc(text || "Memuat data...") + "</div>";
    }

    function emptyHTML(title, message, actionHTML) {
        return (
            '<div class="user-state">' +
                '<div class="user-state-icon">📭</div>' +
                "<h4>" + esc(title) + "</h4>" +
                "<p>" + esc(message) + "</p>" +
                (actionHTML || "") +
            "</div>"
        );
    }

    function errorHTML(message, retryFn) {
        return (
            '<div class="user-state">' +
                '<div class="user-state-icon">⚠️</div>' +
                "<h4>Gagal memuat data</h4>" +
                "<p>" + esc(message || "Terjadi kesalahan saat mengambil data dari server.") + "</p>" +
                '<button type="button" class="user-btn user-btn-primary" id="' + esc(retryFn || "userRetryBtn") + '">Coba Lagi</button>' +
            "</div>"
        );
    }

    window.UserCards = {
        esc: esc,
        rupiah: rupiah,
        src: src,
        priceInfo: priceInfo,
        priceHTML: priceHTML,
        placeholder: placeholderHTML,
        mediaImage: mediaImage,
        courseCard: courseCard,
        bootcampCard: bootcampCard,
        inisial: inisial,
        formatDateID: formatDateID,
        loaderHTML: loaderHTML,
        emptyHTML: emptyHTML,
        errorHTML: errorHTML
    };
})();
