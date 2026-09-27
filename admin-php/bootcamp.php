<?php

header("Content-Type: application/json; charset=UTF-8");

include_once __DIR__ . "/../config/koneksi.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (($_SESSION["role"] ?? null) !== "admin") {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Akses admin diperlukan."], JSON_UNESCAPED_UNICODE);
    exit;
}


/* =================================================
   FUNCTION RESPONSE JSON
================================================= */

function responseJSON(
    $success,
    $message,
    $data = null
) {

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


/* =================================================
   VALIDASI DATABASE
================================================= */

if (
    !$conn
) {

    responseJSON(
        false,
        "Koneksi database gagal."
    );

}


/* =================================================
   METHOD
================================================= */

$method =
    $_SERVER["REQUEST_METHOD"];


/* =================================================
   ACTION
================================================= */

$action =
    $_GET["action"] ?? null;


/* =================================================
   SUPPORT _method
   DARI JAVASCRIPT ADMIN
================================================= */

$requestMethod =
    $_POST["_method"] ??
    $_REQUEST["_method"] ??
    null;


/*
   JavaScript mengirim:

   POST + _method=PUT
   POST + _method=DELETE

   Jadi ubah menjadi action internal.
*/

if (
    $method === "POST" &&
    !empty($requestMethod)
) {

    $requestMethod =
        strtoupper(
            trim(
                $requestMethod
            )
        );


    if (
        $requestMethod === "PUT"
    ) {

        $action =
            "PUT";

    } elseif (
        $requestMethod === "DELETE"
    ) {

        $action =
            "DELETE";

    }

}


/* =================================================
   SUPPORT action=save
================================================= */

if (
    $action === "save"
) {

    if (
        !empty($_POST["id"])
    ) {

        $action =
            "PUT";

    } else {

        $action =
            "INSERT";

    }

}


/* =================================================
   SUPPORT action=delete
================================================= */

if (
    $action === "delete"
) {

    $action =
        "DELETE";

}


/* =================================================
   FOLDER UPLOAD GAMBAR
================================================= */

$uploadDir =
    __DIR__ .
    "/../assets/bootcamp/";

$uploadPath =
    "assets/bootcamp/";


if (
    !is_dir(
        $uploadDir
    )
) {

    if (
        !mkdir(
            $uploadDir,
            0777,
            true
        )
    ) {

        responseJSON(
            false,
            "Folder upload gambar gagal dibuat."
        );

    }

}


/* =================================================
   FUNCTION UPLOAD GAMBAR
================================================= */

function uploadImage(
    $uploadDir,
    $uploadPath
) {

    if (
        !isset(
            $_FILES["gambar"]
        )
    ) {

        return null;

    }


    if (
        $_FILES["gambar"]["error"] ===
        UPLOAD_ERR_NO_FILE
    ) {

        return null;

    }


    if (
        $_FILES["gambar"]["error"] !==
        UPLOAD_ERR_OK
    ) {

        responseJSON(
            false,
            "Gagal mengunggah gambar."
        );

    }


    $fileTmp =
        $_FILES["gambar"]["tmp_name"];


    $fileName =
        $_FILES["gambar"]["name"];


    $fileSize =
        $_FILES["gambar"]["size"];


    /* =================================================
       MAX 5 MB
    ================================================= */

    if (
        $fileSize >
        5 * 1024 * 1024
    ) {

        responseJSON(
            false,
            "Ukuran gambar maksimal 5 MB."
        );

    }


    /* =================================================
       CEK MIME
    ================================================= */

    $mimeType =
        mime_content_type(
            $fileTmp
        );


    $allowedTypes = [

        "image/jpeg",
        "image/png",
        "image/webp"

    ];


    if (
        !in_array(
            $mimeType,
            $allowedTypes,
            true
        )
    ) {

        responseJSON(
            false,
            "Format gambar harus JPG, PNG, atau WEBP."
        );

    }


    /* =================================================
       EXTENSION
    ================================================= */

    $extension =
        strtolower(
            pathinfo(
                $fileName,
                PATHINFO_EXTENSION
            )
        );


    $allowedExtensions = [

        "jpg",
        "jpeg",
        "png",
        "webp"

    ];


    if (
        !in_array(
            $extension,
            $allowedExtensions,
            true
        )
    ) {

        responseJSON(
            false,
            "Extension gambar tidak valid."
        );

    }


    /* =================================================
       NAMA BARU
    ================================================= */

    $newFileName =
        uniqid(
            "bootcamp_",
            true
        ) .
        "." .
        $extension;


    $destination =
        $uploadDir .
        $newFileName;


    /* =================================================
       PINDAHKAN FILE
    ================================================= */

    if (
        !move_uploaded_file(
            $fileTmp,
            $destination
        )
    ) {

        responseJSON(
            false,
            "Gagal menyimpan gambar."
        );

    }


    return
        $uploadPath .
        $newFileName;

}


/* =================================================
   GET DATA BOOTCAMP
================================================= */

if (
    $method === "GET"
) {

    $bootcampSelect = "SELECT b.*,
        COALESCE(t.transaction_count,0) AS transaction_count,
        COALESCE(t.participant_count,0) AS participant_count,
        COALESCE(t.revenue,0) AS revenue,
        CASE WHEN b.kuota <= 0 THEN 'Penuh'
             WHEN CURDATE() > b.tanggal_berakhir THEN 'Selesai'
             WHEN CURDATE() < b.tanggal_mulai THEN 'Akan Datang'
             ELSE 'Berjalan' END AS status_program
        FROM bootcamp b
        LEFT JOIN (
            SELECT produk_id, COUNT(*) AS transaction_count,
                COUNT(DISTINCT CASE WHEN transaction_status='settlement' AND user_id IS NOT NULL THEN user_id END) AS participant_count,
                SUM(CASE WHEN transaction_status='settlement' THEN gross_amount ELSE 0 END) AS revenue
            FROM transaksi WHERE jenis_produk='bootcamp' GROUP BY produk_id
        ) t ON t.produk_id=b.id";

    $id =
        $_GET["id"] ?? null;


    /* =================================================
       GET DETAIL
    ================================================= */

    if (
        $id !== null &&
        $id !== ""
    ) {

        $id =
            (int) $id;


        $stmt =
            mysqli_prepare(
                $conn,
                $bootcampSelect . " WHERE b.id = ?"
            );


        if (
            !$stmt
        ) {

            responseJSON(
                false,
                "Query detail bootcamp gagal."
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


        $data =
            mysqli_fetch_assoc(
                $result
            );


        mysqli_stmt_close(
            $stmt
        );


        if (
            !$data
        ) {

            responseJSON(
                false,
                "Bootcamp tidak ditemukan."
            );

        }


        responseJSON(
            true,
            "Data bootcamp berhasil diambil.",
            $data
        );

    }


    /* =================================================
       GET SEMUA
    ================================================= */

    $query = mysqli_query($conn, $bootcampSelect . " ORDER BY b.id DESC");


    if (
        !$query
    ) {

        responseJSON(
            false,
            "Gagal mengambil data bootcamp: " .
            mysqli_error($conn)
        );

    }


    $data = [];


    while (
        $row =
        mysqli_fetch_assoc(
            $query
        )
    ) {

        $data[] =
            $row;

    }


    responseJSON(
        true,
        "Data bootcamp berhasil diambil.",
        $data
    );

}


/* =================================================
   DELETE BOOTCAMP
================================================= */

if (
    $method === "POST" &&
    $action === "DELETE"
) {

    $id =
        $_POST["id"] ??
        null;


    if (
        !$id
    ) {

        responseJSON(
            false,
            "ID bootcamp wajib diisi."
        );

    }


    $id =
        (int) $id;


    /* =================================================
       CEK DATA LAMA
    ================================================= */

    $stmt =
        mysqli_prepare(
            $conn,
            "SELECT gambar
             FROM bootcamp
             WHERE id = ?"
        );


    if (
        !$stmt
    ) {

        responseJSON(
            false,
            "Query pencarian bootcamp gagal."
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


    $oldData =
        mysqli_fetch_assoc(
            $result
        );


    mysqli_stmt_close(
        $stmt
    );


    if (
        !$oldData
    ) {

        responseJSON(
            false,
            "Bootcamp tidak ditemukan."
        );

    }


    /* =================================================
       DELETE DATABASE
    ================================================= */

    $stmt =
        mysqli_prepare(
            $conn,
            "DELETE
             FROM bootcamp
             WHERE id = ?"
        );


    if (
        !$stmt
    ) {

        responseJSON(
            false,
            "Query hapus bootcamp gagal."
        );

    }


    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        $error =
            mysqli_stmt_error(
                $stmt
            );


        mysqli_stmt_close(
            $stmt
        );


        responseJSON(
            false,
            "Gagal menghapus bootcamp: " .
            $error
        );

    }


    mysqli_stmt_close(
        $stmt
    );


    /* =================================================
       HAPUS GAMBAR
    ================================================= */

    if (
        !empty(
            $oldData["gambar"]
        )
    ) {

        $oldImagePath =
            __DIR__ .
            "/../" .
            ltrim(
                $oldData["gambar"],
                "/"
            );


        if (
            file_exists(
                $oldImagePath
            )
        ) {

            @unlink(
                $oldImagePath
            );

        }

    }


    responseJSON(
        true,
        "Bootcamp berhasil dihapus."
    );

}


/* =================================================
   UPDATE BOOTCAMP
================================================= */

if (
    $method === "POST" &&
    $action === "PUT"
) {

    $id =
        $_POST["id"] ??
        null;


    $judul =
        trim(
            $_POST["judul"] ??
            ""
        );


    $kategori =
        trim(
            $_POST["kategori"] ??
            ""
        );


    $mentor =
        trim(
            $_POST["mentor"] ??
            ""
        );


    $hargaInput =
        $_POST["harga"] ??
        null;


    $tanggal_mulai =
        $_POST["tanggal_mulai"] ??
        "";


    $tanggal_berakhir =
        $_POST["tanggal_berakhir"] ??
        "";


    $kuotaInput =
        $_POST["kuota"] ??
        null;


    $benefit =
        trim(
            $_POST["benefit"] ??
            ""
        );


    $deskripsi =
        trim(
            $_POST["deskripsi"] ??
            ""
        );


    $promoAktifInput =
        $_POST["promo_aktif"] ??
        0;


    /* =================================================
       VALIDASI DATA DASAR
    ================================================= */

    if (
        !$id ||
        empty($judul) ||
        empty($kategori) ||
        empty($mentor) ||
        empty($tanggal_mulai) ||
        empty($tanggal_berakhir)
    ) {

        responseJSON(
            false,
            "Data bootcamp wajib dilengkapi."
        );

    }


    /* =================================================
       VALIDASI HARGA
    ================================================= */

    if (
        $hargaInput === null ||
        $hargaInput === "" ||
        !is_numeric($hargaInput)
    ) {

        responseJSON(
            false,
            "Harga tidak valid."
        );

    }


    $harga =
        (float) $hargaInput;


    if (
        $harga < 0
    ) {

        responseJSON(
            false,
            "Harga tidak boleh kurang dari 0."
        );

    }


    /* =================================================
       VALIDASI KUOTA
    ================================================= */

    if (
        $kuotaInput === null ||
        $kuotaInput === "" ||
        !is_numeric($kuotaInput)
    ) {

        responseJSON(
            false,
            "Kuota tidak valid."
        );

    }


    $kuota =
        (int) $kuotaInput;


    if (
        $kuota < 0
    ) {

        responseJSON(
            false,
            "Kuota tidak boleh kurang dari 0."
        );

    }


    /* =================================================
       VALIDASI TANGGAL
    ================================================= */

    if (
        $tanggal_berakhir <
        $tanggal_mulai
    ) {

        responseJSON(
            false,
            "Tanggal berakhir tidak boleh sebelum tanggal mulai."
        );

    }


    $id =
        (int) $id;


    /* =================================================
       PROMO AKTIF
    ================================================= */

    $promoAktif =
        (
            $promoAktifInput === 1 ||
            $promoAktifInput === "1" ||
            $promoAktifInput === true
        )
            ? 1
            : 0;


    /* =================================================
       HARGA PROMO
    ================================================= */

    $hargaPromo =
        null;


    if (
        $promoAktif === 1
    ) {

        /*
         * Field harus ada dan tidak kosong.
         *
         * Nilai "0" tetap VALID.
         */

        if (
            !isset(
                $_POST["harga_promo"]
            ) ||
            $_POST["harga_promo"] === ""
        ) {

            responseJSON(
                false,
                "Harga promo wajib diisi."
            );

        }


        if (
            !is_numeric(
                $_POST["harga_promo"]
            )
        ) {

            responseJSON(
                false,
                "Harga promo tidak valid."
            );

        }


        $hargaPromo =
            (float)
            $_POST["harga_promo"];


        if (
            $hargaPromo < 0
        ) {

            responseJSON(
                false,
                "Harga promo tidak boleh kurang dari 0."
            );

        }


        /*
         * Harga promo 0 = GRATIS.
         *
         * Jadi jangan menolak:
         *
         * harga       = 1000000
         * harga_promo = 0
         */

        if (
            $hargaPromo > 0 &&
            $harga > 0 &&
            $hargaPromo >= $harga
        ) {

            responseJSON(
                false,
                "Harga promo harus lebih kecil dari harga normal."
            );

        }


        /*
         * Kalau harga normal = 0,
         * harga promo juga harus 0.
         */

        if (
            $harga <= 0 &&
            $hargaPromo > 0
        ) {

            responseJSON(
                false,
                "Jika harga normal gratis, harga promo harus 0."
            );

        }

    }

    else {

        /*
         * Promo tidak aktif
         * disimpan sebagai 0.
         */

        $hargaPromo =
            0;

    }


    /* =================================================
       DATA LAMA
    ================================================= */

    $stmt =
        mysqli_prepare(
            $conn,
            "SELECT gambar
             FROM bootcamp
             WHERE id = ?"
        );


    if (
        !$stmt
    ) {

        responseJSON(
            false,
            "Query data lama gagal."
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


    $oldData =
        mysqli_fetch_assoc(
            $result
        );


    mysqli_stmt_close(
        $stmt
    );


    if (
        !$oldData
    ) {

        responseJSON(
            false,
            "Bootcamp tidak ditemukan."
        );

    }


    /* =================================================
       UPLOAD GAMBAR BARU
    ================================================= */

    $gambarBaru =
        uploadImage(
            $uploadDir,
            $uploadPath
        );


    /* =================================================
       UPDATE DENGAN GAMBAR BARU
    ================================================= */

    if (
        $gambarBaru !== null
    ) {

        $stmt =
            mysqli_prepare(
                $conn,
                "UPDATE bootcamp
                 SET
                    judul = ?,
                    kategori = ?,
                    mentor = ?,
                    harga = ?,
                    harga_promo = ?,
                    promo_aktif = ?,
                    tanggal_mulai = ?,
                    tanggal_berakhir = ?,
                    kuota = ?,
                    gambar = ?,
                    benefit = ?,
                    deskripsi = ?
                 WHERE id = ?"
            );


        if (
            !$stmt
        ) {

            responseJSON(
                false,
                "Query update bootcamp gagal: " .
                mysqli_error($conn)
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "sssddississsi",
            $judul,
            $kategori,
            $mentor,
            $harga,
            $hargaPromo,
            $promoAktif,
            $tanggal_mulai,
            $tanggal_berakhir,
            $kuota,
            $gambarBaru,
            $benefit,
            $deskripsi,
            $id
        );


        if (
            !mysqli_stmt_execute(
                $stmt
            )
        ) {

            $error =
                mysqli_stmt_error(
                    $stmt
                );


            mysqli_stmt_close(
                $stmt
            );


            responseJSON(
                false,
                "Gagal memperbarui bootcamp: " .
                $error
            );

        }


        mysqli_stmt_close(
            $stmt
        );


        /* =================================================
           HAPUS GAMBAR LAMA
        ================================================= */

        if (
            !empty(
                $oldData["gambar"]
            )
        ) {

            $oldImagePath =
                __DIR__ .
                "/../" .
                ltrim(
                    $oldData["gambar"],
                    "/"
                );


            if (
                file_exists(
                    $oldImagePath
                )
            ) {

                @unlink(
                    $oldImagePath
                );

            }

        }

    }


    /* =================================================
       UPDATE TANPA GANTI GAMBAR
    ================================================= */

    else {

        $stmt =
            mysqli_prepare(
                $conn,
                "UPDATE bootcamp
                 SET
                    judul = ?,
                    kategori = ?,
                    mentor = ?,
                    harga = ?,
                    harga_promo = ?,
                    promo_aktif = ?,
                    tanggal_mulai = ?,
                    tanggal_berakhir = ?,
                    kuota = ?,
                    benefit = ?,
                    deskripsi = ?
                 WHERE id = ?"
            );


        if (
            !$stmt
        ) {

            responseJSON(
                false,
                "Query update bootcamp gagal: " .
                mysqli_error($conn)
            );

        }


        mysqli_stmt_bind_param(
            $stmt,
            "sssddississi",
            $judul,
            $kategori,
            $mentor,
            $harga,
            $hargaPromo,
            $promoAktif,
            $tanggal_mulai,
            $tanggal_berakhir,
            $kuota,
            $benefit,
            $deskripsi,
            $id
        );


        if (
            !mysqli_stmt_execute(
                $stmt
            )
        ) {

            $error =
                mysqli_stmt_error(
                    $stmt
                );


            mysqli_stmt_close(
                $stmt
            );


            responseJSON(
                false,
                "Gagal memperbarui bootcamp: " .
                $error
            );

        }


        mysqli_stmt_close(
            $stmt
        );

    }


    responseJSON(
        true,
        "Bootcamp berhasil diperbarui."
    );

}


/* =================================================
   INSERT BOOTCAMP
================================================= */

if (
    $method === "POST" &&
    $action === "INSERT"
) {

    $judul =
        trim(
            $_POST["judul"] ??
            ""
        );


    $kategori =
        trim(
            $_POST["kategori"] ??
            ""
        );


    $mentor =
        trim(
            $_POST["mentor"] ??
            ""
        );


    $hargaInput =
        $_POST["harga"] ??
        null;


    $tanggal_mulai =
        $_POST["tanggal_mulai"] ??
        "";


    $tanggal_berakhir =
        $_POST["tanggal_berakhir"] ??
        "";


    $kuotaInput =
        $_POST["kuota"] ??
        null;


    $benefit =
        trim(
            $_POST["benefit"] ??
            ""
        );


    $deskripsi =
        trim(
            $_POST["deskripsi"] ??
            ""
        );


    $promoAktifInput =
        $_POST["promo_aktif"] ??
        0;


    /* =================================================
       VALIDASI DATA DASAR
    ================================================= */

    if (
        empty($judul) ||
        empty($kategori) ||
        empty($mentor) ||
        empty($tanggal_mulai) ||
        empty($tanggal_berakhir)
    ) {

        responseJSON(
            false,
            "Data bootcamp wajib dilengkapi."
        );

    }


    /* =================================================
       VALIDASI HARGA
    ================================================= */

    if (
        $hargaInput === null ||
        $hargaInput === "" ||
        !is_numeric($hargaInput)
    ) {

        responseJSON(
            false,
            "Harga tidak valid."
        );

    }


    $harga =
        (float) $hargaInput;


    if (
        $harga < 0
    ) {

        responseJSON(
            false,
            "Harga tidak boleh kurang dari 0."
        );

    }


    /* =================================================
       VALIDASI KUOTA
    ================================================= */

    if (
        $kuotaInput === null ||
        $kuotaInput === "" ||
        !is_numeric($kuotaInput)
    ) {

        responseJSON(
            false,
            "Kuota tidak valid."
        );

    }


    $kuota =
        (int) $kuotaInput;


    if (
        $kuota < 0
    ) {

        responseJSON(
            false,
            "Kuota tidak boleh kurang dari 0."
        );

    }


    /* =================================================
       VALIDASI TANGGAL
    ================================================= */

    if (
        $tanggal_berakhir <
        $tanggal_mulai
    ) {

        responseJSON(
            false,
            "Tanggal berakhir tidak boleh sebelum tanggal mulai."
        );

    }


    /* =================================================
       PROMO AKTIF
    ================================================= */

    $promoAktif =
        (
            $promoAktifInput === 1 ||
            $promoAktifInput === "1" ||
            $promoAktifInput === true
        )
            ? 1
            : 0;


    /* =================================================
       HARGA PROMO
    ================================================= */

    $hargaPromo =
        null;


    if (
        $promoAktif === 1
    ) {

        /*
         * Field harga promo harus dikirim.
         * Nilai 0 tetap VALID.
         */

        if (
            !isset(
                $_POST["harga_promo"]
            ) ||
            $_POST["harga_promo"] === ""
        ) {

            responseJSON(
                false,
                "Harga promo wajib diisi."
            );

        }


        if (
            !is_numeric(
                $_POST["harga_promo"]
            )
        ) {

            responseJSON(
                false,
                "Harga promo tidak valid."
            );

        }


        $hargaPromo =
            (float)
            $_POST["harga_promo"];


        if (
            $hargaPromo < 0
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
            $hargaPromo > 0 &&
            $harga > 0 &&
            $hargaPromo >= $harga
        ) {

            responseJSON(
                false,
                "Harga promo harus lebih kecil dari harga normal."
            );

        }


        /*
         * Harga normal 0
         * berarti promo harus 0.
         */

        if (
            $harga <= 0 &&
            $hargaPromo > 0
        ) {

            responseJSON(
                false,
                "Jika harga normal gratis, harga promo harus 0."
            );

        }

    }

    else {

        /*
         * Promo tidak aktif
         * disimpan sebagai 0.
         */

        $hargaPromo =
            0;

    }


    /* =================================================
       UPLOAD GAMBAR
    ================================================= */

    $gambar =
        uploadImage(
            $uploadDir,
            $uploadPath
        );


    /*
     * Kalau tidak ada gambar,
     * simpan string kosong.
     */

    if (
        $gambar === null
    ) {

        $gambar =
            "";

    }


    /* =================================================
       INSERT
    ================================================= */

    $stmt =
        mysqli_prepare(
            $conn,
            "INSERT INTO bootcamp
            (
                judul,
                kategori,
                mentor,
                harga,
                harga_promo,
                promo_aktif,
                tanggal_mulai,
                tanggal_berakhir,
                kuota,
                gambar,
                benefit,
                deskripsi
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )"
        );


    if (
        !$stmt
    ) {

        responseJSON(
            false,
            "Query tambah bootcamp gagal: " .
            mysqli_error($conn)
        );

    }


    mysqli_stmt_bind_param(
        $stmt,
        "sssddississs",
        $judul,
        $kategori,
        $mentor,
        $harga,
        $hargaPromo,
        $promoAktif,
        $tanggal_mulai,
        $tanggal_berakhir,
        $kuota,
        $gambar,
        $benefit,
        $deskripsi
    );


    if (
        !mysqli_stmt_execute(
            $stmt
        )
    ) {

        $error =
            mysqli_stmt_error(
                $stmt
            );


        mysqli_stmt_close(
            $stmt
        );


        responseJSON(
            false,
            "Gagal menambahkan bootcamp: " .
            $error
        );

    }


    $newId =
        mysqli_insert_id(
            $conn
        );


    mysqli_stmt_close(
        $stmt
    );


    responseJSON(
        true,
        "Bootcamp berhasil ditambahkan.",
        [
            "id" =>
                $newId
        ]
    );

}


/* =================================================
   ACTION TIDAK DIDUKUNG
================================================= */

responseJSON(
    false,
    "Method atau action tidak didukung."
);

?>