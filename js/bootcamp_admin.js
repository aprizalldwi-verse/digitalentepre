const BOOTCAMP_API =
    "../admin-php/bootcamp.php";


let bootcampData =
    [];

let editingId =
    null;

let activeFilter =
    "Semua Kategori";

let searchKeyword =
    "";





/* =====================================================
   SIDEBAR
===================================================== */

function toggleSidebar() {

    const sidebar =
        document.getElementById(
            "sidebar"
        );

    const overlay =
        document.getElementById(
            "overlay"
        );


    if (!sidebar) {

        return;

    }


    sidebar.classList.toggle(
        "open"
    );


    if (overlay) {

        overlay.classList.toggle(
            "show"
        );

    }


    document.body.classList.toggle(
        "sidebar-open"
    );

}


function closeSidebar() {

    const sidebar =
        document.getElementById(
            "sidebar"
        );

    const overlay =
        document.getElementById(
            "overlay"
        );


    if (sidebar) {

        sidebar.classList.remove(
            "open"
        );

    }


    if (overlay) {

        overlay.classList.remove(
            "show"
        );

    }


    document.body.classList.remove(
        "sidebar-open"
    );

}





/* =====================================================
   RUPIAH
===================================================== */

function formatRupiah(value) {

    const number =
        Number(value);

    if (
        !Number.isFinite(
            number
        ) ||
        number <= 0
    ) {

        return "Gratis";

    }


    return (
        "Rp " +
        number.toLocaleString(
            "id-ID"
        )
    );

}





/* =====================================================
   HTML ESCAPE
===================================================== */

function escapeHTML(value) {

    return String(
        value ??
        ""
    )
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );

}





/* =====================================================
   PROMO DATA
===================================================== */

function getPromoData(item) {

    const hargaNormal =
        Number(
            item.harga
        ) || 0;


    let hargaPromo =
        0;


    /*
     * Jangan memakai || "" atau || 0
     * untuk membedakan nilai kosong dan 0.
     */

    if (
        item.harga_promo !== null &&
        item.harga_promo !== undefined &&
        item.harga_promo !== ""
    ) {

        hargaPromo =
            Number(
                item.harga_promo
            );

    }


    if (
        !Number.isFinite(
            hargaPromo
        )
    ) {

        hargaPromo = 0;

    }


    const promoAktif =
        Number(
            item.promo_aktif
        ) === 1 ||
        item.promo_aktif === true ||
        item.promo_aktif === "1";


    let diskon =
        0;


    /*
     * PROMO GRATIS
     *
     * Harga normal > 0
     * Harga promo = 0
     *
     * Diskon = 100%
     */

    if (
        promoAktif &&
        hargaNormal > 0 &&
        hargaPromo === 0
    ) {

        diskon =
            100;

    }


    /*
     * PROMO NORMAL
     */

    else if (
        promoAktif &&
        hargaNormal > 0 &&
        hargaPromo > 0 &&
        hargaPromo < hargaNormal
    ) {

        diskon =
            Math.round(
                (
                    (
                        hargaNormal -
                        hargaPromo
                    ) /
                    hargaNormal
                ) *
                100
            );

    }


    /*
     * Harga normal = 0
     *
     * Produk sudah gratis.
     */

    else if (
        hargaNormal <= 0
    ) {

        diskon =
            0;

    }


    return {

        aktif:
            promoAktif,

        hargaNormal:
            hargaNormal,

        hargaPromo:
            hargaPromo,

        diskon:
            diskon

    };

}





/* =====================================================
   BENEFIT
===================================================== */

function parseBenefits(
    benefit
) {

    if (!benefit) {

        return [];

    }


    if (
        Array.isArray(
            benefit
        )
    ) {

        return benefit;

    }


    try {

        const parsed =
            JSON.parse(
                benefit
            );


        if (
            Array.isArray(
                parsed
            )
        ) {

            return parsed;

        }

    } catch (error) {

        // lanjut split

    }


    return String(
        benefit
    )
        .split(
            /[,|\n]+/
        )
        .map(
            function (
                value
            ) {

                return value.trim();

            }
        )
        .filter(
            Boolean
        );

}





/* =====================================================
   LOAD DATA
===================================================== */

