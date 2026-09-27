/* ==================================================
   SIDEBAR TOGGLE
================================================== */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('overlay');

  if (sidebar) sidebar.classList.toggle('open');
  if (overlay) overlay.classList.toggle('show');
  document.body.classList.toggle('sidebar-open');
}

/* ==================================================
   HERO AUTO SLIDER
================================================== */
let currentSlide = 0;
const slidesContainer = document.getElementById('heroSlides');
const dots = document.querySelectorAll('.hero-dots .dot');
const totalSlides = dots.length;

function updateSlidePosition() {
  if (!slidesContainer) return;
  slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;

  dots.forEach((dot, index) => {
    dot.classList.toggle('active', index === currentSlide);
  });
}

function nextSlide() {
  if (totalSlides === 0) return;
  currentSlide = (currentSlide + 1) % totalSlides;
  updateSlidePosition();
}

if (totalSlides > 0) {
  let slideInterval = setInterval(nextSlide, 4000);
  const heroSlider = document.querySelector('.hero-slider');
  if (heroSlider) {
    heroSlider.addEventListener('mouseenter', () => clearInterval(slideInterval));
    heroSlider.addEventListener('mouseleave', () => (slideInterval = setInterval(nextSlide, 4000)));
  }
}

/* ==================================================
   MODAL CONTROLLER & PROFILE
================================================== */
function openModal(title) {
  const modal = document.getElementById('modal');
  const modalTitle = document.getElementById('modalTitle');

  if (!modal) return;
  if (modalTitle && title) {
    modalTitle.textContent = title;
  }

  modal.classList.add('show');
}

function closeModal() {
  const modal = document.getElementById('modal');
  if (modal) {
    modal.classList.remove('show');
  }
}

function escapeHTML(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

// Buka Profil & Tampilkan Tombol Keluar di Dalam Modal
function openProfile() {
  const modal = document.getElementById('modal');
  if (!modal) return;

  const nama = localStorage.getItem('belajaryuk_nama');
  const email = localStorage.getItem('belajaryuk_email');

  const modalTitle = document.getElementById('modalTitle');
  const modalBody = modal.querySelector('.modal-box p');
  const modalAction = modal.querySelector('.modal-action');

  if (modalTitle) {
    modalTitle.innerHTML = '👤 Profil Saya';
  }

  if (modalBody) {
    modalBody.innerHTML = `
      <div style="text-align: left; margin-bottom: 20px;">
        <p style="margin-bottom: 8px;"><strong>Nama:</strong> ${escapeHTML(nama || '-')}</p>
        <p style="margin-bottom: 0;"><strong>Email:</strong> ${escapeHTML(email || '-')}</p>
      </div>
    `;
  }

  // Mengubah tombol aksi modal menjadi Tombol Keluar Akun (Logout)
  if (modalAction) {
    modalAction.textContent = 'Keluar Akun';
    modalAction.style.background = '#ef4444'; // Warna merah untuk logout
    modalAction.onclick = logoutUser;
  }

  modal.classList.add('show');
}

function logoutUser() {

  localStorage.removeItem('belajaryuk_login');
  localStorage.removeItem('belajaryuk_nama');
  localStorage.removeItem('belajaryuk_email');

  // Hapus session PHP
  fetch('../proses/logout.php', {
    method: 'GET',
    credentials: 'include',
    cache: 'no-store'
  })
    .then(function () {
      window.location.href = '../pages/index.html';
    })
    .catch(function (error) {
      console.error('ERROR LOGOUT:', error);
      window.location.href = '../pages/index.html';
    });

}

/* ==================================================
   EVENT LISTENER & NAVBAR RENDER
================================================== */
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('modal');

  // Tutup modal saat klik luar box modal (backdrop)
  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        closeModal();
      }
    });
  }

  // Tutup modal saat tombol ESC dipencet
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeModal();
    }
  });

  // Tangani Login Parameter dari URL
  const navActions = document.getElementById('navActions');
  const urlParams = new URLSearchParams(window.location.search);

  if (urlParams.get('login') === 'success') {
    const nama = urlParams.get('nama');
    const email = urlParams.get('email');

    if (nama) {
      localStorage.setItem('belajaryuk_login', 'true');
      localStorage.setItem('belajaryuk_nama', nama);
    }
    if (email) {
      localStorage.setItem('belajaryuk_email', email);
    }

    window.history.replaceState({}, document.title, window.location.pathname);
  }

  // Render Navbar Profil secara seragam
  if (navActions) {
    const sudahLogin = localStorage.getItem('belajaryuk_login');
    const namaUser = localStorage.getItem('belajaryuk_nama');

    if (sudahLogin === 'true' && namaUser) {
      navActions.innerHTML = `
          <a href="#" class="profile-button" onclick="openProfile(); return false;">
              <span class="profile-icon">👤</span>
              <span class="profile-text">
                  <span class="profile-label">Profil</span>
                  <span class="profile-name">${escapeHTML(namaUser)}</span>
              </span>
          </a>
          <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()" aria-label="Menu Navigasi">
              <span></span><span></span><span></span>
          </button>
      `;
    }
  }
});
