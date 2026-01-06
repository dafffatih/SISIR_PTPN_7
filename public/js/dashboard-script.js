document.addEventListener("DOMContentLoaded", function () {
    const data = window.dashboardData;
    const commonFont = 'Inter, sans-serif';

    // --- 1. CHART HARGA (BULANAN/LINE) ---
    // User minta "Grafik Perbulan", jadi kita gunakan kategori bulan
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    var priceOptions = {
        series: data.priceMonthly, // Menggunakan data dummy bulanan yang disiapkan di PHP
        chart: {
            type: 'line',
            height: 320,
            toolbar: { show: false },
            zoom: { enabled: false },
            fontFamily: commonFont
        },
        colors: ['#1E293B', '#F97316', '#0D9488'], // Hitam, Orange, Hijau
        stroke: { curve: 'smooth', width: 3 },
        xaxis: {
            categories: months,
            labels: { style: { colors: '#94a3b8', fontSize: '11px' } },
            axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                formatter: (val) => (val/1000).toFixed(0) + 'k',
                style: { colors: '#94a3b8', fontSize: '11px' }
            }
        },
        grid: {
            borderColor: '#F1F5F9',
            strokeDashArray: 4,
            padding: { top: 0, right: 10, bottom: 0, left: 10 }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'right',
            offsetY: -20,
            markers: { radius: 12 }
        }
    };
    new ApexCharts(document.querySelector("#chart-price-monthly"), priceOptions).render();


    // --- 2. DONUT CONFIGURATION (Common) ---
    const donutConfig = {
        chart: { type: 'donut', height: 180, fontFamily: commonFont },
        colors: ['#2563EB', '#0D9488', '#F59E0B', '#64748B', '#94A3B8'],
        dataLabels: { enabled: false },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: { show: false } // Kita pakai HTML label custom di tengah
                }
            }
        },
        legend: { show: false }, // Legend custom pakai HTML
        stroke: { show: false }
    };

    // Buyer Chart
    new ApexCharts(document.querySelector("#chart-buyer"), {
        ...donutConfig,
        series: data.topBuyers,
        labels: data.topBuyersLabels
    }).render();

    // Product Chart
    new ApexCharts(document.querySelector("#chart-product"), {
        ...donutConfig,
        series: data.topProducts,
        labels: data.topProductsLabels
    }).render();


    // --- 3. MONTHLY BAR CHARTS (Volume & Revenue) ---
    // Konfigurasi Bar Chart Side-by-Side
    const barConfig = {
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false },
            fontFamily: commonFont
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '50%',
                borderRadius: 3
            }
        },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: months,
            labels: { style: { colors: '#94a3b8', fontSize: '10px' } },
            axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: { show: false }, // Hide Y-Axis biar bersih
        grid: { show: false },
        legend: { position: 'bottom', markers: { radius: 12 }, offsetY: 5 },
        tooltip: {
            shared: true,
            intersect: false
        }
    };

    // Render Volume Chart
    new ApexCharts(document.querySelector("#chart-monthly-vol"), {
        ...barConfig,
        series: [
            { name: 'Real', data: data.volumeReal },
            { name: 'RKAP', data: data.rkapVol }
        ],
        colors: ['#F97316', '#E2E8F0'] // Orange vs Abu-abu
    }).render();

    // Render Revenue Chart
    new ApexCharts(document.querySelector("#chart-monthly-rev"), {
        ...barConfig,
        series: [
            { name: 'Real', data: data.revenueReal },
            { name: 'RKAP', data: data.rkapRev }
        ],
        colors: ['#334155', '#E2E8F0'] // Dark Blue vs Abu-abu
    }).render();
});