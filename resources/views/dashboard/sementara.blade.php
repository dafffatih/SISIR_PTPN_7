@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
{{-- BLOK PHP: DATA PRE-PROCESSING --}}
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    // 1. Data Cleaning (Sama seperti sebelumnya)
    $cleanTopBuyers = [];
    $fixedBuyers = [];
    foreach($topBuyers as $name => $val) {
        $upperName = strtoupper($name);
        if(!isset($fixedBuyers[$upperName])) $fixedBuyers[$upperName] = 0;
        $fixedBuyers[$upperName] += $val;
    }
    arsort($fixedBuyers);
    $cleanTopBuyers = array_slice($fixedBuyers, 0, 5);

    // 2. FUNGSI MEMBUAT SINGKATAN (INISIAL)
    // Contoh: "Wilson Tunggal Perkasa" -> "WTP"
    $buyerInitials = [];
    foreach(array_keys($cleanTopBuyers) as $name) {
        $words = explode(' ', $name);
        $initial = '';
        foreach($words as $w) {
            // Ambil huruf pertama, abaikan karakter aneh, pastikan uppercase
            $initial .= strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $w), 0, 1));
        }
        // Jika hasil kosong (misal simbol), pakai 3 huruf pertama nama asli
        $buyerInitials[] = $initial ?: substr($name, 0, 3);
    }

    // 3. Simulasi Data Breakdown Mutu
    $mutuBreakdown = [
        ['name' => 'SIR 20', 'val' => 25200, 'pct' => 42],
        ['name' => 'RSS 1', 'val' => 12500, 'pct' => 21],
        ['name' => 'SIR 3L', 'val' => 10200, 'pct' => 17],
        ['name' => 'SIR 3WF', 'val' => 8500, 'pct' => 14],
        ['name' => 'OFF GRADE', 'val' => 2477, 'pct' => 6],
    ];
@endphp

<link rel="stylesheet" href="{{ asset('css/dashboard-custom.css') }}">

