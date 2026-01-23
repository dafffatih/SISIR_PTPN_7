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
            let normalizedKey = key.trim().toUpperCase();
            if (normalizedKey !== 'TOTAL' && normalizedKey !== 'LAINNYA') {
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
    var initialBuyerData = processChartData(data.rawTopBuyers, 'TOTAL');

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
                    // PERBAIKAN 1: Desimal diubah jadi 0
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val / 1000) + " Ton";
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
    var initialProductData = processChartData(data.rawTopProducts, 'TOTAL');

    var productOptions = {
        chart: { type: 'pie', height: 200, fontFamily: commonFont },
        colors: data.prodColors,
        series: initialProductData.series,
        labels: initialProductData.labels,
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                let value = opts.w.globals.series[opts.seriesIndex];
                let grandTotal = window.currentProductTotal || initialProductTotal;
                let pct = grandTotal > 0 ? (value / grandTotal) * 100 : 0;
                return Math.round(pct) + '%';
            },
            style: {
                fontSize: '11px',
                fontFamily: commonFont,
                fontWeight: 'bold'
            },
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
                    // PERBAIKAN 2: Ubah dari Persentase ke Ton, dan Desimal 0
                    return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(val / 1000) + " Ton";
                }
            }
        }
    };

    var chartProduct = new ApexCharts(document.querySelector("#chart-product"), productOptions);
    chartProduct.render();


    // ==========================================================
    // 3. UPDATE FUNCTION (DONUT)
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

        // PERBAIKAN 3: Update angka tengah saat ganti dropdown (Desimal 0)
        if (centerTotalEl) {
            centerTotalEl.innerText = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 })
                .format(grandTotal / 1000);
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
            zoom: { enabled: false },
            selection: { enabled: false },
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
    // CUSTOM DRAG-TO-SCROLL FOR CHART CONTAINER
    // ==========================================================
    (function initChartDragScroll() {
        const scrollContainer = document.querySelector('.price-chart-wrapper .chart-scroll-container');
        if (!scrollContainer) return;

        let isDragging = false;
        let startX = 0;
        let scrollLeft = 0;

        scrollContainer.addEventListener('mousedown', (e) => {
            if (e.target.closest('.apexcharts-tooltip') || e.target.closest('.apexcharts-legend')) return;

            isDragging = true;
            scrollContainer.style.cursor = 'grabbing';
            startX = e.pageX - scrollContainer.offsetLeft;
            scrollLeft = scrollContainer.scrollLeft;
            e.preventDefault();
        });

        scrollContainer.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.pageX - scrollContainer.offsetLeft;
            const walk = (x - startX); 
            scrollContainer.scrollLeft = scrollLeft - walk;
        });

        scrollContainer.addEventListener('mouseup', () => {
            isDragging = false;
            scrollContainer.style.cursor = 'grab';
        });

        scrollContainer.addEventListener('mouseleave', () => {
            isDragging = false;
            scrollContainer.style.cursor = 'grab';
        });

        scrollContainer.addEventListener('touchstart', (e) => {
            if (e.target.closest('.apexcharts-tooltip') || e.target.closest('.apexcharts-legend')) return;
            isDragging = true;
            startX = e.touches[0].pageX - scrollContainer.offsetLeft;
            scrollLeft = scrollContainer.scrollLeft;
        }, { passive: true });

        scrollContainer.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            const x = e.touches[0].pageX - scrollContainer.offsetLeft;
            const walk = (x - startX);
            scrollContainer.scrollLeft = scrollLeft - walk;
        }, { passive: true });

        scrollContainer.addEventListener('touchend', () => {
            isDragging = false;
        });

        scrollContainer.style.cursor = 'grab';
    })();

    // ==========================================================
    // 5. BAR CHARTS (Volume & Revenue)
    // ==========================================================

    function createPercentAnnotations(realData, rkapData) {
        return data.monthLabels.map((month, index) => {
            const real = parseFloat(realData[index] || 0);
            const rkap = parseFloat(rkapData[index] || 0);
            let percentText = "";

            if (rkap > 0) {
                const pct = Math.round((real / rkap) * 100);
                percentText = `${pct}%`;
            }

            return {
                x: month,
                y: 0,
                borderColor: 'transparent',
                label: {
                    borderColor: 'transparent',
                    style: {
                        background: 'transparent',
                        fontSize: '11px',
                        fontFamily: 'Inter, sans-serif',
                        padding: { left: 0, right: 0, top: 0, bottom: 0 },
                        cssClass: 'apexcharts-point-annotation-label'
                    },
                    text: percentText,
                    position: 'center',
                    offsetY: 0,
                }
            };
        });
    }

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
                columnWidth: '60%',
                borderRadius: 4,
                dataLabels: {
                    position: 'top',
                    hideOverflowingLabels: false
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
        yaxis: { show: false },
        grid: {
            show: false,
            padding: { top: 40, bottom: 20 }
        },
        legend: { position: 'bottom', offsetY: 10 },
        tooltip: { shared: true, intersect: false }
    };

    // Helper formatter untuk Label Bar Chart
    const fmtBarChart = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 });

    // --- CHART MONTHLY VOLUME (BAGI 1000 -> TON) ---
    const volRealTon = data.volumeReal.map(v => v / 1000);
    const volRkapTon = data.rkapVol.map(v => v / 1000);

    new ApexCharts(document.querySelector("#chart-monthly-vol"), {
        ...commonBarConfig,
        annotations: {
            points: createPercentAnnotations(data.volumeReal, data.rkapVol)
        },
        series: [
            { name: 'Real', data: volRealTon },
            { name: 'RKAP', data: volRkapTon }
        ],
        colors: ['#F97316', '#a2c4c9'],
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: { 
                fontSize: '10px', 
                colors: ['#334155'] 
            },
            formatter: function (val) {
                return fmtBarChart.format(val);
            },
            background: { enabled: false },
            dropShadow: { enabled: false }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return fmtBarChart.format(val) + " Ton";
                }
            }
        }
    }).render();

    // --- CHART MONTHLY REVENUE (BAGI 1 MILYAR -> MILYAR) ---
    const revRealBil = data.revenueReal.map(v => v / 1000000000);
    const revRkapBil = data.rkapRev.map(v => v / 1000000000);

    new ApexCharts(document.querySelector("#chart-monthly-rev"), {
        ...commonBarConfig,
        annotations: {
            points: createPercentAnnotations(data.revenueReal, data.rkapRev)
        },
        series: [
            { name: 'Real', data: revRealBil },
            { name: 'RKAP', data: revRkapBil }
        ],
        colors: ['#F97316', '#a2c4c9'],
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: { 
                fontSize: '10px', 
                colors: ['#334155'] 
            },
            formatter: function (val) {
                return fmtBarChart.format(val);
            },
            background: { enabled: false },
            dropShadow: { enabled: false }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return "Rp " + fmtBarChart.format(val) + " Milyar";
                }
            }
        }
    }).render();


    // ==========================================================
    // GLOBAL RESIZE HANDLER
    // ==========================================================
    let globalResizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(globalResizeTimer);
        globalResizeTimer = setTimeout(function () {
            window.dispatchEvent(new Event('resize'));
            document.querySelectorAll('.apexcharts-canvas').forEach(function (canvas) {
                if (canvas.parentElement) {
                    canvas.style.width = '100%';
                }
            });
        }, 150);
    });

    // ==========================================================
    // 6. LOGIKA FILTER BULAN & KALKULASI TOTAL METRICS (FIXED)
    // ==========================================================
    
    const startSelect = document.getElementById('month-start');
    const endSelect = document.getElementById('month-end');
    
    const elVolReal = document.getElementById('metric-vol-real');
    const elVolRkap = document.getElementById('metric-vol-rkap');
    const elVolProg = document.getElementById('metric-vol-progress');
    
    const elRevReal = document.getElementById('metric-rev-real');
    const elRevRkap = document.getElementById('metric-rev-rkap');
    const elRevProg = document.getElementById('metric-rev-progress');

    const elSidebarVol = document.getElementById('sidebar-vol-real');
    const elSidebarVolRkap = document.getElementById('sidebar-vol-rkap');
    const elSidebarVolPct = document.getElementById('sidebar-vol-pct'); // Target Volume %

    const elSidebarRev = document.getElementById('sidebar-rev-real');
    const elSidebarRevRkap = document.getElementById('sidebar-rev-rkap');
    const elSidebarRevPct = document.getElementById('sidebar-rev-pct'); // Target Revenue %

    function calculateMetrics() {
        let startMonth = parseInt(startSelect.value);
        let endMonth = parseInt(endSelect.value);

        if (startMonth > endMonth) { 
            endMonth = startMonth; 
            endSelect.value = endMonth; 
        }

        const startIndex = startMonth - 1; 
        const endIndex = endMonth; 

        const sumFiltered = (arr) => {
            if (!arr || arr.length === 0) return 0;
            return arr.slice(startIndex, endIndex).reduce((a, b) => a + b, 0);
        };

        const sumFullYear = (arr) => {
            if (!arr || arr.length === 0) return 0;
            return arr.reduce((a, b) => a + b, 0);
        };

        // --- 1. DATA HEADER (DINAMIS SESUAI FILTER) ---
        const headVolReal = sumFiltered(data.volumeReal); 
        const headVolRkap = sumFiltered(data.rkapVol);
        const headRevReal = sumFiltered(data.revenueReal);
        const headRevRkap = sumFiltered(data.rkapRev);

        let progVol = headVolRkap > 0 ? (headVolReal / headVolRkap) * 100 : 0;
        let progRev = headRevRkap > 0 ? (headRevReal / headRevRkap) * 100 : 0;

        // --- 2. DATA SIDEBAR (STATIS JAN-DES) ---
        const sideVolReal = sumFullYear(data.volumeReal);
        const sideRevReal = sumFullYear(data.revenueReal);
        
        const sideVolRkap = sumFullYear(data.rkapVol);
        const sideRevRkap = sumFullYear(data.rkapRev);

        // Hitung persentase sidebar
        let sideVolPct = sideVolRkap > 0 ? (sideVolReal / sideVolRkap) * 100 : 0;
        let sideRevPct = sideRevRkap > 0 ? (sideRevReal / sideRevRkap) * 100 : 0;

        // ==========================================
        // FORMATTER
        // ==========================================
        const fmtDec = new Intl.NumberFormat('id-ID', { 
            minimumFractionDigits: 0,
            maximumFractionDigits: 3 
        });
        
        // HAPUS atau TIDAK PERLU fmtInt untuk persentase sidebar
        // const fmtInt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }); 

        // ==========================================
        // UPDATE HEADER (DINAMIS)
        // ==========================================
        if (elVolReal) elVolReal.innerText = fmtDec.format(headVolReal / 1000); 
        if (elVolRkap) elVolRkap.innerText = fmtDec.format(headVolRkap / 1000);
        if (elVolProg) {
            elVolProg.innerText = progVol.toFixed(1) + '%';
            elVolProg.className = 'progress-val ' + (progVol >= 100 ? 'progress-green' : 'progress-red');
        }

        if (elRevReal) elRevReal.innerText = 'Rp ' + fmtDec.format(headRevReal / 1000000000); 
        if (elRevRkap) elRevRkap.innerText = 'Rp ' + fmtDec.format(headRevRkap / 1000000000);
        if (elRevProg) {
            elRevProg.innerText = progRev.toFixed(1) + '%';
            elRevProg.className = 'progress-val ' + (progRev >= 100 ? 'progress-green' : 'progress-red');
        }

        // ==========================================
        // UPDATE SIDEBAR (STATIS FULL YEAR)
        // ==========================================
        
        // --- VOLUME SIDEBAR ---
        if (elSidebarVol) {
            elSidebarVol.innerHTML = fmtDec.format(sideVolReal / 1000) + ' <small>Ton</small>';
        }
        if (elSidebarVolRkap) {
            elSidebarVolRkap.innerHTML = fmtDec.format(sideVolRkap / 1000) + ' <small>Ton</small>';
        }
        
        // --- PERBAIKAN DI SINI (Volume %) ---
        if (elSidebarVolPct) {
            // Gunakan .toFixed(1) agar muncul 1 angka belakang koma (3.7%)
            elSidebarVolPct.innerText = sideVolPct.toFixed(1) + '%';
        }

        // --- REVENUE SIDEBAR ---
        if (elSidebarRev) {
            elSidebarRev.innerHTML = 'Rp ' + fmtDec.format(sideRevReal / 1000000000) + ' <small>Milyar</small>';
        }
        if (elSidebarRevRkap) {
            elSidebarRevRkap.innerHTML = 'Rp ' + fmtDec.format(sideRevRkap / 1000000000) + ' <small>Milyar</small>';
        }

        // --- PERBAIKAN DI SINI (Revenue %) ---
        if (elSidebarRevPct) {
            // Gunakan .toFixed(1) agar muncul 1 angka belakang koma (3.7%)
            elSidebarRevPct.innerText = sideRevPct.toFixed(1) + '%';
        }
    }

    if (startSelect && endSelect) {
        startSelect.addEventListener('change', calculateMetrics);
        endSelect.addEventListener('change', calculateMetrics);
    }

    // Jalankan kalkulasi awal
    calculateMetrics();

    
});