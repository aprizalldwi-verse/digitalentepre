/* =========================================================
   BELAJARYUK — HOMEPAGE (js/home.js)
   ---------------------------------------------------------
   Semua data diambil dari database lewat proses/katalog.php
   ========================================================= */

(function () {
    "use strict";

    var KATALOG = "../proses/katalog.php";

    var KATEGORI_ICON = {
        "Front-End": "💻",
        "Back-End": "⚙️",
        "UI/UX": "🎨",
        "Design": "🖌️",
        "Data & AI": "📊",
        "Mobile Dev": "📱",
        "Programming": "💻",
        "Web Development": "🌐",
        "Digital Marketing": "📣",
        "Content": "📝",
        "Business": "📈",
        "Data Science": "📊"
    };

    function $(id) {
        return document.getElementById(id);
    }

    function fetchJSON(url) {
        return fetch(url, { credentials: "include", cache: "no-store", headers: { "Accept": "application/json" } })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error("Respons server tidak valid (" + response.status + ").");
                });
            })
            .then(function (data) {
                if (data && data.success === false) {
                    throw new Error(data.message || "Gagal memuat data.");
                }
                return data;
            });
    }

    function set(id, html) {
        var el = $(id);
        if (el) el.innerHTML = html;
    }

    /* ---------------------------------------------------------
       HERO SHOWCASE (gambar course dari database)
    --------------------------------------------------------- */
    function loadHero(data) {
        if (!data || !data.length) return;

        var items = data.slice(0, 3);
        var html = items.map(function (course, index) {
            return (
                '<div class="user-hero-showcase-item' + (index === 0 ? " is-main" : "") + '">' +
                    UserCards.mediaImage(course.gambar, course.nama_produk) +
                "</div>"
            );
        }).join("");

        set("heroShowcase", html);
    }

    /* ---------------------------------------------------------
       KATEGORI
    --------------------------------------------------------- */
    function loadKategori() {
        set("kategoriGrid", UserCards.loaderHTML("Memuat kategori..."));

        fetchJSON(KATALOG + "?type=kategori")
            .then(function (result) {
                var list = (result.data && result.data.elearning) ? result.data.elearning : [];
                if (!list.length) {
                    set("kategoriGrid", UserCards.emptyHTML("Belum ada kategori", "Kategori kelas akan muncul setelah tersedia di database."));
                    return;
                }
                set("kategoriGrid", list.map(function (item) {
                    var icon = KATEGORI_ICON[item.nama] || "📚";
                    return (
                        '<a class="user-cat-card" href="elearning.html?kategori=' + encodeURIComponent(item.nama) + '">' +
                            '<span class="user-cat-icon">' + icon + "</span>" +
                            '<div class="user-cat-name">' + UserCards.esc(item.nama) + "</div>" +
                            '<div class="user-cat-count">' + UserCards.esc(item.jumlah) + " Kelas</div>" +
                        "</a>"
                    );
                }).join(""));
            })
            .catch(function (error) {
                set("kategoriGrid", UserCards.errorHTML(error.message));
            });
    }

    /* ---------------------------------------------------------
       E-LEARNING POPULER
    --------------------------------------------------------- */
    function loadPopuler() {
        set("populerGrid", UserCards.loaderHTML("Memuat kelas..."));

        fetchJSON(KATALOG + "?type=elearning&sort=terpopuler&limit=5")
            .then(function (result) {
                var list = result.data || [];
                if (!list.length) {
                    set("populerGrid", UserCards.emptyHTML("Belum ada kelas", "Kelas E-Learning akan tampil di sini."));
                    return;
                }
                set("populerGrid", list.map(UserCards.courseCard).join(""));
                loadHero(list);
            })
            .catch(function (error) {
                set("populerGrid", UserCards.errorHTML(error.message));
            });
    }

    /* ---------------------------------------------------------
       BOOTCAMP
    --------------------------------------------------------- */
    function loadBootcamp() {
        set("bootcampGrid", UserCards.loaderHTML("Memuat bootcamp..."));

        fetchJSON(KATALOG + "?type=bootcamp&sort=terpopuler&limit=3")
            .then(function (result) {
                var list = result.data || [];
                if (!list.length) {
                    set("bootcampGrid", UserCards.emptyHTML("Belum ada bootcamp", "Program bootcamp akan tampil di sini."));
                    return;
                }
                set("bootcampGrid", list.map(UserCards.bootcampCard).join(""));
            })
            .catch(function (error) {
                set("bootcampGrid", UserCards.errorHTML(error.message));
            });
    }

    /* ---------------------------------------------------------
       INSTRUKTUR
    --------------------------------------------------------- */
    function loadInstruktur() {
        set("instrukturGrid", UserCards.loaderHTML("Memuat instruktur..."));

        fetchJSON(KATALOG + "?type=instruktur")
            .then(function (result) {
                var list = result.data || [];
                if (!list.length) {
                    set("instrukturGrid", UserCards.emptyHTML("Belum ada instruktur", "Data instruktur akan tampil setelah tersedia di database."));
                    return;
                }
                set("instrukturGrid", list.map(function (item) {
                    return (
                        '<div class="user-instructor-card">' +
                            '<span class="user-avatar user-avatar-lg">' + UserCards.esc(item.inisial) + "</span>" +
                            '<div class="user-instructor-name">' + UserCards.esc(item.nama) + "</div>" +
                            '<div class="user-instructor-role">' + UserCards.esc(item.keahlian || "Instruktur") + "</div>" +
                            '<div class="user-instructor-meta">' +
                                "<span><strong>" + UserCards.esc(item.jumlah_bootcamp) + "</strong> Bootcamp</span>" +
                                "<span><strong>" + UserCards.esc(item.total_kuota) + "</strong> Kuota</span>" +
                            "</div>" +
                        "</div>"
                    );
                }).join(""));
            })
            .catch(function (error) {
                set("instrukturGrid", UserCards.errorHTML(error.message));
            });
    }

    /* ---------------------------------------------------------
       STATISTIK + RINGKASAN TENTANG
    --------------------------------------------------------- */
    function formatAngka(value) {
        var n = Number(value || 0);
        if (n >= 1000000) return (n / 1000000).toFixed(1).replace(".0", "") + "M+";
        if (n >= 1000) return (n / 1000).toFixed(1).replace(".0", "") + "K+";
        return n.toLocaleString("id-ID");
    }

    function loadStatistik() {
        fetchJSON(KATALOG + "?type=statistik")
            .then(function (result) {
                var s = result.data || {};
                if ($("statPengguna")) $("statPengguna").textContent = formatAngka(s.pengguna);
                if ($("statKelas")) $("statKelas").textContent = formatAngka(s.kelas_dan_bootcamp);
                if ($("statTransaksi")) $("statTransaksi").textContent = formatAngka(s.transaksi_berhasil);
                if ($("statPeserta")) $("statPeserta").textContent = formatAngka(s.peserta_aktif);

                if ($("heroFloatPeserta")) $("heroFloatPeserta").textContent = formatAngka(s.peserta_aktif);
                if ($("heroFloatKelas")) $("heroFloatKelas").textContent = formatAngka(s.kelas_dan_bootcamp);

                if ($("tentangKelas")) $("tentangKelas").textContent = (s.kelas || 0) + " kelas";
                if ($("tentangBootcamp")) $("tentangBootcamp").textContent = (s.bootcamp || 0) + " program";
                if ($("tentangPengguna")) $("tentangPengguna").textContent = formatAngka(s.pengguna) + " user";
            })
            .catch(function () {
                /* angka tetap "-" bila gagal, tidak dikarang */
            });

        fetchJSON(KATALOG + "?type=instruktur")
            .then(function (result) {
                var list = result.data || [];
                if ($("tentangInstruktur")) $("tentangInstruktur").textContent = list.length + " mentor";
            })
            .catch(function () { /* abaikan */ });
    }

    document.addEventListener("DOMContentLoaded", function () {
        loadKategori();
        loadPopuler();
        loadBootcamp();
        loadInstruktur();
        loadStatistik();
    });
})();