async function loadBootcamp() {

    const grid =
        document.getElementById(
            "bootcampGrid"
        );


    const count =
        document.getElementById(
            "bootcampCount"
        );


    const databaseStatus =
        document.getElementById(
            "databaseStatus"
        );


    if (!grid) {

        return;

    }


    grid.innerHTML = `

        <div class="empty-state">

            <h3>
                Memuat data bootcamp...
            </h3>

            <p>
                Silakan tunggu sebentar.
            </p>

        </div>

    `;


    try {

        const response =
            await fetch(
                BOOTCAMP_API +
                "?action=list&_" +
                Date.now(),
                {
                    method:
                        "GET",

                    cache:
                        "no-store",

                    headers: {

                        "Accept":
                            "application/json"

                    }

                }
            );


        if (
            !response.ok
        ) {

            throw new Error(
                "Server error " +
                response.status
            );

        }


        const result =
            await response.json();


        console.log(
            "DATA BOOTCAMP:",
            result
        );


        if (
            Array.isArray(
                result
            )
        ) {

            bootcampData =
                result;

        }

        else {

            bootcampData =
                Array.isArray(
                    result.data
                )
                    ? result.data
                    : [];

        }


        if (count) {

            count.textContent =
                bootcampData.length;

        }


        if (
            databaseStatus
        ) {

            databaseStatus.textContent =
                "Online";

            databaseStatus.style.color =
                "#16a34a";

        }


        applyFilters();


    } catch (error) {

        console.error(
            "LOAD BOOTCAMP ERROR:",
            error
        );


        if (
            databaseStatus
        ) {

            databaseStatus.textContent =
                "Offline";

            databaseStatus.style.color =
                "#dc2626";

        }


        grid.innerHTML = `

            <div class="empty-state">

                <h3>
                    Gagal mengambil data bootcamp
                </h3>

                <p>
                    ${escapeHTML(
                        error.message
                    )}
                </p>

            </div>

        `;

    }

}





/* =====================================================
   FILTER
===================================================== */

function applyFilters() {

    let filtered =
        [...bootcampData];


    if (
        activeFilter !==
        "Semua Kategori"
    ) {

        filtered =
            filtered.filter(
                function (
                    item
                ) {

                    return String(
                        item.kategori ||
                        ""
                    )
                        .trim()
                        .toLowerCase() ===
                        String(
                            activeFilter
                        )
                            .trim()
                            .toLowerCase();

                }
            );

    }


    if (
        searchKeyword
    ) {

        filtered =
            filtered.filter(
                function (
                    item
                ) {

                    const text = [

                        item.judul,

                        item.nama_bootcamp,

                        item.kategori,

                        item.mentor,

                        item.deskripsi,

                        item.benefit

                    ]
                        .map(
                            function (
                                value
                            ) {

                                return String(
                                    value ||
                                    ""
                                )
                                    .toLowerCase();

                            }
                        )
                        .join(
                            " "
                        );


                    return text.includes(
                        searchKeyword
                    );

                }
            );

    }


    renderBootcamp(
        filtered
    );

}





/* =====================================================
   RENDER
===================================================== */

function renderBootcamp(
    data
) {

    const grid =
        document.getElementById(
            "bootcampGrid"
        );


    if (!grid) {

        return;

    }


    if (!data.length) {

        grid.innerHTML = `

            <div class="empty-state">

                <h3>
                    Bootcamp tidak ditemukan
                </h3>

                <p>
                    Coba gunakan pencarian
                    atau kategori lain.
                </p>

            </div>

        `;

        return;

    }


    grid.innerHTML =
        data
            .map(
                createBootcampCard
            )
            .join("");

}





/* =====================================================
   CREATE CARD
===================================================== */

