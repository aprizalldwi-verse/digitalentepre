// ============================================================
// E-LEARNING ADMIN JAVASCRIPT
// ============================================================

const API_URL = "../admin-php/elearning.php";

let courses = [];
let editingId = null;


// ============================================================
// FILTER VARIASI MODUL
// ============================================================

let activeModule = "Semua Modul";
let currentSearch = "";

const MODULES = [
    "Semua Modul",
    "Front-End",
    "Back-End",
    "UI/UX",
    "Design",
    "Data & AI",
    "Mobile Dev"
];


// ============================================================
// SIDEBAR
// ============================================================

function toggleSidebar() {

    const sidebar =
        document.getElementById("sidebar");

    const overlay =
        document.querySelector(".overlay");


    if (!sidebar) return;


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
        document.getElementById("sidebar");

    const overlay =
        document.querySelector(".overlay");


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


// ============================================================
// PROMO DATA
// ============================================================

function getPromoData(course) {

    const hargaNormal =
        Number(course.harga) || 0;


    const promoAktif =
        Number(
            course.promo_aktif
        ) === 1 ||
        course.promo_aktif === true ||
        course.promo_aktif === "1";


    /*
        JANGAN menggunakan:

        Number(course.harga_promo) || 0

        untuk menentukan apakah field kosong.

        Karena 0 adalah harga yang valid.
    */

    let hargaPromo = 0;


    if (
        course.harga_promo !== null &&
        course.harga_promo !== undefined &&
        course.harga_promo !== ""
    ) {

        hargaPromo =
            Number(
                course.harga_promo
            );

    }


    if (
        isNaN(hargaPromo)
    ) {

        hargaPromo =
            0;

    }


    let diskon = 0;


    // ========================================================
    // HARGA NORMAL GRATIS
    // ========================================================

    if (
        hargaNormal <= 0
    ) {

        diskon = 100;

    }


    // ========================================================
    // PROMO AKTIF + PROMO 0
    // ========================================================

    else if (
        promoAktif &&
        hargaPromo === 0
    ) {

        diskon = 100;

    }


    // ========================================================
    // PROMO NORMAL
    // ========================================================

    else if (
        promoAktif &&
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


// ============================================================
// LOAD COURSE
// ============================================================

async function loadCourses() {

    const grid =
        document.getElementById(
            "courseGrid"
        );


    if (!grid) return;


    grid.innerHTML = `
        <div class="course-message">
            Memuat data course...
        </div>
    `;


    try {

        const response =
            await fetch(
                API_URL,
                {
                    method:
                        "GET",

                    cache:
                        "no-cache"
                }
            );


        if (!response.ok) {

            throw new Error(
                "Server mengembalikan status " +
                response.status
            );

        }


        const result =
            await response.json();


        console.log(
            "Data dari PHP:",
            result
        );


        if (
            result.success === false
        ) {

            throw new Error(
                result.message ||
                "Gagal mengambil data."
            );

        }


        if (
            Array.isArray(
                result
            )
        ) {

            courses =
                result;

        }

        else {

            courses =
                result.data ||
                [];

        }


        // ====================================================
        // CEK DATA COURSE
        // ====================================================

        console.log(
            "Data course:",
            courses
        );


        console.log(
            "Subkategori course:",
            courses.map(
                function (
                    course
                ) {

                    return {

                        id:
                            course.id,

                        nama:
                            course.nama_produk,

                        subkategori:
                            course.subkategori,

                        benefit:
                            course.benefit,

                        harga:
                            course.harga,

                        harga_promo:
                            course.harga_promo,

                        promo_aktif:
                            course.promo_aktif

                    };

                }
            )
        );


        // ====================================================
        // TAMPILKAN COURSE
        // ====================================================

        applyCourseFilters();


        // ====================================================
        // JUMLAH COURSE
        // ====================================================

        const courseCount =
            document.getElementById(
                "courseCount"
            );


        if (courseCount) {

            courseCount.textContent =
                courses.length;

        }


        // ====================================================
        // STATUS DATABASE
        // ====================================================

        const databaseStatus =
            document.getElementById(
                "databaseStatus"
            );


        if (databaseStatus) {

            databaseStatus.textContent =
                "Online";


            databaseStatus.style.color =
                "#16a34a";

        }


    }

    catch (error) {

        console.error(
            "LOAD COURSE ERROR:",
            error
        );


        const databaseStatus =
            document.getElementById(
                "databaseStatus"
            );


        if (databaseStatus) {

            databaseStatus.textContent =
                "Offline";


            databaseStatus.style.color =
                "#dc2626";

        }


        grid.innerHTML = `
            <div class="course-message">

                <strong>
                    Gagal mengambil data course
                </strong>

                <br>

                ${escapeHTML(
                    error.message
                )}

                <br><br>

                Pastikan PHP dan database
                sudah berjalan.

            </div>
        `;

    }

}


// ============================================================
// FILTER COURSE
// ============================================================

function applyCourseFilters() {

    let filtered =
        [
            ...courses
        ];


    // ========================================================
    // FILTER VARIASI MODUL
    // ========================================================

    if (
        activeModule &&
        activeModule !== "Semua Modul"
    ) {

        filtered =
            filtered.filter(
                function (
                    course
                ) {

                    const subkategori =
                        String(
                            course.subkategori ||
                            ""
                        )
                            .trim()
                            .toLowerCase();


                    const filter =
                        String(
                            activeModule ||
                            ""
                        )
                            .trim()
                            .toLowerCase();


                    return (
                        subkategori ===
                        filter
                    );

                }
            );

    }


    // ========================================================
    // FILTER SEARCH
    // ========================================================

    if (
        currentSearch
    ) {

        filtered =
            filtered.filter(
                function (
                    course
                ) {

                    const nama =
                        String(
                            course.nama_produk ||
                            ""
                        )
                            .toLowerCase();


                    const deskripsi =
                        String(
                            course.deskripsi ||
                            ""
                        )
                            .toLowerCase();


                    const durasi =
                        String(
                            course.durasi ||
                            ""
                        )
                            .toLowerCase();


                    const jadwal =
                        String(
                            course.jadwal ||
                            ""
                        )
                            .toLowerCase();


                    const subkategori =
                        String(
                            course.subkategori ||
                            ""
                        )
                            .toLowerCase();


                    const benefit =
                        String(
                            course.benefit ||
                            ""
                        )
                            .toLowerCase();


                    return (

                        nama.includes(
                            currentSearch
                        ) ||

                        deskripsi.includes(
                            currentSearch
                        ) ||

                        durasi.includes(
                            currentSearch
                        ) ||

                        jadwal.includes(
                            currentSearch
                        ) ||

                        subkategori.includes(
                            currentSearch
                        ) ||

                        benefit.includes(
                            currentSearch
                        )

                    );

                }
            );

    }


    // ========================================================
    // RENDER
    // ========================================================

    renderCourses(
        filtered
    );

}


// ============================================================
// SETUP FILTER VARIASI MODUL
// ============================================================

function setupModuleFilters() {

    const buttons =
        document.querySelectorAll(
            ".module-filter-btn"
        );


    if (
        !buttons.length
    ) {

        console.log(
            "Tombol variasi modul tidak ditemukan."
        );

        return;

    }


    buttons.forEach(
        function (
            button
        ) {

            button.addEventListener(
                "click",
                function (
                    event
                ) {

                    event.preventDefault();


                    // =========================================
                    // FILTER
                    // =========================================

                    const filter =
                        button.dataset.filter ||
                        button.textContent.trim();


                    if (
                        !MODULES.includes(
                            filter
                        )
                    ) {

                        return;

                    }


                    // =========================================
                    // SET FILTER
                    // =========================================

                    activeModule =
                        filter;


                    // =========================================
                    // BUTTON AKTIF
                    // =========================================

                    buttons.forEach(
                        function (
                            btn
                        ) {

                            btn.classList.remove(
                                "active"
                            );

                        }
                    );


                    button.classList.add(
                        "active"
                    );


                    // =========================================
                    // FILTER
                    // =========================================

                    applyCourseFilters();

                }
            );

        }
    );

}


// ============================================================
// RENDER COURSE
// ============================================================

function renderCourses(
    data
) {

    const grid =
        document.getElementById(
            "courseGrid"
        );


    if (!grid) return;


    // ========================================================
    // TIDAK ADA DATA
    // ========================================================

    if (
        !Array.isArray(data) ||
        data.length === 0
    ) {

        let message =
            "Belum ada course.";


        if (
            activeModule &&
            activeModule !== "Semua Modul"
        ) {

            message =
                `Belum ada course untuk variasi "${escapeHTML(
                    activeModule
                )}".`;

        }

        else if (
            currentSearch
        ) {

            message =
                "Course yang dicari tidak ditemukan.";

        }


        grid.innerHTML = `

            <div class="course-message">

                <strong>
                    ${message}
                </strong>

                <br><br>

                ${
                    activeModule !==
                    "Semua Modul"

                    ?

                    "Tambahkan course dengan variasi modul tersebut."

                    :

                    'Tambahkan course E-Learning menggunakan tombol "Tambah Course".'
                }

            </div>

        `;

        return;

    }


    // ========================================================
    // RENDER CARD
    // ========================================================

    grid.innerHTML =
        data.map(
            function (
                course
            ) {


                // =================================================
                // GAMBAR
                // =================================================

                let gambar =
                    "";


                if (
                    course.gambar &&
                    String(
                        course.gambar
                    ).trim() !== ""
                ) {

                    let imagePath =
                        String(
                            course.gambar
                        ).trim();


                    if (
                        typeof window.resolveImagePath ===
                        "function"
                    ) {

                        gambar =
                            window.resolveImagePath(
                                imagePath
                            );

                    }

                    else if (
                        imagePath.startsWith(
                            "../"
                        )
                    ) {

                        gambar =
                            imagePath;

                    }

                    else {

                        gambar =
                            "../" +
                            imagePath;

                    }

                }


                // =================================================
                // DATA DASAR
                // =================================================

                const nama =
                    escapeHTML(
                        course.nama_produk ||
                        "Tanpa Nama"
                    );


                const deskripsi =
                    escapeHTML(
                        course.deskripsi ||
                        "Tidak ada deskripsi."
                    );


                // =================================================
                // PROMO
                // =================================================

                const promo =
                    getPromoData(
                        course
                    );


                let hargaHTML =
                    "";


                // =================================================
                // GRATIS
                // =================================================

                if (
                    promo.hargaNormal <= 0
                ) {

                    hargaHTML = `

                        <div
                            class="course-price"
                            style="
                                color:#16a34a;
                                font-size:15px;
                                font-weight:800;
                            "
                        >

                            Gratis

                        </div>

                    `;

                }

                else if (
                    promo.aktif &&
                    promo.hargaPromo === 0
                ) {

                    hargaHTML = `

                        <div
                            style="
                                display:flex;
                                flex-direction:column;
                                gap:2px;
                                align-items:flex-start;
                            "
                        >

                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    gap:6px;
                                    flex-wrap:wrap;
                                "
                            >

                                <span
                                    style="
                                        color:#94a3b8;
                                        font-size:10px;
                                        text-decoration:line-through;
                                        font-weight:500;
                                    "
                                >

                                    ${formatRupiah(
                                        promo.hargaNormal
                                    )}

                                </span>


                                <span
                                    style="
                                        display:inline-flex;
                                        align-items:center;
                                        padding:3px 6px;
                                        border-radius:5px;
                                        background:#dcfce7;
                                        color:#16a34a;
                                        font-size:9px;
                                        font-weight:700;
                                    "
                                >

                                    100% OFF

                                </span>

                            </div>


                            <span
                                style="
                                    color:#16a34a;
                                    font-size:15px;
                                    font-weight:800;
                                "
                            >

                                Gratis

                            </span>

                        </div>

                    `;

                }

                // =================================================
                // PROMO NORMAL
                // =================================================

                else if (
                    promo.aktif &&
                    promo.hargaPromo > 0 &&
                    promo.hargaPromo <
                    promo.hargaNormal
                ) {

                    hargaHTML = `

                        <div
                            style="
                                display:flex;
                                flex-direction:column;
                                gap:2px;
                                align-items:flex-start;
                            "
                        >

                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    gap:6px;
                                    flex-wrap:wrap;
                                "
                            >

                                <span
                                    style="
                                        color:#94a3b8;
                                        font-size:10px;
                                        text-decoration:line-through;
                                        font-weight:500;
                                    "
                                >

                                    ${formatRupiah(
                                        promo.hargaNormal
                                    )}

                                </span>


                                <span
                                    style="
                                        display:inline-flex;
                                        align-items:center;
                                        padding:3px 6px;
                                        border-radius:5px;
                                        background:#dcfce7;
                                        color:#16a34a;
                                        font-size:9px;
                                        font-weight:700;
                                    "
                                >

                                    ${promo.diskon}% OFF

                                </span>

                            </div>


                            <span
                                style="
                                    color:#2563eb;
                                    font-size:15px;
                                    font-weight:800;
                                "
                            >

                                ${formatRupiah(
                                    promo.hargaPromo
                                )}

                            </span>

                        </div>

                    `;

                }

                // =================================================
                // HARGA NORMAL
                // =================================================

                else {

                    hargaHTML = `

                        <div
                            class="course-price"
                        >

                            ${formatRupiah(
                                promo.hargaNormal
                            )}

                        </div>

                    `;

                }


                // =================================================
                // SUBKATEGORI
                // =================================================

                const subkategoriRaw =
                    course.subkategori ||
                    "";


                const subkategori =
                    escapeHTML(
                        subkategoriRaw ||
                        "Belum ditentukan"
                    );


                // =================================================
                // DURASI
                // =================================================

                const durasi =
                    escapeHTML(
                        course.durasi ||
                        ""
                    );


                // =================================================
                // JADWAL
                // =================================================

                const jadwal =
                    escapeHTML(
                        course.jadwal ||
                        ""
                    );


                // =================================================
                // BENEFIT
                // =================================================

                const benefitHTML =
                    createBenefitHTML(
                        course.benefit
                    );


                // =================================================
                // IMAGE
                // =================================================

                let imageHTML =
                    "";


                if (
                    gambar
                ) {

                    imageHTML = `

                        <img
                            src="${escapeAttribute(
                                gambar
                            )}"
                            alt="${escapeAttribute(
                                nama
                            )}"
                            onerror="imageError(this)"
                        >

                    `;

                }

                else {

                    imageHTML = `

                        <div
                            class="course-no-image"
                            style="
                                width:100%;
                                height:100%;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                background:#eff6ff;
                                color:#2563eb;
                                font-size:13px;
                                font-weight:700;
                            "
                        >

                            Belum ada gambar

                        </div>

                    `;

                }


                // =================================================
                // CARD
                // =================================================

                return `

                    <div
                        class="course-card"
                        data-subkategori="${escapeAttribute(
                            subkategoriRaw
                        )}"
                    >

                        <!-- =====================================
                             GAMBAR
                        ====================================== -->

                        <div
                            class="course-image"
                        >

                            ${imageHTML}


                            <span
                                class="course-category"
                            >

                                E-Learning

                            </span>

                        </div>


                        <!-- =====================================
                             BODY
                        ====================================== -->

                        <div
                            class="course-body"
                        >


                            <!-- =================================
                                 SUBKATEGORI
                            ================================== -->

                            <div
                                style="
                                    display:flex;
                                    align-items:center;
                                    gap:6px;
                                    margin-bottom:8px;
                                    flex-wrap:wrap;
                                "
                            >

                                <span
                                    style="
                                        display:inline-flex;
                                        align-items:center;
                                        padding:5px 9px;
                                        border-radius:7px;
                                        background:#eef2ff;
                                        color:#4338ca;
                                        font-size:10px;
                                        font-weight:700;
                                    "
                                >

                                    ${subkategori}

                                </span>

                            </div>


                            <!-- =================================
                                 NAMA COURSE
                            ================================== -->

                            <h3
                                class="course-name"
                            >

                                ${nama}

                            </h3>


                            <!-- =================================
                                 DESKRIPSI
                            ================================== -->

                            <p
                                class="course-description"
                            >

                                ${deskripsi}

                            </p>

                            <div class="course-data-facts">
                                <span>ID #${Number(course.id)}</span>
                                <span>Status ${escapeHTML(course.status || "-")}</span>
                                <span>Instruktur ${course.instructor ? escapeHTML(course.instructor) : "Belum ditautkan"}</span>
                                <span>${Number(course.participant_count || 0)} peserta</span>
                                <span>${Number(course.transaction_count || 0)} transaksi</span>
                                <span>${formatRupiah(course.revenue || 0)} pemasukan</span>
                                <span>Dibuat ${course.created_at ? new Date(String(course.created_at).replace(" ", "T")).toLocaleDateString("id-ID", {day:"numeric", month:"long", year:"numeric"}) : "-"}</span>
                            </div>


                            <!-- =================================
                                 DURASI + JADWAL
                            ================================== -->

                            <div
                                style="
                                    display:flex;
                                    flex-wrap:wrap;
                                    gap:6px;
                                    margin-top:10px;
                                "
                            >

                                ${
                                    course.durasi

                                    ?

                                    `

                                        <span
                                            style="
                                                display:inline-flex;
                                                align-items:center;
                                                padding:5px 8px;
                                                border-radius:6px;
                                                background:#f1f5f9;
                                                color:#475569;
                                                font-size:10px;
                                                font-weight:600;
                                                white-space:nowrap;
                                            "
                                        >

                                            ⏱
                                            ${durasi}

                                        </span>

                                    `

                                    :

                                    ""
                                }


                                ${
                                    course.jadwal

                                    ?

                                    `

                                        <span
                                            style="
                                                display:inline-flex;
                                                align-items:center;
                                                padding:5px 8px;
                                                border-radius:6px;
                                                background:#f1f5f9;
                                                color:#475569;
                                                font-size:10px;
                                                font-weight:600;
                                                white-space:nowrap;
                                            "
                                        >

                                            ◷
                                            ${jadwal}

                                        </span>

                                    `

                                    :

                                    ""
                                }

                            </div>


                            <!-- =================================
                                 BENEFIT
                            ================================== -->

                            ${benefitHTML}


                            <!-- =================================
                                 FOOTER
                            ================================== -->

                            <div
                                class="course-footer"
                            >


                                <!-- HARGA -->

                                ${hargaHTML}


                                <!-- ACTION -->

                                <div
                                    class="course-actions"
                                >


                                    <button
                                        type="button"
                                        class="course-action edit-btn"
                                        onclick="editCourse(${Number(
                                            course.id
                                        )})"
                                    >

                                        Edit

                                    </button>


                                    <button
                                        type="button"
                                        class="course-action delete-btn"
                                        onclick="deleteCourse(${Number(
                                            course.id
                                        )})"
                                    >

                                        Hapus

                                    </button>


                                </div>


                            </div>


                        </div>


                    </div>

                `;

            }
        )
        .join("");

}


// ============================================================
// CREATE BENEFIT HTML
// ============================================================

function createBenefitHTML(
    benefit
) {

    if (
        benefit === null ||
        benefit === undefined ||
        benefit === ""
    ) {

        return "";

    }


    let benefits =
        [];


    // ========================================================
    // ARRAY
    // ========================================================

    if (
        Array.isArray(
            benefit
        )
    ) {

        benefits =
            benefit;

    }

    // ========================================================
    // STRING
    // ========================================================

    else {

        const value =
            String(
                benefit
            ).trim();


        if (!value) {

            return "";

        }


        try {

            const parsed =
                JSON.parse(
                    value
                );


            if (
                Array.isArray(
                    parsed
                )
            ) {

                benefits =
                    parsed;

            }

            else {

                benefits =
                    value.split(",");

            }

        }

        catch (
            error
        ) {

            benefits =
                value.split(",");

        }

    }


    // ========================================================
    // BERSIHKAN
    // ========================================================

    benefits =
        benefits
            .map(
                function (
                    item
                ) {

                    return String(
                        item
                    ).trim();

                }
            )
            .filter(
                function (
                    item
                ) {

                    return (
                        item !== ""
                    );

                }
            );


    if (
        benefits.length === 0
    ) {

        return "";

    }


    // ========================================================
    // MAKSIMAL 3
    // ========================================================

    benefits =
        benefits.slice(
            0,
            3
        );


    // ========================================================
    // HTML
    // ========================================================

    return `

        <div
            class="course-benefits"
            style="
                width:100%;
                display:flex;
                flex-wrap:wrap;
                gap:6px;
                margin-top:8px;
                align-items:center;
            "
        >

            ${benefits.map(
                function (
                    item
                ) {

                    return `

                        <span
                            class="benefit-chip"
                            style="
                                display:inline-flex;
                                align-items:center;
                                gap:4px;
                                max-width:100%;
                                box-sizing:border-box;
                                padding:5px 8px;
                                border-radius:6px;
                                background:#eff6ff;
                                color:#2563eb;
                                font-size:10px;
                                font-weight:600;
                                line-height:1.3;
                                white-space:nowrap;
                                overflow:hidden;
                                text-overflow:ellipsis;
                            "
                        >

                            ✓
                            ${escapeHTML(
                                item
                            )}

                        </span>

                    `;

                }
            ).join("")}

        </div>

    `;

}


// ============================================================
// OPEN MODAL
// ============================================================

function openCourseModal() {

    editingId =
        null;


    const modal =
        document.getElementById(
            "courseModal"
        );


    const form =
        document.getElementById(
            "courseForm"
        );


    if (form) {

        form.reset();

    }


    const courseId =
        document.getElementById(
            "courseId"
        );


    if (courseId) {

        courseId.value =
            "";

    }


    const kategori =
        document.getElementById(
            "kategori"
        );


    if (kategori) {

        kategori.value =
            "E-Learning";

    }


    const subkategori =
        document.getElementById(
            "subkategori"
        );


    if (subkategori) {

        subkategori.value =
            "";

    }


    const modalTitle =
        document.getElementById(
            "modalTitle"
        );


    if (modalTitle) {

        modalTitle.textContent =
            "Tambah Course";

    }


    const saveButton =
        document.querySelector(
            ".save-btn"
        );


    if (saveButton) {

        saveButton.textContent =
            "Simpan Course";

    }


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

        preview.style.display =
            "none";

    }


    // ========================================================
    // RESET BENEFIT
    // ========================================================

    const benefitInput =
        document.getElementById(
            "benefitInput"
        );


    if (benefitInput) {

        benefitInput.value =
            "";

    }


    const benefitTags =
        document.getElementById(
            "benefitTags"
        );


    if (benefitTags) {

        benefitTags.innerHTML =
            "";

    }


    // ========================================================
    // RESET PROMO
    // ========================================================

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


    const discountInfo =
        document.getElementById(
            "discountInfo"
        );


    if (promoAktif) {

        promoAktif.checked =
            false;

    }


    if (promoFields) {

        promoFields.classList.remove(
            "show"
        );

    }


    if (hargaPromo) {

        hargaPromo.value =
            "0";

        /*
            0 boleh digunakan.
        */

        hargaPromo.min =
            "0";

    }


    if (discountInfo) {

        discountInfo.textContent =
            "Diskon 0%";

        discountInfo.style.color =
            "#16a34a";

    }


    if (modal) {

        modal.classList.add(
            "show"
        );

    }

}


// ============================================================
// CLOSE MODAL
// ============================================================

function closeCourseModal() {

    const modal =
        document.getElementById(
            "courseModal"
        );


    if (modal) {

        modal.classList.remove(
            "show"
        );

    }


    editingId =
        null;

}


// ============================================================
// EDIT COURSE
// ============================================================

async function editCourse(
    id
) {

    try {

        const response =
            await fetch(
                `${API_URL}?id=${encodeURIComponent(
                    id
                )}`,
                {
                    method:
                        "GET",

                    cache:
                        "no-cache"
                }
            );


        if (!response.ok) {

            throw new Error(
                "Gagal mengambil data course."
            );

        }


        const result =
            await response.json();


        console.log(
            "Data edit:",
            result
        );


        let course;


        if (
            Array.isArray(
                result
            )
        ) {

            course =
                result[0];

        }

        else {

            course =
                result.data ||
                result;

        }


        if (
            Array.isArray(
                course
            )
        ) {

            course =
                course[0];

        }


        if (
            !course ||
            !course.id
        ) {

            throw new Error(
                "Data course tidak ditemukan."
            );

        }


        // ====================================================
        // ID
        // ====================================================

        editingId =
            course.id;


        const idInput =
            document.getElementById(
                "courseId"
            );


        if (idInput) {

            idInput.value =
                course.id;

        }


        // ====================================================
        // NAMA
        // ====================================================

        const namaCourse =
            document.getElementById(
                "namaCourse"
            );


        if (namaCourse) {

            namaCourse.value =
                course.nama_produk ||
                "";

        }


        // ====================================================
        // HARGA
        // ====================================================

        const harga =
            document.getElementById(
                "harga"
            );


        if (harga) {

            /*
                Jangan pakai:

                course.harga || 0

                karena 0 harus tetap valid.
            */

            harga.value =
                (
                    course.harga !== null &&
                    course.harga !== undefined
                )
                    ?
                    course.harga
                    :
                    0;

        }


        // ====================================================
        // PROMO
        // ====================================================

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


        const discountInfo =
            document.getElementById(
                "discountInfo"
            );


        const isPromoAktif =
            Number(
                course.promo_aktif
            ) === 1 ||
            course.promo_aktif === true ||
            course.promo_aktif === "1";


        if (promoAktif) {

            promoAktif.checked =
                isPromoAktif;

        }


        if (
            promoFields &&
            isPromoAktif
        ) {

            promoFields.classList.add(
                "show"
            );

        }

        else if (
            promoFields
        ) {

            promoFields.classList.remove(
                "show"
            );

        }


        if (
            hargaPromo
        ) {

            hargaPromo.min =
                "0";


            /*
                Jangan pakai:

                course.harga_promo || ""

                karena harga_promo = 0
                harus tetap ditampilkan sebagai 0.
            */

            if (
                isPromoAktif
            ) {

                if (
                    course.harga_promo !== null &&
                    course.harga_promo !== undefined &&
                    course.harga_promo !== ""
                ) {

                    hargaPromo.value =
                        course.harga_promo;

                }

                else {

                    hargaPromo.value =
                        "0";

                }

            }

            else {

                hargaPromo.value =
                    "";

            }

        }


        // ====================================================
        // HITUNG DISKON EDIT
        // ====================================================

        if (
            discountInfo &&
            isPromoAktif
        ) {

            const hargaNormal =
                Number(
                    course.harga
                ) || 0;


            let hargaPromoValue =
                0;


            if (
                course.harga_promo !== null &&
                course.harga_promo !== undefined &&
                course.harga_promo !== ""
            ) {

                hargaPromoValue =
                    Number(
                        course.harga_promo
                    );

            }


            // ==============================================
            // NORMAL GRATIS
            // ==============================================

            if (
                hargaNormal <= 0
            ) {

                discountInfo.textContent =
                    "Gratis";


                discountInfo.style.color =
                    "#16a34a";

            }

            // ==============================================
            // PROMO GRATIS
            // ==============================================

            else if (
                hargaPromoValue === 0
            ) {

                discountInfo.textContent =
                    "Gratis (Diskon 100%)";


                discountInfo.style.color =
                    "#16a34a";

            }

            // ==============================================
            // PROMO NORMAL
            // ==============================================

            else if (
                hargaPromoValue > 0 &&
                hargaPromoValue <
                hargaNormal
            ) {

                const diskon =
                    Math.round(
                        (
                            (
                                hargaNormal -
                                hargaPromoValue
                            ) /
                            hargaNormal
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

            else {

                discountInfo.textContent =
                    "Harga promo harus lebih kecil dari harga normal.";


                discountInfo.style.color =
                    "#dc2626";

            }

        }

        else if (
            discountInfo
        ) {

            discountInfo.textContent =
                "Diskon 0%";


            discountInfo.style.color =
                "#16a34a";

        }


        // ====================================================
        // DESKRIPSI
        // ====================================================

        const deskripsi =
            document.getElementById(
                "deskripsi"
            );


        if (deskripsi) {

            deskripsi.value =
                course.deskripsi ||
                "";

        }


        // ====================================================
        // KATEGORI
        // ====================================================

        const kategori =
            document.getElementById(
                "kategori"
            );


        if (kategori) {

            kategori.value =
                course.kategori ||
                "E-Learning";

        }


        // ====================================================
        // SUBKATEGORI
        // ====================================================

        const subkategori =
            document.getElementById(
                "subkategori"
            );


        if (subkategori) {

            subkategori.value =
                course.subkategori ||
                "";

        }


        // ====================================================
        // DURASI
        // ====================================================

        const durasi =
            document.getElementById(
                "durasi"
            );


        if (durasi) {

            durasi.value =
                course.durasi ||
                "";

        }


        // ====================================================
        // JADWAL
        // ====================================================

        const jadwal =
            document.getElementById(
                "jadwal"
            );


        if (jadwal) {

            jadwal.value =
                course.jadwal ||
                "";

        }


        // ====================================================
        // BENEFIT
        // ====================================================

        const benefitInput =
            document.getElementById(
                "benefitInput"
            );


        if (benefitInput) {

            let benefitValue =
                course.benefit ||
                "";


            if (
                Array.isArray(
                    benefitValue
                )
            ) {

                benefitValue =
                    benefitValue.join(
                        ","
                    );

            }


            benefitInput.value =
                benefitValue;

        }


        // ====================================================
        // GAMBAR
        // ====================================================

        const gambarInput =
            document.getElementById(
                "gambar"
            );


        if (gambarInput) {

            gambarInput.value =
                "";

        }


        const preview =
            document.getElementById(
                "imagePreview"
            );


        if (
            preview &&
            course.gambar
        ) {

            let previewPath =
                String(
                    course.gambar
                );


            if (
                typeof window.resolveImagePath ===
                "function"
            ) {

                previewPath =
                    window.resolveImagePath(
                        previewPath
                    );

            }

            else if (
                !previewPath.startsWith(
                    "../"
                )
            ) {

                previewPath =
                    "../" +
                    previewPath;

            }


            preview.src =
                previewPath;


            preview.classList.add(
                "show"
            );


            preview.style.display =
                "block";

        }

        else if (
            preview
        ) {

            preview.src =
                "";

            preview.classList.remove(
                "show"
            );

            preview.style.display =
                "none";

        }


        // ====================================================
        // MODAL TITLE
        // ====================================================

        const modalTitle =
            document.getElementById(
                "modalTitle"
            );


        if (modalTitle) {

            modalTitle.textContent =
                "Edit Course";

        }


        const saveButton =
            document.querySelector(
                ".save-btn"
            );


        if (saveButton) {

            saveButton.textContent =
                "Perbarui Course";

        }


        const modal =
            document.getElementById(
                "courseModal"
            );


        if (modal) {

            modal.classList.add(
                "show"
            );

        }


    }

    catch (error) {

        console.error(
            "EDIT COURSE ERROR:",
            error
        );


        alert(
            "Gagal mengambil data course: " +
            error.message
        );

    }

}


// ============================================================
// SIMPAN / UPDATE COURSE
// ============================================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const form =
            document.getElementById(
                "courseForm"
            );


        if (!form) return;


        form.addEventListener(
            "submit",
            async function (
                event
            ) {

                event.preventDefault();


                // =================================================
                // DATA
                // =================================================

                const namaProduk =
                    document.getElementById(
                        "namaCourse"
                    )?.value.trim() ||
                    "";


                const deskripsi =
                    document.getElementById(
                        "deskripsi"
                    )?.value.trim() ||
                    "";


                const harga =
                    document.getElementById(
                        "harga"
                    )?.value ||
                    "";


                const subkategori =
                    document.getElementById(
                        "subkategori"
                    )?.value.trim() ||
                    "";


                const durasi =
                    document.getElementById(
                        "durasi"
                    )?.value.trim() ||
                    "";


                const jadwal =
                    document.getElementById(
                        "jadwal"
                    )?.value.trim() ||
                    "";


                const benefit =
                    document.getElementById(
                        "benefitInput"
                    )?.value.trim() ||
                    "";


                const gambarInput =
                    document.getElementById(
                        "gambar"
                    );


                const gambar =
                    gambarInput?.files[0];


                // =================================================
                // PROMO
                // =================================================

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


                // =================================================
                // VALIDASI NAMA
                // =================================================

                if (
                    !namaProduk
                ) {

                    alert(
                        "Nama course harus diisi."
                    );

                    return;

                }


                // =================================================
                // VALIDASI DESKRIPSI
                // =================================================

                if (
                    !deskripsi
                ) {

                    alert(
                        "Deskripsi course harus diisi."
                    );

                    return;

                }


                // =================================================
                // VALIDASI HARGA
                // =================================================

                /*
                    Harga normal BOLEH 0.

                    0 = GRATIS
                */

                if (
                    harga === "" ||
                    harga === null ||
                    isNaN(
                        Number(harga)
                    ) ||
                    Number(harga) < 0
                ) {

                    alert(
                        "Harga course tidak valid."
                    );

                    return;

                }


                // =================================================
                // VALIDASI SUBKATEGORI
                // =================================================

                if (
                    !subkategori
                ) {

                    alert(
                        "Variasi modul harus dipilih."
                    );

                    return;

                }


                // =================================================
                // VALIDASI PROMO
                // =================================================

                if (
                    promoAktif === 1
                ) {

                    /*
                        Harga promo boleh 0.

                        Yang tidak boleh:
                        - kosong
                        - huruf
                        - negatif
                    */

                    if (
                        hargaPromo === "" ||
                        hargaPromo === null ||
                        isNaN(
                            Number(
                                hargaPromo
                            )
                        ) ||
                        Number(
                            hargaPromo
                        ) < 0
                    ) {

                        alert(
                            "Harga promo tidak valid."
                        );

                        return;

                    }


                    /*
                        Promo 0 = GRATIS
                    */

                    if (
                        Number(
                            hargaPromo
                        ) === 0
                    ) {

                        // Tidak perlu validasi
                        // hargaPromo >= harga.

                    }

                    /*
                        Kalau harga normal 0,
                        promo tidak perlu lebih kecil.

                        Karena course sudah gratis.
                    */

                    else if (
                        Number(
                            harga
                        ) === 0
                    ) {

                        // Tetap izinkan.

                    }

                    /*
                        Promo normal
                    */

                    else if (
                        Number(
                            hargaPromo
                        ) >=
                        Number(
                            harga
                        )
                    ) {

                        alert(
                            "Harga promo harus lebih kecil dari harga normal."
                        );

                        return;

                    }

                }


                // =================================================
                // FORMDATA
                // =================================================

                const formData =
                    new FormData();


                formData.append(
                    "nama_produk",
                    namaProduk
                );


                formData.append(
                    "kategori",
                    "E-Learning"
                );


                formData.append(
                    "subkategori",
                    subkategori
                );


                formData.append(
                    "deskripsi",
                    deskripsi
                );


                formData.append(
                    "harga",
                    harga
                );


                // =================================================
                // PROMO DATABASE
                // =================================================

                formData.append(
                    "promo_aktif",
                    promoAktif
                );


                formData.append(
                    "harga_promo",
                    promoAktif === 1
                        ? hargaPromo
                        : "0"
                );


                formData.append(
                    "durasi",
                    durasi
                );


                formData.append(
                    "jadwal",
                    jadwal
                );


                formData.append(
                    "benefit",
                    benefit
                );


                // =================================================
                // GAMBAR
                // =================================================

                if (
                    gambar
                ) {

                    formData.append(
                        "gambar",
                        gambar
                    );

                }


                // =================================================
                // UPDATE
                // =================================================

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


                // =================================================
                // BUTTON
                // =================================================

                const saveButton =
                    form.querySelector(
                        ".save-btn"
                    );


                const originalText =
                    saveButton
                        ?
                        saveButton.textContent
                        :
                        "Simpan Course";


                if (
                    saveButton
                ) {

                    saveButton.disabled =
                        true;


                    saveButton.textContent =
                        editingId
                            ?
                            "Memperbarui..."
                            :
                            "Menyimpan...";

                }


                // =================================================
                // REQUEST
                // =================================================

                try {

                    const response =
                        await fetch(
                            API_URL,
                            {

                                method:
                                    "POST",

                                body:
                                    formData

                            }
                        );


                    if (
                        !response.ok
                    ) {

                        throw new Error(
                            "Server error: " +
                            response.status
                        );

                    }


                    const result =
                        await response.json();


                    console.log(
                        "Response simpan:",
                        result
                    );


                    if (
                        result.success === false
                    ) {

                        throw new Error(
                            result.message ||
                            "Gagal menyimpan course."
                        );

                    }


                    alert(
                        editingId
                            ?
                            "Course berhasil diperbarui!"
                            :
                            "Course berhasil ditambahkan!"
                    );


                    closeCourseModal();


                    await loadCourses();


                }

                catch (
                    error
                ) {

                    console.error(
                        "SAVE COURSE ERROR:",
                        error
                    );


                    alert(
                        "Gagal menyimpan course: " +
                        error.message
                    );

                }

                finally {

                    if (
                        saveButton
                    ) {

                        saveButton.disabled =
                            false;


                        saveButton.textContent =
                            originalText;

                    }

                }

            }
        );

    }
);


// ============================================================
// HAPUS COURSE
// ============================================================

async function deleteCourse(
    id
) {

    const yakin =
        confirm(
            "Yakin ingin menghapus course ini?"
        );


    if (
        !yakin
    ) return;


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
                API_URL,
                {

                    method:
                        "POST",

                    body:
                        formData

                }
            );


        if (
            !response.ok
        ) {

            throw new Error(
                "Server error: " +
                response.status
            );

        }


        const result =
            await response.json();


        console.log(
            "Response delete:",
            result
        );


        if (
            result.success === false
        ) {

            throw new Error(
                result.message ||
                "Gagal menghapus course."
            );

        }


        alert(
            "Course berhasil dihapus."
        );


        await loadCourses();


    }

    catch (
        error
    ) {

        console.error(
            "DELETE COURSE ERROR:",
            error
        );


        alert(
            "Gagal menghapus course: " +
            error.message
        );

    }

}


// ============================================================
// SEARCH
// ============================================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const searchInput =
            document.getElementById(
                "searchCourse"
            );


        if (!searchInput) return;


        searchInput.addEventListener(
            "input",
            function () {

                currentSearch =
                    this.value
                        .toLowerCase()
                        .trim();


                applyCourseFilters();

            }
        );

    }
);


