<?php

header("Content-Type: application/json; charset=UTF-8");

include "../config/koneksi.php";

/** @var mysqli $conn */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SESSION["role"] ?? null) !== "admin") {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Akses admin diperlukan."], JSON_UNESCAPED_UNICODE);
    exit;
}

mysqli_set_charset($conn, "utf8mb4");


/* =========================================================
   FUNCTION RESPONSE
========================================================= */

function responseJSON($success, $message = "", $data = null)
{
    echo json_encode(
        [
            "success" => $success,
            "message" => $message,
            "data" => $data
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/* =========================================================
   CEK DATABASE
========================================================= */

if (!$conn) {

    responseJSON(
        false,
        "Koneksi database gagal."
    );

}


/* =========================================================
   METHOD
========================================================= */

$requestMethod =
    strtoupper(
        $_SERVER["REQUEST_METHOD"]
    );


/* =========================================================
   GET
   - GET semua course
   - GET detail course berdasarkan ID
========================================================= */

if ($requestMethod === "GET") {

    $courseSelect = "SELECT p.id, p.nama_produk, p.kategori, p.subkategori, p.deskripsi, p.harga, p.harga_promo, p.promo_aktif, p.durasi, p.jadwal, p.benefit, p.gambar, p.status, p.created_at,
        COALESCE(t.transaction_count,0) AS transaction_count,
        COALESCE(t.participant_count,0) AS participant_count,
        COALESCE(t.revenue,0) AS revenue,
        NULL AS instructor
        FROM products p
        LEFT JOIN (
            SELECT produk_id, COUNT(*) AS transaction_count,
                COUNT(DISTINCT CASE WHEN transaction_status='settlement' AND user_id IS NOT NULL THEN user_id END) AS participant_count,
                SUM(CASE WHEN transaction_status='settlement' THEN gross_amount ELSE 0 END) AS revenue
            FROM transaksi WHERE jenis_produk='elearning' GROUP BY produk_id
        ) t ON t.produk_id=p.id";


    /* =====================================================
       DETAIL COURSE
    ===================================================== */

    if (
        isset($_GET["id"]) &&
        $_GET["id"] !== ""
    ) {

        $id =
            intval(
                $_GET["id"]
            );


        $stmt =
            mysqli_prepare(
                $conn,
                $courseSelect . " WHERE p.id = ? AND p.kategori = 'E-Learning' LIMIT 1"
            );


        if (!$stmt) {

            responseJSON(
                false,
                "Query detail course gagal: " .
                mysqli_error($conn)
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id
        );


        mysqli_stmt_execute(
            $stmt
        );


        $result =
            mysqli_stmt_get_result(
                $stmt
            );


        if (
            !$result ||
            mysqli_num_rows($result) === 0
        ) {

            responseJSON(
                false,
                "Course tidak ditemukan."
            );

        }


        $course =
            mysqli_fetch_assoc(
                $result
            );


        mysqli_stmt_close(
            $stmt
        );


        responseJSON(
            true,
            "Data course berhasil diambil.",
            $course
        );

    }


    /* =====================================================
       SEMUA COURSE E-LEARNING
    ===================================================== */

    $query = $courseSelect . " WHERE p.kategori = 'E-Learning' ORDER BY p.id DESC";


    $result =
        mysqli_query(
            $conn,
            $query
        );


    if (!$result) {

        responseJSON(
            false,
            "Gagal mengambil data course: " .
            mysqli_error($conn)
        );

    }


    $courses = [];


    while (
        $row =
        mysqli_fetch_assoc($result)
    ) {

        $courses[] =
            $row;

    }


    responseJSON(
        true,
        "Data course berhasil diambil.",
        $courses
    );

}


/* =========================================================
   POST
   - Tambah
   - Edit menggunakan _method=PUT
   - Hapus menggunakan _method=DELETE
========================================================= */

if ($requestMethod === "POST") {


    $method =
        strtoupper(
            $_POST["_method"] ?? "POST"
        );


    /* =====================================================
       DELETE
    ===================================================== */

    if ($method === "DELETE") {


        $id =
            intval(
                $_POST["id"] ?? 0
            );


        if ($id <= 0) {

            responseJSON(
                false,
                "ID course tidak valid."
            );

        }


        /* =================================================
           AMBIL GAMBAR LAMA
        ================================================= */

        $stmtImage =
            mysqli_prepare(
                $conn,
                "SELECT gambar
                 FROM products
                 WHERE id = ?
                 LIMIT 1"
            );


        if (!$stmtImage) {

            responseJSON(
                false,
                "Query gambar gagal: " .
                mysqli_error($conn)
            );

        }


        mysqli_stmt_bind_param(
            $stmtImage,
            "i",
            $id
        );


        mysqli_stmt_execute(
            $stmtImage
        );


        $resultImage =
            mysqli_stmt_get_result(
                $stmtImage
            );


        $oldImage = "";


        if (
            $resultImage &&
            mysqli_num_rows($resultImage) > 0
        ) {

            $imageData =
                mysqli_fetch_assoc(
                    $resultImage
                );


            $oldImage =
                $imageData["gambar"] ?? "";

        }


        mysqli_stmt_close(
            $stmtImage
        );


        /* =================================================
           HAPUS DATABASE
        ================================================= */

        $stmt =
            mysqli_prepare(
                $conn,
                "DELETE FROM products
                 WHERE id = ?
                 AND kategori = 'E-Learning'"
            );


        if (!$stmt) {

            responseJSON(
                false,
                "Query delete gagal: " .
                mysqli_error($conn)
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $id
        );


        if (
            !mysqli_stmt_execute($stmt)
        ) {

            responseJSON(
                false,
                "Gagal menghapus course: " .
                mysqli_stmt_error($stmt)
            );

        }


        mysqli_stmt_close(
            $stmt
        );


        /* =================================================
           HAPUS FILE GAMBAR LAMA
        ================================================= */

        if (!empty($oldImage)) {

            $oldImagePath =
                realpath(
                    __DIR__ .
                    "/../" .
                    $oldImage
                );


            if (
                $oldImagePath &&
                is_file($oldImagePath)
            ) {

                @unlink(
                    $oldImagePath
                );

            }

        }


        responseJSON(
            true,
            "Course berhasil dihapus."
        );

    }


    /* =====================================================
       UPDATE
    ===================================================== */

    if ($method === "PUT") {


        $id =
            intval(
                $_POST["id"] ?? 0
            );


        $nama_produk =
            trim(
                $_POST["nama_produk"] ?? ""
            );


        $subkategori =
            trim(
                $_POST["subkategori"] ?? ""
            );


        $deskripsi =
            trim(
                $_POST["deskripsi"] ?? ""
            );


        $harga =
            floatval(
                $_POST["harga"] ?? 0
            );


        $durasi =
            trim(
                $_POST["durasi"] ?? ""
            );


        $jadwal =
            trim(
                $_POST["jadwal"] ?? ""
            );


        $benefit =
            trim(
                $_POST["benefit"] ?? ""
            );


        /* =================================================
           PROMO
        ================================================= */

        $promo_aktif =
            isset($_POST["promo_aktif"]) &&
            (
                $_POST["promo_aktif"] === "1" ||
                $_POST["promo_aktif"] === 1 ||
                $_POST["promo_aktif"] === true
            )
                ? 1
                : 0;


        $harga_promo = null;


        if ($promo_aktif === 1) {

            /*
             * Cek apakah field harga promo memang dikirim.
             * Nilai 0 tetap dianggap valid.
             */

            if (
                isset($_POST["harga_promo"]) &&
                $_POST["harga_promo"] !== ""
            ) {

                $harga_promo =
                    floatval(
                        $_POST["harga_promo"]
                    );

            } else {

                responseJSON(
                    false,
                    "Harga promo wajib diisi."
                );

            }


            /* =============================================
               VALIDASI HARGA PROMO
            ============================================= */

            if (
                $harga_promo < 0
            ) {

                responseJSON(
                    false,
                    "Harga promo tidak boleh kurang dari 0."
                );

            }


            /*
             * Harga promo 0 = GRATIS
             *
             * Jadi:
             * harga normal 100000
             * harga promo 0
             * promo aktif
             *
             * TETAP VALID.
             */

            if (
                $harga_promo > 0 &&
                $harga > 0 &&
                $harga_promo >= $harga
            ) {

                responseJSON(
                    false,
                    "Harga promo harus lebih kecil dari harga normal."
                );

            }

        } else {

            $harga_promo = null;

        }


        /* =================================================
           VALIDASI
        ================================================= */

        if ($id <= 0) {

            responseJSON(
                false,
                "ID course tidak valid."
            );

        }


        if ($nama_produk === "") {

            responseJSON(
                false,
                "Nama course wajib diisi."
            );

        }


        if ($subkategori === "") {

            responseJSON(
                false,
                "Variasi modul wajib dipilih."
            );

        }


        if ($harga < 0) {

            responseJSON(
                false,
                "Harga course tidak valid."
            );

        }


        /* =================================================
           GAMBAR BARU
        ================================================= */

        $gambarBaru = null;


        if (
            isset($_FILES["gambar"]) &&
            $_FILES["gambar"]["error"] === UPLOAD_ERR_OK
        ) {


            $file =
                $_FILES["gambar"];


            $allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];


            $mimeType =
                mime_content_type(
                    $file["tmp_name"]
                );


            if (
                !in_array(
                    $mimeType,
                    $allowedTypes,
                    true
                )
            ) {

                responseJSON(
                    false,
                    "Format gambar harus JPG, JPEG, PNG, atau WEBP."
                );

            }


            $extension =
                strtolower(
                    pathinfo(
                        $file["name"],
                        PATHINFO_EXTENSION
                    )
                );


            $newFileName =
                "course_" .
                time() .
                "_" .
                bin2hex(
                    random_bytes(4)
                ) .
                "." .
                $extension;


            $uploadDir =
                __DIR__ .
                "/../assets/elearning/";


            if (
                !is_dir($uploadDir)
            ) {

                mkdir(
                    $uploadDir,
                    0777,
                    true
                );

            }


            $targetPath =
                $uploadDir .
                $newFileName;


            if (
                !move_uploaded_file(
                    $file["tmp_name"],
                    $targetPath
                )
            ) {

                responseJSON(
                    false,
                    "Gagal mengupload gambar."
                );

            }


            $gambarBaru =
                "assets/elearning/" .
                $newFileName;

        }


        /* =================================================
           UPDATE DENGAN GAMBAR BARU
        ================================================= */

        if ($gambarBaru !== null) {


            /* =============================================
               AMBIL GAMBAR LAMA
            ============================================= */

            $stmtOld =
                mysqli_prepare(
                    $conn,
                    "SELECT gambar
                     FROM products
                     WHERE id = ?
                     LIMIT 1"
                );


            if (!$stmtOld) {

                responseJSON(
                    false,
                    "Query gambar lama gagal: " .
                    mysqli_error($conn)
                );

            }


            mysqli_stmt_bind_param(
                $stmtOld,
                "i",
                $id
            );


            mysqli_stmt_execute(
                $stmtOld
            );


            $oldResult =
                mysqli_stmt_get_result(
                    $stmtOld
                );


            $oldImage = "";


            if (
                $oldResult &&
                mysqli_num_rows($oldResult) > 0
            ) {

                $oldData =
                    mysqli_fetch_assoc(
                        $oldResult
                    );


                $oldImage =
                    $oldData["gambar"] ?? "";

            }


            mysqli_stmt_close(
                $stmtOld
            );


            /* =============================================
               UPDATE
            ============================================= */

            $stmt =
                mysqli_prepare(
                    $conn,
                    "UPDATE products
                     SET
                        nama_produk = ?,
                        kategori = 'E-Learning',
                        subkategori = ?,
                        deskripsi = ?,
                        harga = ?,
                        harga_promo = ?,
                        promo_aktif = ?,
                        durasi = ?,
                        jadwal = ?,
                        benefit = ?,
                        gambar = ?
                     WHERE id = ?"
                );


            if (!$stmt) {

                responseJSON(
                    false,
                    "Query update gagal: " .
                    mysqli_error($conn)
                );

            }


            mysqli_stmt_bind_param(
                $stmt,
                "sssddissssi",
                $nama_produk,
                $subkategori,
                $deskripsi,
                $harga,
                $harga_promo,
                $promo_aktif,
                $durasi,
                $jadwal,
                $benefit,
                $gambarBaru,
                $id
            );


            if (
                !mysqli_stmt_execute($stmt)
            ) {

                responseJSON(
                    false,
                    "Gagal update course: " .
                    mysqli_stmt_error($stmt)
                );

            }


            mysqli_stmt_close(
                $stmt
            );


            /* =============================================
               HAPUS GAMBAR LAMA
            ============================================= */

            if (!empty($oldImage)) {

                $oldImagePath =
                    realpath(
                        __DIR__ .
                        "/../" .
                        $oldImage
                    );


                if (
                    $oldImagePath &&
                    is_file($oldImagePath)
                ) {

                    @unlink(
                        $oldImagePath
                    );

                }

            }

        } else {


            /* =================================================
               UPDATE TANPA GAMBAR
            ================================================= */

            $stmt =
                mysqli_prepare(
                    $conn,
                    "UPDATE products
                     SET
                        nama_produk = ?,
                        kategori = 'E-Learning',
                        subkategori = ?,
                        deskripsi = ?,
                        harga = ?,
                        harga_promo = ?,
                        promo_aktif = ?,
                        durasi = ?,
                        jadwal = ?,
                        benefit = ?
                     WHERE id = ?"
                );


            if (!$stmt) {

                responseJSON(
                    false,
                    "Query update gagal: " .
                    mysqli_error($conn)
                );

            }


            mysqli_stmt_bind_param(
                $stmt,
                "sssddisssi",
                $nama_produk,
                $subkategori,
                $deskripsi,
                $harga,
                $harga_promo,
                $promo_aktif,
                $durasi,
                $jadwal,
                $benefit,
                $id
            );


            if (
                !mysqli_stmt_execute($stmt)
            ) {

                responseJSON(
                    false,
                    "Gagal update course: " .
                    mysqli_stmt_error($stmt)
                );

            }


            mysqli_stmt_close(
                $stmt
            );

        }


        responseJSON(
            true,
            "Course berhasil diperbarui."
        );

    }


    /* =====================================================
       CREATE COURSE
    ===================================================== */

    $nama_produk =
        trim(
            $_POST["nama_produk"] ?? ""
        );


    $subkategori =
        trim(
            $_POST["subkategori"] ?? ""
        );


    $deskripsi =
        trim(
            $_POST["deskripsi"] ?? ""
        );


    $harga =
        floatval(
            $_POST["harga"] ?? 0
        );


    $durasi =
        trim(
            $_POST["durasi"] ?? ""
        );


    $jadwal =
        trim(
            $_POST["jadwal"] ?? ""
        );


    $benefit =
        trim(
            $_POST["benefit"] ?? ""
        );


    /* =================================================
       PROMO
    ================================================= */

    $promo_aktif =
        isset($_POST["promo_aktif"]) &&
        (
            $_POST["promo_aktif"] === "1" ||
            $_POST["promo_aktif"] === 1 ||
            $_POST["promo_aktif"] === true
        )
            ? 1
            : 0;


    $harga_promo = null;


    if ($promo_aktif === 1) {

        /*
         * Nilai 0 tetap diperbolehkan.
         */

        if (
            isset($_POST["harga_promo"]) &&
            $_POST["harga_promo"] !== ""
        ) {

            $harga_promo =
                floatval(
                    $_POST["harga_promo"]
                );

        } else {

            responseJSON(
                false,
                "Harga promo wajib diisi."
            );

        }


        /* =============================================
           VALIDASI
        ============================================= */

        if (
            $harga_promo < 0
        ) {

            responseJSON(
                false,
                "Harga promo tidak boleh kurang dari 0."
            );

        }


        /*
         * Harga promo 0 = GRATIS.
         */

        if (
            $harga_promo > 0 &&
            $harga > 0 &&
            $harga_promo >= $harga
        ) {

            responseJSON(
                false,
                "Harga promo harus lebih kecil dari harga normal."
            );

        }

    } else {

        $harga_promo = null;

    }


    /* =================================================
       VALIDASI
    ================================================= */

    if ($nama_produk === "") {

        responseJSON(
            false,
            "Nama course wajib diisi."
        );

    }


    if ($subkategori === "") {

        responseJSON(
            false,
            "Variasi modul wajib dipilih."
        );

    }


    if ($harga < 0) {

        responseJSON(
            false,
            "Harga course tidak valid."
        );

    }


    /* =================================================
       UPLOAD GAMBAR
    ================================================= */

    $gambar = "";


    if (
        isset($_FILES["gambar"]) &&
        $_FILES["gambar"]["error"] === UPLOAD_ERR_OK
    ) {


        $file =
            $_FILES["gambar"];


        $allowedTypes = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];


        $mimeType =
            mime_content_type(
                $file["tmp_name"]
            );


        if (
            !in_array(
                $mimeType,
                $allowedTypes,
                true
            )
        ) {

            responseJSON(
                false,
                "Format gambar harus JPG, JPEG, PNG, atau WEBP."
            );

        }


        $extension =
            strtolower(
                pathinfo(
                    $file["name"],
                    PATHINFO_EXTENSION
                )
            );


        $fileName =
            "course_" .
            time() .
            "_" .
            bin2hex(
                random_bytes(4)
            ) .
            "." .
            $extension;


        $uploadDir =
            __DIR__ .
            "/../assets/elearning/";


        if (
            !is_dir($uploadDir)
        ) {

            mkdir(
                $uploadDir,
                0777,
                true
            );

        }


        $targetPath =
            $uploadDir .
            $fileName;


        if (
            !move_uploaded_file(
                $file["tmp_name"],
                $targetPath
            )
        ) {

            responseJSON(
                false,
                "Gagal mengupload gambar."
            );

        }


        $gambar =
            "assets/elearning/" .
            $fileName;

    }


    /* =================================================
       INSERT
    ================================================= */

    $stmt =
        mysqli_prepare(
            $conn,
            "INSERT INTO products
            (
                nama_produk,
                kategori,
                subkategori,
                deskripsi,
                harga,
                harga_promo,
                promo_aktif,
                durasi,
                jadwal,
                benefit,
                gambar
            )
            VALUES
            (
                ?,
                'E-Learning',
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )"
        );


    if (!$stmt) {

        responseJSON(
            false,
            "Query tambah course gagal: " .
            mysqli_error($conn)
        );

    }


    mysqli_stmt_bind_param(
        $stmt,
        "sssddissss",
        $nama_produk,
        $subkategori,
        $deskripsi,
        $harga,
        $harga_promo,
        $promo_aktif,
        $durasi,
        $jadwal,
        $benefit,
        $gambar
    );


    if (
        !mysqli_stmt_execute($stmt)
    ) {

        responseJSON(
            false,
            "Gagal menambahkan course: " .
            mysqli_stmt_error($stmt)
        );

    }


    $newId =
        mysqli_insert_id($conn);


    mysqli_stmt_close(
        $stmt
    );


    responseJSON(
        true,
        "Course berhasil ditambahkan.",
        [
            "id" => $newId
        ]
    );

}


/* =========================================================
   METHOD TIDAK DIDUKUNG
========================================================= */

responseJSON(
    false,
    "Method request tidak didukung."
);

?>