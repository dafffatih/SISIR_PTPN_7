document.addEventListener("DOMContentLoaded", function () {
    const data = window.dashboardData;
    const commonFont = 'Inter, sans-serif';

    // --- 1. CHART HARGA (TETAP) ---
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    var priceOptions = {
        series: data.priceMonthly,
        chart: { type: 'line', height: 320, toolbar: { show: false }, zoom: { enabled: false }, fontFamily: commonFont },
        colors: ['#1E293B', '#F97316', '#0D9488'],
        stroke: { curve: 'smooth', width: 3 },
        xaxis: { categories: months, labels: { style: { colors: '#94a3b8', fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { labels: { formatter: (val) => (val/1000).toFixed(0) + 'k', style: { colors: '#94a3b8', fontSize: '11px' } } },
        grid: { borderColor: '#F1F5F9', strokeDashArray: 4, padding: { top: 0, right: 10, bottom: 0, left: 10 } },
        legend: { position: 'top', horizontalAlign: 'right', offsetY: -20, markers: { radius: 12 } }
    };
    new ApexCharts(document.querySelector("#chart-price-monthly"), priceOptions).render();


    // --- 2. DONUT CONFIGURATION (MODIFIKASI: LABEL AKTIF) ---
    // Pastikan ada cukup warna untuk 5 data
    const donutColors = ['#2563EB', '#0D9488', '#F59E0B', '#F43F5E', '#8B5CF6']; 

    const commonDonutOptions = {
        chart: { type: 'donut', height: 180, fontFamily: commonFont },
        colors: donutColors,
        
        // AKTIFKAN LABEL DATA DI DALAM CHART
        dataLabels: { 
            enabled: true,
            formatter: function (val, opts) {
                // Tampilkan Nama Series (Inisial) saja jika ruang sempit
                // atau Nama + Persen
                return opts.w.config.labels[opts.seriesIndex];
            },
            style: {
                fontSize: '10px',
                fontFamily: commonFont,
                fontWeight: 'bold',
                colors: ['#fff'] // Warna teks putih agar kontras dengan slice
            },
            dropShadow: { enabled: false }
        },
        
        plotOptions: {
            pie: {
                donut: {
                    size: '65%', // Sedikit diperkecil agar label muat
                    labels: { show: false }
                }
            }
        },
        legend: { show: false }, // Legend kita pakai HTML custom di samping
        stroke: { show: false },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + " Ton";
                }
            }
        }
    };

    // Buyer Chart (Menggunakan Labels Inisial)
    new ApexCharts(document.querySelector("#chart-buyer"), {
        ...commonDonutOptions,
        series: data.topBuyers,
        labels: data.topBuyersLabels // Array Inisial (WTP, dll)
    }).render();

    // Product Chart
    new ApexCharts(document.querySelector("#chart-product"), {
        ...commonDonutOptions,
        series: data.topProducts,
        labels: data.topProductsLabels
    }).render();


    // --- 3. MONTHLY BAR CHARTS (TETAP) ---
    const barConfig = {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: commonFont },
        plotOptions: { bar: { horizontal: false, columnWidth: '50%', borderRadius: 3 } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: { categories: months, labels: { style: { colors: '#94a3b8', fontSize: '10px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { show: false },
        grid: { show: false },
        legend: { position: 'bottom', markers: { radius: 12 }, offsetY: 5 },
        tooltip: { shared: true, intersect: false }
    };

    new ApexCharts(document.querySelector("#chart-monthly-vol"), {
        ...barConfig,
        series: [{ name: 'Real', data: data.volumeReal }, { name: 'RKAP', data: data.rkapVol }],
        colors: ['#F97316', '#E2E8F0']
    }).render();

    new ApexCharts(document.querySelector("#chart-monthly-rev"), {
        ...barConfig,
        series: [{ name: 'Real', data: data.revenueReal }, { name: 'RKAP', data: data.rkapRev }],
        colors: ['#334155', '#E2E8F0']
    }).render();
});