// ============================================================
// FORMAT RUPIAH
// ============================================================

function formatRupiah(
    value
) {

    const number =
        Number(value) || 0;


    return new Intl.NumberFormat(
        "id-ID",
        {

            style:
                "currency",

            currency:
                "IDR",

            minimumFractionDigits:
                0,

            maximumFractionDigits:
                0

        }
    ).format(
        number
    );

}


// ============================================================
// ESCAPE HTML
// ============================================================

function escapeHTML(
    value
) {

    return String(
        value ?? ""
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


function escapeAttribute(
    value
) {

    return escapeHTML(
        value
    );

}


// ============================================================
// IMAGE ERROR
// ============================================================

function imageError(
    image
) {

    if (
        !image
    ) return;


    image.style.display =
        "none";


    const wrapper =
        image.parentElement;


    if (
        !wrapper
    ) return;


    if (
        wrapper.querySelector(
            ".course-no-image"
        )
    ) {

        return;

    }


    const noImage =
        document.createElement(
            "div"
        );


    noImage.className =
        "course-no-image";


    noImage.style.cssText = `

        width:100%;

        height:100%;

        display:flex;

        align-items:center;

        justify-content:center;

        background:#eff6ff;

        color:#2563eb;

        font-size:13px;

        font-weight:700;

    `;


    noImage.textContent =
        "Gambar tidak tersedia";


    wrapper.appendChild(
        noImage
    );

}


// ============================================================
// ESCAPE + MODAL
// ============================================================

document.addEventListener(
    "keydown",
    function (
        event
    ) {

        if (
            event.key ===
            "Escape"
        ) {

            closeCourseModal();

            closeSidebar();

        }

    }
);


document.addEventListener(
    "click",
    function (
        event
    ) {

        const modal =
            document.getElementById(
                "courseModal"
            );


        if (
            modal &&
            event.target === modal
        ) {

            closeCourseModal();

        }

    }
);


// ============================================================
// PREVIEW GAMBAR
// ============================================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const gambarInput =
            document.getElementById(
                "gambar"
            );


        if (
            !gambarInput
        ) return;


        gambarInput.addEventListener(
            "change",
            function () {

                const file =
                    this.files[0];


                const preview =
                    document.getElementById(
                        "imagePreview"
                    );


                if (
                    !preview
                ) return;


                if (
                    !file
                ) {

                    preview.src =
                        "";

                    preview.classList.remove(
                        "show"
                    );

                    preview.style.display =
                        "none";

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


                    preview.style.display =
                        "none";


                    return;

                }


                if (
                    file.size >
                    5 * 1024 * 1024
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


                    preview.style.display =
                        "none";


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


                        preview.style.display =
                            "block";

                    };


                reader.readAsDataURL(
                    file
                );

            }
        );

    }
);