function createBootcampCard(
    item
) {

    const id =
        Number(
            item.id
        ) || 0;


    const judul =
        item.judul ||
        item.nama_bootcamp ||
        item.nama_produk ||
        "Tanpa Nama";


    const kategori =
        item.kategori ||
        "Bootcamp";


    const mentor =
        item.mentor ||
        "Belum ditentukan";


    const kuota =
        Number(
            item.kuota ||
            item.kuota_peserta ||
            0
        );


    const deskripsi =
        item.deskripsi ||
        "Belum ada deskripsi.";


    const benefit =
        parseBenefits(
            item.benefit
        );


    const harga =
        Number(
            item.harga
        ) || 0;


    const promo =
        getPromoData(
            item
        );


    const tanggalMulai =
        formatDate(
            item.tanggal_mulai
        );


    const tanggalBerakhir =
        formatDate(
            item.tanggal_berakhir
        );


    let imagePath =
        item.gambar ||
        "";


    imagePath =
        String(
            imagePath
        ).trim();


    if (
        imagePath &&
        typeof window.resolveImagePath ===
        "function"
    ) {

        imagePath =
            window.resolveImagePath(
                imagePath
            );

    }

    else if (
        imagePath &&
        !imagePath.startsWith(
            "http://"
        ) &&
        !imagePath.startsWith(
            "https://"
        ) &&
        !imagePath.startsWith(
            "../"
        ) &&
        !imagePath.startsWith(
            "/"
        )
    ) {

        imagePath =
            "../" +
            imagePath;

    }


    const status = item.status_program || (kuota <= 0 ? "Penuh" : "Status tidak tersedia");


    let imageHTML =
        imagePath
            ? `

                <img
                    src="${escapeHTML(
                        imagePath
                    )}"
                    alt="${escapeHTML(
                        judul
                    )}"
                    onerror="this.parentElement.innerHTML='<div style=&quot;width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#4338ca;background:#eef2ff;font-size:12px;font-weight:700;&quot;>Belum ada gambar</div>'"
                >

            `
            : `

                <div
                    style="
                        width:100%;
                        height:100%;
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        color:#4338ca;
                        background:#eef2ff;
                        font-size:12px;
                        font-weight:700;
                    "
                >

                    Belum ada gambar

                </div>

            `;


    let benefitHTML =
        "";


    if (
        benefit.length
    ) {

        benefitHTML = `

            <div class="course-benefits">

                ${benefit
                    .slice(
                        0,
                        4
                    )
                    .map(
                        function (
                            itemBenefit
                        ) {

                            return `

                                <span class="benefit-chip">

                                    ✓
                                    ${escapeHTML(
                                        itemBenefit
                                    )}

                                </span>

                            `;

                        }
                    )
                    .join(
                        ""
                    )}

            </div>

        `;

    }


    /* =================================================
       PRICE
    ================================================= */

    let priceHTML =
        "";


    /*
     * PROMO GRATIS
     */

    if (
        promo.aktif &&
        promo.hargaNormal > 0 &&
        promo.hargaPromo === 0
    ) {

        priceHTML = `

            <div class="course-price-promo">

                <span class="course-price-old">

                    ${formatRupiah(
                        promo.hargaNormal
                    )}

                </span>

                <div class="course-price-new-row">

                    <span class="course-price-new">

                        Gratis

                    </span>

                    <span class="course-discount">

                        100%

                    </span>

                </div>

            </div>

        `;

    }


    /*
     * PROMO NORMAL
     */

    else if (
        promo.aktif &&
        promo.hargaNormal > 0 &&
        promo.hargaPromo > 0 &&
        promo.hargaPromo <
        promo.hargaNormal
    ) {

        priceHTML = `

            <div class="course-price-promo">

                <span class="course-price-old">

                    ${formatRupiah(
                        promo.hargaNormal
                    )}

                </span>

                <div class="course-price-new-row">

                    <span class="course-price-new">

                        ${formatRupiah(
                            promo.hargaPromo
                        )}

                    </span>

                    <span class="course-discount">

                        ${promo.diskon}%

                    </span>

                </div>

            </div>

        `;

    }


    /*
     * HARGA NORMAL
     */

    else {

        priceHTML = `

            <span class="course-price-normal">

                ${
                    harga <= 0
                        ? "Gratis"
                        : formatRupiah(
                            harga
                        )
                }

            </span>

        `;

    }


    return `

        <article
            class="course-card"
            data-id="${id}"
            data-subkategori="${escapeHTML(
                kategori
            )}"
        >


            <!-- IMAGE -->

            <div class="course-image">

                ${imageHTML}


                <span class="course-category">

                    BOOTCAMP

                </span>


                <span class="course-status">

                    ${escapeHTML(
                        status
                    )}

                </span>


                ${
                    promo.aktif &&
                    promo.diskon > 0
                        ? `

                            <span class="course-promo-badge">

                                ${promo.diskon}%
                                OFF

                            </span>

                        `
                        : ""
                }


            </div>


            <!-- BODY -->

            <div class="course-body">


                <span class="course-subcategory">

                    ${escapeHTML(
                        kategori
                    )}

                </span>


                <h3 class="course-name">

                    ${escapeHTML(
                        judul
                    )}

                </h3>


                <p class="course-description">

                    ${escapeHTML(
                        deskripsi
                    )}

                </p>


                <!-- META -->

                <div class="course-meta">


                    <div class="course-meta-item">

                        <span class="course-meta-label">

                            Mentor

                        </span>

                        <span class="course-meta-value">

                            ${escapeHTML(
                                mentor
                            )}

                        </span>

                    </div>


                    <div class="course-meta-item">

                        <span class="course-meta-label">

                            Kuota

                        </span>

                        <span class="course-meta-value">

                            ${kuota}

                            peserta

                        </span>

                    </div>


                    <div class="course-meta-item">

                        <span class="course-meta-label">

                            Mulai

                        </span>

                        <span class="course-meta-value">

                            ${escapeHTML(
                                tanggalMulai
                            )}

                        </span>

                    </div>


                    <div class="course-meta-item">

                        <span class="course-meta-label">

                            Berakhir

                        </span>

                        <span class="course-meta-value">

                            ${escapeHTML(
                                tanggalBerakhir
                            )}

                        </span>

                    </div>


                </div>


                ${benefitHTML}


                <!-- FOOTER -->

                <div class="course-footer">


                    <div class="course-price-box">


                        <span class="course-price-label">

                            Harga

                        </span>


                        ${priceHTML}


                    </div>


                    <!-- EDIT + HAPUS -->

                    <div class="course-actions">


                        <button
                            type="button"
                            class="course-action edit-btn"
                            onclick="editBootcamp(${id})"
                        >

                            Edit

                        </button>


                        <button
                            type="button"
                            class="course-action delete-btn"
                            onclick="deleteBootcamp(${id})"
                        >

                            Hapus

                        </button>


                    </div>


                </div>


            </div>


        </article>

    `;

}





/* =====================================================
   DATE
===================================================== */

