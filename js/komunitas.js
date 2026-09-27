document.addEventListener("DOMContentLoaded", function () {

    // ==========================================
    // ELEMENT CARD KOMUNITAS
    // ==========================================

    const communityLocked =
        document.querySelectorAll(".community-locked");

    const communityOpen =
        document.querySelectorAll(".community-open");


    // ==========================================
    // MODE BELUM LOGIN
    // ==========================================

    function showGuestCommunity() {

        console.log("KOMUNITAS: GUEST");

        communityLocked.forEach(function (element) {
            element.style.display = "block";
        });

        communityOpen.forEach(function (element) {
            element.style.display = "none";
        });
    }


    // ==========================================
    // MODE SUDAH LOGIN
    // ==========================================

    function showMemberCommunity() {

        console.log("KOMUNITAS: MEMBER");

        communityLocked.forEach(function (element) {
            element.style.display = "none";
        });

        communityOpen.forEach(function (element) {
            element.style.display = "block";
        });
    }


    // ==========================================
    // DEFAULT: CARD TERKUNCI
    // ==========================================

    showGuestCommunity();


    // ==========================================
    // CEK STATUS LOGIN
    // ==========================================

    fetch("../proses/status_login.php", {
        method: "GET",

        credentials: "include",

        cache: "no-store",

        headers: {
            "Accept": "application/json"
        }
    })

    .then(function (response) {

        console.log(
            "Status HTTP:",
            response.status
        );

        if (!response.ok) {
            throw new Error(
                "status_login.php tidak bisa diakses."
            );
        }

        return response.json();
    })

    .then(function (data) {

        console.log(
            "HASIL STATUS LOGIN KOMUNITAS:",
            data
        );


        // ======================================
        // SUDAH LOGIN
        // ======================================

        if (
            data.logged_in === true ||
            data.logged_in === 1 ||
            data.logged_in === "1"
        ) {

            showMemberCommunity();

        }


        // ======================================
        // BELUM LOGIN
        // ======================================

        else {

            showGuestCommunity();

        }

    })

    .catch(function (error) {

        console.error(
            "ERROR CEK LOGIN KOMUNITAS:",
            error
        );

        // Kalau gagal cek login,
        // tetap kunci grup

        showGuestCommunity();

    });

});