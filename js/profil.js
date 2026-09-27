/* =========================================================
   BELAJARYUK — PROFIL (js/profil.js)
   ---------------------------------------------------------
   Semua aksi memakai proses/profil.php (session user)
   ========================================================= */

(function () {
    "use strict";

    var API = "../proses/profil.php";

    function $(id) { return document.getElementById(id); }

    function send(payload, method) {
        var isGet = (method || "POST").toUpperCase() === "GET";
        var options = {
            method: isGet ? "GET" : (method || "POST"),
            credentials: "include",
            cache: "no-store",
            headers: { "Accept": "application/json" }
        };
        if (!isGet) {
            options.headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(payload || {});
        }

        return fetch(API, options).then(function (response) {
            if (response.status === 401) {
                window.location.href = "../proses/masuk.php?redirect=" + encodeURIComponent(window.location.href);
                return new Promise(function () { /* menunggu redirect */ });
            }
            return response.json().catch(function () {
                throw new Error("Respons server tidak valid (HTTP " + response.status + ").");
            });
        });
    }

    function fill(data) {
        if (!data) return;

        if ($("profileName")) $("profileName").textContent = data.nama || "-";
        if ($("profileEmail")) $("profileEmail").textContent = data.email || "-";
        if ($("profileId")) $("profileId").textContent = "#" + (data.id || "-");
        if ($("profileRole")) $("profileRole").textContent = String(data.role || "user").toUpperCase();
        if ($("profileStatus")) $("profileStatus").textContent = data.status ? String(data.status) : "aktif";

        var since = data.created_at || data.registered_at || null;
        if ($("profileSince")) {
            $("profileSince").textContent = since
                ? UserApp.formatDate(since, false)
                : "-";
        }

        if ($("profileAvatar")) $("profileAvatar").textContent = UserApp.initials(data.nama || "U");
        if ($("inputNama")) $("inputNama").value = data.nama || "";
        if ($("inputEmail")) $("inputEmail").value = data.email || "";
    }

    function loadProfile() {
        return send(null, "GET").then(function (result) {
            if (result && result.success && result.data) {
                fill(result.data);
            } else if (result && result.success && Array.isArray(result.data) && result.data.length) {
                fill(result.data[0]);
            } else {
                fill({
                    nama: UserApp.state.nama,
                    email: UserApp.state.email,
                    role: UserApp.state.role
                });
            }
            return null;
        }).catch(function (error) {
            console.error("PROFIL ERROR:", error);
            fill({
                nama: UserApp.state.nama,
                email: UserApp.state.email,
                role: UserApp.state.role
            });
        });
    }

    function bindProfileForm() {
        var form = $("profileForm");
        if (!form) return;

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            var button = $("saveProfileButton");
            var original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = "Menyimpan...";

            send({
                action: "update",
                nama: ($("inputNama").value || "").trim(),
                email: ($("inputEmail").value || "").trim()
            })
                .then(function (result) {
                    if (!result || result.success !== true) {
                        throw new Error((result && result.message) || "Gagal menyimpan profil.");
                    }
                    UserApp.toast(result.message || "Profil berhasil diperbarui.", "success");
                    if (result.data) fill(result.data);
                    return UserApp.loadStatus();
                })
                .then(function () { loadProfile(); })
                .catch(function (error) {
                    UserApp.toast(error.message || "Gagal menyimpan profil.", "error");
                })
                .finally(function () {
                    button.disabled = false;
                    button.innerHTML = original;
                });
        });
    }

    function bindPasswordForm() {
        var form = $("passwordForm");
        if (!form) return;

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            var lama = $("inputPasswordLama").value;
            var baru = $("inputPasswordBaru").value;
            var ulangi = $("inputPasswordConfirm").value;

            if (baru.length < 8) {
                UserApp.toast("Kata sandi baru minimal 8 karakter.", "error");
                return;
            }
            if (baru !== ulangi) {
                UserApp.toast("Konfirmasi kata sandi tidak sama.", "error");
                return;
            }

            var button = $("savePasswordButton");
            var original = button.innerHTML;
            button.disabled = true;
            button.innerHTML = "Memperbarui...";

            send({
                action: "password",
                password_lama: lama,
                password_baru: baru,
                password_ulang: ulangi
            })
                .then(function (result) {
                    if (!result || result.success !== true) {
                        throw new Error((result && result.message) || "Gagal memperbarui kata sandi.");
                    }
                    UserApp.toast(result.message || "Kata sandi berhasil diperbarui.", "success");
                    form.reset();
                })
                .catch(function (error) {
                    UserApp.toast(error.message || "Gagal memperbarui kata sandi.", "error");
                })
                .finally(function () {
                    button.disabled = false;
                    button.innerHTML = original;
                });
        });
    }

    function bindLogout() {
        var button = $("logoutButton");
        if (!button) return;
        button.addEventListener("click", function () { UserApp.logout(); });
    }

    document.addEventListener("DOMContentLoaded", function () {
        UserApp.loadStatus()
            .then(function (status) {
                if (!status || !status.loggedIn) {
                    window.location.href = "../proses/masuk.php?redirect=" + encodeURIComponent(window.location.href);
                    return new Promise(function () { /* menunggu redirect */ });
                }
                return loadProfile();
            })
            .then(function () {
                bindProfileForm();
                bindPasswordForm();
                bindLogout();
            })
            .catch(function () {
                bindProfileForm();
                bindPasswordForm();
                bindLogout();
            });
    });
})();