function formatDate(
    value
) {

    if (!value) {

        return "-";

    }


    const text =
        String(
            value
        )
            .trim()
            .split(
                "T"
            )[0]
            .split(
                " "
            )[0];


    const parts =
        text.split(
            "-"
        );


    if (
        parts.length !==
        3
    ) {

        return text;

    }


    const month = [

        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "Mei",
        "Jun",
        "Jul",
        "Agu",
        "Sep",
        "Okt",
        "Nov",
        "Des"

    ];


    return (

        Number(
            parts[2]
        ) +
        " " +
        month[
            Number(
                parts[1]
            ) - 1
        ] +
        " " +
        parts[0]

    );

}





/* =====================================================
   OPEN MODAL TAMBAH
===================================================== */

function openBootcampModal() {

    const modal =
        document.getElementById(
            "bootcampModal"
        );


    const form =
        document.getElementById(
            "bootcampForm"
        );


    editingId =
        null;


    if (form) {

        form.reset();

    }


    const bootcampId =
        document.getElementById(
            "bootcampId"
        );


    if (bootcampId) {

        bootcampId.value =
            "";

    }


    const modalTitle =
        document.getElementById(
            "modalTitle"
        );


    if (modalTitle) {

        modalTitle.textContent =
            "Tambah Bootcamp";

    }


    const saveButton =
        document.querySelector(
            ".save-btn"
        );


    if (saveButton) {

        saveButton.textContent =
            "Simpan Bootcamp";

    }


    resetPromo();


    const preview =
        document.getElementById(
            "imagePreview"
        );


    if (preview) {

        preview.src =
            "";

        preview.classList.remove(
            "show"
        );

    }


    if (modal) {

        modal.classList.add(
            "show"
        );

    }

}





/* =====================================================
   CLOSE MODAL
===================================================== */

function closeBootcampModal() {

    const modal =
        document.getElementById(
            "bootcampModal"
        );


    if (modal) {

        modal.classList.remove(
            "show"
        );

    }


    editingId =
        null;

}





/* =====================================================
   EDIT BOOTCAMP
===================================================== */

async function editBootcamp(
    id
) {

    try {

        const response =
            await fetch(
                BOOTCAMP_API +
                "?id=" +
                encodeURIComponent(
                    id
                ) +
                "&_=" +
                Date.now(),
                {
                    method:
                        "GET",

                    cache:
                        "no-store",

                    headers: {

                        "Accept":
                            "application/json"

                    }

                }
            );


        if (
            !response.ok
        ) {

            throw new Error(
                "Gagal mengambil data bootcamp."
            );

        }


        const result =
            await response.json();


        console.log(
            "DATA EDIT BOOTCAMP:",
            result
        );


        let item =
            result;


        if (
            result &&
            result.data
        ) {

            item =
                result.data;

        }


        if (
            Array.isArray(
                item
            )
        ) {

            item =
                item[0];

        }


        if (
            !item ||
            !item.id
        ) {

            throw new Error(
                "Data bootcamp tidak ditemukan."
            );

        }


        editingId =
            item.id;


        /* =================================================
           ID
        ================================================= */

        const bootcampId =
            document.getElementById(
                "bootcampId"
            );


        if (bootcampId) {

            bootcampId.value =
                item.id;

        }


        /* =================================================
           JUDUL
        ================================================= */

        const judul =
            document.getElementById(
                "judul"
            );


        if (judul) {

            judul.value =
                item.judul ||
                "";

        }


        /* =================================================
           KATEGORI
        ================================================= */

        const kategori =
            document.getElementById(
                "kategori"
            );


        if (kategori) {

            kategori.value =
                item.kategori ||
                "";

        }


        /* =================================================
           MENTOR
        ================================================= */

        const mentor =
            document.getElementById(
                "mentor"
            );


        if (mentor) {

            mentor.value =
                item.mentor ||
                "";

        }


        /* =================================================
           HARGA
        ================================================= */

        const harga =
            document.getElementById(
                "harga"
            );


        if (harga) {

            harga.value =
                (
                    item.harga !== null &&
                    item.harga !== undefined &&
                    item.harga !== ""
                )
                    ? item.harga
                    : 0;

        }


        /* =================================================
           KUOTA
        ================================================= */

        const kuota =
            document.getElementById(
                "kuota"
            );


        if (kuota) {

            kuota.value =
                (
                    item.kuota !== null &&
                    item.kuota !== undefined &&
                    item.kuota !== ""
                )
                    ? item.kuota
                    : (
                        item.kuota_peserta ||
                        ""
                    );

        }


        /* =================================================
           TANGGAL
        ================================================= */

        const tanggalMulai =
            document.getElementById(
                "tanggal_mulai"
            );


        if (tanggalMulai) {

            tanggalMulai.value =
                normalizeDate(
                    item.tanggal_mulai
                );

        }


        const tanggalBerakhir =
            document.getElementById(
                "tanggal_berakhir"
            );


        if (tanggalBerakhir) {

            tanggalBerakhir.value =
                normalizeDate(
                    item.tanggal_berakhir
                );

        }


        /* =================================================
           DESKRIPSI
        ================================================= */

        const deskripsi =
            document.getElementById(
                "deskripsi"
            );


        if (deskripsi) {

            deskripsi.value =
                item.deskripsi ||
                "";

        }


        /* =================================================
           BENEFIT
        ================================================= */

        const benefitInput =
            document.getElementById(
                "benefit"
            );


        if (benefitInput) {

            benefitInput.value =
                Array.isArray(
                    item.benefit
                )
                    ? item.benefit.join(
                        ", "
                    )
                    : (
                        item.benefit ||
                        ""
                    );

        }


        /* =================================================
           PROMO
        ================================================= */

        const promoAktif =
            document.getElementById(
                "promoAktif"
            );


        const promoFields =
            document.getElementById(
                "promoFields"
            );


        const hargaPromo =
            document.getElementById(
                "hargaPromo"
            );


        const promo =
            getPromoData(
                item
            );


        if (promoAktif) {

            promoAktif.checked =
                promo.aktif;

        }


        if (promoFields) {

            if (
                promo.aktif
            ) {

                promoFields.classList.add(
                    "show"
                );

            }

            else {

                promoFields.classList.remove(
                    "show"
                );

            }

        }


        if (hargaPromo) {

            /*
             * PENTING:
             * nilai 0 harus tetap 0,
             * bukan berubah menjadi string kosong.
             */

            if (
                promo.aktif
            ) {

                if (
                    item.harga_promo !== null &&
                    item.harga_promo !== undefined &&
                    item.harga_promo !== ""
                ) {

                    hargaPromo.value =
                        item.harga_promo;

                }

                else {

                    hargaPromo.value =
                        0;

                }

            }

            else {

                hargaPromo.value =
                    "";

            }

        }


        updateDiscount();


        /* =================================================
           GAMBAR
        ================================================= */

        const preview =
            document.getElementById(
                "imagePreview"
            );


        if (
            preview &&
            item.gambar
        ) {

            let imagePath =
                String(
                    item.gambar
                ).trim();


            if (
                typeof window.resolveImagePath ===
                "function"
            ) {

                imagePath =
                    window.resolveImagePath(
                        imagePath
                    );

            }

            else if (
                !imagePath.startsWith(
                    "http://"
                ) &&
                !imagePath.startsWith(
                    "https://"
                ) &&
                !imagePath.startsWith(
                    "../"
                ) &&
                !imagePath.startsWith(
                    "/"
                )
            ) {

                imagePath =
                    "../" +
                    imagePath;

            }


            preview.src =
                imagePath;


            preview.classList.add(
                "show"
            );

        }


        /* =================================================
           MODAL TITLE
        ================================================= */

        const modalTitle =
            document.getElementById(
                "modalTitle"
            );


        if (modalTitle) {

            modalTitle.textContent =
                "Edit Bootcamp";

        }


        const saveButton =
            document.querySelector(
                ".save-btn"
            );


        if (saveButton) {

            saveButton.textContent =
                "Perbarui Bootcamp";

        }


        const modal =
            document.getElementById(
                "bootcampModal"
            );


        if (modal) {

            modal.classList.add(
                "show"
            );

        }


    } catch (error) {

        console.error(
            "EDIT BOOTCAMP ERROR:",
            error
        );


        alert(
            "Gagal mengambil data bootcamp: " +
            error.message
        );

    }

}





