(function () {
    var range = document.getElementById('dashboardDateRange');
    if (!range) return;
    var startInput = document.getElementById('dashboardRangeStart');
    var endInput = document.getElementById('dashboardRangeEnd');
    var endpoint = '../admin-php/admin_portal.php';
    var search = document.getElementById('globalSearch');
    var results = document.getElementById('globalSearchResults');
    var searchTimer;
    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char];
        });
    }
    function rupiah(value) { return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits:0}); }
    async function loadSummary() {
        var params = new URLSearchParams({action:'overview',range:range.value});
        if (range.value === 'custom') { params.set('start',startInput.value); params.set('end',endInput.value); }
        var response = await fetch(endpoint + '?' + params.toString(), {headers:{'Accept':'application/json'}});
        var result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Ringkasan tidak dapat dimuat.');
        var data = result.data;
        var users = document.getElementById('totalUsers');
        var courses = document.getElementById('totalProducts');
        var transactions = document.getElementById('totalTransactions');
        var revenue = document.getElementById('totalRevenue');
        if (users) users.textContent = Number(data.users).toLocaleString('id-ID');
        if (courses) courses.textContent = Number(data.courses).toLocaleString('id-ID');
        if (transactions) transactions.textContent = Number(data.transactions).toLocaleString('id-ID');
        if (revenue) revenue.textContent = rupiah(data.income);
        document.getElementById('totalRegistrations').textContent = Number(data.registrations).toLocaleString('id-ID');
        document.getElementById('totalExpenses').textContent = rupiah(data.expenses);
        document.getElementById('netProfit').textContent = rupiah(data.profit);
        var selected = document.getElementById('periodFilter');
        if (selected) selected.value = range.value === 'last7' ? '7' : range.value === 'last30' ? '30' : range.value === 'thisMonth' ? 'month' : range.value === 'thisYear' ? 'year' : '7';
        var chartParams = new URLSearchParams({action:'charts',range:range.value});
        if (range.value === 'custom') { chartParams.set('start',startInput.value); chartParams.set('end',endInput.value); }
        var chartResponse = await fetch(endpoint + '?' + chartParams.toString(), {headers:{'Accept':'application/json'}});
        var chart = await chartResponse.json();
        if (chartResponse.ok && chart.success && typeof createSalesChart === 'function') {
            var days = Object.keys(chart.data);
            createSalesChart(days.map(function(day){return new Date(day+'T00:00:00').toLocaleDateString('id-ID',{day:'numeric',month:'short'});}), days.map(function(day){return chart.data[day].income;}));
        }
        var paymentResponse = await fetch(endpoint + '?' + new URLSearchParams({action:'payments',range:range.value}).toString() + (range.value === 'custom' ? '&start=' + encodeURIComponent(startInput.value) + '&end=' + encodeURIComponent(endInput.value) : ''), {headers:{'Accept':'application/json'}});
        var paymentData = await paymentResponse.json();
        if (paymentResponse.ok && paymentData.success && typeof createPaymentChart === 'function') createPaymentChart(paymentData.data);
    }
    range.addEventListener('change', function () {
        var custom = range.value === 'custom';
        startInput.hidden = !custom; endInput.hidden = !custom;
        if (!custom) loadSummary().catch(function (error) { console.error(error); });
    });
    [startInput,endInput].forEach(function(input){input.addEventListener('change',function(){if(startInput.value&&endInput.value&&startInput.value<=endInput.value)loadSummary().catch(function(error){console.error(error);});});});
    loadSummary().catch(function (error) { console.error(error); });
    fetch(endpoint + '?action=settings', {headers:{'Accept':'application/json'}}).then(function(response){return response.json();}).then(function(payload){
        if(!payload.success)return;
        [['dashboardWhatsappLink','whatsapp_url'],['dashboardTelegramLink','telegram_url'],['dashboardInstagramLink','instagram_url']].forEach(function(pair){
            var link=document.getElementById(pair[0]);var url=payload.data[pair[1]];
            if(url){link.href=url;link.target='_blank';link.rel='noopener noreferrer';}else{link.removeAttribute('href');link.setAttribute('aria-disabled','true');}
        });
    }).catch(function(error){console.error(error);});
    if (search) search.addEventListener('input', function () {
        clearTimeout(searchTimer); var query = search.value.trim();
        if (query.length < 2) { results.hidden = true; return; }
        searchTimer = setTimeout(async function () {
            try {
                var response = await fetch(endpoint + '?action=search&q=' + encodeURIComponent(query), {headers:{'Accept':'application/json'}});
                var result = await response.json();
                results.innerHTML = result.success && result.data.length ? result.data.map(function (item) {
                    var href = item.type === 'user' ? 'user_admin.php' : item.type === 'transaction' ? 'transaksi_admin.php' : item.type === 'bootcamp' ? 'bootcamp_admin.php' : 'elearning_admin.php';
                    return '<a href="'+href+'"><span class="search-type">'+escapeHtml(item.type)+'</span><strong>'+escapeHtml(item.label)+'</strong><small>'+escapeHtml(item.detail)+'</small></a>';
                }).join('') : '<p class="search-no-results">Tidak ditemukan.</p>';
                results.hidden = false;
            } catch (error) { results.innerHTML = '<p class="search-no-results">Pencarian tidak tersedia.</p>'; results.hidden = false; }
        }, 250);
    });
    document.addEventListener('click', function (event) { if (!event.target.closest('.topbar-search') && results) results.hidden = true; });
})();