// ============================================================
// PROMO INPUT + HITUNG DISKON
// ============================================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

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


        const hargaCourse =
            document.getElementById(
                "harga"
            );


        const discountInfo =
            document.getElementById(
                "discountInfo"
            );


        // ====================================================
        // IZINKAN HARGA 0
        // ====================================================

        if (
            hargaCourse
        ) {

            hargaCourse.min =
                "0";

        }


        if (
            hargaPromo
        ) {

            hargaPromo.min =
                "0";

        }


        // ====================================================
        // TOGGLE PROMO
        // ====================================================

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


                        /*
                            Kalau kosong saat promo
                            diaktifkan, isi 0.

                            Jadi admin bisa langsung
                            memasukkan promo gratis.
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


                        if (
                            hargaPromo
                        ) {

                            hargaPromo.value =
                                "";

                        }


                        if (
                            discountInfo
                        ) {

                            discountInfo.textContent =
                                "Diskon 0%";

                            discountInfo.style.color =
                                "#16a34a";

                        }

                    }

                }
            );

        }


        // ====================================================
        // UPDATE DISKON
        // ====================================================

        function updateDiscount() {

            const harga =
                parseFloat(
                    hargaCourse?.value || 0
                );


            /*
                Jangan membuat promo 0 menjadi
                dianggap kosong.
            */

            let promo =
                0;


            if (
                hargaPromo &&
                hargaPromo.value !== ""
            ) {

                promo =
                    Number(
                        hargaPromo.value
                    );

            }


            // ==============================================
            // PROMO TIDAK AKTIF
            // ==============================================

            if (
                !promoAktif ||
                !promoAktif.checked
            ) {

                if (
                    discountInfo
                ) {

                    discountInfo.textContent =
                        "Diskon 0%";


                    discountInfo.style.color =
                        "#16a34a";

                }

                return;

            }


            // ==============================================
            // HARGA INVALID
            // ==============================================

            if (
                isNaN(harga) ||
                harga < 0 ||
                isNaN(promo) ||
                promo < 0
            ) {

                if (
                    discountInfo
                ) {

                    discountInfo.textContent =
                        "Harga tidak valid.";


                    discountInfo.style.color =
                        "#dc2626";

                }

                return;

            }


            // ==============================================
            // HARGA NORMAL 0
            // ==============================================

            if (
                harga === 0
            ) {

                if (
                    discountInfo
                ) {

                    discountInfo.textContent =
                        "Gratis";


                    discountInfo.style.color =
                        "#16a34a";

                }

                return;

            }


            // ==============================================
            // PROMO 0 = GRATIS
            // ==============================================

            if (
                promo === 0
            ) {

                if (
                    discountInfo
                ) {

                    discountInfo.textContent =
                        "Gratis (Diskon 100%)";


                    discountInfo.style.color =
                        "#16a34a";

                }

                return;

            }


            // ==============================================
            // PROMO TERLALU BESAR
            // ==============================================

            if (
                promo >= harga
            ) {

                if (
                    discountInfo
                ) {

                    discountInfo.textContent =
                        "Harga promo harus lebih kecil dari harga normal.";


                    discountInfo.style.color =
                        "#dc2626";

                }

                return;

            }


            // ==============================================
            // HITUNG DISKON
            // ==============================================

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


            if (
                discountInfo
            ) {

                discountInfo.textContent =
                    "Diskon " +
                    diskon +
                    "%";


                discountInfo.style.color =
                    "#16a34a";

            }

        }


        if (
            hargaCourse
        ) {

            hargaCourse.addEventListener(
                "input",
                updateDiscount
            );

        }


        if (
            hargaPromo
        ) {

            hargaPromo.addEventListener(
                "input",
                updateDiscount
            );

        }

    }
);


// ============================================================
// START
// ============================================================

document.addEventListener(
    "DOMContentLoaded",
    function () {

        // Load course
        loadCourses();


        // Aktifkan filter variasi modul
        setupModuleFilters();

    }
);