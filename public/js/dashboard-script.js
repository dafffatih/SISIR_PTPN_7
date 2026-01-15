document.addEventListener("DOMContentLoaded", function () {
    const data = window.dashboardData;
    const commonFont = 'Inter, sans-serif';

    // ==========================================================
    // HELPER FUNCTIONS
    // ==========================================================

    function getGrandTotal(source, category) {
        if (!source[category]) return 0;
        return source[category]['TOTAL'] || 0;
    }

    function processChartData(source, category) {
        let categoryData = source[category] || {};
        let series = [];
        let labels = [];
        
        Object.keys(categoryData).forEach(key => {
            if (key !== 'TOTAL' && key !== 'LAINNYA') {
                series.push(Number(categoryData[key]));
                labels.push(key);
            }
        });
        return { series, labels };
    }

    // ==========================================================
    // 1. CHART BUYERS
    // ==========================================================
    var initialBuyerTotal = getGrandTotal(data.rawTopBuyers, 'TOTAL');
    var initialBuyerData  = processChartData(data.rawTopBuyers, 'TOTAL');

    var buyerOptions = {
        chart: { type: 'pie', height: 200, fontFamily: commonFont },
        colors: data.chartColors,
        series: initialBuyerData.series,
        labels: initialBuyerData.labels,
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                let value = opts.w.globals.series[opts.seriesIndex];
                let grandTotal = window.currentBuyerTotal || initialBuyerTotal; 
                let pct = grandTotal > 0 ? (value / grandTotal) * 100 : 0;
                return Math.round(pct) + '%'; 
            },
            style: { fontSize: '11px', fontFamily: commonFont, fontWeight: 'bold' },
            dropShadow: { enabled: false }
        },
        plotOptions: {
            pie: { 
                offsetY: 0, 
                customScale: 1,
                donut: { size: '65%' },
                dataLabels: { offset: -20 } 
            }
        },
        legend: { show: false },
        stroke: { show: false },
        tooltip: {
            y: { 
                formatter: function (val) { 
                    return new Intl.NumberFormat('id-ID').format(val / 1000) + " Ton"; 
                } 
            }
        }
    };

    var chartBuyer = new ApexCharts(document.querySelector("#chart-buyer"), buyerOptions);
    chartBuyer.render();

    // ==========================================================
    // 2. CHART PRODUCTS
    // ==========================================================
    var initialProductTotal = getGrandTotal(data.rawTopProducts, 'TOTAL');
    var initialProductData  = processChartData(data.rawTopProducts, 'TOTAL');

    var productOptions = {
        chart: { type: 'pie', height: 200, fontFamily: commonFont },
        colors: data.prodColors,
        series: initialProductData.series,
        labels: initialProductData.labels,
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                let value = opts.w.globals.series[opts.seriesIndex];
                let valTon = value / 1000;
                return new Intl.NumberFormat('id-ID').format(Math.round(valTon)) + " Ton"; 
            },
            style: { fontSize: '10px', fontFamily: commonFont, fontWeight: 'bold' },
            dropShadow: { enabled: false }
        },
        plotOptions: {
            pie: {
                offsetY: 0,
                customScale: 1,
                dataLabels: { offset: -20 }
            }
        },
        legend: { show: false }, 
        stroke: { show: false },
        tooltip: {
            y: { 
                formatter: function (val) { 
                    let grandTotal = window.currentProductTotal || initialProductTotal;
                    let pct = grandTotal > 0 ? (val / grandTotal) * 100 : 0;
                    return Math.round(pct) + '%';
                } 
            }
        }
    };

    var chartProduct = new ApexCharts(document.querySelector("#chart-product"), productOptions);
    chartProduct.render();


    // ==========================================================
    // 3. UPDATE FUNCTION
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
        let grandTotal = 0;

        if (type === 'buyer') {
            rawSource = data.rawTopBuyers;
            colors = data.chartColors;
            legendContainer = document.getElementById('buyer-legend-container');
            centerTotalEl = document.getElementById('buyer-center-total');
            grandTotal = getGrandTotal(rawSource, category);
            window.currentBuyerTotal = grandTotal; 
        } else {
            rawSource = data.rawTopProducts;
            colors = data.prodColors;
            legendContainer = document.getElementById('product-legend-container');
            centerTotalEl = document.getElementById('product-center-total');
            grandTotal = getGrandTotal(rawSource, category);
            window.currentProductTotal = grandTotal; 
        }

        let processed = processChartData(rawSource, category);

        if (chartInstance) {
            chartInstance.updateOptions({ labels: processed.labels, colors: colors });
            chartInstance.updateSeries(processed.series);
        }

        if (centerTotalEl) {
            centerTotalEl.innerText = new Intl.NumberFormat('id-ID')
                .format(Math.round(grandTotal / 1000));
        }

        if (legendContainer) {
            let html = '';
            processed.labels.forEach((name, index) => {
                let color = colors[index % colors.length];
                html += `
                    <div class="legend-item">
                        <span class="dot" style="background:${color}"></span>
                        <span class="name">${name}</span>
                    </div>`;
            });
            legendContainer.innerHTML = html;
        }
    }

    // ==========================================================
    // 4. CHART HARGA
    // ==========================================================
    const originalPriceSeries = JSON.parse(JSON.stringify(data.priceDaily));

    var priceOptions = {
        series: data.priceDaily,
        chart: {
            type: 'line',
            height: 320,
            toolbar: { show: false },
            fontFamily: commonFont,
            animations: {
                enabled: true, easing: 'easeinout', speed: 600,
                animateGradually: { enabled: true, delay: 150 },
                dynamicAnimation: { enabled: true, speed: 500 }
            }
        },
        colors: ['#1E293B', '#F97316', '#0D9488'],
        stroke: { curve: 'smooth', width: 2 },
        xaxis: {
            type: 'datetime',
            labels: { format: 'MMM', style: { colors: '#0F172A', fontSize: '12px', fontWeight: 600 } },
            axisBorder: { show: false }, axisTicks: { show: false }
        },
        yaxis: {
            min: 20000, max: 50000, tickAmount: 6,
            labels: {
                formatter: (val) => 'Rp ' + (val / 1000).toFixed(0) + 'k',
                style: { colors: '#0F172A', fontSize: '12px', fontWeight: 600 }
            }
        },
        tooltip: { shared: true, x: { format: 'dd MMM yyyy' } },
        grid: { borderColor: '#262323', strokeDashArray: 4, yaxis: { lines: { show: true } } },
        legend: { position: 'top', horizontalAlign: 'right' }
    };

    const priceChart = new ApexCharts(document.querySelector("#chart-price-monthly"), priceOptions);
    priceChart.render();

    function filterPriceByMonth(value) {
        if (value === 'all') {
            priceChart.updateOptions({ xaxis: {} }, false, true);
            priceChart.updateSeries(originalPriceSeries, true);
            return;
        }
        const monthCount = parseInt(value);
        const now = new Date();
        const fromDate = new Date();
        fromDate.setMonth(now.getMonth() - monthCount);
        const filteredSeries = originalPriceSeries.map(series => ({
            name: series.name,
            data: series.data.filter(point => new Date(point[0]) >= fromDate)
        }));
        priceChart.updateOptions({ xaxis: { min: fromDate.getTime(), max: now.getTime() } }, false, true);
        priceChart.updateSeries(filteredSeries, true);
    }

    const priceRangeSelect = document.getElementById('price-range');
    if (priceRangeSelect) {
        priceRangeSelect.addEventListener('change', function () { filterPriceByMonth(this.value); });
    }
    filterPriceByMonth(12);

    // ==========================================================
    // 5. BAR CHARTS (Volume & Revenue)
    // ==========================================================

    // --- 1. Fungsi Smart Formatter (Revenue) ---
    function smartRevenueFormat(val) {
        if (val === 0 || val === null || isNaN(val)) return "0";
        let num = parseFloat(val);
        let absNum = Math.abs(num);
        
        if (absNum >= 1000000000) return (num / 1000000000).toFixed(1) + ' M';
        else if (absNum >= 1000000) return (num / 1000000).toFixed(0) + ' Jt';
        else return num.toFixed(0) + ' M';
    }

    // --- 2. Fungsi Membuat Anotasi Persentase di Bawah ---
    // Ini trik untuk menaruh teks persentase di dasar grafik (y=0)
    function createPercentAnnotations(realData, rkapData) {
    return data.monthLabels.map((month, index) => {
        const real = parseFloat(realData[index] || 0);
        const rkap = parseFloat(rkapData[index] || 0);
        let percentText = "";

        if (rkap > 0) {
            const pct = Math.round((real / rkap) * 100);
            percentText = `(${pct}%)`;
        }

        return {
            x: month, 
            y: 0,
            borderColor: 'transparent',
            label: {
                borderColor: 'transparent',
                style: {
                    color: '#ffffff',
                    fontSize: '10px',
                    fontWeight: 'bold',
                    background: 'rgba(0, 0, 0, 0.5)', 
                    padding: { left: 4, right: 4, top: 2, bottom: 2 } 
                },
                text: percentText,
                position: 'center',
                offsetY: -5,
            }
        };
    });
}

    // --- 3. Konfigurasi Umum ---
    const commonBarConfig = {
        chart: { 
            type: 'bar', 
            height: 400, 
            toolbar: { show: false }, 
            fontFamily: commonFont 
        },
        plotOptions: { 
            bar: { 
                horizontal: false, 
                columnWidth: '60%', // Lebar batang sedikit ditambah agar rapat
                borderRadius: 4,
                dataLabels: {
                    position: 'top', // Label Angka tetap di Atas
                    hideOverflowingLabels: false // PENTING: Agar label tetap muncul walau sempit
                }
            } 
        },
        stroke: { show: true, width: 6, colors: ['transparent'] }, 
        xaxis: {
            categories: data.monthLabels,
            labels: { 
                style: { colors: '#64748B', fontSize: '12px', fontWeight: 600 }
            },
            axisBorder: { show: false }, 
            axisTicks: { show: false }
        },
        yaxis: { show: false }, // Sembunyikan Y Axis
        grid: { 
            show: false,
            padding: { top: 40, bottom: 20 } // Padding atas utk Angka, Bawah utk Persen
        },
        legend: { position: 'bottom', offsetY: 10 },
        tooltip: { shared: true, intersect: false }
    };

    // --- A. CHART MONTHLY VOLUME ---
    new ApexCharts(document.querySelector("#chart-monthly-vol"), {
        ...commonBarConfig,
        annotations: {
            points: createPercentAnnotations(data.volumeReal, data.rkapVol)
        },
        series: [
            { name: 'Real', data: data.volumeReal }, 
            { name: 'RKAP', data: data.rkapVol }
        ],
        colors: ['#F97316', '#CBD5E1'], 
        dataLabels: {
            enabled: true,
            offsetY: -25, // Floating di atas batang
            style: { 
                fontSize: '11px', 
                colors: ['#0F172A'], 
                fontWeight: 800,
            },
            background: { 
                enabled: true, 
                foreColor: '#fff', 
                padding: 4, 
                opacity: 0.5, // OPACITY DIKURANGI (Transparan)
                borderRadius: 2,
                borderWidth: 0,
            },
            formatter: function (val) {
                // Hanya return Angka (Persentase sudah diurus Annotations)
                return val >= 1000 ? (val / 1000).toFixed(1) + 'k' : val.toFixed(0);
            }
        }
    }).render();

    // --- B. CHART MONTHLY REVENUE ---
    new ApexCharts(document.querySelector("#chart-monthly-rev"), {
        ...commonBarConfig,
        annotations: {
            points: createPercentAnnotations(data.revenueReal, data.rkapRev)
        },
        series: [
            { name: 'Real', data: data.revenueReal }, 
            { name: 'RKAP', data: data.rkapRev }
        ],
        colors: ['#334155', '#CBD5E1'], 
        dataLabels: {
            enabled: true,
            offsetY: -25, 
            style: { 
                fontSize: '11px', 
                colors: ['#0F172A'], 
                fontWeight: 800,
            },
            background: { 
                enabled: true, 
                foreColor: '#fff', 
                padding: 4, 
                opacity: 0.5, // OPACITY DIKURANGI
                borderRadius: 2,
            },
            formatter: function (val) {
                return smartRevenueFormat(val); // Hanya return Angka
            }
        }
    }).render();
});