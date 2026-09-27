/* =========================================================
   BELAJARYUK — KELAS SAYA (js/kelas.js)
   ---------------------------------------------------------
   Sumber data : proses/kelas.php (hanya kelas berbayar
                 milik user yang sedang login)
   ========================================================= */

(function () {
    "use strict";

    var KELAS_API = "../proses/kelas.php";

    var allItems = [];
    var activeJenis = "all";

    function $(id) { return document.getElementById(id); }

    function showState(name) {
        var map = {
            loading: "kelasLoading",
            empty: "kelasEmpty",
            grid: "kelasGrid",
            notfound: "kelasNotFound"
        };
        Object.keys(map).forEach(function (key) {
            var el = $(map[key]);
            if (el) {
                el.style.display = key === name
                    ? (key === "grid" ? "grid" : (key === "loading" ? "flex" : "block"))
                    : "none";
            }
        });
    }

    function detailHref(item) {
        return item.jenis_produk === "bootcamp"
            ? "detail_bootcamp.html?id=" + item.produk_id
            : "detail.html?id=" + item.produk_id;
    }

    function kelasCard(item) {
        var isBootcamp = item.jenis_produk === "bootcamp";
        var nama = item.nama_produk || "Kelas";
        var tanggal = item.tanggal_mulai
            ? UserCards.formatDateID(item.tanggal_mulai)
            : (item.transaction_time ? UserCards.formatDateID(item.transaction_time) : "-");
        var image = UserCards.src(item.gambar);

        return '' +
            '<article class="user-kelas-card">' +
                '<div class="user-kelas-media">' +
                    '<img src="' + UserCards.esc(image) + '" alt="' + UserCards.esc(nama) + '"' +
                        ' onerror="this.onerror=null;this.style.display=\'none\';' +
                        'this.parentNode.insertAdjacentHTML(\'beforeend\',UserCards.placeholder());">' +
                    '<span class="user-badge ' + (isBootcamp ? "user-badge-warning" : "user-badge-primary") + '">' +
                        (isBootcamp ? "Bootcamp" : "E-Learning") + '</span>' +
                '</div>' +
                '<div class="user-kelas-body">' +
                    '<div class="user-kelas-tag">' + UserCards.esc(item.kategori || (isBootcamp ? "Bootcamp" : "E-Learning")) +
                        (item.subkategori ? " • " + UserCards.esc(item.subkategori) : "") + '</div>' +
                    '<h3 class="user-kelas-title">' + UserCards.esc(nama) + '</h3>' +
                    '<p class="user-kelas-desc">' + UserCards.esc((item.deskripsi || "").slice(0, 130)) +
                        ((item.deskripsi || "").length > 130 ? "…" : "") + '</p>' +
                    '<ul class="user-kelas-meta">' +
                        '<li><span>🗓</span>' + UserCards.esc(tanggal) + '</li>' +
                        (item.durasi ? '<li><span>⏱</span>' + UserCards.esc(item.durasi) + '</li>' : "") +
                        '<li><span>🧾</span>' + UserCards.esc(item.order_id || "-") + '</li>' +
                    '</ul>' +
                    '<div class="user-kelas-foot">' +
                        '<strong class="user-kelas-price">' + UserCards.esc(UserCards.rupiah(item.gross_amount)) + '</strong>' +
                        '<a class="user-btn user-btn-primary" href="' + detailHref(item) + '">' +
                            (isBootcamp ? "Lihat Program" : "Lanjutkan Belajar") + '</a>' +
                    '</div>' +
                '</div>' +
            '</article>';
    }

    function filtered() {
        var q = (($("kelasSearch") && $("kelasSearch").value) || "").trim().toLowerCase();
        return allItems.filter(function (item) {
            var jenisOk = activeJenis === "all" || item.jenis_produk === activeJenis;
            var text = (item.nama_produk + " " + (item.kategori || "") + " " + (item.subkategori || "")).toLowerCase();
            return jenisOk && (!q || text.indexOf(q) !== -1);
        });
    }

    function render() {
        var list = filtered();
        var count = $("kelasCount");
        if (count) count.textContent = allItems.length + " Kelas";

        if (!allItems.length) {
            showState("empty");
            return;
        }

        if (!list.length) {
            showState("notfound");
            return;
        }

        var grid = $("kelasGrid");
        grid.innerHTML = list.map(kelasCard).join("");
        showState("grid");
    }

    function bind() {
        var search = $("kelasSearch");
        if (search) {
            search.addEventListener("input", render);
        }

        var chips = $("kelasChips");
        if (chips) {
            chips.addEventListener("click", function (event) {
                var chip = event.target.closest("[data-jenis]");
                if (!chip) return;
                activeJenis = chip.getAttribute("data-jenis");
                chips.querySelectorAll(".user-chip").forEach(function (c) {
                    c.classList.toggle("is-active", c === chip);
                });
                render();
            });
        }
    }

    function load() {
        showState("loading");

        fetch(KELAS_API + "?_=" + Date.now(), {
            credentials: "include",
            cache: "no-store",
            headers: { "Accept": "application/json" }
        })
            .then(function (response) {
                if (response.status === 401) {
                    window.location.href = "../proses/masuk.php?redirect=" +
                        encodeURIComponent(window.location.href);
                    return new Promise(function () { /* menunggu redirect */ });
                }
                return response.json().catch(function () {
                    throw new Error("Respons server tidak valid (HTTP " + response.status + ").");
                });
            })
            .then(function (result) {
                if (!result || result.success !== true) {
                    throw new Error((result && result.message) || "Gagal memuat kelas saya.");
                }
                allItems = Array.isArray(result.data) ? result.data : [];
                render();
            })
            .catch(function (error) {
                console.error("KELAS ERROR:", error);
                showState("empty");
                UserApp.toast(error.message || "Gagal memuat kelas saya.", "error");
            });
    }

    window.UserKelas = {
        card: kelasCard,
        detailHref: detailHref
    };

    document.addEventListener("DOMContentLoaded", function () {
        bind();
        UserApp.loadStatus()
            .then(function () { load(); })
            .catch(function () { load(); });
    });
})();
