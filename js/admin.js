/* ========================================
   BELAJARYUK ADMIN DASHBOARD - MODERN
   ADMIN.JS
   ======================================== */

/* ========================================
   GLOBAL
   ======================================== */
var salesChartInstance = null;
var paymentChartInstance = null;

/* ========================================
   FORMAT RUPIAH
   ======================================== */
function formatRupiah(number) {
    number = Number(number) || 0;
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0
    }).format(number);
}

/* ========================================
   FORMAT NUMBER
   ======================================== */
function formatNumber(number) {
    number = Number(number) || 0;
    return new Intl.NumberFormat("id-ID").format(number);
}

/* ========================================
   DATE DISPLAY
   ======================================== */
function setCurrentDate() {
    var el = document.getElementById('currentDate');
    if (!el) return;

    var now = new Date();
    var days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    var months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    var dayName = days[now.getDay()];
    var day = now.getDate();
    var month = months[now.getMonth()];
    var year = now.getFullYear();

    el.textContent = dayName + ', ' + day + ' ' + month + ' ' + year;
}

/* ========================================
   SIDEBAR TOGGLE
   ======================================== */
function toggleSidebar() {
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('adminOverlay');

    if (!sidebar) return;

    sidebar.classList.toggle('open');

    if (overlay) {
        overlay.classList.toggle('show');
    }

    document.body.classList.toggle('sidebar-open');
}

function closeSidebar() {
    var sidebar = document.getElementById('adminSidebar');
    var overlay = document.getElementById('adminOverlay');

    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('show');
    document.body.classList.remove('sidebar-open');
}

/* ========================================
   SALES CHART
   ======================================== */
function createSalesChart(labels, values) {
    var canvas = document.getElementById('salesChart');
    if (!canvas) return;
    if (typeof Chart === 'undefined') return;

    var ctx = canvas.getContext('2d');

    if (salesChartInstance) {
        salesChartInstance.destroy();
        salesChartInstance = null;
    }

    if (!labels || labels.length === 0) {
        labels = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        values = [0, 0, 0, 0, 0, 0, 0];
    }

    var gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.15)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.01)');

    salesChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Penjualan',
                data: values,
                borderColor: '#6366f1',
                backgroundColor: gradient,
                borderWidth: 2.5,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBorderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#e2e8f0',
                    bodyColor: '#e2e8f0',
                    titleFont: { size: 12, weight: '600' },
                    bodyFont: { size: 13, weight: '700' },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return formatRupiah(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11, weight: '500', family: 'Inter' }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 11, weight: '500', family: 'Inter' },
                        callback: function(value) {
                            return formatCompactRupiah(value);
                        }
                    }
                }
            }
        }
    });
}

/* ========================================
   COMPACT RUPIAH
   ======================================== */
function formatCompactRupiah(number) {
    number = Number(number) || 0;
    if (number >= 1000000000) return 'Rp ' + (number / 1000000000).toFixed(1).replace('.0', '') + ' M';
    if (number >= 1000000) return 'Rp ' + (number / 1000000).toFixed(1).replace('.0', '') + ' jt';
    if (number >= 1000) return 'Rp ' + (number / 1000).toFixed(0) + ' rb';
    return 'Rp ' + number;
}

/* ========================================
   PAYMENT CHART
   ======================================== */
