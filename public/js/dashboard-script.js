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
    // Allows users to drag the chart horizontally to see hidden data
    // Speed matches user's drag speed for natural feel
    // ==========================================================
    (function initChartDragScroll() {
        const scrollContainer = document.querySelector('.price-chart-wrapper .chart-scroll-container');
        if (!scrollContainer) return;

        let isDragging = false;
        let startX = 0;
        let scrollLeft = 0;

        // Mouse events
        scrollContainer.addEventListener('mousedown', (e) => {
            // Ignore if clicking on chart interactive elements
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
            const walk = (x - startX); // 1:1 speed ratio
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

        // Touch events for mobile
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

        // Set initial cursor style
        scrollContainer.style.cursor = 'grab';
    })();

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
                percentText = `${pct}%`;
            }

            return {
                x: month,
                y: 0,
                borderColor: 'transparent',
                label: {
                    borderColor: 'transparent',
                    style: {
                        // Warna akan di-override oleh CSS (!important), tapi set transparant untuk safety
                        background: 'transparent', // <--- PENTING: Hapus background kotak
                        fontSize: '11px',          // Sesuaikan ukuran font
                        fontFamily: 'Inter, sans-serif',
                        padding: { left: 0, right: 0, top: 0, bottom: 0 },
                        cssClass: 'apexcharts-point-annotation-label' // Pastikan class ini terbaca
                    },
                    text: percentText,
                    position: 'center',
                    offsetY: 0, // Sesuaikan posisi vertikal jika perlu
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
        colors: ['#F97316', '#a2c4c9'],
        dataLabels: {
            enabled: true,
            offsetY: -25,
            style: {
                fontSize: '11px',
                // Hapus colors, cssClass, dll dari sini. Kita atur di CSS saja.
            },
            background: { enabled: false }, // Matikan background
            dropShadow: { enabled: false },
            // formatter: function (val) {
            //     return smartRevenueFormat(val);
            // }
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
        colors: ['#F97316', '#a2c4c9'],
        dataLabels: {
            enabled: true,
            offsetY: -25,
            style: {
                fontSize: '11px',
                // Hapus colors, cssClass, dll dari sini. Kita atur di CSS saja.
            },
            background: { enabled: false }, // Matikan background
            dropShadow: { enabled: false },
            // formatter: function (val) {
            //     return smartRevenueFormat(val);
            // }
        }
    }).render();

    // ==========================================================
    // GLOBAL RESIZE HANDLER FOR ALL CHARTS
    // Ensures all ApexCharts resize properly when window changes
    // ==========================================================
    let globalResizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(globalResizeTimer);
        globalResizeTimer = setTimeout(function () {
            // Trigger ApexCharts resize event
            window.dispatchEvent(new Event('resize'));

            // Update all chart widths to match container
            document.querySelectorAll('.apexcharts-canvas').forEach(function (canvas) {
                if (canvas.parentElement) {
                    canvas.style.width = '100%';
                }
            });
        }, 150);
    });
    // ==========================================================
    // 6. LOGIKA FILTER BULAN & KALKULASI TOTAL METRICS
    // ==========================================================
    
    const startSelect = document.getElementById('month-start');
    const endSelect = document.getElementById('month-end');
    
    // Elemen DOM untuk Volume
    const elVolReal = document.getElementById('metric-vol-real');
    const elVolRkap = document.getElementById('metric-vol-rkap');
    const elVolProg = document.getElementById('metric-vol-progress');
    
    // Elemen DOM untuk Revenue
    const elRevReal = document.getElementById('metric-rev-real');
    const elRevRkap = document.getElementById('metric-rev-rkap');
    const elRevProg = document.getElementById('metric-rev-progress');

    function calculateMetrics() {
        // Ambil value bulan (1-12)
        let startMonth = parseInt(startSelect.value); 
        let endMonth = parseInt(endSelect.value);

        // Validasi: Jika start > end, tukar atau set sama (opsional, disini kita biarkan user tanggung jawab atau force end >= start)
        if (startMonth > endMonth) {
            // Opsional: Alert user atau otomatis ubah endMonth
            endMonth = startMonth; 
            endSelect.value = endMonth;
        }

        // Array index dimulai dari 0 (Januari) s/d 11 (Desember)
        // Kita kurangi 1 karena value dropdown 1-12
        const startIndex = startMonth - 1; 
        const endIndex = endMonth; // Slice di JS bersifat exclusive di parameter kedua, jadi tidak perlu dikurangi 1 agar bulan akhir terbawa

        // Fungsi Helper Penjumlahan Array
        const sumArray = (arr) => {
            if (!arr || arr.length === 0) return 0;
            // Ambil potongan array dari startIndex sampai endIndex
            const sliced = arr.slice(startIndex, endIndex); 
            // Jumlahkan
            return sliced.reduce((a, b) => a + b, 0);
        };

        // --- 1. HITUNG VOLUME ---
        // Data diambil dari rekap4['volume_real'] dan rekap4['volume_rkap']
        const totalVolReal = sumArray(data.volumeReal); 
        const totalVolRkap = sumArray(data.rkapVol);
        
        // Progress Volume
        let progVol = 0;
        if (totalVolRkap > 0) {
            progVol = (totalVolReal / totalVolRkap) * 100;
        }

        // --- 2. HITUNG REVENUE ---
        // Data diambil dari rekap4['revenue_real'] dan rekap4['revenue_rkap']
        const totalRevReal = sumArray(data.revenueReal);
        const totalRevRkap = sumArray(data.rkapRev);

        // Progress Revenue
        let progRev = 0;
        if (totalRevRkap > 0) {
            progRev = (totalRevReal / totalRevRkap) * 100;
        }

        // --- 3. UPDATE TAMPILAN (FORMAT ANGKA) ---
        const fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }); // Format ribuan (titik)

        // Update Volume (Dibagi 1000 untuk Ton jika data aslinya Kg, sesuaikan dengan logic backend Anda)
        // Cek backend Anda: $rekap4 sudah berupa angka murni. 
        // Di blade lama Anda pakai /1000. Saya asumsikan data di rekap4 perlu dibagi 1000 untuk jadi TON?
        // Note: Biasanya rekap4 sudah dalam satuan yang pas, tapi mari kita ikuti pola blade lama Anda yang membagi.
        // TAPI: Di DashboardController, rekap4['volume_real'] sepertinya sudah float. 
        // Jika di grafik angkanya jutaan, berarti Kg. Jika ribuan, berarti Ton.
        // Mari asumsikan logic blade Anda sebelumnya: ($top5Buyers... / 1000). 
        // JIKA data.volumeReal satuannya KG, maka bagi 1000. JIKA sudah TON, hapus pembagiannya.
        // Amannya kita lihat metric number blade Anda: number_format(X / 1000). Jadi kita bagi 1000.
        
        elVolReal.innerText = fmt.format(totalVolReal); 
        elVolRkap.innerText = fmt.format(totalVolRkap); // RKAP biasanya juga perlu disesuaikan satuannya
        
        elVolProg.innerText = progVol.toFixed(1) + '%';
        // Update warna progress
        elVolProg.className = 'progress-val ' + (progVol >= 100 ? 'progress-green' : 'progress-red');


        // Update Revenue (Dibagi 1.000.000.000 untuk Milyar)
        // Di controller Anda: $rekap4['revenue_real'] sepertinya raw number.
        // Blade lama: number_format($totalRevenue / 1000000000).
        elRevReal.innerText = 'Rp ' + fmt.format(totalRevReal);
        elRevRkap.innerText = 'Rp ' + fmt.format(totalRevRkap);

        elRevProg.innerText = progRev.toFixed(1) + '%';
        elRevProg.className = 'progress-val ' + (progRev >= 100 ? 'progress-green' : 'progress-red');
    }

    // Event Listeners
    if (startSelect && endSelect) {
        startSelect.addEventListener('change', calculateMetrics);
        endSelect.addEventListener('change', calculateMetrics);
    }

    // Jalankan sekali saat load agar angka muncul (Default Jan-Des)
    calculateMetrics();
});