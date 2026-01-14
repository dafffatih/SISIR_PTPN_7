document.addEventListener("DOMContentLoaded", function () {
    const data = window.dashboardData;
    const commonFont = 'Inter, sans-serif';

    // ==========================================================
    // SIMPAN DATA ASLI (UNTUK RESET & ANIMASI)
    // ==========================================================
    const originalPriceSeries = JSON.parse(JSON.stringify(data.priceDaily));

    // ==========================================================
    // 1. CHART HARGA
    // ==========================================================
    var priceOptions = {
        series: data.priceDaily,
        chart: {
            type: 'line',
            height: 320,
            toolbar: { show: false },
            fontFamily: commonFont,

            // 🔥 ANIMASI HALUS
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 600,
                animateGradually: {
                    enabled: true,
                    delay: 150
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 500
                }
            }
        },

        colors: ['#1E293B', '#F97316', '#0D9488'],
        stroke: { curve: 'smooth', width: 2 },

        xaxis: {
            type: 'datetime',
            labels: {
                format: 'MMM',
                style: {
                    colors: '#0F172A',
                    fontSize: '12px',
                    fontWeight: 600
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },

        yaxis: {
            min: 20000,
            max: 50000,
            tickAmount: 6,
            labels: {
                formatter: (val) => 'Rp ' + (val / 1000).toFixed(0) + 'k',
                style: {
                    colors: '#0F172A',
                    fontSize: '12px',
                    fontWeight: 600
                }
            }
        },

        tooltip: {
            shared: true,
            x: { format: 'dd MMM yyyy' }
        },

        grid: {
            borderColor: '#262323',
            strokeDashArray: 4,
            yaxis: { lines: { show: true } }
        },

        legend: { position: 'top', horizontalAlign: 'right' }
    };

    // ==========================================================
    // INIT CHART INSTANCE
    // ==========================================================
    const priceChart = new ApexCharts(
        document.querySelector("#chart-price-monthly"),
        priceOptions
    );
    priceChart.render();

    // ==========================================================
    // FILTER BULAN + ANIMASI
    // ==========================================================
    function filterPriceByMonth(value) {

        // ===== MODE: SEMUA BULAN =====
        if (value === 'all') {

            // Reset window X-axis
            priceChart.updateOptions({
                xaxis: {}
            }, false, true);

            // 🔥 Update series TERPISAH agar animasi jalan
            priceChart.updateSeries(originalPriceSeries, true);
            return;
        }

        // ===== MODE: FILTER BULAN =====
        const monthCount = parseInt(value);
        const now = new Date();
        const fromDate = new Date();
        fromDate.setMonth(now.getMonth() - monthCount);

        const filteredSeries = originalPriceSeries.map(series => ({
            name: series.name,
            data: series.data.filter(point => new Date(point[0]) >= fromDate)
        }));

        priceChart.updateOptions({
            xaxis: {
                min: fromDate.getTime(),
                max: now.getTime()
            }
        }, false, true);

        // 🔥 Animasi data
        priceChart.updateSeries(filteredSeries, true);
    }

    // ==========================================================
    // EVENT DROPDOWN FILTER BULAN
    // ==========================================================
    const priceRangeSelect = document.getElementById('price-range');
    if (priceRangeSelect) {
        priceRangeSelect.addEventListener('change', function () {
            filterPriceByMonth(this.value); // ⚠️ jangan parseInt
        });
    }

    // ==========================================================
    // DEFAULT LOAD (12 BULAN)
    // ==========================================================
    filterPriceByMonth(12);

    // ==========================================================
    // 2. DONUT CHARTS (TIDAK DIUBAH)
    // ==========================================================
    function getDonutOptions(colors) {
        return {
            chart: { type: 'pie', height: 200, fontFamily: commonFont },
            colors: colors,
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    return opts.w.config.labels[opts.seriesIndex];
                },
                style: {
                    fontSize: '10px',
                    fontFamily: commonFont,
                    fontWeight: 'bold',
                    colors: ['#fff']
                },
                dropShadow: { enabled: false }
            },
            plotOptions: {
                pie: { offsetY: 0, customScale: 1 }
            },
            legend: { show: false },
            stroke: { show: false },
            tooltip: {
                y: { formatter: function (val) { return val + " Ton"; } }
            }
        };
    }

    var buyerOptions = getDonutOptions(data.chartColors);
    buyerOptions.series = data.topBuyers;
    buyerOptions.labels = data.topBuyersLabels;
    var chartBuyer = new ApexCharts(document.querySelector("#chart-buyer"), buyerOptions);
    chartBuyer.render();

    var productOptions = getDonutOptions(data.prodColors);
    productOptions.series = data.topProducts;
    productOptions.labels = data.topProductsLabels;
    var chartProduct = new ApexCharts(document.querySelector("#chart-product"), productOptions);
    chartProduct.render();

    // ==========================================================
    // 3. BAR CHARTS (TIDAK DIUBAH)
    // ==========================================================
    const barConfig = {
        chart: { type: 'bar', height: 280, toolbar: { show: false }, fontFamily: commonFont },
        plotOptions: { bar: { horizontal: false, columnWidth: '50%', borderRadius: 3 } },
        dataLabels: { enabled: false },
        stroke: { show: true, width: 2, colors: ['transparent'] },
        xaxis: {
            categories: data.monthLabels,
            labels: { style: { colors: '#94a3b8', fontSize: '10px' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { show: false },
        grid: { show: false },
        legend: { position: 'bottom', markers: { radius: 12 }, offsetY: 5 },
        tooltip: { shared: true, intersect: false }
    };

    new ApexCharts(document.querySelector("#chart-monthly-vol"), {
        ...barConfig,
        series: [
            { name: 'Real', data: data.volumeReal },
            { name: 'RKAP', data: data.rkapVol }
        ],
        colors: ['#F97316', '#E2E8F0']
    }).render();

    new ApexCharts(document.querySelector("#chart-monthly-rev"), {
        ...barConfig,
        series: [
            { name: 'Real', data: data.revenueReal },
            { name: 'RKAP', data: data.rkapRev }
        ],
        colors: ['#334155', '#E2E8F0']
    }).render();

    // ==========================================================
    // 4. DONUT FILTER LISTENER (TIDAK DIUBAH)
    // ==========================================================
    const buyerSelect = document.getElementById('buyer-filter');
    if (buyerSelect) {
        buyerSelect.addEventListener('change', function (e) {
            updateDonutData(e.target.value, 'buyer', chartBuyer);
        });
    }

    const productSelect = document.getElementById('product-filter');
    if (productSelect) {
        productSelect.addEventListener('change', function (e) {
            updateDonutData(e.target.value, 'product', chartProduct);
        });
    }

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

        let categoryData = rawSource && rawSource[category] ? rawSource[category] : {};
        let totalSum = categoryData['TOTAL'] || 0;

        let newSeries = [];
        let newLabels = [];

        Object.keys(categoryData).forEach(key => {
            if (key !== 'TOTAL') {
                newSeries.push(Number(categoryData[key]));
                newLabels.push(key);
            }
        });

        if (chartInstance) {
            chartInstance.updateOptions({ labels: newLabels, colors: colors });
            chartInstance.updateSeries(newSeries);
        }

        if (centerTotalEl) {
            centerTotalEl.innerText = new Intl.NumberFormat('id-ID')
                .format(Math.round(totalSum / 1000));
        }

        if (legendContainer) {
            let html = '';
            newLabels.forEach((name, index) => {
                let val = newSeries[index];
                let pct = totalSum > 0 ? Math.round((val / totalSum) * 100) : 0;
                let color = colors[index % colors.length];

                html += `
                    <div class="legend-item">
                        <span class="dot" style="background:${color}"></span>
                        <span class="name">${name}</span>
                        <span class="val">${pct}%</span>
                    </div>`;
            });
            legendContainer.innerHTML = html;
        }
    }
});
