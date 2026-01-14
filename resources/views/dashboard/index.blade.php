@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
{{-- BLOK PHP: INITIALIZATION DATA --}}
@php
    // 1. Prepare Top Buyers (Ambil kategori TOTAL untuk tampilan awal)
    $initBuyers = $top5Buyers['TOTAL'] ?? [];
    
    // Pisahkan 'TOTAL' (grand total)
    $buyersTotalVol = $initBuyers['TOTAL'] ?? 0;
    if(isset($initBuyers['TOTAL'])) unset($initBuyers['TOTAL']); 
    
    // Gunakan Key langsung (Nama Asli: WTP, MOP, dll)
    $buyerLabels = array_keys($initBuyers);

    // 2. Prepare Top Products
    $initProducts = $top5Products['TOTAL'] ?? [];
    $productsTotalVol = $initProducts['TOTAL'] ?? 0;
    if(isset($initProducts['TOTAL'])) unset($initProducts['TOTAL']);
    
    $productLabels = array_keys($initProducts);

    // Warna Chart
    $chartColors = ['#2563EB', '#0D9488', '#F59E0B', '#64748B', '#94A3B8', '#8B5CF6'];
    $prodColors  = ['#EF4444', '#10B981', '#3B82F6', '#F59E0B', '#6366F1', '#EC4899'];
@endphp

<link rel="stylesheet" href="{{ asset('css/dashboard-custom.css') }}">