/* =====================================================
   NORMALIZE DATE
===================================================== */

function normalizeDate(
    value
) {

    if (!value) {

        return "";

    }


    return String(
        value
    )
        .split(
            "T"
        )[0]
        .split(
            " "
        )[0];

}





/* =====================================================
   DELETE BOOTCAMP
===================================================== */

async function deleteBootcamp(
    id
) {

    const yakin =
        confirm(
            "Yakin ingin menghapus bootcamp ini?"
        );


    if (!yakin) {

        return;

    }


    try {

        const formData =
            new FormData();


        formData.append(
            "id",
            id
        );


        formData.append(
            "_method",
            "DELETE"
        );


        const response =
            await fetch(
                BOOTCAMP_API,
                {
                    method:
                        "POST",

                    body:
                        formData,

                    cache:
                        "no-store"

                }
            );


        if (
            !response.ok
        ) {

            throw new Error(
                "Server error " +
                response.status
            );

        }


        const result =
            await response.json();


        console.log(
            "DELETE RESULT:",
            result
        );


        if (
            result &&
            result.success === false
        ) {

            throw new Error(
                result.message ||
                "Gagal menghapus bootcamp."
            );

        }


        alert(
            result.message ||
            "Bootcamp berhasil dihapus!"
        );


        await loadBootcamp();


    } catch (error) {

        console.error(
            "DELETE BOOTCAMP ERROR:",
            error
        );


        alert(
            "Gagal menghapus bootcamp: " +
            error.message
        );

    }

}





/* =====================================================
   SAVE / UPDATE
===================================================== */

const bootcampForm =
    document.getElementById(
        "bootcampForm"
    );


