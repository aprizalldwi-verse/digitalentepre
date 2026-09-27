/* =========================================================
   BELAJARYUK — CHECKOUT (js/checkout.js)
   ---------------------------------------------------------
   Ringkasan pesanan : proses/katalog.php?type=...&id=...
   Pembayaran       : proses/create_payment.php (Midtrans Snap)
   Setelah bayar     : pages/riwayat.html (data transaksi DB)
   ========================================================= */

(function () {
    "use strict";

    var KATALOG = "../proses/katalog.php";
    var PAYMENT = "../proses/create_payment.php";

    var product = null;
    var jenis = "";
    var produkId = 0;

    function $(id) { return document.getElementById(id); }

    function set(id, value) {
        var el = $(id);
        if (el) el.textContent = value;
    }

    function parseParams() {
        var params = new URLSearchParams(window.location.search);
        var type = params.get("type") === "bootcamp" ? "bootcamp" : "elearning";
        var id = parseInt(params.get("id") || "0", 10);
        return { type: type, id: isNaN(id) ? 0 : id };
    }

    function showState(kind) {
        if ($("checkoutLoading")) $("checkoutLoading").style.display = kind === "loading" ? "flex" : "none";
        if ($("checkoutContent")) $("checkoutContent").style.display = kind === "content" ? "grid" : "none";
        if ($("checkoutError")) $("checkoutError").style.display = kind === "error" ? "block" : "none";
    }

    function showError(title, message) {
        set("checkoutErrorTitle", title);
        set("checkoutErrorMessage", message);
        showState("error");
    }

    function fetchJSON(url, options) {
        return fetch(url, options || {}).then(function (response) {
            if (response.status === 401) {
                var target = encodeURIComponent(window.location.href);
                window.location.href = "../proses/masuk.php?redirect=" + target;
                return new Promise(function () { /* menunggu redirect */ });
            }
            return response.json().catch(function () {
                throw new Error("Respons server tidak valid (HTTP " + response.status + ").");
            });
        });
    }

    function ownedCheck() {
        return fetchJSON("../proses/riwayat.php?_=" + Date.now())
            .then(function (result) {
                if (!result || result.success !== true || !Array.isArray(result.data)) return false;
                return result.data.some(function (trx) {
                    var status = String(trx.transaction_status || "").toLowerCase();
                    return (status === "settlement" || status === "capture") &&
                        String(trx.jenis_produk) === jenis &&
                        Number(trx.produk_id) === produkId;
                });
            })
            .catch(function () { return false; });
    }

    function renderOrder(item, owned) {
        var info = UserCards.priceInfo(item);
        var discount = Math.max(0, info.normal - info.current);
        var isBootcamp = jenis === "bootcamp";
        var name = isBootcamp ? item.judul : item.nama_produk;

        document.title = "Checkout: " + name + " — BelajarYuk";

        var media = $("orderImage");
        if (media) {
            var path = UserCards.src(item.gambar);
            if (path) {
                media.src = path;
                media.onerror = function () {
                    media.onerror = null;
                    media.style.display = "none";
                    media.parentNode.insertAdjacentHTML("beforeend", UserCards.placeholder());
                };
            } else {
                media.style.display = "none";
                media.parentNode.insertAdjacentHTML("beforeend", UserCards.placeholder());
            }
        }

        set("orderCategory", (item.kategori || (isBootcamp ? "Bootcamp" : "E-Learning")) +
            (isBootcamp ? " • Bootcamp" : ""));
        set("orderName", name);

        var sub = [];
        if (item.mentor) sub.push("Mentor: " + item.mentor);
        if (item.durasi) sub.push("Durasi: " + item.durasi);
        if (item.subkategori) sub.push(item.subkategori);
        set("orderSub", sub.length ? sub.join(" • ") : (item.deskripsi || "-").slice(0, 110));

        set("lineNormal", UserCards.rupiah(info.normal));
        set("lineDiscount", discount > 0 ? "– " + UserCards.rupiah(discount) : "Tidak ada promo");
        set("lineTotal", UserCards.rupiah(info.current));

        var back = $("backButton");
        if (back) back.href = isBootcamp ? "bootcamp.html" : "elearning.html";

        var breadcrumbBack = $("breadcrumbBack");
        if (breadcrumbBack) breadcrumbBack.href = isBootcamp ? "bootcamp.html" : "elearning.html";

        var payButton = $("payButton");
        if (owned) {
            payButton.disabled = true;
            payButton.textContent = "✔ Kamu Sudah Punya Akses";
            if (back) {
                back.href = "kelas.html";
                back.textContent = "Buka Kelas Saya";
            }
        }

        showState("content");

        return payButton;
    }

    function buyerInfo() {
        var state = (window.UserApp && UserApp.state) || {};
        set("buyerName", state.nama || "-");
        set("buyerEmail", state.email || "-");
    }

    function pay(button) {
        button.disabled = true;
        var originalHTML = button.innerHTML;
        button.innerHTML = "Memproses...";

        fetch(PAYMENT, {
            method: "POST",
            credentials: "include",
            cache: "no-store",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify({ jenis_produk: jenis, produk_id: produkId })
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

                var snapToken = result.data.snap_token || "";
                if (!snapToken) {
                    throw new Error("Snap token tidak diberikan oleh server.");
                }

                if (typeof window.snap === "undefined") {
                    throw new Error("Midtrans Snap belum tersedia. Periksa koneksi internet.");
                }

                var orderId = result.data.order_id || "";
                var goToRiwayat = function (status) {
                    var url = "riwayat.html?order_id=" + encodeURIComponent(orderId) + "&status=" + status;
                    UserApp.toast(
                        status === "success"
                            ? "Pembayaran berhasil! Kelas langsung terbuka."
                            : status === "pending"
                                ? "Pembayaran sedang diproses. Cek Riwayat Transaksi."
                                : "Pembayaran dibatalkan. Kamu bisa mencoba lagi dari Riwayat.",
                        status === "error" ? "error" : "success"
                    );
                    setTimeout(function () { window.location.href = url; }, 700);
                };

                window.snap.pay(snapToken, {
                    onSuccess: function () { goToRiwayat("success"); },
                    onPending: function () { goToRiwayat("pending"); },
                    onError: function () { goToRiwayat("error"); },
                    onClose: function () {
                        button.disabled = false;
                        button.innerHTML = originalHTML;
                        UserApp.toast("Kamu menutup jendela pembayaran. Transaksi tetap tersimpan.", "info");
                    }
                });
            })
            .catch(function (error) {
                console.error("CHECKOUT ERROR:", error);
                button.disabled = false;
                button.innerHTML = originalHTML;
                UserApp.toast(error.message || "Gagal memproses pembayaran.", "error");
            });
    }

    function init() {
        var parsed = parseParams();
        jenis = parsed.type;
        produkId = parsed.id;

        if (!produkId) {
            showError("Pesanan tidak ditemukan", "URL checkout tidak memiliki ID produk yang valid.");
            return;
        }

        fetchJSON(KATALOG + "?type=" + jenis + "&id=" + produkId)
            .then(function (result) {
                if (!result.success || !result.data) {
                    showError("Produk tidak ditemukan", result.message || "Produk sudah dihapus dari database.");
                    return Promise.reject(new Error("stop"));
                }
                product = result.data;
                return ownedCheck().then(function (owned) {
                    var button = renderOrder(product, owned);
                    buyerInfo();
                    if (button && !owned) {
                        button.addEventListener("click", function () { pay(button); });
                    }
                });
            })
            .catch(function (error) {
                if (error && error.message === "stop") return;
                console.error("CHECKOUT LOAD ERROR:", error);
                showError("Gagal memuat pesanan", error.message || "Terjadi kesalahan saat mengambil data produk.");
            });
    }

    document.addEventListener("DOMContentLoaded", function () {
        UserApp.loadStatus()
            .then(function () { init(); })
            .catch(function () { init(); });
    });
})();
