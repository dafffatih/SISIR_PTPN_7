document.addEventListener("DOMContentLoaded", function () {
    const data = window.dashboardData;
    const commonFont = 'Inter, sans-serif';

    // ==========================================================
    // 1. CHART HARGA (TETAP)
    // ==========================================================
    var priceOptions = {
        series: data.priceDaily, 
        chart: { 
            type: 'line', 
            height: 320, 
            toolbar: { show: false },
            fontFamily: commonFont 
        },
        colors: ['#1E293B', '#F97316', '#0D9488'],
        stroke: { curve: 'smooth', width: 2 },
        xaxis: { 
            type: 'datetime',
            labels: { 
                format: 'MMM',
                style: { colors: '#94a3b8', fontSize: '11px' } 
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { 
            min: 20000,
            max: 50000,
            tickAmount: 6,
            labels: { 
                formatter: (val) => 'Rp ' + (val/1000).toFixed(0) + 'k', 
                style: { colors: '#94a3b8', fontSize: '11px' } 
            } 
        },
        tooltip: {
            shared: true,
            x: { format: 'dd MMM yyyy' }
        },
        grid: { borderColor: '#F1F5F9', strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right' }
    };
    new ApexCharts(document.querySelector("#chart-price-monthly"), priceOptions).render();


    // ==========================================================
    // 2. DONUT CHARTS (Dengan Fungsi Update)
    // ==========================================================
    
    // Helper: Opsi Umum Donut
    function getDonutOptions(colors) {
        return {
            chart: { type: 'donut', height: 180, fontFamily: commonFont },
            colors: colors,
            // LABEL DI DALAM DONUT
            dataLabels: { 
                enabled: true,
                formatter: function (val, opts) {
                    // Tampilkan Nama Series (WTP, MOP) langsung
                    return opts.w.config.labels[opts.seriesIndex];
                },
                style: { fontSize: '10px', fontFamily: commonFont, fontWeight: 'bold', colors: ['#fff'] },
                dropShadow: { enabled: false }
            },
            plotOptions: {
                pie: { donut: { size: '65%', labels: { show: false } } }
            },
            legend: { show: false },
            stroke: { show: false },
            tooltip: {
                y: { formatter: function(val) { return val + " Ton"; } }
            }
        };
    }

    // --- Render Awal Chart Buyer ---
    var buyerOptions = getDonutOptions(data.chartColors);
    buyerOptions.series = data.topBuyers;
    buyerOptions.labels = data.topBuyersLabels; // Label Awal (TOTAL)
    var chartBuyer = new ApexCharts(document.querySelector("#chart-buyer"), buyerOptions);
    chartBuyer.render();

    // --- Render Awal Chart Product ---
    var productOptions = getDonutOptions(data.prodColors);
    productOptions.series = data.topProducts;
    productOptions.labels = data.topProductsLabels; // Label Awal (TOTAL)
    var chartProduct = new ApexCharts(document.querySelector("#chart-product"), productOptions);
    chartProduct.render();

    // ==========================================================
    // 3. BAR CHARTS (Volume & Revenue)
    // ==========================================================
    const barConfig = {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: commonFont },
        plotOptions: { bar: { horizontal: false, columnWidth: '50%', borderRadius: 3 } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: { categories: data.monthLabels, labels: { style: { colors: '#94a3b8', fontSize: '10px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
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


    // ==========================================================
    // 4. EVENT LISTENERS (LOGIC UPDATE DROPDOWN)
    // ==========================================================

    // Listener Buyer
    const buyerSelect = document.getElementById('buyer-filter');
    if(buyerSelect) {
        buyerSelect.addEventListener('change', function(e) {
            updateDonutData(e.target.value, 'buyer', chartBuyer);
        });
    }

    // Listener Product
    const productSelect = document.getElementById('product-filter');
    if(productSelect) {
        productSelect.addEventListener('change', function(e) {
            updateDonutData(e.target.value, 'product', chartProduct);
        });
    }

    /**
     * FUNGSI INTI UPDATE CHART & LEGEND
     */
    function updateDonutData(category, type, chartInstance) {
        let rawSource, legendContainer, centerTotalEl, colors;

        if (type === 'buyer') {
            rawSource = data.rawTopBuyers;
            colors = data.chartColors;
            legendContainer = document.getElementById('buyer-legend-container');
            centerTotalEl = document.getElementById('buyer-center-total');
        } else {
            rawSource = data.rawTopProducts;
            colors = data.prodColors;
            legendContainer = document.getElementById('product-legend-container');
            centerTotalEl = document.getElementById('product-center-total');
        }

        // Ambil data (Fallback ke object kosong jika key tidak ada)
        let categoryData = rawSource && rawSource[category] ? rawSource[category] : {};
        let totalSum = categoryData['TOTAL'] || 0;

        // Siapkan Array Baru
        let newSeries = [];
        let newLabels = [];
        
        // Loop data untuk mengisi Series & Labels
        Object.keys(categoryData).forEach(key => {
            // PENTING: Skip key 'TOTAL' agar tidak masuk ke grafik
            if (key !== 'TOTAL') {
                newSeries.push(Number(categoryData[key]));
                newLabels.push(key); // PENTING: GUNAKAN NAMA ASLI (WTP, MOP)
            }
        });

        // Update Chart Apex
        if(chartInstance) {
            chartInstance.updateOptions({ 
                labels: newLabels, 
                colors: colors // Reset warna agar urutan konsisten
            });
            chartInstance.updateSeries(newSeries);
        }

        // Update Center Total
        if(centerTotalEl) {
            let formattedTotal = new Intl.NumberFormat('id-ID').format(Math.round(totalSum / 1000));
            centerTotalEl.innerText = formattedTotal;
        }

        // Update HTML Legend di samping
        if(legendContainer) {
            let html = '';
            newLabels.forEach((name, index) => {
                let val = newSeries[index];
                let pct = totalSum > 0 ? Math.round((val / totalSum) * 100) : 0;
                let color = colors[index % colors.length];
                
                html += `
                <div class="legend-item">
                    <span class="dot" style="background: ${color}"></span>
                    <span class="name" title="${name}">${name}</span>
                    <span class="val">${pct}%</span>
                </div>`;
            });
            legendContainer.innerHTML = html;
        }
    }
});