if (
    bootcampForm
) {

    bootcampForm.addEventListener(
        "submit",
        async function (
            event
        ) {

            event.preventDefault();


            /* =================================================
               AMBIL DATA
            ================================================= */

            const judul =
                document.getElementById(
                    "judul"
                ).value.trim();


            const kategori =
                document.getElementById(
                    "kategori"
                ).value;


            const mentor =
                document.getElementById(
                    "mentor"
                ).value.trim();


            const harga =
                document.getElementById(
                    "harga"
                ).value;


            const kuota =
                document.getElementById(
                    "kuota"
                ).value;


            const tanggalMulai =
                document.getElementById(
                    "tanggal_mulai"
                ).value;


            const tanggalBerakhir =
                document.getElementById(
                    "tanggal_berakhir"
                ).value;


            const deskripsiElement =
                document.getElementById(
                    "deskripsi"
                );


            const deskripsi =
                deskripsiElement
                    ? deskripsiElement.value.trim()
                    : "";


            const benefitElement =
                document.getElementById(
                    "benefit"
                );


            const benefit =
                benefitElement
                    ? benefitElement.value.trim()
                    : "";


            const promoAktifInput =
                document.getElementById(
                    "promoAktif"
                );


            const hargaPromoInput =
                document.getElementById(
                    "hargaPromo"
                );


            const promoAktif =
                promoAktifInput &&
                promoAktifInput.checked
                    ? 1
                    : 0;


            const hargaPromo =
                hargaPromoInput
                    ? hargaPromoInput.value
                    : "";



            /* =================================================
               VALIDASI JUDUL
            ================================================= */

            if (!judul) {

                alert(
                    "Judul bootcamp harus diisi."
                );

                return;

            }


            /* =================================================
               VALIDASI KATEGORI
            ================================================= */

            if (!kategori) {

                alert(
                    "Kategori harus dipilih."
                );

                return;

            }


            /* =================================================
               VALIDASI MENTOR
            ================================================= */

            if (!mentor) {

                alert(
                    "Mentor harus diisi."
                );

                return;

            }


            /* =================================================
               VALIDASI HARGA
            ================================================= */

            if (
                harga === "" ||
                harga === null ||
                Number(harga) < 0
            ) {

                alert(
                    "Harga bootcamp tidak valid."
                );

                return;

            }


            /* =================================================
               VALIDASI KUOTA
            ================================================= */

            if (
                kuota === "" ||
                Number(kuota) <= 0
            ) {

                alert(
                    "Kuota harus lebih dari 0."
                );

                return;

            }


            /* =================================================
               VALIDASI TANGGAL
            ================================================= */

            if (
                !tanggalMulai
            ) {

                alert(
                    "Tanggal mulai harus diisi."
                );

                return;

            }


            if (
                !tanggalBerakhir
            ) {

                alert(
                    "Tanggal berakhir harus diisi."
                );

                return;

            }


            /* =================================================
               VALIDASI PROMO
            ================================================= */

            if (
                promoAktif === 1
            ) {


                /*
                 * KOSONG = TIDAK VALID
                 *
                 * 0 = VALID
                 */

                if (
                    hargaPromo === "" ||
                    hargaPromo === null
                ) {

                    alert(
                        "Harga promo harus diisi."
                    );

                    return;

                }


                const hargaNormalNumber =
                    Number(
                        harga
                    );


                const hargaPromoNumber =
                    Number(
                        hargaPromo
                    );


                if (
                    !Number.isFinite(
                        hargaPromoNumber
                    ) ||
                    hargaPromoNumber < 0
                ) {

                    alert(
                        "Harga promo tidak valid."
                    );

                    return;

                }


                /*
                 * HARGA NORMAL 0
                 *
                 * Maka harga promo hanya boleh 0.
                 */

                if (
                    hargaNormalNumber <= 0 &&
                    hargaPromoNumber > 0
                ) {

                    alert(
                        "Jika harga normal gratis, harga promo harus 0."
                    );

                    return;

                }


                /*
                 * PROMO 0 = GRATIS
                 *
                 * Jangan menjalankan validasi
                 * promo >= harga.
                 */

                if (
                    hargaPromoNumber > 0 &&
                    hargaNormalNumber > 0 &&
                    hargaPromoNumber >=
                    hargaNormalNumber
                ) {

                    alert(
                        "Harga promo harus lebih kecil dari harga normal."
                    );

                    return;

                }

            }



            /* =================================================
               GAMBAR
            ================================================= */

            const gambarInput =
                document.getElementById(
                    "gambar"
                );


            const gambar =
                gambarInput &&
                gambarInput.files.length
                    ? gambarInput.files[0]
                    : null;



            /* =================================================
               FORMDATA
            ================================================= */

            const formData =
                new FormData();


            formData.append(
                "judul",
                judul
            );


            formData.append(
                "kategori",
                kategori
            );


            formData.append(
                "mentor",
                mentor
            );


            formData.append(
                "harga",
                harga
            );


            formData.append(
                "kuota",
                kuota
            );


            formData.append(
                "tanggal_mulai",
                tanggalMulai
            );


            formData.append(
                "tanggal_berakhir",
                tanggalBerakhir
            );


            formData.append(
                "deskripsi",
                deskripsi
            );


            formData.append(
                "benefit",
                benefit
            );


            formData.append(
                "promo_aktif",
                promoAktif
            );


            /*
             * PROMO AKTIF
             *
             * Nilai 0 tetap dikirim sebagai "0".
             */

            formData.append(
                "harga_promo",
                promoAktif === 1
                    ? hargaPromo
                    : ""
            );


            if (
                gambar
            ) {

                formData.append(
                    "gambar",
                    gambar
                );

            }


            /* =================================================
               UPDATE ID
            ================================================= */

            if (
                editingId
            ) {

                formData.append(
                    "id",
                    editingId
                );


                formData.append(
                    "_method",
                    "PUT"
                );

            }


            /* =================================================
               BUTTON
            ================================================= */

            const saveButton =
                document.querySelector(
                    ".save-btn"
                );


            const originalText =
                saveButton
                    ? saveButton.textContent
                    : "Simpan Bootcamp";


            if (saveButton) {

                saveButton.disabled =
                    true;


                saveButton.textContent =
                    editingId
                        ? "Memperbarui..."
                        : "Menyimpan...";

            }


            /* =================================================
               REQUEST
            ================================================= */

            try {

                const response =
                    await fetch(
                        BOOTCAMP_API,
                        {
                            method:
                                "POST",

                            body:
                                formData,

                            cache:
                                "no-store"

                        }
                    );


                if (
                    !response.ok
                ) {

                    throw new Error(
                        "Server error " +
                        response.status
                    );

                }


                const result =
                    await response.json();


                console.log(
                    "SAVE RESULT:",
                    result
                );


                if (
                    result &&
                    result.success === false
                ) {

                    throw new Error(
                        result.message ||
                        "Gagal menyimpan bootcamp."
                    );

                }


                alert(
                    editingId
                        ? "Bootcamp berhasil diperbarui!"
                        : "Bootcamp berhasil ditambahkan!"
                );


                closeBootcampModal();


                await loadBootcamp();


            } catch (error) {

                console.error(
                    "SAVE BOOTCAMP ERROR:",
                    error
                );


                alert(
                    "Gagal menyimpan bootcamp: " +
                    error.message
                );


            } finally {

                if (saveButton) {

                    saveButton.disabled =
                        false;

                    saveButton.textContent =
                        originalText;

                }

            }

        }
    );

}





