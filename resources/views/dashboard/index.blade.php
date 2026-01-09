@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
{{-- BLOK PHP: DATA PRE-PROCESSING --}}
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    // 1. Data Cleaning Top Buyers (Sama seperti sebelumnya)
    $cleanTopBuyers = [];
    $fixedBuyers = [];
    foreach($topBuyers as $name => $val) {
        $upperName = strtoupper($name);
        if(!isset($fixedBuyers[$upperName])) $fixedBuyers[$upperName] = 0;
        $fixedBuyers[$upperName] += $val;
    }
    arsort($fixedBuyers);
    $cleanTopBuyers = array_slice($fixedBuyers, 0, 5);

    // 2. Initials Buyer
    $buyerInitials = [];
    foreach(array_keys($cleanTopBuyers) as $name) {
        $words = explode(' ', $name);
        $initial = '';
        foreach($words as $w) {
            $initial .= strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $w), 0, 1));
        }
        $buyerInitials[] = $initial ?: substr($name, 0, 3);
    }

    // 3. DATA MANUAL UNTUK RINCIAN MUTU (DATA MAPPING)
    // Dibuat agar sesuai dengan snippet 'Rincian Mutu' yang Anda minta.
    // Angka diambil dari screenshot Excel Anda (Rekap4) agar presisi.
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

    {{-- BARIS 1: KEY METRICS --}}
    <div class="row-grid-2">
        <div class="card-metric">
            <div class="metric-content">
                <div class="metric-left">
                    <span class="metric-label">Total Volume</span>
                    <div class="metric-value-group">
                        <span class="metric-number">{{ number_format($totalVolume, 0, ',', '.') }}</span>
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
                            <span class="metric-number-small">{{ number_format($rkapVolume, 0, ',', '.') }}</span>
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
                        <span class="metric-number">Rp {{ number_format($totalRevenue / 1000000000, 0, ',', '.') }}</span>
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

    {{-- BARIS 2: TREND & DONUTS --}}
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
                            <span class="dot" style="background: {{ $colors[$i % count($colors)] }}"></span>
                            <span class="name" title="{{ $buyer }}">
                                {{ Str::limit($buyer, 15) }} 
                                <span class="text-xs text-gray-400">({{ $buyerInitials[$i] }})</span>
                            </span>
                            <span class="val">{{ $totalVolume > 0 ? round(($vol/$totalVolume)*100, 0) : 0 }}%</span>
                        </div>
                        @php $i++; @endphp
                        @endforeach
                    </div>
                    
                    <div class="donut-center-label">
                        <span class="lbl">Total Ton</span>
                        <span class="num">{{ number_format($totalVolume, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="card-std card-half">
                <div class="card-header"><h3>Top 5 Products</h3></div>
                <div class="donut-container">
                    <div id="chart-product" class="chart-donut"></div>
                    <div class="custom-legend">
                        @php
                            $colors = ['#2563EB', '#0D9488', '#F59E0B', '#64748B', '#94A3B8', '#EF4444', '#10B981', '#F59E0B', '#6366F1', '#EC4899'];
                            $i=0; 
                        @endphp
                        @foreach($topProducts as $prod => $vol)
                        <div class="legend-item">
                            <span class="dot" style="background: {{ $colors[$i % count($colors)] }}"></span>
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

    {{-- BARIS 3: TABEL STOK --}}
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
                            <th>SIR 3L</th>
                            <th>SIR 3WF</th>
                            <th>TOTAL</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- STOK PRODUKSI --}}
                        @php
                            $StokSir20  = $stokData['produksi']['sir20'];
                            $StokRss    = $stokData['produksi']['rss'];
                            $StokSir3l  = $stokData['produksi']['sir3l'];
                            $StokSir3wf = $stokData['produksi']['sir3wf'];
                            $StokTotal  = $StokSir20 + $StokRss + $StokSir3l + $StokSir3wf;
                        @endphp
                        <tr>
                            <td>Stok Produksi</td>
                            <td>{{ number_format($StokSir20, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokRss, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokSir3l, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokSir3wf, 0, ',', '.') }}</td>
                            <td class="font-bold">{{ number_format($StokTotal, 0, ',', '.') }}</td>
                        </tr>

                        <tr class="separator-header"><td colspan="5">OUTSTANDING CONTRACT</td></tr>
                        
                        @php
                            $StokSbSir20  = $stokData['sudah_bayar']['sir20'];
                            $StokSbRss    = $stokData['sudah_bayar']['rss'];
                            $StokSbSir3l  = $stokData['sudah_bayar']['sir3l'];
                            $StokSbSir3wf = $stokData['sudah_bayar']['sir3wf'];
                            $StokSbTotal  = $StokSbSir20 + $StokSbRss + $StokSbSir3l + $StokSbSir3wf;
                        @endphp
                        <tr>
                            <td>Sudah Bayar</td>
                            <td>{{ number_format($StokSbSir20, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokSbRss, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokSbSir3l, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokSbSir3wf, 0, ',', '.') }}</td>
                            <td class="font-bold">{{ number_format($StokSbTotal, 0, ',', '.') }}</td>
                        </tr>
                        @php
                            $StokBbSir20  = $stokData['belum_bayar']['sir20'];
                            $StokBbRss    = $stokData['belum_bayar']['rss'];
                            $StokBbSir3l  = $stokData['belum_bayar']['sir3l'];
                            $StokBbSir3wf = $stokData['belum_bayar']['sir3wf'];
                            $StokBbTotal  = $StokBbSir20 + $StokBbRss + $StokBbSir3l + $StokBbSir3wf;
                        @endphp
                        <tr>
                            <td>Belum Bayar</td>
                            <td>{{ number_format($StokBbSir20, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBbRss, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBbSir3l, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBbSir3wf, 0, ',', '.') }}</td>
                            <td class="font-bold">{{ number_format($StokBbTotal, 0, ',', '.') }}</td>
                        </tr>

                        {{-- JUMLAH OUTSTANDING --}}
                        @php
                            $jmlSir20  = $StokSbSir20 + $StokBbSir20;
                            $jmlRss    = $StokSbRss + $StokBbRss;
                            $jmlSir3l  = $StokSbSir3l + $StokBbSir3l;
                            $jmlSir3wf = $StokSbSir3l + $StokBbSir3l;
                            $jmlTotal  = $jmlSir20 + $jmlRss + $jmlSir3l + $jmlSir3wf;
                        @endphp
                        <tr class="row-sum">
                            <td>Jumlah</td>
                            <td>{{ number_format($jmlSir20, 0, ',', '.') }}</td>
                            <td>{{ number_format($jmlRss, 0, ',', '.') }}</td>
                            <td>{{ number_format($jmlSir3l, 0, ',', '.') }}</td>
                            <td>{{ number_format($jmlSir3wf, 0, ',', '.') }}</td>
                            <td>{{ number_format($jmlTotal, 0, ',', '.') }}</td>
                        </tr>

                        {{-- STOK BEBAS PRODUKSI --}}
                        @php
                            $StokBebasSir20  = $StokSir20 - $jmlSir20;
                            $StokBebasRss    = $StokRss - $jmlRss;
                            $StokBebasSir3l  = $StokSir3l - $jmlSir3l;
                            $StokBebasSir3wf = $StokSir3wf - $jmlSir3wf;
                            $StokBebasTotal  = $StokTotal - $jmlTotal;
                        @endphp
                        <tr class="row-highlight">
                            <td>Stok Bebas (Prod)</td>
                            <td class="{{ $StokBebasSir20 < 0 ? 'text-red-600 font-bold' : '' }}">
                                {{ number_format($StokBebasSir20, 0, ',', '.') }}
                            </td>
                            <td class="{{ $StokBebasRss < 0 ? 'text-red-600 font-bold' : '' }}">
                                {{ number_format($StokBebasRss, 0, ',', '.') }}
                            </td>
                            <td class="{{ $StokBebasSir3l < 0 ? 'text-red-600 font-bold' : '' }}">
                                {{ number_format($StokBebasSir3l, 0, ',', '.') }}
                            </td>
                            <td class="{{ $StokBebasSir3wf < 0 ? 'text-red-600 font-bold' : '' }}">
                                {{ number_format($StokBebasSir3wf, 0, ',', '.') }}
                            </td>
                            <td class="{{ $StokBebasTotal < 0 ? 'text-red-700 font-black' : 'font-black' }}">
                                {{ number_format($StokBebasTotal, 0, ',', '.') }}
                            </td>
                        </tr>


                        @php
                            $StokBahanBakuSir20  = $stokData['bahan_baku']['sir20'];
                            $StokBahanBakuRss    = $stokData['bahan_baku']['rss'];
                            $StokBahanBakuSir3l  = $stokData['bahan_baku']['sir3l'];
                            $StokBahanBakuSir3wf = $stokData['bahan_baku']['sir3wf'];
                            $StokBahanBakuTotal  = $StokBahanBakuSir20 + $StokBahanBakuRss + $StokBahanBakuSir3l + $StokBahanBakuSir3wf;
                        @endphp
                        <tr>
                            <td>Stok Bahan Baku</td>
                            <td>{{ number_format($StokBahanBakuSir20, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBahanBakuRss, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBahanBakuSir3l, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBahanBakuSir3wf, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBahanBakuTotal, 0, ',', '.') }}</td>
                        </tr>

                        {{-- STOK BEBAS AKHIR --}}
                        @php
                            $StokBebasFixSir20  = $StokBebasSir20 + $StokBahanBakuSir20;
                            $StokBebasFixRss    = $StokBebasRss + $StokBahanBakuRss;
                            $StokBebasFixSir3l  = $StokBebasSir3l + $StokBahanBakuSir3l;
                            $StokBebasFixSir3wf = $StokBebasSir3wf + $StokBahanBakuSir3wf;
                            $StokBebasFixTotal  = $StokBebasFixSir20 + $StokBebasFixRss + $StokBebasFixSir3l + $StokBebasFixSir3wf;
                        @endphp
                        <tr class="row-highlight-final">
                            <td>STOK BEBAS AKHIR</td>

                            <td class="{{ $StokBebasFixSir20 < 0 ? 'text-red font-bold' : '' }}">
                                {{ number_format($StokBebasFixSir20, 0, ',', '.') }}
                            </td>

                            <td class="{{ $StokBebasFixRss < 0 ? 'text-red font-bold' : '' }}">
                                {{ number_format($StokBebasFixRss, 0, ',', '.') }}
                            </td>

                            <td class="{{ $StokBebasFixSir3l < 0 ? 'text-red font-bold' : '' }}">
                                {{ number_format($StokBebasFixSir3l, 0, ',', '.') }}
                            </td>

                            <td class="{{ $StokBebasFixSir3wf < 0 ? 'text-red font-bold' : '' }}">
                                {{ number_format($StokBebasFixSir3wf, 0, ',', '.') }}
                            </td>

                            <td class="{{ $StokBebasFixTotal < 0 ? 'text-red font-black' : 'font-black' }}">
                                {{ number_format($StokBebasFixTotal, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="empty-placeholder"></div>
    </div>

    {{-- BARIS 4: MONTHLY VOLUME (UPDATED) --}}
    <div class="card-std p-0 full-row-card">
        <div class="chart-header-padded">
            <h3>Monthly Volume (Real vs RKAP)</h3>
        </div>
        <div class="layout-3-cols">
            <div class="col-chart">
                <div id="chart-monthly-vol"></div>
            </div>

            <div class="col-middle bg-light">
                    <p class="sidebar-title">Rincian Mutu (Volume)</p>
                    <div class="mutu-list">
                        @php
                            // Ambil Total Volume dari array mutu index ke-4 (Total)
                            $totalVolumeMutu = $mutu['volume'][4] ?? 0;
                        @endphp

                        @foreach($mutu['label'] as $index => $label)
                            {{-- Kita lewati baris 'Total' agar tidak muncul di list --}}
                            @if($label === 'Total') @continue @endif

                            @php
                                $vol = $mutu['volume'][$index] ?? 0;
                                // Hitung persentase terhadap total volume
                                $pct = $totalVolumeMutu > 0 ? round(($vol / $totalVolumeMutu) * 100, 1) : 0;
                            @endphp

                            <div class="mutu-item">
                                <div class="mutu-info">
                                    <span class="mutu-name">{{ $label }}</span>
                                    <span class="mutu-pct">{{ $pct }}%</span>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill orange" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="mutu-val">{{ number_format($vol, 0, ',', '.') }} Ton</span>
                            </div>
                        @endforeach
                    </div>
                </div>

            <div class="col-right bg-light border-left">
                <p class="sidebar-title">Ringkasan Total</p>
                <div class="stats-container">
                    {{-- TOTAL REAL (Presisi 2 Desimal, tanpa /1000) --}}
                    <div class="summary-item">
                        <span class="sum-label">Total Real</span>
                        <span class="sum-val orange">{{ number_format($totalVolume, 2, ',', '.') }} <small>Ton</small></span>
                    </div>
                    
                    {{-- TOTAL RKAP (Presisi 2 Desimal, tanpa /1000) --}}
                    <div class="summary-item">
                        <span class="sum-label">Total RKAP</span>
                        <span class="sum-val dark">{{ number_format($rkapVolume, 2, ',', '.') }} <small>Ton</small></span>
                    </div>

                    <div class="summary-item">
                        <span class="sum-label">Percentage</span>
                        <span class="sum-val huge">{{ $rkapVolume > 0 ? round(($totalVolume/$rkapVolume)*100, 0) : 0 }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BARIS 5: MONTHLY REVENUE (UPDATED) --}}
    <div class="card-std p-0 full-row-card">
        <div class="chart-header-padded">
            <h3>Monthly Revenue (Real vs RKAP)</h3>
        </div>
        <div class="layout-3-cols">
            <div class="col-chart">
                <div id="chart-monthly-rev"></div>
            </div>

            <div class="col-middle bg-light">
                    <p class="sidebar-title">Rincian Mutu (Revenue)</p>
                    <div class="mutu-list">
                        @php
                            // Ambil Total Revenue dari array mutu index ke-4 (Total)
                            $totalRevenueMutu = $mutu['revenue'][4] ?? 0;
                        @endphp

                        @foreach($mutu['label'] as $index => $label)
                            {{-- Lewati baris 'Total' agar tidak duplikat dengan kolom kanan --}}
                            @if($label === 'Total') @continue @endif

                            @php
                                $revValue = $mutu['revenue'][$index] ?? 0;
                                // Hitung persentase revenue mutu terhadap total revenue
                                $pctRev = $totalRevenueMutu > 0 ? round(($revValue / $totalRevenueMutu) * 100, 1) : 0;
                            @endphp

                            <div class="mutu-item">
                                <div class="mutu-info">
                                    <span class="mutu-name">{{ $label }}</span>
                                    <span class="mutu-pct">{{ $pctRev }}%</span>
                                </div>
                                <div class="progress-bar-bg">
                                    {{-- Gunakan class 'orange' agar sesuai dengan tema revenue --}}
                                    <div class="progress-bar-fill" style="width: {{ $pctRev }}%"></div>
                                </div>
                                <span class="mutu-val">Rp {{ number_format($revValue, 0, ',', '.') }} M</span>
                            </div>
                        @endforeach
                    </div>
                </div>

            <div class="col-right bg-light border-left">
                <p class="sidebar-title">Ringkasan Total</p>
                <div class="stats-container">
                    <div class="summary-item">
                        <span class="sum-label">Total Real</span>
                        <span class="sum-val orange">
                            Rp {{ number_format($totalRevenueMutu, 0, ',', '.') }}<small>B</small>
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Total RKAP</span>
                        <span class="sum-val dark">
                            Rp {{ number_format($rkapRevenue / 1000000000, 0, ',', '.') }}<small>B</small>
                        </span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Percentage</span>
                        @php
                            // Gunakan RKAP Revenue global yg sudah dikonversi ke satuan yg sebanding jika perlu
                            // Atau hitung ulang dari komponen mutu jika ada RKAP mutu
                            $totalPercentageRev = $rkapRevenue > 0 ? round(($totalRevenue/$rkapRevenue)*100, 0) : 0;
                        @endphp
                        <span class="sum-val huge">{{ $totalPercentageRev }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
    window.dashboardData = {
        priceDaily: @json($trendPriceDaily),
        topBuyers: @json(array_values($topBuyers)),
        topBuyersLabels: @json($buyerInitials), 
        topProducts: @json(array_values($topProducts)),
        topProductsLabels: @json(array_keys($topProducts)),
        volumeReal: @json($rekap4['volume_real']),
        rkapVol: @json($rekap4['volume_rkap']),
        revenueReal: @json($rekap4['revenue_real']),
        rkapRev: @json($rekap4['revenue_rkap']),
        monthLabels: @json($rekap4['labels'])
    };
</script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="{{ asset('js/dashboard-script.js') }}"></script>
@endsection