function createPaymentChart(paymentData) {
    var canvas = document.getElementById('paymentChart');
    var legendContainer = document.getElementById('paymentLegend');
    if (!canvas) return;
    if (typeof Chart === 'undefined') return;

    var ctx = canvas.getContext('2d');

    if (paymentChartInstance) {
        paymentChartInstance.destroy();
        paymentChartInstance = null;
    }

    if (!paymentData || paymentData.length === 0) {
        if (legendContainer) {
            legendContainer.innerHTML = '<div class="empty-state-small">Belum ada data pembayaran</div>';
        }
        return;
    }

    var labels = [];
    var values = [];
    var colors = ['#6366f1', '#22c55e', '#f97316', '#3b82f6', '#a855f7', '#ef4444', '#14b8a6'];
    var total = 0;

    paymentData.forEach(function(item) {
        var method = item.method || 'Lainnya';
        var jumlah = parseInt(item.jumlah) || 0;

        if (method === 'bank_transfer') method = 'Bank Transfer';
        else if (method === 'ewallet') method = 'E-Wallet';
        else if (method === 'credit_card') method = 'Kartu Kredit';
        else if (method === 'qris') method = 'QRIS';
        else method = method.charAt(0).toUpperCase() + method.slice(1);

        labels.push(method);
        values.push(jumlah);
        total += jumlah;
    });

    paymentChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, labels.length),
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleColor: '#e2e8f0',
                    bodyColor: '#e2e8f0',
                    titleFont: { size: 12, weight: '600' },
                    bodyFont: { size: 13, weight: '700' },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            var pct = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                            return context.label + ': ' + context.parsed + ' (' + pct + '%)';
                        }
                    }
                }
            }
        },
        plugins: [{
            id: 'centerText',
            afterDraw: function(chart) {
                var width = chart.width,
                    height = chart.height,
                    ctx = chart.ctx;
                ctx.restore();
                ctx.textBaseline = 'middle';
                ctx.textAlign = 'center';

                ctx.fillStyle = '#1e293b';
                ctx.font = 'bold 22px Inter';
                ctx.fillText(formatNumber(total), width / 2, height / 2 - 8);

                ctx.fillStyle = '#94a3b8';
                ctx.font = '500 11px Inter';
                ctx.fillText('Transaksi', width / 2, height / 2 + 14);

                ctx.save();
            }
        }]
    });

    // Build legend
    if (legendContainer) {
        var html = '';
        labels.forEach(function(label, i) {
            var pct = total > 0 ? Math.round((values[i] / total) * 100) : 0;
            html += '<div class="payment-legend-item">';
            html += '  <div class="payment-legend-left">';
            html += '    <div class="payment-legend-dot" style="background:' + colors[i] + '"></div>';
            html += '    <span class="payment-legend-name">' + label + '</span>';
            html += '  </div>';
            html += '  <span class="payment-legend-value">' + values[i] + ' (' + pct + '%)</span>';
            html += '</div>';
        });
        legendContainer.innerHTML = html;
    }
}

/* ========================================
   CHANGE CHART PERIOD (AJAX)
   ======================================== */
function changeChartPeriod() {
    var filter = document.getElementById('periodFilter');
    if (!filter) return;

    var period = filter.value;

    fetch('../admin-php/dashboard.php?period=' + encodeURIComponent(period) + '&chart_only=1', {
        method: 'GET',
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
    })
    .then(function(response) {
        if (!response.ok) throw new Error('HTTP ' + response.status);
        return response.json();
    })
    .then(function(data) {
        if (data.success === false) return;

        var chartData = data.chart || data.sales_chart || data.chart_data || [];
        var labels = [];
        var values = [];

        if (Array.isArray(chartData)) {
            chartData.forEach(function(item) {
                labels.push(item.label || item.bulan || '-');
                values.push(Number(item.value || item.total || 0));
            });
        }

        createSalesChart(labels, values);
    })
    .catch(function(err) {
        console.error('Chart load error:', err);
    });
}

/* ========================================
   ESCAPE HTML
   ======================================== */
function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/* ========================================
   DOM READY
   ======================================== */
document.addEventListener('DOMContentLoaded', function() {

    // Set current date
    setCurrentDate();

    // Sidebar link close
    var sidebarLinks = document.querySelectorAll('.sidebar-menu .menu-item');
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) {
                closeSidebar();
            }
        });
    });

    // ESC key close sidebar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeSidebar();
    });

    // Resize handler
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            closeSidebar();
        }
        if (salesChartInstance) salesChartInstance.resize();
        if (paymentChartInstance) paymentChartInstance.resize();
    });

    // Initialize sales chart from embedded data
    if (typeof SALES_CHART_DATA !== 'undefined') {
        createSalesChart(SALES_CHART_DATA.labels, SALES_CHART_DATA.values);
    }

    // Initialize payment chart from embedded data
    if (typeof PAYMENT_DATA !== 'undefined') {
        createPaymentChart(PAYMENT_DATA);
    }
});
