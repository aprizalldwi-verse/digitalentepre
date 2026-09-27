/* =========================================================
   BELAJARYUK — DETAIL COURSE (js/detail.js)
   ---------------------------------------------------------
   Data course : proses/katalog.php?type=elearning&id=...
   Status beli : proses/riwayat.php (session user)
   Aksi beli   : pages/checkout.html (memakai create_payment.php)
   ========================================================= */

(function () {
    "use strict";

    var KATALOG = "../proses/katalog.php";
    var RIWAYAT = "../proses/riwayat.php";

    var course = null;
    var purchased = false;

    function $(id) { return document.getElementById(id); }

    function courseId() {
        var params = new URLSearchParams(window.location.search);
        var id = parseInt(params.get("id") || "0", 10);
        return isNaN(id) ? 0 : id;
    }

    function fetchJSON(url, options) {
        return fetch(url, Object.assign({ credentials: "include", cache: "no-store", headers: { "Accept": "application/json" } }, options || {}))
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
        var loading = $("loadingDetail");
        if (loading) loading.style.display = "none";
        var content = $("detailContent");
        if (content) content.style.display = "none";
        var errorBox = $("errorDetail");
        if (errorBox) errorBox.style.display = "block";
        if ($("errorDetailTitle")) $("errorDetailTitle").textContent = title;
        if ($("errorDetailMessage")) $("errorDetailMessage").textContent = message;
    }

    function setText(id, value) {
        var el = $(id);
        if (el) el.textContent = value;
    }

    function render(course) {
        var nama = course.nama_produk || "Kelas";
        var benefits = course.benefit_list && course.benefit_list.length
            ? course.benefit_list
            : String(course.benefit || "").split("|").map(function (s) { return s.trim(); }).filter(Boolean);

        document.title = nama + " — BelajarYuk";

        var media = $("courseImage");
        if (media) {
            var path = UserCards.src(course.gambar);
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

        setText("breadcrumbName", nama);
        setText("courseName", nama);
        setText("courseCategory", course.kategori || "E-Learning");
        setText("courseSubcategory", course.subkategori || "-");
        setText("courseDescription", course.deskripsi || "Deskripsi belum tersedia.");
        setText("aboutCourse", course.deskripsi || "Deskripsi belum tersedia.");

        var peserta = Number(course.peserta || 0);

        var meta = $("courseMeta");
        if (meta) {
            var items = [];
            if (course.subkategori) items.push("<span>🏷 " + UserCards.esc(course.subkategori) + "</span>");
            if (course.durasi) items.push("<span>🕒 " + UserCards.esc(course.durasi) + "</span>");
            if (course.jadwal) items.push("<span>🗓 " + UserCards.esc(course.jadwal) + "</span>");
            items.push("<span>👥 " + (peserta > 0 ? UserCards.esc(peserta) + " peserta" : "Kelas baru") + "</span>");
            meta.innerHTML = items.join("");
        }

        setText("infoKategori", course.subkategori || course.kategori || "-");
        setText("infoDurasi", course.durasi || "-");
        setText("infoJadwal", course.jadwal || "-");
        setText("infoPeserta", peserta > 0 ? peserta + " orang" : "Baru");

        setText("summarySub", course.subkategori || "-");
        setText("summaryDurasi", course.durasi || "-");
        setText("summaryJadwal", course.jadwal || "-");
        setText("summaryPeserta", peserta > 0 ? peserta + " orang" : "Baru");
        setText("summaryHarga", UserCards.rupiah(UserCards.priceInfo(course).current));

        var priceBox = $("coursePrice");
        if (priceBox) {
            var info = UserCards.priceInfo(course);
            priceBox.innerHTML =
                (info.showOld ? "<s>" + UserCards.esc(UserCards.rupiah(info.normal)) + "</s>" : "") +
                "<strong>" + UserCards.esc(UserCards.rupiah(info.current)) + "</strong>";
        }

        var benefitGrid = $("benefitGrid");
        if (benefitGrid) {
            benefitGrid.innerHTML = benefits.length
                ? benefits.map(function (item) {
                    return '<div class="user-benefit-item"><span class="user-check">✓</span>' + UserCards.esc(item) + "</div>";
                }).join("")
                : '<p style="color:#64748B">Materi belum tersedia.</p>';
        }

        var curriculum = $("curriculumList");
        if (curriculum) {
            curriculum.innerHTML = benefits.length
                ? benefits.map(function (item) {
                    return "<li><span>" + UserCards.esc(item) + "</span></li>";
                }).join("")
                : '<li><span>Materi akan diumumkan.</span></li>';
        }

        var loading = $("loadingDetail");
        if (loading) loading.style.display = "none";
        var content = $("detailContent");
        if (content) content.style.display = "block";

        updateCTA();
    }

    /* ---------------------------------------------------------
       CTA: Beli Sekarang / Mulai Belajar
    --------------------------------------------------------- */
    function updateCTA() {
        var button = $("registerButton");
        var secondary = $("secondaryButton");
        if (!button || !course) return;

        if (purchased) {
            button.textContent = "▶ Mulai Belajar";
            button.onclick = function () {
                window.location.href = "kelas.html?id=" + course.id;
            };
            if (secondary) {
                secondary.style.display = "inline-flex";
                secondary.textContent = "Buka Kelas Saya";
                secondary.href = "kelas.html";
            }
            return;
        }

        button.textContent = "🛒 Beli Sekarang — " + UserCards.rupiah(UserCards.priceInfo(course).current);
        button.onclick = function () {
            UserApp.requireLogin().then(function () {
                window.location.href = "checkout.html?type=elearning&id=" + course.id;
            });
        };

        if (secondary) secondary.style.display = "none";
    }

    /* ---------------------------------------------------------
       CEK STATUS PEMBELIAN (database transaksi)
    --------------------------------------------------------- */
    function checkPurchased(id) {
        return fetchJSON(RIWAYAT + "?_=" + Date.now())
            .then(function (result) {
                if (!result || result.success !== true || !Array.isArray(result.data)) {
                    return false;
                }
                return result.data.some(function (trx) {
                    var status = String(trx.transaction_status || "").toLowerCase();
                    var sudahBayar = status === "settlement" || status === "capture";
                    return sudahBayar &&
                        String(trx.jenis_produk) === "elearning" &&
                        Number(trx.produk_id) === Number(id);
                });
            })
            .catch(function () { return false; });
    }

    function loadCourse() {
        var id = courseId();
        if (!id) {
            showError("Kelas tidak ditemukan", "ID kelas tidak valid atau tidak disertakan pada URL.");
            return;
        }

        fetchJSON(KATALOG + "?type=elearning&id=" + id)
            .then(function (result) {
                if (!result.success || !result.data) {
                    showError("Kelas tidak ditemukan", result.message || "Data kelas tidak ada di database.");
                    return;
                }
                course = result.data;
                render(course);
                return checkPurchased(id);
            })
            .then(function (isOwned) {
                if (typeof isOwned === "boolean") {
                    purchased = isOwned;
                    updateCTA();
                }
            })
            .catch(function (error) {
                showError("Gagal memuat kelas", error.message || "Terjadi kesalahan pada server.");
            });
    }

    function setupTabs() {
        var tabs = document.querySelectorAll("[data-tab]");
        tabs.forEach(function (tab) {
            tab.addEventListener("click", function () {
                var target = tab.getAttribute("data-tab");
                tabs.forEach(function (t) { t.classList.toggle("is-active", t === tab); });
                document.querySelectorAll("[data-panel]").forEach(function (panel) {
                    panel.classList.toggle("is-active", panel.getAttribute("data-panel") === target);
                });
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        setupTabs();
        loadCourse();
    });
})();