<div class="dashboard-container">
    
    <div class="dashboard-header">
        <div>
            <h1>Dashboard Overview</h1>
            <p>PTPN 1 Regional 7 - Rubber Trading Analytics</p>
        </div>
        <div class="filter-box">
            <div style="position: relative; display: inline-block;">
                <span class="icon-calendar" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 1;">📅</span>
                
                <select onchange="window.location.href='{{ url('set-year') }}/' + this.value" 
                        style="padding-left: 35px; padding-right: 10px; padding-top: 6px; padding-bottom: 6px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; font-weight: bold; cursor: pointer; height: 38px; color: #0f172a;">
                    
                    {{-- Default Option --}}
                    <option value="default" {{ $sharedCurrentYear === 'Default' ? 'selected' : '' }}>
                        Default (2025)
                    </option>

                    {{-- Dynamic Years from Database --}}
                    @foreach($sharedAvailableYears as $year)
                        <option value="{{ $year }}" {{ $sharedCurrentYear == $year ? 'selected' : '' }}>
                            {{ $year }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- BARIS 1: KEY METRICS --}}
    <div class="row-grid-2">
        <div class="card-metric">
            <div class="metric-content">
                <div class="metric-left">
                    <span class="metric-label">Total Volume</span>
                    <div class="metric-value-group">
                        <span class="metric-number">{{ number_format($top5Buyers["TOTAL"]["TOTAL"]/1000, 0, ',', '.') }}</span>
                        <span class="metric-unit">Ton</span>
                    </div>
                    @php
                        $progress = $rkapRevenue > 0
                            ? round(($totalVolume / $rkapVolume) * 100, 1)
                            : 0;
                    @endphp
                    <div class="metric-progress">
                        Progress:
                        <span class="progress-val {{ $progress >= 100 ? 'progress-green' : 'progress-red' }}">
                            {{ $progress }}%
                        </span>
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
                        <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
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
                        <span class="metric-unit">Milyar</span>
                    </div>
                    @php
                        $progress = $rkapRevenue > 0
                            ? round(($totalRevenue / $rkapRevenue) * 100, 1)
                            : 0;
                    @endphp
                    <div class="metric-progress">
                        Progress:
                        <span class="progress-val {{ $progress >= 100 ? 'progress-green' : 'progress-red' }}">
                            {{ $progress }}%
                        </span>
                    </div>
                </div>
                <div class="metric-right">
                    <div class="rkap-info">
                        <span class="metric-label">Total Revenue RKAP</span>
                        <div class="metric-value-group right-align">
                            <span class="metric-number-small">Rp {{ number_format($rkapRevenue/1000000000, 0, ',', '.') }}</span>
                            <span class="metric-unit-small">Milyar</span>
                        </div>
                    </div>
                    <div class="icon-box bg-orange">
                        <svg width="24" height="24" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BARIS 2: TREND, LAST PRICE & DONUTS --}}
    <div class="row-grid-2">
        <div class="col-stacked">
            {{-- 1. GRAFIK TREND --}}
            <div class="card-std price-chart-wrapper">
                <div class="card-header">
                    <h3>Daily Selling Price</h3>
                </div>

                <!-- DROPDOWN DI POJOK KANAN ATAS -->
                <div class="price-filter">
                    <select id="price-range" class="dashboard-select">
                        <option value="3">3 Bulan Terakhir</option>
                        <option value="6">6 Bulan Terakhir</option>
                        <option value="9">9 Bulan Terakhir</option>
                        <option value="12">12 Bulan Terakhir</option>
                        <option value="all" selected>Semua Bulan</option>
                    </select>
                </div>

                <div id="chart-price-monthly"></div>
            </div>


            {{-- 2. LAST TENDER PRICE --}}
            <div class="card-std last-tender-card">
                <div style="margin-bottom: 16px; font-size: 14px; color: #64748B;">Last Tender Price/Kg</div>
                <div class="tender-grid">
                    @php
                        $pSir20 = $lastTender['sir20']['price'] ?? 0;
                        $dSir20 = $lastTender['sir20']['date'] ?? '-';
                        $pRss   = $lastTender['rss']['price'] ?? 0;
                        $dRss   = $lastTender['rss']['date'] ?? '-';
                        $pSir3l = $lastTender['sir3l']['price'] ?? 0;
                        $dSir3l = $lastTender['sir3l']['date'] ?? '-';
                    @endphp
                    <div class="tender-item">
                        <span class="tender-meta">SIR20 - {{ $dSir20 }}</span>
                        <span class="tender-price">Rp {{ number_format($pSir20, 0, ',', '.') }}</span>
                    </div>
                    <div class="tender-item tender-rss">
                        <span class="tender-meta">RSS - {{ $dRss }}</span>
                        <span class="tender-price">Rp {{ number_format($pRss, 0, ',', '.') }}</span>
                    </div>
                    <div class="tender-item tender-sir3l">
                        <span class="tender-meta">SIR3L - {{ $dSir3l }}</span>
                        <span class="tender-price">Rp {{ number_format($pSir3l, 0, ',', '.') }}</span>                        
                    </div>
                </div>
            </div>
        </div>

        <div class="col-stacked">
            {{-- 1. TOP 5 BUYERS --}}
            <div class="card-std card-half">
                <div class="card-header flex-between">
                    <h3>Top 5 Buyers</h3>
                    <select id="buyer-filter" class="dashboard-select">
                        <option value="TOTAL" selected>TOTAL</option>
                        <option value="SIR 20">SIR 20</option>
                        <option value="RSS 1">RSS 1</option>
                        <option value="SIR 3L">SIR 3L</option>
                        <option value="SIR 3WF">SIR 3WF</option>
                    </select>
                </div>
                <div class="donut-container">
                    {{-- Wrapper Sisi Kiri (Chart + Total Text) --}}
                    <div class="chart-side-wrapper">
                        <div id="chart-buyer" class="chart-donut"></div>
                        
                        {{-- Pindahkan Text Total ke sini (Bawah Chart) --}}
                        <div class="donut-center-label">
                            <span class="lbl">Total Ton</span>
                            <span class="num" id="buyer-center-total">{{ number_format($buyersTotalVol/1000, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    {{-- Wrapper Sisi Kanan (Legend) --}}
                    <div class="custom-legend" id="buyer-legend-container">
                        @php $i=0; @endphp
                        @foreach($initBuyers as $buyer => $vol)
                        <div class="legend-item">
                            <span class="dot" style="background: {{ $chartColors[$i % count($chartColors)] }}"></span>
                            <span class="name" title="{{ $buyer }}">
                                {{ $buyer }}
                            </span>
                            <span class="val">{{ $buyersTotalVol > 0 ? round(($vol/$buyersTotalVol)*100, 0) : 0 }}%</span>
                        </div>
                        @php $i++; @endphp
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 2. TOP 5 PRODUCTS --}}
            <div class="card-std card-half">
                <div class="card-header flex-between">
                    <h3>Top 5 Products</h3>
                    <select id="product-filter" class="dashboard-select">
                        <option value="TOTAL" selected>TOTAL</option>
                        <option value="SIR 20">SIR 20</option>
                        <option value="RSS 1">RSS 1</option>
                        <option value="SIR 3L">SIR 3L</option>
                        <option value="SIR 3WF">SIR 3WF</option>
                    </select>
                </div>
                <div class="donut-container">
                    {{-- Wrapper Sisi Kiri (Chart + Total Text) --}}
                    <div class="chart-side-wrapper">
                        <div id="chart-product" class="chart-donut"></div>
                        
                        {{-- Pindahkan Text Total ke sini --}}
                        <div class="donut-center-label">
                            <span class="lbl">Total Ton</span>
                            <span class="num" id="product-center-total">{{ number_format($productsTotalVol/1000, 0, ',', '.') }}</span>
                        </div>
                    </div>
                    
                    {{-- Wrapper Sisi Kanan (Legend) --}}
                    <div class="custom-legend" id="product-legend-container">
                        @php $i=0; @endphp
                        @foreach($initProducts as $prod => $vol)
                        <div class="legend-item">
                            <span class="dot" style="background: {{ $prodColors[$i % count($prodColors)] }}"></span>
                            <span class="name">{{ $prod }}</span>
                            <span class="val">{{ $productsTotalVol > 0 ? round(($vol/$productsTotalVol)*100, 0) : 0 }}%</span>
                        </div>
                        @php $i++; @endphp
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BARIS 3: TABEL STOK --}}
    <div class="row-grid-2">
        {{-- ... Kode Tabel Stok (Biarkan sama, tidak ada perubahan) ... --}}
        {{-- SAYA PERSINGKAT TAMPILAN KODE DI SINI AGAR TIDAK KEPANJANGAN, SILAKAN PASTE BAGIAN TABEL STOK DARI KODE SEBELUMNYA DI SINI --}}
        <div class="card-std card-table-compact">
            <div class="card-header flex-between">
                <h3>Stok Bebas</h3>
                <span class="badge-date">{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
            <div class="table-responsive">
                <table class="table-stok">
                    {{-- ... Konten Tabel Stok Anda ... --}}
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
                         @php
                            $p = $stokData['produksi'] ?? [];
                            $StokSir20  = $p['sir20'] ?? 0;
                            $StokRss    = $p['rss'] ?? 0;
                            $StokSir3l  = $p['sir3l'] ?? 0;
                            $StokSir3wf = $p['sir3wf'] ?? 0;
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
                        <tr class="separator-header">
                            <td colspan="6" style="font-style: italic;">Outstanding Contract</td>
                        </tr>
                         @php
                            $sb = $stokData['sudah_bayar'] ?? [];
                            $StokSbSir20  = $sb['sir20'] ?? 0;
                            $StokSbRss    = $sb['rss'] ?? 0;
                            $StokSbSir3l  = $sb['sir3l'] ?? 0;
                            $StokSbSir3wf = $sb['sir3wf'] ?? 0;
                            $StokSbTotal  = $StokSbSir20 + $StokSbRss + $StokSbSir3l + $StokSbSir3wf;
                        @endphp
                        <tr>
                            <td style="padding-left:16px;">Sudah Bayar</td>
                            <td>{{ number_format($StokSbSir20, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokSbRss, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokSbSir3l, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokSbSir3wf, 0, ',', '.') }}</td>
                            <td class="font-bold">{{ number_format($StokSbTotal, 0, ',', '.') }}</td>
                        </tr>
                         @php
                            $bb = $stokData['belum_bayar'] ?? [];
                            $StokBbSir20  = $bb['sir20'] ?? 0;
                            $StokBbRss    = $bb['rss'] ?? 0;
                            $StokBbSir3l  = $bb['sir3l'] ?? 0;
                            $StokBbSir3wf = $bb['sir3wf'] ?? 0;
                            $StokBbTotal  = $StokBbSir20 + $StokBbRss + $StokBbSir3l + $StokBbSir3wf;
                        @endphp
                        <tr>
                            <td style="padding-left:16px;">Belum Bayar</td>
                            <td>{{ number_format($StokBbSir20, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBbRss, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBbSir3l, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBbSir3wf, 0, ',', '.') }}</td>
                            <td class="font-bold">{{ number_format($StokBbTotal, 0, ',', '.') }}</td>
                        </tr>
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
                        @php
                            $StokBebasSir20  = $StokSir20 - $jmlSir20;
                            $StokBebasRss    = $StokRss - $jmlRss;
                            $StokBebasSir3l  = $StokSir3l - $jmlSir3l;
                            $StokBebasSir3wf = $StokSir3wf - $jmlSir3wf;
                            $StokBebasTotal  = $StokTotal - $jmlTotal;
                        @endphp
                        @php
                        // === STOK BEBAS FIX ===
                        $bk = $stokData['bahan_baku'] ?? [];

                        $StokBahanBakuSir20  = $bk['sir20'] ?? 0;
                        $StokBahanBakuRss    = $bk['rss'] ?? 0;
                        $StokBahanBakuSir3l  = $bk['sir3l'] ?? 0;
                        $StokBahanBakuSir3wf = $bk['sir3wf'] ?? 0;

                        $StokBebasFixSir20  = $StokBebasSir20 + $StokBahanBakuSir20;
                        $StokBebasFixRss    = $StokBebasRss + $StokBahanBakuRss;
                        $StokBebasFixSir3l  = $StokBebasSir3l + $StokBahanBakuSir3l;
                        $StokBebasFixSir3wf = $StokBebasSir3wf + $StokBahanBakuSir3wf;
                        $StokBebasFixTotal  = $StokBebasFixSir20 + $StokBebasFixRss + $StokBebasFixSir3l + $StokBebasFixSir3wf;
                        @endphp

                        <tr class="row-highlight">
                            <td class="stok-label">Stok Bebas</td>
                            <td>{{ number_format($StokBebasFixSir20, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBebasFixRss, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBebasFixSir3l, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBebasFixSir3wf, 0, ',', '.') }}</td>
                            <td>{{ number_format($StokBebasFixTotal, 0, ',', '.') }}</td>
                        </tr>

                        @php
                            $bk = $stokData['bahan_baku'] ?? [];
                            $StokBahanBakuSir20  = $bk['sir20'] ?? 0;
                            $StokBahanBakuRss    = $bk['rss'] ?? 0;
                            $StokBahanBakuSir3l  = $bk['sir3l'] ?? 0;
                            $StokBahanBakuSir3wf = $bk['sir3wf'] ?? 0;
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
                        @php
                            $StokBebasFixSir20  = $StokBebasSir20 + $StokBahanBakuSir20;
                            $StokBebasFixRss    = $StokBebasRss + $StokBahanBakuRss;
                            $StokBebasFixSir3l  = $StokBebasSir3l + $StokBahanBakuSir3l;
                            $StokBebasFixSir3wf = $StokBebasSir3wf + $StokBahanBakuSir3wf;
                            $StokBebasFixTotal  = $StokBebasFixSir20 + $StokBebasFixRss + $StokBebasFixSir3l + $StokBebasFixSir3wf;
                        @endphp
                        <tr class="row-highlight-final">
                            <td>STOK BEBAS AKHIR</td>
                            <td class="{{ $StokBebasFixSir20 < 0 ? 'text-red font-bold' : '' }}">{{ number_format($StokBebasFixSir20, 0, ',', '.') }}</td>
                            <td class="{{ $StokBebasFixRss < 0 ? 'text-red font-bold' : '' }}">{{ number_format($StokBebasFixRss, 0, ',', '.') }}</td>
                            <td class="{{ $StokBebasFixSir3l < 0 ? 'text-red font-bold' : '' }}">{{ number_format($StokBebasFixSir3l, 0, ',', '.') }}</td>
                            <td class="{{ $StokBebasFixSir3wf < 0 ? 'text-red font-bold' : '' }}">{{ number_format($StokBebasFixSir3wf, 0, ',', '.') }}</td>
                            <td class="{{ $StokBebasFixTotal < 0 ? 'text-red font-black' : 'font-black' }}">{{ number_format($StokBebasFixTotal, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="empty-placeholder"></div>
    </div>

    {{-- BARIS 4: MONTHLY VOLUME --}}
    <div class="card-std p-0 full-row-card">
        <div class="layout-3-cols">
            <div class="col-chart">
                <div class="chart-header-padded">
                    <h3>Monthly Volume (Real vs RKAP)</h3>
                </div>
                <div class="col-chart">
                    <div id="chart-monthly-vol"></div>
                </div>
            </div>
            <div class="col-middle bg-light">
                <p class="sidebar-title">Rincian Mutu (Volume)</p>
                <div class="mutu-list">
                    @if(isset($mutu['label']) && is_array($mutu['label']))
                        @foreach($mutu['label'] as $index => $label)
                            @if(strtoupper(trim($label)) === 'TOTAL') @continue @endif
                            @php
                                $vol = isset($mutu['volume'][$index]) ? $mutu['volume'][$index] : 0;
                                $pct = $totalVolume > 0 ? round(($vol / $totalVolume) * 100, 1) : 0;
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
                    @endif
                </div>
            </div>
            <div class="col-right bg-light border-left">
                <p class="sidebar-title">Ringkasan Total</p>
                <div class="stats-container">
                    <div class="summary-item">
                        <span class="sum-label">Total Real</span>
                        <span class="sum-val orange">{{ number_format($totalVolume, 0, ',', '.') }} <small>Ton</small></span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Total RKAP</span>
                        <span class="sum-val dark">{{ number_format($rkapVolume, 0, ',', '.') }} <small>Ton</small></span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Percentage</span>
                        <span class="sum-val huge">{{ $rkapVolume > 0 ? round(($totalVolume/$rkapVolume)*100, 0) : 0 }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BARIS 5: MONTHLY REVENUE --}}
    <div class="card-std p-0 full-row-card">
        <div class="layout-3-cols">
            <div class="col-chart">
                <div class="chart-header-padded">
                    <h3>Monthly Revenue (Real vs RKAP)</h3>
                </div>
                <div class="col-chart">
                    <div id="chart-monthly-rev"></div>
                </div>
            </div>
            <div class="col-middle bg-light">
                <p class="sidebar-title">Rincian Mutu (Revenue)</p>
                <div class="mutu-list">
                    @if(isset($mutu['label']) && is_array($mutu['label']))
                        @foreach($mutu['label'] as $index => $label)
                            @if(strtoupper(trim($label)) === 'TOTAL') @continue @endif
                            @php
                                $revValue = isset($mutu['revenue'][$index]) ? $mutu['revenue'][$index] : 0;
                                $totalRevRaw = $totalRevenue / 1000000000;
                                $pctRev = $totalRevRaw > 0 ? round(($revValue / $totalRevRaw) * 100, 1) : 0;
                            @endphp
                            <div class="mutu-item">
                                <div class="mutu-info">
                                    <span class="mutu-name">{{ $label }}</span>
                                    <span class="mutu-pct">{{ $pctRev }}%</span>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill orange" style="width: {{ $pctRev }}%"></div>
                                </div>
                                <span class="mutu-val">Rp {{ number_format($revValue, 0, ',', '.') }} M</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="col-right bg-light border-left">
                <p class="sidebar-title">Ringkasan Total</p>
                <div class="stats-container">
                    <div class="summary-item">
                        <span class="sum-label">Total Real</span>
                        <span class="sum-val orange">Rp {{ number_format($totalRevenue / 1000000000, 0, ',', '.') }}<small>M</small></span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Total RKAP</span>
                        <span class="sum-val dark">Rp {{ number_format($rkapRevenue / 1000000000, 0, ',', '.') }}<small>M</small></span>
                    </div>
                    <div class="summary-item">
                        <span class="sum-label">Percentage</span>
                        @php $totalPercentageRev = $rkapRevenue > 0 ? round(($totalRevenue/$rkapRevenue)*100, 0) : 0; @endphp
                        <span class="sum-val huge">{{ $totalPercentageRev }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- SCRIPT PENGHUBUNG --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    // Memasukkan seluruh data dari PHP ke object Window agar bisa dibaca file JS eksternal
    window.dashboardData = {
        priceDaily: @json($trendPriceDaily),
        
        // Data Mentah dari Controller (Kuncinya sudah "WTP", "MOP", dll)
        rawTopBuyers: @json($top5Buyers), 
        rawTopProducts: @json($top5Products),
        
        // Data Init Awal (Hanya untuk render pertama)
        topBuyers: @json(array_values($initBuyers)),
        // PERBAIKAN: Kirim Key langsung sebagai label (WTP, MOP) tanpa diproses lagi
        topBuyersLabels: @json($buyerLabels), 
        
        topProducts: @json(array_values($initProducts)),
        topProductsLabels: @json($productLabels),
        
        // Data Lain
        volumeReal: @json($rekap4['volume_real']),
        rkapVol: @json($rekap4['volume_rkap']),
        revenueReal: @json($rekap4['revenue_real']),
        rkapRev: @json($rekap4['revenue_rkap']),
        monthLabels: @json($rekap4['labels']),
        chartColors: @json($chartColors),
        prodColors: @json($prodColors)
    };
</script>

{{-- PANGGIL SCRIPT UTAMA DI BAWAH DATA DEFINITION --}}
<script src="{{ asset('js/dashboard-script.js') }}"></script>
@endsection