/* =====================================================
   PROMO TOGGLE
===================================================== */

const promoAktif =
    document.getElementById(
        "promoAktif"
    );


const promoFields =
    document.getElementById(
        "promoFields"
    );


if (
    promoAktif
) {

    promoAktif.addEventListener(
        "change",
        function () {

            if (
                this.checked
            ) {

                if (
                    promoFields
                ) {

                    promoFields.classList.add(
                        "show"
                    );

                }


                const hargaPromo =
                    document.getElementById(
                        "hargaPromo"
                    );


                /*
                 * Saat promo baru diaktifkan,
                 * isi default 0 agar produk
                 * bisa langsung dibuat gratis.
                 */

                if (
                    hargaPromo &&
                    hargaPromo.value === ""
                ) {

                    hargaPromo.value =
                        "0";

                }


                updateDiscount();

            }

            else {

                if (
                    promoFields
                ) {

                    promoFields.classList.remove(
                        "show"
                    );

                }


                const hargaPromo =
                    document.getElementById(
                        "hargaPromo"
                    );


                if (
                    hargaPromo
                ) {

                    hargaPromo.value =
                        "";

                }


                updateDiscount();

            }

        }
    );

}





/* =====================================================
   DISCOUNT
===================================================== */

function updateDiscount() {

    const hargaElement =
        document.getElementById(
            "harga"
        );


    const promoElement =
        document.getElementById(
            "hargaPromo"
        );


    const discountInfo =
        document.getElementById(
            "discountInfo"
        );


    if (!discountInfo) {

        return;

    }


    const harga =
        Number(
            hargaElement
                ? hargaElement.value
                : 0
        ) || 0;


    const promo =
        promoElement &&
        promoElement.value !== ""
            ? Number(
                promoElement.value
            )
            : null;


    if (
        !promoAktif ||
        !promoAktif.checked
    ) {

        discountInfo.textContent =
            "Diskon 0%";

        discountInfo.style.color =
            "#16a34a";

        return;

    }


    /*
     * HARGA NORMAL GRATIS
     */

    if (
        harga <= 0
    ) {

        if (
            promo === null ||
            promo === 0
        ) {

            discountInfo.textContent =
                "Gratis";

            discountInfo.style.color =
                "#16a34a";

        }

        else {

            discountInfo.textContent =
                "Harga promo harus 0.";

            discountInfo.style.color =
                "#dc2626";

        }

        return;

    }


    /*
     * PROMO KOSONG
     */

    if (
        promo === null
    ) {

        discountInfo.textContent =
            "Harga promo belum diisi.";

        discountInfo.style.color =
            "#dc2626";

        return;

    }


    /*
     * PROMO GRATIS
     */

    if (
        promo === 0
    ) {

        discountInfo.textContent =
            "Gratis (Diskon 100%)";

        discountInfo.style.color =
            "#16a34a";

        return;

    }


    /*
     * PROMO NEGATIF
     */

    if (
        promo < 0
    ) {

        discountInfo.textContent =
            "Harga promo tidak valid.";

        discountInfo.style.color =
            "#dc2626";

        return;

    }


    /*
     * PROMO LEBIH BESAR / SAMA
     * DENGAN HARGA NORMAL
     */

    if (
        promo >= harga
    ) {

        discountInfo.textContent =
            "Harga promo harus lebih kecil dari harga normal.";

        discountInfo.style.color =
            "#dc2626";

        return;

    }


    const diskon =
        Math.round(
            (
                (
                    harga -
                    promo
                ) /
                harga
            ) *
            100
        );


    discountInfo.textContent =
        "Diskon " +
        diskon +
        "%";


    discountInfo.style.color =
        "#16a34a";

}





