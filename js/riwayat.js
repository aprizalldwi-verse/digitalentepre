/* =========================================================
   BELAJARYUK — RIWAYAT TRANSAKSI (js/riwayat.js)
   ---------------------------------------------------------
   Daftar transaksi : proses/riwayat.php
   Bayar ulang      : proses/create_payment.php + Snap
   ========================================================= */

(function () {
    "use strict";

    var RIWAYAT_API = "../proses/riwayat.php";
    var PAYMENT_API = "../proses/create_payment.php";

    var all = [];
    var activeStatus = "all";

    function $(id) { return document.getElementById(id); }

    function showState(name) {
        var ids = {
            loading: "riwayatLoading",
            empty: "riwayatEmpty",
            notfound: "riwayatNotFound",
            table: "riwayatWrap",
            cards: "riwayatCards"
        };
        Object.keys(ids).forEach(function (key) {
            var el = $(ids[key]);
            if (el) el.style.display = key === name
                ? (key === "table" ? "block" : (key === "cards" ? "grid" : (key === "loading" ? "flex" : "block")))
                : "none";
        });
    }

    function category(status) {
        if (status === "settlement" || status === "capture") return "paid";
        if (status === "pending") return "pending";
        return "failed";
    }

    function statusLabel(status) {
        var map = {
            settlement: "Lunas",
            capture: "Lunas",
            pending: "Menunggu Bayar",
            deny: "Ditolak",
            cancel: "Dibatalkan",
            expire: "Kedaluwarsa",
            failure: "Gagal",
            refund: "Refund"
        };
        return map[status] || status;
    }

    function badgeClass(status) {
        var group = category(status);
        if (group === "paid") return "user-badge user-badge-success";
        if (group === "pending") return "user-badge user-badge-warning";
        return "user-badge user-badge-danger";
    }

    function tanggal(trx) {
        var value = trx.settlement_time || trx.transaction_time || trx.created_at;
        return value ? UserCards.formatDateID(value) : "-";
    }

    function filtered() {
        return all.filter(function (trx) {
            return activeStatus === "all" ||
                category(String(trx.transaction_status || "").toLowerCase()) === activeStatus;
        });
    }

    function renderStats() {
        var paid = all.filter(function (t) { return category(t.transaction_status) === "paid"; });
        var pending = all.filter(function (t) { return category(t.transaction_status) === "pending"; });
        var nominal = paid.reduce(function (sum, t) { return sum + Number(t.gross_amount || 0); }, 0);

        if ($("statTotal")) $("statTotal").textContent = all.length;
        if ($("statPaid")) $("statPaid").textContent = paid.length;
        if ($("statPending")) $("statPending").textContent = pending.length;
        if ($("statNominal")) $("statNominal").textContent = UserCards.rupiah(nominal);
    }

    function actionCell(trx) {
        var status = String(trx.transaction_status || "").toLowerCase();
        var href = trx.jenis_produk === "bootcamp"
            ? "detail_bootcamp.html?id=" + trx.produk_id
            : "detail.html?id=" + trx.produk_id;

        if (category(status) === "pending") {
            return '<button type="button" class="user-btn user-btn-primary user-btn-sm" ' +
                'data-pay="' + UserCards.esc(trx.order_id) + '">💳 Bayar Lagi</button>';
        }
        if (category(status) === "paid") {
            return '<a class="user-btn user-btn-outline user-btn-sm" href="kelas.html">Buka Kelas</a>';
        }
        return '<a class="user-btn user-btn-outline user-btn-sm" href="' + href + '">Lihat Produk</a>';
    }

    function renderTable(list) {
        $("riwayatBody").innerHTML = list.map(function (trx) {
            var status = String(trx.transaction_status || "").toLowerCase();
            return '<tr' + (trx.order_id === highlightOrderId() ? ' class="is-highlight"' : "") + '>' +
                '<td><strong>' + UserCards.esc(trx.order_id || "-") + '</strong></td>' +
                '<td>' + UserCards.esc(trx.nama_produk || "-") + '</td>' +
                '<td>' + (trx.jenis_produk === "bootcamp" ? "Bootcamp" : "E-Learning") + '</td>' +
                '<td>' + UserCards.esc(UserCards.rupiah(trx.gross_amount)) + '</td>' +
                '<td>' + UserCards.esc(tanggal(trx)) + '</td>' +
                '<td><span class="' + badgeClass(status) + '">' +
                    UserCards.esc(statusLabel(status)) + '</span></td>' +
                '<td>' + actionCell(trx) + '</td>' +
                "</tr>";
        }).join("");
    }

    function render() {
        if (!all.length) { showState("empty"); return; }
        var list = filtered();
        if (!list.length) { showState("notfound"); return; }

        renderTable(list);
        showState("table");

        $("riwayatBody").querySelectorAll("[data-pay]").forEach(function (btn) {
            btn.addEventListener("click", function () {
                var trx = all.find(function (t) { return String(t.order_id) === btn.getAttribute("data-pay"); });
                if (trx) pay(trx, btn);
            });
        });
    }

    function highlightOrderId() {
        return UserApp.params().get("order_id") || "";
    }

    function pay(trx, button) {
        var original = button.innerHTML;
        button.disabled = true;
        button.innerHTML = "Memproses...";

        fetch(PAYMENT_API, {
            method: "POST",
            credentials: "include",
            cache: "no-store",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify({ jenis_produk: trx.jenis_produk, produk_id: Number(trx.produk_id) })
        })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error("Response pembayaran bukan JSON (HTTP " + response.status + ").");
                });
            })
            .then(function (result) {
                if (result.success !== true || !result.data) {
                    throw new Error(result.message || "Gagal membuat pembayaran.");
                }
                var token = result.data.snap_token || "";
                if (!token) throw new Error("Snap token tidak diberikan oleh server.");
                if (typeof window.snap === "undefined") {
                    throw new Error("Midtrans Snap belum tersedia. Periksa koneksi internet.");
                }

                window.snap.pay(token, {
                    onSuccess: function () {
                        UserApp.toast("Pembayaran berhasil.", "success");
                        load();
                    },
                    onPending: function () {
                        UserApp.toast("Pembayaran menunggu konfirmasi.", "info");
                        load();
                    },
                    onError: function () {
                        UserApp.toast("Pembayaran gagal.", "error");
                        load();
                    },
                    onClose: function () {
                        button.disabled = false;
                        button.innerHTML = original;
                    }
                });
            })
            .catch(function (error) {
                console.error("PAY ERROR:", error);
                button.disabled = false;
                button.innerHTML = original;
                UserApp.toast(error.message || "Gagal memproses pembayaran.", "error");
            });
    }

    function bind() {
        var chips = $("riwayatChips");
        if (chips) {
            chips.addEventListener("click", function (event) {
                var chip = event.target.closest("[data-status]");
                if (!chip) return;
                activeStatus = chip.getAttribute("data-status");
                chips.querySelectorAll(".user-chip").forEach(function (c) {
                    c.classList.toggle("is-active", c === chip);
                });
                render();
            });
        }
    }

    function consumeParams() {
        var status = UserApp.params().get("status");
        var orderId = UserApp.params().get("order_id");
        if (!orderId) return;

        var text = status === "success"
            ? "Pembayaran berhasil! Terima kasih."
            : status === "pending"
                ? "Pembayaran diterima, menunggu konfirmasi."
                : status === "error"
                    ? "Pembayaran dibatalkan atau gagal."
                    : "";
        if (text) UserApp.toast(text, status === "error" ? "error" : "success");
    }

    function load() {
        showState("loading");

        fetch(RIWAYAT_API + "?_=" + Date.now(), {
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
                    throw new Error((result && result.message) || "Gagal memuat riwayat.");
                }
                all = Array.isArray(result.data) ? result.data : [];
                renderStats();
                render();
            })
            .catch(function (error) {
                console.error("RIWAYAT ERROR:", error);
                showState("empty");
                UserApp.toast(error.message || "Gagal memuat riwayat transaksi.", "error");
            });
    }

    document.addEventListener("DOMContentLoaded", function () {
        bind();
        consumeParams();
        UserApp.loadStatus()
            .then(function () { load(); })
            .catch(function () { load(); });
    });
})();
