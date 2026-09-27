/* =========================================================
   BELAJARYUK — DETAIL BOOTCAMP (js/detail_bootcamp.js)
   ---------------------------------------------------------
   Data bootcamp : proses/katalog.php?type=bootcamp&id=...
   Status daftar : proses/riwayat.php (session user)
   Aksi daftar   : pages/checkout.html (create_payment.php)
   ========================================================= */

(function () {
    "use strict";

    var KATALOG = "../proses/katalog.php";
    var RIWAYAT = "../proses/riwayat.php";

    var bootcamp = null;
    var registered = false;

    function $(id) { return document.getElementById(id); }

    function bootcampId() {
        var params = new URLSearchParams(window.location.search);
        var id = parseInt(params.get("id") || "0", 10);
        return isNaN(id) ? 0 : id;
    }

    function fetchJSON(url) {
        return fetch(url, { credentials: "include", cache: "no-store", headers: { "Accept": "application/json" } })
            .then(function (response) {
                if (response.status === 401) {
                    return { success: false, unauthorized: true, message: "Belum login" };
                }
                return response.json().catch(function () {
                    throw new Error("Respons server tidak valid (" + response.status + ").");
                });
            });
    }

    function showError(title, message) {
        if ($("loadingDetail")) $("loadingDetail").style.display = "none";
        if ($("detailContent")) $("detailContent").style.display = "none";
        if ($("errorDetail")) $("errorDetail").style.display = "block";
        if ($("errorDetailTitle")) $("errorDetailTitle").textContent = title;
        if ($("errorDetailMessage")) $("errorDetailMessage").textContent = message;
    }

    function setText(id, value) {
        var el = $(id);
        if (el) el.textContent = value;
    }

    function formatDate(value) {
        if (!value) return "-";
        var date = new Date(value);
        if (isNaN(date.getTime())) return value;
        return date.toLocaleDateString("id-ID", { day: "numeric", month: "long", year: "numeric" });
    }

    function durationText(start, end) {
        if (!start || !end) return "-";
        var s = new Date(start), e = new Date(end);
        if (isNaN(s.getTime()) || isNaN(e.getTime())) return "-";
        var days = Math.round((e - s) / 86400000);
        if (days <= 0) return "-";
        var weeks = Math.round(days / 7);
        if (weeks >= 8) {
            var months = Math.max(1, Math.round(weeks / 4.3));
            return months + " bulan";
        }
        return weeks + " minggu";
    }

    function render(item) {
        var nama = item.judul || "Bootcamp";
        document.title = nama + " — BelajarYuk";

        var media = $("courseImage");
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

        var benefits = item.benefit_list && item.benefit_list.length
            ? item.benefit_list
            : String(item.benefit || "").split("|").map(function (s) { return s.trim(); }).filter(Boolean);

        var kuota = Number(item.kuota || 0);
        var peserta = Number(item.peserta || 0);
        var sisa = Math.max(0, kuota - peserta);
        var penuh = kuota > 0 && peserta >= kuota;

        setText("breadcrumbName", nama);
        setText("courseName", nama);
        setText("courseCategory", item.kategori || "Bootcamp");
        setText("courseDescription", item.deskripsi || "Deskripsi belum tersedia.");
        setText("aboutCourse", item.deskripsi || "Deskripsi belum tersedia.");

        if ($("quotaBadge")) {
            $("quotaBadge").textContent = penuh ? "Kuota Penuh" : "Sisa kuota " + sisa;
            $("quotaBadge").className = "user-badge " + (penuh ? "user-badge-danger" : "user-badge-warning");
        }

        var meta = $("courseMeta");
        if (meta) {
            var items = [];
            if (item.kategori) items.push("<span>🏷 " + UserCards.esc(item.kategori) + "</span>");
            if (item.mentor) items.push("<span>🧑‍🏫 " + UserCards.esc(item.mentor) + "</span>");
            items.push("<span>🕒 " + UserCards.esc(durationText(item.tanggal_mulai, item.tanggal_berakhir)) + "</span>");
            items.push("<span>👥 " + UserCards.esc(peserta) + "/" + UserCards.esc(kuota) + " peserta</span>");
            meta.innerHTML = items.join("");
        }

        setText("infoMentor", item.mentor || "-");
        setText("infoMulai", formatDate(item.tanggal_mulai));
        setText("infoSelesai", formatDate(item.tanggal_berakhir));
        setText("infoKuota", kuota + " peserta");
        setText("infoPeserta", peserta + " orang");

        setText("summaryKategori", item.kategori || "-");
        setText("summaryMentor", item.mentor || "-");
        setText("summaryDurasi", durationText(item.tanggal_mulai, item.tanggal_berakhir));
        setText("summaryKuota", peserta + " / " + kuota);
        setText("summaryHarga", UserCards.rupiah(UserCards.priceInfo(item).current));
        setText("summaryMulai", formatDate(item.tanggal_mulai));
        setText("summaryAkhir", formatDate(item.tanggal_berakhir));

        var priceBox = $("coursePrice");
        if (priceBox) {
            var info = UserCards.priceInfo(item);
            priceBox.innerHTML =
                (info.showOld ? "<s>" + UserCards.esc(UserCards.rupiah(info.normal)) + "</s>" : "") +
                "<strong>" + UserCards.esc(UserCards.rupiah(info.current)) + "</strong>";
        }

        if ($("benefitGrid")) {
            $("benefitGrid").innerHTML = benefits.length
                ? benefits.map(function (b) {
                    return '<div class="user-benefit-item"><span class="user-check">✓</span>' + UserCards.esc(b) + "</div>";
                }).join("")
                : '<p style="color:#64748B">Materi belum tersedia.</p>';
        }

        if ($("curriculumList")) {
            $("curriculumList").innerHTML = benefits.length
                ? benefits.map(function (b) { return "<li><span>" + UserCards.esc(b) + "</span></li>"; }).join("")
                : "<li><span>Materi akan diumumkan.</span></li>";
        }

        if ($("loadingDetail")) $("loadingDetail").style.display = "none";
        if ($("detailContent")) $("detailContent").style.display = "block";

        updateCTA();
    }

    function updateCTA() {
        var button = $("registerButton");
        var secondary = $("secondaryButton");
        if (!button || !bootcamp) return;

        var kuota = Number(bootcamp.kuota || 0);
        var peserta = Number(bootcamp.peserta || 0);
        var penuh = kuota > 0 && peserta >= kuota;

        if (registered) {
            button.textContent = "✔ Anda Sudah Terdaftar";
            button.disabled = true;
            if (secondary) {
                secondary.style.display = "inline-flex";
                secondary.textContent = "Lihat Kelas Saya";
                secondary.href = "kelas.html";
            }
            return;
        }

        button.disabled = false;
        button.textContent = penuh ? "Kuota Penuh" : "📝 Daftar Sekarang — " + UserCards.rupiah(UserCards.priceInfo(bootcamp).current);

        button.onclick = function () {
            if (penuh) return;
            UserApp.requireLogin().then(function () {
                window.location.href = "checkout.html?type=bootcamp&id=" + bootcamp.id;
            });
        };

        if (secondary) secondary.style.display = "none";
    }

    function checkRegistered(id) {
        return fetchJSON(RIWAYAT + "?_=" + Date.now())
            .then(function (result) {
                if (!result || result.success !== true || !Array.isArray(result.data)) return false;
                return result.data.some(function (trx) {
                    var status = String(trx.transaction_status || "").toLowerCase();
                    return (status === "settlement" || status === "capture") &&
                        String(trx.jenis_produk) === "bootcamp" &&
                        Number(trx.produk_id) === Number(id);
                });
            })
            .catch(function () { return false; });
    }

    function loadBootcamp() {
        var id = bootcampId();
        if (!id) {
            showError("Bootcamp tidak ditemukan", "ID bootcamp tidak valid atau tidak disertakan pada URL.");
            return;
        }

        fetchJSON(KATALOG + "?type=bootcamp&id=" + id)
            .then(function (result) {
                if (!result.success || !result.data) {
                    showError("Bootcamp tidak ditemukan", result.message || "Data bootcamp tidak ada di database.");
                    return;
                }
                bootcamp = result.data;
                render(bootcamp);
                return checkRegistered(id);
            })
            .then(function (isRegistered) {
                if (typeof isRegistered === "boolean") {
                    registered = isRegistered;
                    updateCTA();
                }
            })
            .catch(function (error) {
                showError("Gagal memuat bootcamp", error.message || "Terjadi kesalahan pada server.");
            });
    }

    function setupTabs() {
        document.querySelectorAll("[data-tab]").forEach(function (tab) {
            tab.addEventListener("click", function () {
                var target = tab.getAttribute("data-tab");
                document.querySelectorAll("[data-tab]").forEach(function (t) {
                    t.classList.toggle("is-active", t === tab);
                });
                document.querySelectorAll("[data-panel]").forEach(function (panel) {
                    panel.classList.toggle("is-active", panel.getAttribute("data-panel") === target);
                });
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        setupTabs();
        loadBootcamp();
    });
})();
