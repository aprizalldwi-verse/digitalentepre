/* =========================================================
   BELAJARYUK — KATALOG E-LEARNING (js/elearning.js)
   ---------------------------------------------------------
   Data, pencarian & filter diambil dari database lewat
   proses/katalog.php (prepared statement).
   ========================================================= */

(function () {
    "use strict";

    var KATALOG = "../proses/katalog.php";

    var state = {
        q: "",
        kategori: "",
        sort: "terbaru",
        min_harga: "",
        max_harga: ""
    };

    function $(id) { return document.getElementById(id); }

    function readParams() {
        var params = new URLSearchParams(window.location.search);
        state.q = params.get("q") || "";
        state.kategori = params.get("kategori") || "";
        state.sort = params.get("sort") || "terbaru";
        state.min_harga = params.get("min_harga") || "";
        state.max_harga = params.get("max_harga") || "";
    }

    function writeParams() {
        var params = new URLSearchParams();
        if (state.q) params.set("q", state.q);
        if (state.kategori) params.set("kategori", state.kategori);
        if (state.sort && state.sort !== "terbaru") params.set("sort", state.sort);
        if (state.min_harga) params.set("min_harga", state.min_harga);
        if (state.max_harga) params.set("max_harga", state.max_harga);
        var query = params.toString();
        window.history.replaceState({}, "", window.location.pathname + (query ? "?" + query : ""));
    }

    function buildURL() {
        var params = new URLSearchParams({ type: "elearning" });
        if (state.q) params.set("q", state.q);
        if (state.kategori) params.set("kategori", state.kategori);
        if (state.sort) params.set("sort", state.sort);
        if (state.min_harga !== "") params.set("min_harga", state.min_harga);
        if (state.max_harga !== "") params.set("max_harga", state.max_harga);
        return KATALOG + "?" + params.toString() + "&_=" + Date.now();
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

    /* ---------------------------------------------------------
       KATEGORI (sidebar) — dari database
    --------------------------------------------------------- */
    function loadKategori() {
        var box = $("kategoriList");
        if (!box) return;

        fetchJSON(KATALOG + "?type=kategori")
            .then(function (result) {
                var list = (result.data && result.data.elearning) ? result.data.elearning : [];
                var total = list.reduce(function (sum, item) { return sum + Number(item.jumlah || 0); }, 0);

                var html =
                    '<button type="button" class="user-filter-item' + (state.kategori ? "" : " is-active") + '" data-kategori="">' +
                        "<span>Semua Kelas</span><span>" + total + "</span>" +
                    "</button>";

                html += list.map(function (item) {
                    return (
                        '<button type="button" class="user-filter-item' + (state.kategori === item.nama ? " is-active" : "") + '" data-kategori="' +
                        UserCards.esc(item.nama) + '">' +
                            "<span>" + UserCards.esc(item.nama) + "</span>" +
                            "<span>" + UserCards.esc(item.jumlah) + "</span>" +
                        "</button>"
                    );
                }).join("");

                box.innerHTML = html;

                box.querySelectorAll("[data-kategori]").forEach(function (btn) {
                    btn.addEventListener("click", function () {
                        state.kategori = btn.getAttribute("data-kategori") || "";
                        refresh();
                    });
                });
            })
            .catch(function () {
                box.innerHTML = '<div style="font-size:13px;color:#64748B">Kategori tidak tersedia.</div>';
            });
    }

    /* ---------------------------------------------------------
       DAFTAR COURSE
    --------------------------------------------------------- */
    function loadCourses() {
        var grid = $("courseGrid");
        if (!grid) return;
        grid.innerHTML = UserCards.loaderHTML("Memuat kelas...");

        fetchJSON(buildURL())
            .then(function (result) {
                var courses = result.data || [];

                var label = $("courseTotalLabel");
                if (label) {
                    var deskripsi = state.kategori ? " dalam kategori <strong>" + UserCards.esc(state.kategori) + "</strong>" : "";
                    var kata = state.q ? ' untuk pencarian "<strong>' + UserCards.esc(state.q) + '</strong>"' : "";
                    label.innerHTML = "Menampilkan <strong>" + courses.length + "</strong> kelas" + deskripsi + kata + ".";
                }

                if (!courses.length) {
                    grid.innerHTML = UserCards.emptyHTML(
                        "Kelas tidak ditemukan",
                        "Coba ubah kata kunci, kategori, atau rentang hargamu.",
                        '<button type="button" class="user-btn user-btn-primary" id="emptyReset">Reset Filter</button>'
                    );
                    var emptyReset = $("emptyReset");
                    if (emptyReset) emptyReset.addEventListener("click", resetAll);
                    return;
                }

                grid.innerHTML = courses.map(UserCards.courseCard).join("");
            })
            .catch(function (error) {
                grid.innerHTML = UserCards.errorHTML(error.message);
                var retry = $("userRetryBtn");
                if (retry) retry.addEventListener("click", loadCourses);
            });
    }

    function refresh() {
        writeParams();
        syncControls();
        loadKategori();
        loadCourses();
    }

    function resetAll() {
        state.q = "";
        state.kategori = "";
        state.sort = "terbaru";
        state.min_harga = "";
        state.max_harga = "";
        refresh();
    }

    function syncControls() {
        var search = $("searchCourse");
        if (search && search.value !== state.q) search.value = state.q;

        var sort = $("sortCourse");
        if (sort) sort.value = state.sort;

        var min = $("minHarga");
        if (min) min.value = state.min_harga;

        var max = $("maxHarga");
        if (max) max.value = state.max_harga;
    }

    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(null, args); }, wait);
        };
    }

    document.addEventListener("DOMContentLoaded", function () {
        readParams();
        syncControls();

        var search = $("searchCourse");
        if (search) {
            search.addEventListener("input", debounce(function () {
                state.q = search.value.trim();
                refresh();
            }, 400));
        }

        var sort = $("sortCourse");
        if (sort) {
            sort.addEventListener("change", function () {
                state.sort = sort.value;
                refresh();
            });
        }

        var applyHarga = $("applyHarga");
        if (applyHarga) {
            applyHarga.addEventListener("click", function () {
                state.min_harga = $("minHarga") ? $("minHarga").value : "";
                state.max_harga = $("maxHarga") ? $("maxHarga").value : "";
                refresh();
            });
        }

        var resetBtn = $("resetFilter");
        if (resetBtn) resetBtn.addEventListener("click", resetAll);

        loadKategori();
        loadCourses();
    });
})();
