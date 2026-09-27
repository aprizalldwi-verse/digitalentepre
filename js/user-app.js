/* =========================================================
   BELAJARYUK — RUNTIME FRONTEND USER
   ---------------------------------------------------------
   - Status login diambil dari SESSION server
     (proses/status_login.php), bukan localStorage.
   - Menangani: navbar user, dropdown, sidebar mobile,
     search, guard halaman login, helper global.
   - File ini hanya dipakai halaman pages/*.html
   ========================================================= */

(function () {
    "use strict";

    var STATUS_URL = "../proses/status_login.php";
    var LOGOUT_URL = "../proses/logout.php";

    var state = {
        loggedIn: false,
        nama: "",
        email: "",
        role: ""
    };

    /* ---------------------------------------------------------
       HELPER
    --------------------------------------------------------- */
    function escapeHTML(value) {
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatRupiah(value) {
        var number = Number(value || 0);
        if (isNaN(number)) {
            number = 0;
        }
        return "Rp " + number.toLocaleString("id-ID", { maximumFractionDigits: 0 });
    }

    function formatDate(value, withTime) {
        if (!value) {
            return "-";
        }
        var date = new Date(String(value).replace(" ", "T"));
        if (isNaN(date.getTime())) {
            return value;
        }
        return date.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric",
            hour: withTime ? "2-digit" : undefined,
            minute: withTime ? "2-digit" : undefined
        });
    }

    function initials(name) {
        var parts = String(name || "?").trim().split(/\s+/);
        var first = parts[0] ? parts[0].charAt(0) : "?";
        var second = parts.length > 1 ? parts[parts.length - 1].charAt(0) : "";
        return (first + second).toUpperCase();
    }

    function imgSrc(path) {
        if (!path) {
            return "";
        }
        if (typeof window.resolveImagePath === "function") {
            return window.resolveImagePath(path);
        }
        var value = String(path);
        if (/^(https?:)?\/\//i.test(value) || value.indexOf("data:") === 0 || value.charAt(0) === "/") {
            return value;
        }
        return "../" + value.replace(/^(\.\.\/)+/, "");
    }

    function toast(message, type) {
        var wrap = document.querySelector(".user-toast-wrap");
        if (!wrap) {
            wrap = document.createElement("div");
            wrap.className = "user-toast-wrap";
            document.body.appendChild(wrap);
        }
        var item = document.createElement("div");
        item.className = "user-toast" + (type ? " is-" + type : "");
        item.innerHTML = (type === "error" ? "✕ " : "✓ ") + escapeHTML(message);
        wrap.appendChild(item);
        setTimeout(function () {
            item.style.opacity = "0";
            item.style.transition = "opacity .3s";
            setTimeout(function () { item.remove(); }, 320);
        }, 3600);
    }

    function queryParams() {
        return new URLSearchParams(window.location.search);
    }

    /* ---------------------------------------------------------
       STATUS LOGIN → RENDER NAVBAR
    --------------------------------------------------------- */
    function renderGuestActions() {
        var html =
            '<a href="../proses/masuk.php" class="user-btn user-btn-ghost">Login</a>' +
            '<a href="../proses/daftar.php" class="user-btn user-btn-primary">Daftar</a>' +
            '<button class="user-hamburger" id="userHamburger" type="button" aria-label="Buka menu">☰</button>';
        setHTML("userNavActions", html);
        setHTML("userSidebarAuth",
            '<a href="../proses/masuk.php" class="user-btn user-btn-primary user-btn-block">Login</a>' +
            '<a href="../proses/daftar.php" class="user-btn user-btn-outline user-btn-block">Daftar Gratis</a>'
        );
        setHTML("userSidebarUser", "");
    }

    function renderUserActions() {
        var adminHtml = "";
        if (state.role === "admin") {
            adminHtml = '<a href="../admin/dashboard.php">Panel Admin</a>';
        }

        var html =
            '<div class="user-nav-user">' +
                '<button class="user-nav-user-btn" id="userMenuBtn" type="button" aria-haspopup="true">' +
                    '<span class="user-avatar">' + escapeHTML(initials(state.nama)) + "</span>" +
                    '<span class="user-nav-user-name">' + escapeHTML(state.nama) + "</span>" +
                    '<span class="user-nav-user-caret">▾</span>' +
                "</button>" +
                '<div class="user-dropdown" id="userDropdown">' +
                    '<div class="user-dropdown-head">' +
                        "<strong>" + escapeHTML(state.nama) + "</strong>" +
                        "<span>" + escapeHTML(state.email) + "</span>" +
                    "</div>" +
                    (state.role === "admin" ? "" : '<a href="dashboard.html">📊 Dashboard</a>') +
                    '<a href="kelas.html">📚 Kelas Saya</a>' +
                    '<a href="riwayat.html">🧾 Riwayat Transaksi</a>' +
                    '<a href="profil.html">⚙️ Profil</a>' +
                    adminHtml +
                    '<button type="button" class="is-danger" id="userLogoutBtn">↪ Keluar</button>' +
                "</div>" +
            "</div>" +
            '<button class="user-hamburger" id="userHamburger" type="button" aria-label="Buka menu">☰</button>';

        setHTML("userNavActions", html);

        setHTML("userSidebarUser",
            '<div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">' +
                '<span class="user-avatar">' + escapeHTML(initials(state.nama)) + "</span>" +
                "<div><strong style='font-size:14px;color:#0F172A;display:block;word-break:break-word'>" +
                escapeHTML(state.nama) + "</strong><span style='font-size:12px;color:#64748B'>" +
                escapeHTML(state.email) + "</span></div>" +
            "</div>"
        );

        setHTML("userSidebarAuth",
            (state.role === "admin"
                ? '<a href="../admin/dashboard.php" class="user-btn user-btn-outline user-btn-block">Panel Admin</a>'
                : '<a href="dashboard.html" class="user-btn user-btn-primary user-btn-block">Dashboard</a>') +
            '<a href="kelas.html" class="user-btn user-btn-outline user-btn-block">Kelas Saya</a>' +
            '<a href="profil.html" class="user-btn user-btn-outline user-btn-block">Profil</a>' +
            '<button type="button" class="user-btn user-btn-ghost user-btn-block" id="userSidebarLogout">Keluar</button>'
        );
    }

    function setHTML(id, html) {
        var el = document.getElementById(id);
        if (el) {
            el.innerHTML = html;
        }
    }

    function bindActions() {
        var menuBtn = document.getElementById("userMenuBtn");
        var dropdown = document.getElementById("userDropdown");

        if (menuBtn && dropdown) {
            menuBtn.addEventListener("click", function (event) {
                event.stopPropagation();
                dropdown.classList.toggle("is-open");
            });
            document.addEventListener("click", function (event) {
                if (!dropdown.contains(event.target) && event.target !== menuBtn) {
                    dropdown.classList.remove("is-open");
                }
            });
        }

        var logoutBtn = document.getElementById("userLogoutBtn");
        if (logoutBtn) {
            logoutBtn.addEventListener("click", doLogout);
        }

        var sidebarLogout = document.getElementById("userSidebarLogout");
        if (sidebarLogout) {
            sidebarLogout.addEventListener("click", doLogout);
        }
    }

    function doLogout() {
        fetch(LOGOUT_URL, { credentials: "include", cache: "no-store" })
            .catch(function () { /* abaikan */ })
            .finally(function () {
                try {
                    localStorage.removeItem("belajaryuk_login");
                    localStorage.removeItem("belajaryuk_nama");
                    localStorage.removeItem("belajaryuk_email");
                } catch (e) { /* abaikan */ }
                window.location.href = "../proses/masuk.php?logout=1";
            });
    }

    function loadStatus() {
        return fetch(STATUS_URL + "?_=" + Date.now(), {
            credentials: "include",
            cache: "no-store",
            headers: { "Accept": "application/json" }
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                state.loggedIn = !!data.logged_in;
                state.nama = data.nama || "";
                state.email = data.email || "";
                state.role = data.role || "user";

                if (state.loggedIn) {
                    renderUserActions();
                    try {
                        localStorage.setItem("belajaryuk_login", "true");
                        localStorage.setItem("belajaryuk_nama", state.nama);
                        localStorage.setItem("belajaryuk_email", state.email);
                    } catch (e) { /* abaikan */ }
                } else {
                    renderGuestActions();
                    try {
                        localStorage.removeItem("belajaryuk_login");
                    } catch (e) { /* abaikan */ }
                }

                bindActions();
                return state;
            })
            .catch(function () {
                renderGuestActions();
                bindActions();
                return state;
            });
    }

    /* ---------------------------------------------------------
       GUARD HALAMAN YANG BUTUH LOGIN
    --------------------------------------------------------- */
    function requireLogin() {
        if (state.loggedIn) {
            return Promise.resolve(state);
        }
        return loadStatus().then(function (current) {
            if (current && current.loggedIn) {
                return current;
            }
            var target = encodeURIComponent(window.location.href);
            window.location.href = "../proses/masuk.php?redirect=" + target;
            return new Promise(function () { /* menunggu redirect */ });
        });
    }

    /* ---------------------------------------------------------
       SIDEBAR MOBILE + ACTIVE NAV
    --------------------------------------------------------- */
    function initSidebar() {
        var hamburger = document.getElementById("userHamburger");
        var sidebar = document.getElementById("userSidebar");
        var overlay = document.getElementById("userOverlay");

        function open() {
            if (sidebar) sidebar.classList.add("is-open");
            if (overlay) overlay.classList.add("is-open");
            document.body.style.overflow = "hidden";
        }
        function close() {
            if (sidebar) sidebar.classList.remove("is-open");
            if (overlay) overlay.classList.remove("is-open");
            document.body.style.overflow = "";
        }

        if (hamburger) hamburger.addEventListener("click", open);
        if (overlay) overlay.addEventListener("click", close);

        var closeBtn = document.getElementById("userSidebarClose");
        if (closeBtn) closeBtn.addEventListener("click", close);

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") close();
        });

        window.__closeUserSidebar = close;
    }

    function markActiveNav() {
        var path = window.location.pathname.split("/").pop() || "index.html";
        var links = document.querySelectorAll("[data-nav]");
        links.forEach(function (link) {
            var target = link.getAttribute("data-nav");
            var active =
                (target === "beranda" && (path === "index.html" || path === "")) ||
                (target === "elearning" && (path === "elearning.html" || path === "detail.html")) ||
                (target === "bootcamp" && (path === "bootcamp.html" || path === "detail_bootcamp.html")) ||
                (target === "kelas" && path === "kelas.html") ||
                (target === "riwayat" && path === "riwayat.html");
            if (active) {
                link.classList.add("is-active");
            } else {
                link.classList.remove("is-active");
            }
        });
    }

    /* ---------------------------------------------------------
       SEARCH GLOBAL (navbar & hero)
    --------------------------------------------------------- */
    function initSearch() {
        var forms = document.querySelectorAll("[data-user-search]");
        forms.forEach(function (form) {
            form.addEventListener("submit", function (event) {
                event.preventDefault();
                var input = form.querySelector("input[name='q']");
                var keyword = input ? input.value.trim() : "";
                var base = form.getAttribute("data-search-target") || "elearning.html";
                window.location.href = base + (keyword ? "?q=" + encodeURIComponent(keyword) : "");
            });
        });
    }

    /* ---------------------------------------------------------
       AMBIL PARAM URL & SIMPAN KOMPATibilitas LAMA
    --------------------------------------------------------- */
    function consumeLoginParams() {
        var params = queryParams();
        if (params.get("login") === "success") {
            var nama = params.get("nama");
            var email = params.get("email");
            try {
                if (nama) localStorage.setItem("belajaryuk_nama", nama);
                if (email) localStorage.setItem("belajaryuk_email", email);
                localStorage.setItem("belajaryuk_login", "true");
            } catch (e) { /* abaikan */ }
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }

    /* ---------------------------------------------------------
       BOOT
    --------------------------------------------------------- */
    document.addEventListener("DOMContentLoaded", function () {
        consumeLoginParams();
        initSidebar();
        initSearch();
        markActiveNav();
        loadStatus();
    });

    window.UserApp = {
        state: state,
        loadStatus: loadStatus,
        requireLogin: requireLogin,
        escapeHTML: escapeHTML,
        formatRupiah: formatRupiah,
        formatDate: formatDate,
        initials: initials,
        imgSrc: imgSrc,
        toast: toast,
        params: queryParams,
        logout: doLogout
    };
})();
