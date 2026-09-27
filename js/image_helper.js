/* =====================================================
   IMAGE PATH HELPER
   -----------------------------------------------------
   Satu helper untuk semua gambar course & bootcamp.

   Path di database (products.gambar / bootcamp.gambar):
     assets/elearning/...
     assets/bootcamp/...

   Helper ini:
     1. Menormalkan path lama (legacy) ke struktur baru
     2. Menghitung prefix yang benar sesuai lokasi halaman
        (pages/ -> ../ , admin/ -> ../ , root -> "")
     3. Mengembalikan string kosong untuk path kosong

   Cara pakai:
     <img src="resolveImagePath(course.gambar)">
   ===================================================== */

(function (global) {
    "use strict";

    var cachedPrefix = null;

    /* -------------------------------------------------
       LOKASI FILE HELPER INI
    ------------------------------------------------- */

    function getHelperSource() {
        var script = document.currentScript;

        if (!script) {
            var nodes =
                document.querySelectorAll(
                    'script[src*="image_helper.js"]'
                );

            if (nodes.length) {
                script = nodes[nodes.length - 1];
            }
        }

        if (!script) {
            return "";
        }

        var src =
            script.getAttribute("src") ||
            script.src ||
            "";

        if (!src) {
            return "";
        }

        try {
            return new URL(
                src,
                document.baseURI
            ).href;
        } catch (error) {
            return src;
        }
    }

    /* -------------------------------------------------
       PREFIX DARI HALAMAN MENUJU ROOT PROJECT
       contoh: "../" untuk pages/ dan admin/
    ------------------------------------------------- */

    function getRootPrefix() {
        if (cachedPrefix !== null) {
            return cachedPrefix;
        }

        var marker = "/js/image_helper.js";
        var source = getHelperSource();
        var index = source.lastIndexOf(marker);

        if (index < 0) {
            cachedPrefix = "../";
            return cachedPrefix;
        }

        try {
            var documentURL = new URL(
                document.baseURI
            );

            var rootURL = new URL(
                source.substring(0, index + 1)
            );

            if (
                documentURL.origin !==
                rootURL.origin
            ) {
                cachedPrefix = "../";
                return cachedPrefix;
            }

            var documentParts =
                documentURL.pathname.split("/");

            documentParts.pop();

            documentParts =
                documentParts.filter(Boolean);

            var rootParts =
                rootURL.pathname.split("/");

            rootParts = rootParts.filter(Boolean);

            var shared = 0;

            while (
                shared < rootParts.length &&
                shared < documentParts.length &&
                rootParts[shared] ===
                    documentParts[shared]
            ) {
                shared++;
            }

            if (
                shared !== rootParts.length
            ) {
                cachedPrefix = "../";
                return cachedPrefix;
            }

            var prefix = "";
            var levels =
                documentParts.length - shared;

            for (var i = 0; i < levels; i++) {
                prefix += "../";
            }

            cachedPrefix = prefix;
            return cachedPrefix;

        } catch (error) {
            cachedPrefix = "../";
            return cachedPrefix;
        }
    }

    /* -------------------------------------------------
       NORMALISASI PATH LAMA -> STRUKTUR BARU
    ------------------------------------------------- */

    function normalizeAssetPath(path) {
        path = path.replace(
            /^(\.\.?\/)+/,
            ""
        );

        if (
            path.indexOf(
                "course/assets/images/bootcamp/"
            ) === 0
        ) {
            return (
                "assets/bootcamp/" +
                path.substring(
                    "course/assets/images/bootcamp/"
                        .length
                )
            );
        }

        if (
            path.indexOf(
                "assets/course/bootcamp/"
            ) === 0
        ) {
            return (
                "assets/bootcamp/" +
                path.substring(
                    "assets/course/bootcamp/".length
                )
            );
        }

        if (
            path.indexOf("assets/course/") === 0
        ) {
            return (
                "assets/elearning/" +
                path.substring(
                    "assets/course/".length
                )
            );
        }

        if (
            path.indexOf("course/assets/") === 0
        ) {
            return (
                "assets/elearning/" +
                path.substring(
                    "course/assets/".length
                )
            );
        }

        return path;
    }

    /* -------------------------------------------------
       RESOLVE IMAGE PATH
    ------------------------------------------------- */

    function resolveImagePath(path) {
        if (
            path === null ||
            path === undefined
        ) {
            return "";
        }

        path = String(path).trim();

        if (!path) {
            return "";
        }

        if (
            path.indexOf("http://") === 0 ||
            path.indexOf("https://") === 0 ||
            path.indexOf("//") === 0 ||
            path.indexOf("data:") === 0 ||
            path.indexOf("blob:") === 0
        ) {
            return path;
        }

        if (path.charAt(0) === "/") {
            return path;
        }

        path = normalizeAssetPath(path);

        return getRootPrefix() + path;
    }

    global.resolveImagePath = resolveImagePath;
    global.normalizeAssetPath = normalizeAssetPath;
    global.getAssetRootPrefix = getRootPrefix;
})(window);