<div class="dashboard-container">
    
    <div class="dashboard-header">
        <div>
            <h1>Dashboard Overview</h1>
            <p>PTPN 1 Regional 7 - Rubber Trading Analytics</p>
        </div>
        <div class="filter-box">
             <span class="icon-calendar">📅</span> 2026
        </div>
    </div>

    <div class="row-grid-2">
        <div class="card-metric">
            <div class="metric-content">
                <div class="metric-left">
                    <span class="metric-label">Total Volume</span>
                    <div class="metric-value-group">
                        <span class="metric-number">{{ number_format($totalVolume / 1000, 3, ',', '.') }}</span>
                        <span class="metric-unit">Ton</span>
                    </div>
                    <div class="metric-progress">
                        Progress: <span class="progress-val">{{ $rkapVolume > 0 ? round(($totalVolume/$rkapVolume)*100, 1) : 0 }}%</span>
                    </div>
                </div>
                <div class="metric-right">
                    <div class="rkap-info">
                        <span class="metric-label">Total Volume RKAP</span>
                        <div class="metric-value-group right-align">
                            <span class="metric-number-small">{{ number_format($rkapVolume/1000, 0, ',', '.') }}</span>
                            <span class="metric-unit-small">Ton</span>
                        </div>
                    </div>
                    <div class="icon-box bg-dark">
                        <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-metric">
            <div class="metric-content">
                <div class="metric-left">
                    <span class="metric-label">Total Revenue</span>
                    <div class="metric-value-group">
                        <span class="metric-number">Rp {{ number_format($totalRevenue / 1000000000, 1, ',', '.') }}</span>
                        <span class="metric-unit">B</span>
                    </div>
                    <div class="metric-progress">
                        Progress: <span class="progress-val">{{ $rkapRevenue > 0 ? round(($totalRevenue/$rkapRevenue)*100, 1) : 0 }}%</span>
                    </div>
                </div>
                <div class="metric-right">
                    <div class="rkap-info">
                        <span class="metric-label">Total Revenue RKAP</span>
                        <div class="metric-value-group right-align">
                            <span class="metric-number-small">Rp {{ number_format($rkapRevenue/1000000000, 1, ',', '.') }}</span>
                            <span class="metric-unit-small">B</span>
                        </div>
                    </div>
                    <div class="icon-box bg-orange">
                        <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row-grid-2">
        <div class="card-std">
            <div class="card-header">
                <h3>Average Selling Price Trend (Monthly)</h3>
            </div>
            <div id="chart-price-monthly"></div>
        </div>

        <div class="col-stacked">
            <div class="card-std card-half">
                <div class="card-header"><h3>Top 5 Buyers</h3></div>
                <div class="donut-container">
                    <div id="chart-buyer" class="chart-donut"></div>
                    
                    <div class="custom-legend">
                        @php $colors = ['#2563EB', '#0D9488', '#F59E0B', '#64748B', '#94A3B8']; $i=0; @endphp
                        @foreach($cleanTopBuyers as $buyer => $vol)
                        <div class="legend-item">
                            <span class="dot" style="background: {{ $colors[$i] }}"></span>
                            <span class="name" title="{{ $buyer }}">
                                {{ Str::limit($buyer, 15) }} 
                                {{-- Opsional: Tampilkan singkatan juga di list --}}
                                <span class="text-xs text-gray-400">({{ $buyerInitials[$i] }})</span>
                            </span>
                            <span class="val">{{ $totalVolume > 0 ? round(($vol/$totalVolume)*100, 0) : 0 }}%</span>
                        </div>
                        @php $i++; @endphp
                        @endforeach
                    </div>
                    
                    <div class="donut-center-label">
                        <span class="lbl">Total Ton</span>
                        <span class="num">{{ number_format($totalVolume/1000, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="card-std card-half">
                <div class="card-header"><h3>Top 5 Products</h3></div>
                <div class="donut-container">
                    <div id="chart-product" class="chart-donut"></div>
                    <div class="custom-legend">
                        @php $i=0; @endphp
                        @foreach($topProducts as $prod => $vol)
                        <div class="legend-item">
                            <span class="dot" style="background: {{ $colors[$i] }}"></span>
                            <span class="name">{{ $prod }}</span>
                            <span class="val">{{ $totalVolume > 0 ? round(($vol/$totalVolume)*100, 0) : 0 }}%</span>
                        </div>
                        @php $i++; @endphp
                        @endforeach
                    </div>
                    <div class="donut-center-label">
                        <span class="lbl">Total Ton</span>
                        <span class="num">{{ number_format($totalVolume/1000, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row-grid-2">
        <div class="card-std card-table-compact">
            <div class="card-header flex-between">
                <h3>Stok Bebas</h3>
                <span class="badge-date">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
            <div class="table-responsive">
                <table class="table-stok">
                    <thead>
                        <tr>
                            <th>Uraian</th>
                            <th>SIR 20</th>
                            <th>RSS</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Stok Produksi</td><td>1.328</td><td>1.135</td><td>2.903</td></tr>
                        <tr><td colspan="4" class="separator-row">OUTSTANDING CONTRACT</td></tr>
                        <tr><td>Sudah Bayar</td><td>1.475</td><td>312</td><td>1.868</td></tr>
                        <tr><td>Belum Bayar</td><td>5.161</td><td>2.516</td><td>8.035</td></tr>
                        <tr class="row-sum"><td>Jumlah</td><td>6.636</td><td>2.828</td><td>9.904</td></tr>
                        <tr class="row-highlight"><td>Stok Bebas</td><td>-5.308</td><td>-1.694</td><td>-6.905</td></tr>
                        <tr><td>Stok Bahan Baku</td><td>4.448</td><td>-</td><td>4.448</td></tr>
                        <tr class="row-highlight"><td>Stok Bebas</td><td>-860</td><td>-1.694</td><td>-2.457</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="empty-placeholder"></div>
    </div>

    <div class="card-std p-0 full-row-card">
        <div class="chart-header-padded">
            <h3>Monthly Volume (Real vs RKAP)</h3>
        </div>
        <div class="layout-3-cols">
            <div class="col-chart">
                <div id="chart-monthly-vol"></div>
            </div>
            <div class="col-middle bg-light">
                <p class="sidebar-title">Rincian Mutu</p>
                <div class="mutu-list">
                    @foreach($mutuBreakdown as $m)
                    <div class="mutu-item">
                        <div class="mutu-info">
                            <span class="mutu-name">{{ $m['name'] }}</span>
                            <span class="mutu-pct">{{ $m['pct'] }}%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: {{ $m['pct'] }}%"></div>
                        </div>
                        <span class="mutu-val">{{ number_format($m['val'], 0, ',', '.') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-right bg-light border-left">
                <p class="sidebar-title">Ringkasan Total</p>
                <div class="stats-container">
                    <div class="summary-item">
                        <span class="sum-label">Total Real</span>
                        <span class="sum-val orange">{{ number_format($totalVolume / 1000, 0, ',', '.') }} <small>Ton</small></span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Total RKAP</span>
                        <span class="sum-val dark">{{ number_format($rkapVolume / 1000, 0, ',', '.') }} <small>Ton</small></span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Percentage</span>
                        <span class="sum-val huge">{{ $rkapVolume > 0 ? round(($totalVolume/$rkapVolume)*100, 0) : 0 }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-std p-0 full-row-card">
        <div class="chart-header-padded">
            <h3>Monthly Revenue (Real vs RKAP)</h3>
        </div>
        <div class="layout-3-cols">
            <div class="col-chart">
                <div id="chart-monthly-rev"></div>
            </div>
            <div class="col-middle bg-light">
                <p class="sidebar-title">Rincian Mutu</p>
                <div class="mutu-list">
                    @foreach($mutuBreakdown as $m)
                    <div class="mutu-item">
                        <div class="mutu-info">
                            <span class="mutu-name">{{ $m['name'] }}</span>
                            <span class="mutu-pct">{{ $m['pct'] }}%</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill orange" style="width: {{ $m['pct'] }}%"></div>
                        </div>
                        <span class="mutu-val">Rp {{ number_format(($m['val'] * 25000)/1000000, 0, ',', '.') }}M</span>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="col-right bg-light border-left">
                <p class="sidebar-title">Ringkasan Total</p>
                <div class="stats-container">
                    <div class="summary-item">
                        <span class="sum-label">Total Real</span>
                        <span class="sum-val orange">Rp {{ number_format($totalRevenue / 1000000000, 1, ',', '.') }}<small>B</small></span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Total RKAP</span>
                        <span class="sum-val dark">Rp {{ number_format($rkapRevenue / 1000000000, 1, ',', '.') }}<small>B</small></span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Percentage</span>
                        <span class="sum-val huge">{{ $rkapRevenue > 0 ? round(($totalRevenue/$rkapRevenue)*100, 0) : 0 }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    window.dashboardData = {
        topBuyers: @json(array_values($cleanTopBuyers)),
        // PERUBAHAN DISINI: Label Chart sekarang menggunakan INISIAL (Singkatan)
        topBuyersLabels: @json($buyerInitials), 
        
        topProducts: @json(array_values($topProducts)),
        topProductsLabels: @json(array_keys($topProducts)),
        volumeReal: @json(array_values($volumePerMonth)),
        revenueReal: @json(array_values($revenuePerMonth)),
        rkapVol: Array(12).fill({{ $rkapVolume / 12 }}),
        rkapRev: Array(12).fill({{ $rkapRevenue / 12 }}),
        priceMonthly: [
            { name: 'SIR 20', data: [29000, 29500, 30000, 29800, 30500, 31000, 30800, 31200, 31500, 32000, 31800, 32500] },
            { name: 'RSS', data: [31000, 31500, 32000, 32500, 33000, 32800, 33500, 34000, 33800, 34500, 35000, 34800] },
            { name: 'SIR 3L', data: [30000, 30500, 30800, 31000, 31500, 32000, 31800, 32200, 32500, 33000, 33500, 34000] }
        ]
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="{{ asset('js/dashboard-script.js') }}"></script>
@endsection