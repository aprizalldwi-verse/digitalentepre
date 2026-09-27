/* =========================================================
   BELAJARYUK — DASHBOARD USER (js/dashboard.js)
   ---------------------------------------------------------
   Data akun     : proses/status_login.php (session)
   Transaksi     : proses/riwayat.php
   Kelas saya    : proses/kelas.php
   Katalog       : proses/katalog.php
   ========================================================= */

(function () {
    "use strict";

    var KATALOG = "../proses/katalog.php";
    var RIWAYAT = "../proses/riwayat.php";
    var KELAS = "../proses/kelas.php";

    function $(id) { return document.getElementById(id); }

    function fetchJSON(url) {
        return fetch(url, { credentials: "include", cache: "no-store", headers: { "Accept": "application/json" } })
            .then(function (response) {
                if (response.status === 401) return { success: false, unauthorized: true };
                return response.json().catch(function () {
                    throw new Error("Respons server tidak valid (HTTP " + response.status + ").");
                });
            });
    }

    function isPaid(status) {
        var s = String(status || "").toLowerCase();
        return s === "settlement" || s === "capture";
    }

    function renderUserStats(transactions) {
        var paid = transactions.filter(function (t) { return isPaid(t.transaction_status); });
        var elearning = paid.filter(function (t) { return t.jenis_produk === "elearning"; });
        var bootcamp = paid.filter(function (t) { return t.jenis_produk === "bootcamp"; });
        var nominal = paid.reduce(function (sum, t) { return sum + Number(t.gross_amount || 0); }, 0);

        if ($("userKelas")) $("userKelas").textContent = elearning.length;
        if ($("userBootcamp")) $("userBootcamp").textContent = bootcamp.length;
        if ($("userTransaksi")) $("userTransaksi").textContent = transactions.length;
        if ($("userBelanja")) $("userBelanja").textContent = UserApp.formatRupiah(nominal);
    }

    function renderMyClasses(items) {
        var loading = $("dashKelasLoading");
        var empty = $("dashKelasEmpty");
        var grid = $("dashKelasGrid");

        if (loading) loading.style.display = "none";

        if (!items.length) {
            if (empty) empty.style.display = "block";
            if (grid) grid.style.display = "none";
            return;
        }

        if (empty) empty.style.display = "none";
        grid.style.display = "grid";
        grid.innerHTML = items.slice(0, 4).map(window.UserKelas.card).join("");
    }

    function renderPopular(items) {
        var grid = $("dashPopular");
        if (!grid) return;
        grid.innerHTML = items.length
            ? items.map(UserCards.courseCard).join("")
            : '<div class="user-state" style="grid-column:1/-1"><div class="user-state-icon">📚</div>' +
              '<h4>Belum ada kelas</h4></div>';
    }

    function renderBootcamps(items) {
        var grid = $("dashBootcamp");
        if (!grid) return;
        grid.innerHTML = items.length
            ? items.map(UserCards.bootcampCard).join("")
            : '<div class="user-state" style="grid-column:1/-1"><div class="user-state-icon">🗓</div>' +
              '<h4>Belum ada bootcamp</h4></div>';
    }

    function renderPlatformStats(stats) {
        var cards = $("platformStats");
        if (!cards || !stats) return;
        var values = [
            stats.pengguna,
            stats.kelas_dan_bootcamp,
            stats.transaksi_berhasil,
            stats.peserta_aktif
        ];
        cards.querySelectorAll(".user-stat-card span").forEach(function (el, index) {
            el.textContent = values[index] !== undefined ? values[index] : "-";
        });
    }

    function load() {
        var state = UserApp.state || {};

        fetchJSON(RIWAYAT)
            .then(function (result) {
                if (result.unauthorized) {
                    window.location.href = "../proses/masuk.php?redirect=" + encodeURIComponent(window.location.href);
                    return new Promise(function () { /* menunggu redirect */ });
                }
                var rows = result.success && Array.isArray(result.data) ? result.data : [];
                renderUserStats(rows);
                return null;
            })
            .catch(function () { renderUserStats([]); });

        fetchJSON(KELAS)
            .then(function (result) {
                var items = result.success && Array.isArray(result.data) ? result.data : [];
                renderMyClasses(items);
            })
            .catch(function () { renderMyClasses([]); });

        fetchJSON(KATALOG + "?type=elearning&sort=terpopuler&limit=4")
            .then(function (result) {
                renderPopular(result.success && Array.isArray(result.data) ? result.data : []);
            })
            .catch(function () { renderPopular([]); });

        fetchJSON(KATALOG + "?type=bootcamp&sort=terdekat&limit=3")
            .then(function (result) {
                renderBootcamps(result.success && Array.isArray(result.data) ? result.data : []);
            })
            .catch(function () { renderBootcamps([]); });

        fetchJSON(KATALOG + "?type=statistik")
            .then(function (result) {
                renderPlatformStats(result.success ? result.data : null);
            })
            .catch(function () { renderPlatformStats(null); });

        if (state.nama) {
            var name = String(state.nama).split(" ")[0];
            if ($("dashUserName")) $("dashUserName").textContent = name;
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        UserApp.loadStatus()
            .then(function (status) {
                if (!status || !status.loggedIn) {
                    window.location.href = "../proses/masuk.php?redirect=" + encodeURIComponent(window.location.href);
                    return new Promise(function () { /* menunggu redirect */ });
                }
                load();
                return null;
            })
            .catch(function () { load(); });
    });
})();
