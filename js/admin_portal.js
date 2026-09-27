(function () {
    var root = document.querySelector('.portal-dashboard');
    if (!root) return;
    var section = root.dataset.section;
    var endpoint = '../admin-php/admin_portal.php';
    var content = document.getElementById('portalContent');
    var metrics = document.getElementById('portalMetrics');
    var range = document.getElementById('portalRange');
    var startInput = document.getElementById('portalStart');
    var endInput = document.getElementById('portalEnd');
    var chartInstance = null;
    var selectedDate = '';
    var searchTimer;

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char];
        });
    }
    function rupiah(value) {
        return 'Rp ' + Number(value || 0).toLocaleString('id-ID', {maximumFractionDigits: 0});
    }
    function apiUrl(action, extra) {
        var params = new URLSearchParams({action: action});
        if (extra) Object.keys(extra).forEach(function (key) { params.set(key, extra[key]); });
        if (['sales','registrations','finance','expenses'].indexOf(section) !== -1) {
            params.set('range', range.value);
            if (range.value === 'custom') {
                params.set('start', startInput.value);
                params.set('end', endInput.value);
            }
        }
        return endpoint + '?' + params.toString();
    }
    async function request(action, options, extra) {
        var response = await fetch(apiUrl(action, extra), options || {headers: {'Accept':'application/json'}});
        var json = await response.json();
        if (!response.ok || !json.success) throw new Error(json.message || 'Permintaan gagal.');
        return json;
    }
    function showError(error) {
        content.innerHTML = '<p class="portal-message error">' + escapeHtml(error.message || 'Gagal memuat data.') + '</p>';
        metrics.innerHTML = '';
    }
    function metricCard(label, value, icon, tone) {
        return '<article class="portal-metric ' + tone + '"><span class="portal-metric-icon"><i class="fas ' + icon + '"></i></span><span class="portal-metric-label">' + label + '</span><strong>' + value + '</strong></article>';
    }
    function renderMetrics(data, financeOnly) {
        var html = '';
        if (section === 'sales') {
            html = metricCard('Penjualan Hari Ini', rupiah(data.sales_today), 'fa-calendar-day', 'blue') +
                metricCard('Penjualan 7 Hari', rupiah(data.sales_7_days), 'fa-calendar-week', 'green') +
                metricCard('Penjualan Bulan Ini', rupiah(data.sales_this_month), 'fa-calendar-alt', 'violet') +
                metricCard('Penjualan Tahun Ini', rupiah(data.sales_this_year), 'fa-chart-line', 'orange') +
                metricCard('Total Penjualan', rupiah(data.sales_total), 'fa-coins', 'blue');
            metrics.innerHTML = html;
            return;
        }
        if (!financeOnly) html += metricCard('Pengguna Terdaftar', Number(data.users).toLocaleString('id-ID'), 'fa-users', 'blue') + metricCard('Total Kelas', Number(data.courses).toLocaleString('id-ID'), 'fa-book-open', 'violet') + metricCard('Transaksi', Number(data.transactions).toLocaleString('id-ID'), 'fa-receipt', 'green');
        html += metricCard('Total Pemasukan', rupiah(data.income), 'fa-arrow-trend-up', 'green') + metricCard('Total Pengeluaran', rupiah(data.expenses), 'fa-wallet', 'orange') + metricCard('Profit Bersih', rupiah(data.profit), 'fa-scale-balanced', 'blue');
        metrics.innerHTML = html;
    }
    function renderTable(headers, rows, empty) {
        if (!rows.length) return '<p class="portal-empty">' + escapeHtml(empty) + '</p>';
        return '<div class="portal-table-wrap"><table class="portal-table"><thead><tr>' + headers.map(function (h) { return '<th>' + h + '</th>'; }).join('') + '</tr></thead><tbody>' + rows.join('') + '</tbody></table></div>';
    }
    function renderChart(data, type) {
        if (chartInstance) chartInstance.destroy();
        var canvas = document.getElementById('portalChart');
        if (!canvas || typeof Chart === 'undefined') return;
        var days = Object.keys(data);
        chartInstance = new Chart(canvas.getContext('2d'), {
            type: type === 'sales' ? 'line' : 'bar',
            data: {labels: days.map(function (day) { return new Date(day + 'T00:00:00').toLocaleDateString('id-ID', {day:'numeric', month:'short'}); }), datasets: type === 'sales' ? [{label:'Jumlah transaksi',data:days.map(function(day){return data[day].sales;}),borderColor:'#3565f5',backgroundColor:'rgba(53,101,245,.12)',fill:true,tension:.35}] : [{label:'Pemasukan',data:days.map(function(day){return data[day].income;}),backgroundColor:'#36bd82',borderRadius:4},{label:'Pengeluaran',data:days.map(function(day){return data[day].expenses;}),backgroundColor:'#f4a640',borderRadius:4}]},
            options: {responsive:true, maintainAspectRatio:false, plugins:{legend:{display:type!=='sales'}}, scales:{y:{beginAtZero:true, ticks:{callback:function(value){return type==='sales'?value:rupiah(value);}}}}}
        });
    }
    function rangeParams() {
        var params = {range: range.value};
        if (range.value === 'custom') { params.start = startInput.value; params.end = endInput.value; }
        return params;
    }
    async function loadOverview(financeOnly) {
        var data = await request('overview', null, rangeParams());
        renderMetrics(data.data, financeOnly);
    }
    async function loadSales() {
        await loadOverview(false);
        content.innerHTML = '<div class="portal-panel-heading"><div><span class="portal-kicker">TREN PENJUALAN</span><h2>Jumlah transaksi per hari</h2></div></div><div class="portal-chart"><canvas id="portalChart"></canvas></div>';
        var data = await request('charts', null, rangeParams()); renderChart(data.data, 'sales');
    }
    async function loadFinance() {
        var report = await request('finance-report', null, rangeParams());
        renderMetrics({income:report.summary.income,expenses:report.summary.expenses,profit:report.summary.profit}, true);
        var rows = report.data.map(function(item,index){var date=new Date(item.date.replace(' ','T')).toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});return '<tr><td>'+(index+1)+'</td><td>'+escapeHtml(item.type)+'</td><td>'+date+'</td><td>'+escapeHtml(item.user)+'</td><td>'+escapeHtml(item.product)+'</td><td>'+escapeHtml(item.category)+'</td><td>'+rupiah(item.amount)+'</td><td>'+escapeHtml(item.status)+'</td></tr>';});
        content.innerHTML = '<div class="portal-panel-heading"><div><span class="portal-kicker">ARUS KAS</span><h2>Pemasukan vs Pengeluaran</h2><p>Pemasukan hanya menghitung transaksi berstatus settlement.</p></div></div><div class="portal-chart"><canvas id="portalChart"></canvas></div><div class="portal-panel-heading"><div><span class="portal-kicker">DATA PERIODE TERPILIH</span><h2>Rincian keuangan</h2></div>'+exportActions('finance')+'</div>'+renderTable(['No','Jenis','Tanggal','User','Produk / Deskripsi','Kategori','Nominal','Status'],rows,'Tidak ada transaksi berhasil atau pengeluaran pada periode ini.');
        var data = await request('charts', null, rangeParams()); renderChart(data.data, 'finance');
    }
    function exportActions(mode) {
        return '<div class="report-export-actions"><button type="button" data-export="pdf" data-mode="'+mode+'" title="Unduh PDF">PDF</button><button type="button" data-export="csv" data-mode="'+mode+'" title="Unduh CSV">CSV</button><button type="button" data-export="xlsx" data-mode="'+mode+'" title="Unduh Excel">EXCEL</button><button type="button" data-export="print" data-mode="'+mode+'" title="Print laporan">PRINT</button></div>';
    }
    async function loadRegistrations() {
        var result = await request('registrations', null, rangeParams());
        var rows = result.data.map(function (item, index) {
            var date = item.registration_date ? new Date(item.registration_date.replace(' ', 'T')).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : '-';
            return '<tr><td>'+(index+1)+'</td><td>'+escapeHtml(item.user_name || item.email || 'User')+'</td><td>'+escapeHtml(item.nama_produk)+'</td><td>'+date+'</td><td><span class="portal-status">'+escapeHtml(item.transaction_status)+'</span></td></tr>';
        });
        content.innerHTML = '<div class="portal-panel-heading"><div><span class="portal-kicker">DATA DATABASE</span><h2>Riwayat Pendaftaran</h2><p>Data menggunakan relasi user_id dan produk_id pada transaksi.</p></div></div>' + renderTable(['No','User','Course / Program','Tanggal Daftar','Status'], rows, 'Belum ada data pendaftaran untuk periode ini.');
    }
    async function loadInstructors() {
        var result = await request('instructors');
        var rows = result.data.map(function (item, index) { return '<tr><td>'+(index+1)+'</td><td>'+escapeHtml(item.name)+'</td><td>'+Number(item.bootcamp_count).toLocaleString('id-ID')+'</td></tr>'; });
        content.innerHTML = '<div class="portal-panel-heading"><div><span class="portal-kicker">DATA BOOTCAMP</span><h2>Mentor / Instruktur</h2><p>Daftar ditarik dari kolom mentor pada tabel bootcamp.</p></div></div>' + renderTable(['No','Nama Instruktur','Program'], rows, 'Belum ada nama mentor pada data bootcamp.');
    }
    async function loadCalendar() {
        var now = new Date(); var monthValue = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0');
        content.innerHTML = '<div class="calendar-toolbar"><button type="button" data-month-step="-1" aria-label="Bulan sebelumnya"><i class="fas fa-chevron-left"></i></button><input id="calendarMonth" type="month" value="'+monthValue+'"><button type="button" data-month-step="1" aria-label="Bulan berikutnya"><i class="fas fa-chevron-right"></i></button></div><div class="calendar-layout"><div id="calendarGrid" class="calendar-grid"></div><section id="calendarDetail" class="calendar-detail"><h2>Pilih tanggal</h2><p>Aktivitas harian dari transaksi dan pengeluaran akan tampil di sini.</p></section></div>';
        await renderCalendar(monthValue);
    }
    async function renderCalendar(month) {
        var result = await request('calendar', null, {month:month}); var parts = month.split('-').map(Number); var year=parts[0], monthIndex=parts[1]-1;
        var firstDay = new Date(year,monthIndex,1); var startOffset = (firstDay.getDay()+6)%7; var count = new Date(year,monthIndex+1,0).getDate();
        var html = ['Sen','Sel','Rab','Kam','Jum','Sab','Min'].map(function(day){return '<span class="calendar-weekday">'+day+'</span>';}).join('');
        for (var blank=0; blank<startOffset; blank++) html += '<span class="calendar-blank"></span>';
        for (var day=1; day<=count; day++) { var key=month+'-'+String(day).padStart(2,'0'); var activity=result.days[key]; html += '<button type="button" class="calendar-day'+(activity?' has-activity':'')+'" data-date="'+key+'">'+day+(activity?'<i></i>':'')+'</button>'; }
        document.getElementById('calendarGrid').innerHTML = html;
        document.getElementById('calendarMonth').value = month;
        if (selectedDate && selectedDate.slice(0,7)===month) showCalendarDetail(selectedDate, result.days[selectedDate]);
    }
    function showCalendarDetail(date, data) {
        selectedDate = date;
        var parsed = new Date(date+'T00:00:00').toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});
        var metricsHtml = data ? '<dl><div><dt>Pendaftaran</dt><dd>'+Number(data.registrations).toLocaleString('id-ID')+'</dd></div><div><dt>Transaksi berhasil</dt><dd>'+Number(data.transactions).toLocaleString('id-ID')+'</dd></div><div><dt>Pemasukan</dt><dd>'+rupiah(data.income)+'</dd></div><div><dt>Pengeluaran</dt><dd>'+rupiah(data.expenses)+'</dd></div><div><dt>Profit bersih</dt><dd>'+rupiah(data.profit)+'</dd></div></dl>' : '<p>Tidak ada aktivitas tercatat pada tanggal ini.</p>';
        document.getElementById('calendarDetail').innerHTML = '<span class="portal-kicker">RINGKASAN HARIAN</span><h2>'+parsed+'</h2>'+metricsHtml;
        document.querySelectorAll('.calendar-day').forEach(function(button){button.classList.toggle('selected',button.dataset.date===date);});
    }
    async function loadExpenses() {
        var result = await request('expenses', null, rangeParams());
        var rows = result.data.map(function(item){return '<tr><td>'+new Date(item.expense_date.replace(' ','T')).toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'})+'</td><td>'+escapeHtml(item.category)+'</td><td>'+escapeHtml(item.description)+'</td><td>'+rupiah(item.amount)+'</td><td><button class="portal-icon-action" data-edit-expense="'+item.id+'" title="Edit"><i class="fas fa-pen"></i></button> <button class="portal-icon-action danger" data-delete-expense="'+item.id+'" title="Hapus"><i class="fas fa-trash"></i></button></td></tr>';});
        content.innerHTML = '<div class="portal-form-section"><div><span class="portal-kicker">CATAT PENGELUARAN</span><h2>Tambah pengeluaran</h2></div><form id="expenseForm" class="portal-form"><label>Kategori<input name="category" required maxlength="100"></label><label>Deskripsi<input name="description" required maxlength="500"></label><label>Nominal<input name="amount" type="number" min="1" step="1" required></label><label>Tanggal<input name="expense_date" type="datetime-local" required></label><button class="portal-button primary" type="submit"><i class="fas fa-plus"></i> Simpan</button></form></div><div class="portal-panel-heading"><div><span class="portal-kicker">DATABASE</span><h2>Daftar Pengeluaran</h2></div></div>'+renderTable(['Tanggal','Kategori','Deskripsi','Nominal','Aksi'],rows,'Belum ada pengeluaran pada periode ini.');
    }
    async function loadPartnerships() {
        var result=await request('partnerships'); var rows=result.data.map(function(item){return '<tr><td>'+escapeHtml(item.name)+'</td><td>'+escapeHtml(item.category)+'</td><td>'+escapeHtml(item.contact)+'</td><td>'+escapeHtml(item.status)+'</td><td>'+escapeHtml(item.notes)+'</td><td><button class="portal-icon-action" data-edit-partner="'+item.id+'" title="Edit"><i class="fas fa-pen"></i></button> <button class="portal-icon-action danger" data-delete-partner="'+item.id+'" title="Hapus"><i class="fas fa-trash"></i></button></td></tr>';});
        content.innerHTML='<div class="portal-form-section"><div><span class="portal-kicker">DATA KERJASAMA</span><h2>Tambah mitra</h2></div><form id="partnerForm" class="portal-form"><label>Nama mitra<input name="name" required maxlength="150"></label><label>Kategori<input name="category" maxlength="100"></label><label>Kontak<input name="contact" maxlength="150"></label><label>Status<select name="status"><option value="aktif">Aktif</option><option value="proses">Dalam proses</option><option value="nonaktif">Nonaktif</option></select></label><label>Catatan<input name="notes" maxlength="500"></label><button class="portal-button primary" type="submit"><i class="fas fa-plus"></i> Simpan</button></form></div><div class="portal-panel-heading"><div><span class="portal-kicker">DATABASE</span><h2>Mitra tersimpan</h2></div></div>'+renderTable(['Nama','Kategori','Kontak','Status','Catatan','Aksi'],rows,'Belum ada data kerja sama.');
    }
    async function loadSettings(contactsOnly) {
        var result=await request('settings'); var data=result.data; var fields=contactsOnly?[['contact_email','Email kontak','email'],['contact_phone','Nomor telepon','tel'],['whatsapp_url','WhatsApp URL','url'],['telegram_url','Telegram URL','url'],['instagram_url','Instagram URL','url']]:[['site_name','Nama platform','text'],['contact_email','Email kontak','email'],['contact_phone','Nomor telepon','tel'],['whatsapp_url','WhatsApp URL','url'],['telegram_url','Telegram URL','url'],['instagram_url','Instagram URL','url']];
        var inputs=fields.map(function(field){return '<label>'+field[1]+'<input name="'+field[0]+'" type="'+field[2]+'" maxlength="255" value="'+escapeHtml(data[field[0]]||'')+'"></label>';}).join('');
        content.innerHTML='<div class="portal-form-section"><div><span class="portal-kicker">KONFIGURASI DATABASE</span><h2>'+ (contactsOnly?'Kontak dan kanal sosial':'Informasi panel') +'</h2><p>Nilai disimpan di database dan tidak ditampilkan sebagai data contoh.</p></div><form id="settingsForm" class="portal-form">'+inputs+'<button class="portal-button primary" type="submit"><i class="fas fa-save"></i> Simpan perubahan</button></form></div>';
    }
    async function load() {
        content.innerHTML='<div class="portal-loading">Memuat data dari database...</div>';
        try {
            if (section==='sales') return await loadSales();
            if (section==='finance') return await loadFinance();
            if (section==='registrations') return await loadRegistrations();
            if (section==='instructors') return await loadInstructors();
            if (section==='calendar') return await loadCalendar();
            if (section==='expenses') { await loadOverview(true); return await loadExpenses(); }
            if (section==='partnerships') return await loadPartnerships();
            if (section==='contacts') return await loadSettings(true);
            if (section==='settings') return await loadSettings(false);
        } catch (error) { showError(error); }
    }
    document.getElementById('applyRange').addEventListener('click', function () {
        if (range.value==='custom' && (!startInput.value || !endInput.value || startInput.value>endInput.value)) { content.innerHTML='<p class="portal-message error">Isi rentang tanggal yang valid.</p>'; return; }
        load();
    });
    range.addEventListener('change', function () { var custom=range.value==='custom'; startInput.hidden=!custom; endInput.hidden=!custom; });
    content.addEventListener('submit', async function(event) {
        event.preventDefault(); var form=event.target; var payload=Object.fromEntries(new FormData(form).entries());
        try {
            if (form.id==='expenseForm') { payload.expense_date=payload.expense_date.replace('T',' '); await request('expenses',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)}); }
            else if (form.id==='partnerForm') await request('partnerships',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
            else if (form.id==='settingsForm') await request('settings',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
            await load();
        } catch(error) { window.alert(error.message); }
    });
    content.addEventListener('click', async function(event) {
        var button=event.target.closest('button'); if(!button) return;
        if(button.dataset.export){var params=new URLSearchParams({format:button.dataset.export,mode:button.dataset.mode,range:range.value});if(range.value==='custom'){params.set('start',startInput.value);params.set('end',endInput.value);}var url='../admin-php/report_export.php?'+params.toString();if(button.dataset.export==='print')window.open(url,'_blank','noopener');else window.location.href=url;return;}
        if(button.dataset.date){var date=button.dataset.date; var month=document.getElementById('calendarMonth').value; var data=await request('calendar',null,{month:month}); showCalendarDetail(date,data.days[date]); return;}
        if(button.dataset.monthStep){var control=document.getElementById('calendarMonth');var date=new Date(control.value+'-01T00:00:00');date.setMonth(date.getMonth()+Number(button.dataset.monthStep));return renderCalendar(date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0'));}
        var expenseId=button.dataset.editExpense; var deleteExpense=button.dataset.deleteExpense; var partnerId=button.dataset.editPartner; var deletePartner=button.dataset.deletePartner;
        if(deleteExpense||deletePartner){if(!window.confirm('Hapus data ini dari database?'))return;var action=deleteExpense?'expenses':'partnerships';var id=deleteExpense||deletePartner;try{await request(action,{method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:id})});await load();}catch(error){window.alert(error.message);}return;}
        if(expenseId){var row=button.closest('tr');var cells=row.querySelectorAll('td');var category=window.prompt('Kategori',cells[1].textContent);if(category===null)return;var description=window.prompt('Deskripsi',cells[2].textContent);if(description===null)return;var amount=window.prompt('Nominal',cells[3].textContent.replace(/[^0-9]/g,''));if(amount===null)return;var dateValue=window.prompt('Tanggal (YYYY-MM-DD HH:MM:SS)',new Date().toISOString().slice(0,19).replace('T',' '));if(dateValue===null)return;try{await request('expenses',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:expenseId,category:category,description:description,amount:amount,expense_date:dateValue})});await load();}catch(error){window.alert(error.message);}return;}
        if(partnerId){var rowP=button.closest('tr');var cellsP=rowP.querySelectorAll('td');var name=window.prompt('Nama mitra',cellsP[0].textContent);if(name===null)return;var categoryP=window.prompt('Kategori',cellsP[1].textContent);if(categoryP===null)return;var contact=window.prompt('Kontak',cellsP[2].textContent);if(contact===null)return;var status=window.prompt('Status: aktif, proses, atau nonaktif',cellsP[3].textContent);if(status===null)return;var notes=window.prompt('Catatan',cellsP[4].textContent);if(notes===null)return;try{await request('partnerships',{method:'PUT',headers:{'Content-Type':'application/json'},body:JSON.stringify({id:partnerId,name:name,category:categoryP,contact:contact,status:status,notes:notes})});await load();}catch(error){window.alert(error.message);}}
    });
    var monthInput;
    content.addEventListener('change',function(event){if(event.target.id==='calendarMonth')renderCalendar(event.target.value);});

    var search=document.getElementById('globalSearch');var results=document.getElementById('globalSearchResults');
    search.addEventListener('input',function(){clearTimeout(searchTimer);var q=search.value.trim();if(q.length<2){results.hidden=true;return;}searchTimer=setTimeout(async function(){try{var json=await request('search',null,{q:q});results.innerHTML=json.data.length?json.data.map(function(item){var href=item.type==='user'?'user_admin.php':item.type==='transaction'?'transaksi_admin.php':item.type==='bootcamp'?'bootcamp_admin.php':'elearning_admin.php';return '<a href="'+href+'"><span class="search-type">'+escapeHtml(item.type)+'</span><strong>'+escapeHtml(item.label)+'</strong><small>'+escapeHtml(item.detail)+'</small></a>';}).join(''):'<p class="search-no-results">Tidak ditemukan.</p>';results.hidden=false;}catch(error){results.innerHTML='<p class="search-no-results">'+escapeHtml(error.message)+'</p>';results.hidden=false;}},250);});
    document.addEventListener('click',function(event){if(!event.target.closest('.topbar-search'))results.hidden=true;});
    window.toggleSidebar=function(){var sidebar=document.getElementById('adminSidebar');var overlay=document.getElementById('adminOverlay');sidebar.classList.toggle('open');overlay.classList.toggle('show');document.body.classList.toggle('sidebar-open');};
    window.closeSidebar=function(){document.getElementById('adminSidebar').classList.remove('open');document.getElementById('adminOverlay').classList.remove('show');document.body.classList.remove('sidebar-open');};
    load();
})();