/* =====================================================
   HARGA INPUT
===================================================== */

const hargaInput =
    document.getElementById(
        "harga"
    );


if (
    hargaInput
) {

    hargaInput.addEventListener(
        "input",
        updateDiscount
    );

}





/* =====================================================
   HARGA PROMO INPUT
===================================================== */

const hargaPromoInputGlobal =
    document.getElementById(
        "hargaPromo"
    );


if (
    hargaPromoInputGlobal
) {

    hargaPromoInputGlobal.addEventListener(
        "input",
        updateDiscount
    );

}





/* =====================================================
   RESET PROMO
===================================================== */

function resetPromo() {

    const promo =
        document.getElementById(
            "promoAktif"
        );


    const fields =
        document.getElementById(
            "promoFields"
        );


    const hargaPromo =
        document.getElementById(
            "hargaPromo"
        );


    const discountInfo =
        document.getElementById(
            "discountInfo"
        );


    if (promo) {

        promo.checked =
            false;

    }


    if (fields) {

        fields.classList.remove(
            "show"
        );

    }


    if (hargaPromo) {

        hargaPromo.value =
            "";

    }


    if (discountInfo) {

        discountInfo.textContent =
            "Diskon 0%";

        discountInfo.style.color =
            "#16a34a";

    }

}





/* =====================================================
   IMAGE PREVIEW
===================================================== */

const gambarInputGlobal =
    document.getElementById(
        "gambar"
    );


if (
    gambarInputGlobal
) {

    gambarInputGlobal.addEventListener(
        "change",
        function () {

            const file =
                this.files[0];


            const preview =
                document.getElementById(
                    "imagePreview"
                );


            if (
                !file ||
                !preview
            ) {

                return;

            }


            const allowedTypes = [

                "image/jpeg",

                "image/png",

                "image/webp"

            ];


            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {

                alert(
                    "Format gambar harus JPG, JPEG, PNG, atau WEBP."
                );


                this.value =
                    "";

                preview.src =
                    "";

                preview.classList.remove(
                    "show"
                );

                return;

            }


            if (
                file.size >
                5 *
                1024 *
                1024
            ) {

                alert(
                    "Ukuran gambar maksimal 5 MB."
                );


                this.value =
                    "";

                preview.src =
                    "";

                preview.classList.remove(
                    "show"
                );

                return;

            }


            const reader =
                new FileReader();


            reader.onload =
                function (
                    event
                ) {

                    preview.src =
                        event.target.result;

                    preview.classList.add(
                        "show"
                    );

                };


            reader.readAsDataURL(
                file
            );

        }
    );

}





/* =====================================================
   SEARCH
===================================================== */

const searchBootcamp =
    document.getElementById(
        "searchBootcamp"
    );


if (
    searchBootcamp
) {

    searchBootcamp.addEventListener(
        "input",
        function () {

            searchKeyword =
                this.value
                    .trim()
                    .toLowerCase();


            applyFilters();

        }
    );

}





/* =====================================================
   FILTER
===================================================== */

document
    .querySelectorAll(
        ".module-filter-btn"
    )
    .forEach(
        function (
            button
        ) {

            button.addEventListener(
                "click",
                function () {

                    activeFilter =
                        this.dataset.filter;


                    document
                        .querySelectorAll(
                            ".module-filter-btn"
                        )
                        .forEach(
                            function (
                                item
                            ) {

                                item.classList.remove(
                                    "active"
                                );

                            }
                        );


                    this.classList.add(
                        "active"
                    );


                    applyFilters();

                }
            );

        }
    );





/* =====================================================
   MODAL OUTSIDE CLICK
===================================================== */

document.addEventListener(
    "click",
    function (
        event
    ) {

        const modal =
            document.getElementById(
                "bootcampModal"
            );


        if (
            modal &&
            event.target ===
            modal
        ) {

            closeBootcampModal();

        }

    }
);





/* =====================================================
   ESC
===================================================== */

document.addEventListener(
    "keydown",
    function (
        event
    ) {

        if (
            event.key ===
            "Escape"
        ) {

            closeBootcampModal();

            closeSidebar();

        }

    }
);





/* =====================================================
   START
===================================================== */

document.addEventListener(
    "DOMContentLoaded",
    function () {

        loadBootcamp();

    }
);


loadBootcamp();