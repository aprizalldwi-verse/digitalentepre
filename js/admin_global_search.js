(function () {
    var input = document.getElementById('globalSearch');
    var results = document.getElementById('globalSearchResults');
    if (!input || !results) return;
    var timer;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char];
        });
    }
    input.addEventListener('input', function () {
        clearTimeout(timer);
        var query = input.value.trim();
        if (query.length < 2) { results.hidden = true; return; }
        timer = setTimeout(async function () {
            try {
                var response = await fetch('../admin-php/admin_portal.php?action=search&q=' + encodeURIComponent(query), {headers:{'Accept':'application/json'}});
                var payload = await response.json();
                if (!response.ok || !payload.success) throw new Error(payload.message || 'Pencarian gagal.');
                results.innerHTML = payload.data.length ? payload.data.map(function (item) {
                    var href = item.type === 'user' ? 'user_admin.php' : item.type === 'transaction' ? 'transaksi_admin.php' : item.type === 'bootcamp' ? 'bootcamp_admin.php' : 'elearning_admin.php';
                    return '<a href="'+href+'"><span class="search-type">'+escapeHtml(item.type)+'</span><strong>'+escapeHtml(item.label)+'</strong><small>'+escapeHtml(item.detail)+'</small></a>';
                }).join('') : '<p class="search-no-results">Tidak ditemukan.</p>';
                results.hidden = false;
            } catch (error) {
                results.innerHTML = '<p class="search-no-results">Pencarian tidak tersedia.</p>';
                results.hidden = false;
            }
        }, 250);
    });
    document.addEventListener('click', function (event) { if (!event.target.closest('.topbar-search')) results.hidden = true; });
})();
