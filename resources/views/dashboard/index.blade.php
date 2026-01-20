@extends('layouts.app')

@section('title', 'Dashboard Overview')

@section('content')
{{-- BLOK PHP: INITIALIZATION DATA --}}
@php
    $initBuyers = $top5Buyers['TOTAL'] ?? [];
    $buyersTotalVol = $initBuyers['TOTAL'] ?? 0;
    if(isset($initBuyers['TOTAL'])) unset($initBuyers['TOTAL']); 
    $buyerLabels = array_keys($initBuyers);

    $initProducts = $top5Products['TOTAL'] ?? [];
    $productsTotalVol = $initProducts['TOTAL'] ?? 0;
    if(isset($initProducts['TOTAL'])) unset($initProducts['TOTAL']);
    $productLabels = array_keys($initProducts);

    $hexPalette = ['#134E5E', '#2C7A7B', '#7FB3B8', '#F59E0B', '#FDBA74', '#FED7AA'];
    $chartColors = $hexPalette;
    $prodColors  = $hexPalette;
@endphp

<link rel="stylesheet" href="{{ asset('css/dashboard-custom.css') }}">
<style>
    .apexcharts-datalabels text, 
    .apexcharts-datalabel-value, 
    .apexcharts-point-annotation-label { 
        fill: #000000 !important; 
        stroke: #ffffff !important; 
        stroke-width: 4px !important; 
        paint-order: stroke fill; 
        stroke-linejoin: round;
        font-weight: 800 !important;
        filter: drop-shadow(0px 0px 1px rgba(0,0,0,0.2));
    }
</style>
<div class="dashboard-container">
    
    <div class="dashboard-header">
        <div>
            <h1>Dashboard Overview</h1>
            <p>PTPN 1 Regional 7 - Sales and Inventories</p>
        </div>
    </div>

    {{-- 1. HEADER METRICS (Total Vol & Rev) --}}
    <div class="metrics-row">
        {{-- Total Volume --}}
        <div class="card-metric">
            <div class="metric-content">
                <div class="metric-left">
                
                    {{-- 1. WRAPPER JUDUL & TANGGAL (Agar sejajar horizontal) --}}
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                        <span class="metric-label" style="margin-bottom: 0;">Total Volume</span>
                        
                        {{-- Tanggal H-1 --}}
                        <span class="badge-date" style=" padding: 2px 6px; border-radius: 4px; background-color: #eee;">
                            {{ \Carbon\Carbon::now()->subDay()->format('d/m/Y') }}
                        </span>
                    </div>

                    {{-- 2. NILAI UTAMA --}}
                    <div class="metric-value-group">
                        <span class="metric-number">{{ number_format($top5Buyers["TOTAL"]["TOTAL"]/1000, 0, ',', '.') }}</span>
                        <span class="metric-unit">Ton</span>
                    </div>

                    {{-- 3. PROGRESS --}}
                    @php 
                        $progress = $rkapRevenue > 0 ? round(($totalVolume / $rkapVolume) * 100, 1) : 0; 
                    @endphp
                    <div class="metric-progress">
                        Progress: <span class="progress-val {{ $progress >= 100 ? 'progress-green' : 'progress-red' }}">{{ $progress }}%</span>
                    </div>
                </div>
                <div class="metric-right">
                    <div class="rkap-info">
                        <span class="metric-label1">Total Volume RKAP</span>
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
        {{-- Total Revenue --}}
        <div class="card-metric">
            <div class="metric-content">
                <div class="metric-left">
                    
                    {{-- 1. WRAPPER JUDUL & TANGGAL (Agar sejajar horizontal) --}}
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                        <span class="metric-label" style="margin-bottom: 0;">Total Revenue</span>
                        
                        {{-- Tanggal H-1 --}}
                        <span class="badge-date" style="padding: 2px 6px; border-radius: 4px; background-color: #eee;">
                            {{ \Carbon\Carbon::now()->subDay()->format('d/m/Y') }}
                        </span>
                    </div>

                    {{-- 2. NILAI UTAMA --}}
                    <div class="metric-value-group">
                        <span class="metric-number">Rp {{ number_format($totalRevenue / 1000000000, 0, ',', '.') }}</span>
                        <span class="metric-unit">Milyar</span>
                    </div>

                    {{-- 3. PROGRESS --}}
                    @php 
                        $progress = $rkapRevenue > 0 ? round(($totalRevenue / $rkapRevenue) * 100, 1) : 0; 
                    @endphp
                    <div class="metric-progress">
                        Progress: <span class="progress-val {{ $progress >= 100 ? 'progress-green' : 'progress-red' }}">{{ $progress }}%</span>
                    </div>

                </div>
                <div class="metric-right">
                    <div class="rkap-info">
                        <span class="metric-label1">Total Revenue RKAP</span>
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

    {{-- 2. SPLIT CONTAINER (KIRI & KANAN) --}}
    <div class="main-split-container">
        
        {{-- BAGIAN 1 (KIRI) --}}
        <div class="split-col">
            {{-- Daily Price --}}
            <div class="card-std price-chart-wrapper">
                <div class="card-header">
                    <h3>Daily Selling Price</h3>
                    <select id="price-range" class="dashboard-select">
                        <option value="3">3 Bulan Terakhir</option>
                        <option value="6">6 Bulan Terakhir</option>
                        <option value="9">9 Bulan Terakhir</option>
                        <option value="12">12 Bulan Terakhir</option>
                        <option value="all" selected>Semua Bulan</option>
                    </select>
                </div>
                {{-- WRAPPER SCROLL UNTUK CHART --}}
                <div class="chart-scroll-container">
                    <div id="chart-price-monthly"></div>
                </div>
            </div>
            
            {{-- Last Price --}}
            <div class="card-std last-tender-card">
                <div style="margin-bottom: 16px; font-size: 14px; color: #64748B;">Last Selling Price</div>
                <div class="tender-grid">
                    @php
                        $pSir20 = $lastTender['sir20']['price'] ?? 0; $dSir20 = $lastTender['sir20']['date'] ?? '-';
                        $pRss   = $lastTender['rss']['price'] ?? 0; $dRss   = $lastTender['rss']['date'] ?? '-';
                        $pSir3l = $lastTender['sir3l']['price'] ?? 0; $dSir3l = $lastTender['sir3l']['date'] ?? '-';
                    @endphp
                    <div class="tender-item tender-sir20">
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
            
            {{-- Stok Bebas --}}
            <div class="card-std card-table-compact">
                <div class="card-header flex-between">
                    <h3>Stok Bebas</h3>
                    <span class="badge-date">{{ \Carbon\Carbon::now()->subDay()->format('d/m/Y') }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table-stok">
                        <thead>
                            <tr>
                                <th>Uraian</th><th>SIR 20</th><th>RSS</th><th>SIR 3L</th><th>SIR 3WF</th><th>TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                             @php
                                $p = $stokData['produksi'] ?? [];
                                $StokSir20  = $p['sir20'] ?? 0; $StokRss = $p['rss'] ?? 0; $StokSir3l = $p['sir3l'] ?? 0; $StokSir3wf = $p['sir3wf'] ?? 0;
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
                                <td style="font-style: italic;">Outstanding Contract</td>
                                <td></td><td></td><td></td><td></td><td></td>
                            </tr>
                             @php
                                $sb = $stokData['sudah_bayar'] ?? [];
                                $StokSbSir20 = $sb['sir20'] ?? 0; $StokSbRss = $sb['rss'] ?? 0; $StokSbSir3l = $sb['sir3l'] ?? 0; $StokSbSir3wf = $sb['sir3wf'] ?? 0;
                                $StokSbTotal = $StokSbSir20 + $StokSbRss + $StokSbSir3l + $StokSbSir3wf;
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
                                $StokBbSir20 = $bb['sir20'] ?? 0; $StokBbRss = $bb['rss'] ?? 0; $StokBbSir3l = $bb['sir3l'] ?? 0; $StokBbSir3wf = $bb['sir3wf'] ?? 0;
                                $StokBbTotal = $StokBbSir20 + $StokBbRss + $StokBbSir3l + $StokBbSir3wf;
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
                                $jmlSir20 = $StokSbSir20 + $StokBbSir20; $jmlRss = $StokSbRss + $StokBbRss; $jmlSir3l = $StokSbSir3l + $StokBbSir3l; $jmlSir3wf = $StokSbSir3wf + $StokBbSir3wf;
                                $jmlTotal = $jmlSir20 + $jmlRss + $jmlSir3l + $jmlSir3wf;
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
                                $StokBebasSir20 = $StokSir20 - $jmlSir20; $StokBebasRss = $StokRss - $jmlRss; $StokBebasSir3l = $StokSir3l - $jmlSir3l; $StokBebasSir3wf = $StokSir3wf - $jmlSir3wf;
                                $StokBebasTotal = $StokTotal - $jmlTotal;
                                $bk = $stokData['bahan_baku'] ?? [];
                                $StokBahanBakuSir20 = $bk['sir20'] ?? 0; $StokBahanBakuRss = $bk['rss'] ?? 0; $StokBahanBakuSir3l = $bk['sir3l'] ?? 0; $StokBahanBakuSir3wf = $bk['sir3wf'] ?? 0;
                                $StokBebasFixSir20 = $StokBebasSir20 + $StokBahanBakuSir20;
                                $StokBebasFixRss = $StokBebasRss + $StokBahanBakuRss;
                                $StokBebasFixSir3l = $StokBebasSir3l + $StokBahanBakuSir3l;
                                $StokBebasFixSir3wf = $StokBebasSir3wf + $StokBahanBakuSir3wf;
                                $StokBebasFixTotal = $StokBebasFixSir20 + $StokBebasFixRss + $StokBebasFixSir3l + $StokBebasFixSir3wf;
                            @endphp
                            <tr class="row-highlight">
                                <td class="stok-label">Stok Bebas</td>
                                <td>{{ number_format($StokBebasSir20, 0, ',', '.') }}</td>
                                <td>{{ number_format($StokBebasRss, 0, ',', '.') }}</td>
                                <td>{{ number_format($StokBebasSir3l, 0, ',', '.') }}</td>
                                <td>{{ number_format($StokBebasSir3wf, 0, ',', '.') }}</td>
                                <td>{{ number_format($StokBebasTotal, 0, ',', '.') }}</td>
                            </tr>
                            @php $StokBahanBakuTotal = $StokBahanBakuSir20 + $StokBahanBakuRss + $StokBahanBakuSir3l + $StokBahanBakuSir3wf; @endphp
                            <tr>
                                <td>Stok Bahan Baku</td>
                                <td>{{ number_format($StokBahanBakuSir20, 0, ',', '.') }}</td>
                                <td>{{ number_format($StokBahanBakuRss, 0, ',', '.') }}</td>
                                <td>{{ number_format($StokBahanBakuSir3l, 0, ',', '.') }}</td>
                                <td>{{ number_format($StokBahanBakuSir3wf, 0, ',', '.') }}</td>
                                <td>{{ number_format($StokBahanBakuTotal, 0, ',', '.') }}</td>
                            </tr>
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
        </div>

        

{{-- ================== --}}
{{-- BAGIAN 2 (KANAN) --}}
{{-- ================== --}}
<div class="split-col">

{{-- ================== --}}
{{-- TOP 5 ROW --}}
{{-- ================== --}}
<div class="top5-row">

    {{-- ================== --}}
    {{-- Top 5 Buyers --}}
    {{-- ================== --}}
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
            <div class="chart-side-wrapper">
                <div id="chart-buyer" class="chart-donut"></div>
                <div class="donut-center-label">
                    <span class="lbl">Total Ton</span>
                    <span class="num" id="buyer-center-total">
                        {{ number_format($buyersTotalVol/1000, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- LEGEND BUYERS --}}
            <div class="custom-legend" id="buyer-legend-container">
                @php $i = 0; @endphp
                @foreach($initBuyers as $buyer => $vol)
                    @if($buyer === 'TOTAL' || strtoupper(trim($buyer)) === 'LAINNYA')
                        @continue
                    @endif
                    <div class="legend-item">
                        <span
                            class="dot"
                            style="background: {{ $chartColors[$i % count($chartColors)] }}"
                        ></span>
                        <span class="name" title="{{ $buyer }}">{{ $buyer }}</span>
                    </div>
                    @php $i++; @endphp
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================== --}}
    {{-- Top 5 Products --}}
    {{-- ================== --}}
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
            <div class="chart-side-wrapper">
                <div id="chart-product" class="chart-donut"></div>
                <div class="donut-center-label">
                    <span class="lbl">Total Ton</span>
                    <span class="num" id="product-center-total">
                        {{ number_format($productsTotalVol/1000, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            {{-- LEGEND PRODUCTS --}}
            <div class="custom-legend" id="product-legend-container">
                @php $i = 0; @endphp
                @foreach($initProducts as $prod => $vol)
                    @if($prod === 'TOTAL' || strtoupper(trim($prod)) === 'LAINNYA')
                        @continue
                    @endif
                    <div class="legend-item">
                        <span
                            class="dot"
                            style="background: {{ $prodColors[$i % count($prodColors)] }}"
                        ></span>
                        <span class="name">{{ $prod }}</span>
                    </div>
                    @php $i++; @endphp
                @endforeach
            </div>
        </div>
    </div>

</div>
{{-- ===== END TOP 5 ROW ===== --}}



{{-- ================== --}}
{{-- UTILITAS GUDANG --}}
{{-- ================== --}}
<div class="warehouse-wrapper">
    <div class="card-std warehouse-card">

        {{-- HEADER --}}
        <div class="warehouse-header" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <h3 style="margin: 0; white-space: nowrap;">Utilitas Gudang Produksi Di</h3>
            
            {{-- DROPDOWN 1: KATEGORI (UNIT / IPMG) --}}
            <select class="dashboard-select" id="warehouseGroup" onchange="updateWarehouseTypes()" style="width: auto; min-width: 80px;">
                <option value="unit">Unit</option>
                <option value="ipmg">IPMG</option>
            </select>

            {{-- DROPDOWN 2: TIPE MUTU (Isinya berubah via JS) --}}
            <select class="dashboard-select" id="warehouseSelector" onchange="changeWarehouseTab(this.value)" style="width: auto;">
                {{-- Options akan diisi oleh JavaScript --}}
            </select>
        </div>

        {{-- CONTENT (LOOPING PHP TETAP SAMA) --}}
        <div class="warehouse-box">
            
            @foreach($utilitasGudang as $key => $items)
                {{-- ID dibuat slug agar cocok dengan value dropdown (misal: SIR 20 -> sir-20) --}}
                <div id="warehouse-{{ Str::slug($key) }}" class="warehouse-tab-content" style="display: none;">
                    
                    @forelse($items as $item)
                        <div class="warehouse-row">
                            <span class="label">{{ $item['name'] }}</span>
                            <div class="bar-wrapper">
                                {{-- Bar Stock --}}
                                <div class="bar stock" style="width: {{ $item['percent'] > 100 ? 100 : $item['percent'] }}%">
                                    {{ number_format($item['stock'], 0, ',', '.') }}
                                </div>
                                {{-- Bar Kapasitas --}}
                                <div class="bar capacity">
                                    <span class="cap">{{ number_format($item['capacity'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                            <span class="percent">{{ $item['percent'] }}%</span>
                        </div>
                    @empty
                        <div class="text-center p-3 text-muted">Tidak ada data</div>
                    @endforelse

                </div>
            @endforeach

            {{-- LEGEND --}}
            <div class="warehouse-legend mt-3">
                <div class="legend-item"><span class="legend-box stock"></span><span>Stock</span></div>
                <div class="legend-item"><span class="legend-box capacity"></span><span>Kapasitas</span></div>
                <div class="legend-item"><span class="legend-box percent"></span><span>Persentase</span></div>
            </div>

        </div>
    </div>
</div>



{{-- ================== --}}
{{-- HARGA RATA-RATA --}}
{{-- ================== --}}
<div class="card-std avg-price-card">

    <div class="card-header">
        <h3>Harga Rata-Rata</h3>
    </div>

    <div class="avg-table-box">
        <table class="avg-table">
            <thead>
                <tr>
                    <th>Uraian</th>
                    <th>SIR 20</th>
                    <th>RSS</th>
                    <th>SIR 3L</th>
                    <th>SIR 3WF</th>
                    <th>Rata-Rata</th>
                </tr>
            </thead>
            <tbody>
                {{-- BARIS 1: PENYERAHAN --}}
                <tr>
                    <td>1. Penyerahan</td>
                    <td>{{ number_format($hargaRataRata['penyerahan']['sir20'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['penyerahan']['rss'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['penyerahan']['sir3l'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['penyerahan']['sir3wf'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['penyerahan']['average'], 0, ',', '.') }}</td>
                </tr>

                {{-- SECTION HEADER --}}
                <tr class="section-row">
                    <td colspan="6">Outstanding Contract</td>
                </tr>

                {{-- BARIS: SUDAH BAYAR --}}
                <tr>
                    <td class="indent">Sudah Bayar</td>
                    <td>{{ number_format($hargaRataRata['sudah_bayar']['sir20'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['sudah_bayar']['rss'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['sudah_bayar']['sir3l'], 0, ',', '.') }}</td>
                    <td>
                        {{-- Logika: Jika 0 tampilkan strip (-), jika ada isi tampilkan angka --}}
                        @if($hargaRataRata['sudah_bayar']['sir3wf'] == 0) - 
                        @else {{ number_format($hargaRataRata['sudah_bayar']['sir3wf'], 0, ',', '.') }} 
                        @endif
                    </td>
                    <td>{{ number_format($hargaRataRata['sudah_bayar']['average'], 0, ',', '.') }}</td>
                </tr>

                {{-- BARIS: BELUM BAYAR --}}
                <tr>
                    <td class="indent">Belum Bayar</td>
                    <td>{{ number_format($hargaRataRata['belum_bayar']['sir20'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['belum_bayar']['rss'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['belum_bayar']['sir3l'], 0, ',', '.') }}</td>
                    <td>
                        @if($hargaRataRata['belum_bayar']['sir3wf'] == 0) - 
                        @else {{ number_format($hargaRataRata['belum_bayar']['sir3wf'], 0, ',', '.') }} 
                        @endif
                    </td>
                    <td>{{ number_format($hargaRataRata['belum_bayar']['average'], 0, ',', '.') }}</td>
                </tr>

                {{-- BARIS: TOTAL RATA-RATA --}}
                <tr class="avg-row">
                    <td>Rata-Rata</td>
                    <td>{{ number_format($hargaRataRata['total']['sir20'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['total']['rss'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['total']['sir3l'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['total']['sir3wf'], 0, ',', '.') }}</td>
                    <td>{{ number_format($hargaRataRata['total']['average'], 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


{{-- ========== END SPLIT-COL KANAN ========== --}}
    </div>
    {{-- 3. MONTHLY CHARTS (BAWAH) --}}
    <div class="card-std p-0 full-row-card">
        {{-- UBAH LAYOUT JADI FLEX 2 KOLOM --}}
        <div class="layout-split-chart" style="display: flex; flex-wrap: wrap;">
            
            {{-- KOLOM KIRI: CHART (Lebar lebih besar, misal 75%) --}}
            <div class="col-chart-main" style="flex: 3; min-width: 600px; border-right: 1px solid #eee;">
                <div class="chart-header-padded"><h3>Monthly Volume</h3></div>
                {{-- WRAPPER SCROLL UNTUK CHART --}}
                <div class="chart-scroll-container">
                    <div id="chart-monthly-vol"></div>
                </div>
            </div>

            {{-- KOLOM KANAN: SIDEBAR (Lebar lebih kecil, misal 25%) --}}
            <div class="col-sidebar-right bg-light" style="flex: 1; min-width: 250px; display: flex; flex-direction: column;">
                
                {{-- BAGIAN BAWAH: RINCIAN --}}
                <div class="sidebar-section" style="padding: 1.5rem; flex-grow: 1;">
                    <p class="sidebar-title" style="margin-bottom: 1rem; font-weight: 600; color: #666;">Rincian</p>
                    <div class="mutu-list">
                        @foreach($mutu['label'] as $index => $label)
                            @if(strtoupper(trim($label)) === 'TOTAL') @continue @endif
                            @php $vol = $mutu['volume'][$index] ?? 0; $pct = $totalVolume > 0 ? round(($vol/$totalVolume)*100, 1) : 0; @endphp
                            <div class="mutu-item">
                                <div class="mutu-info"><span class="mutu-name">{{ $label }}</span><span class="mutu-pct">{{ $pct }}%</span></div>
                                <div class="progress-bar-bg"><div class="progress-bar-fill orange" style="width: {{ $pct }}%"></div></div>
                                <span class="mutu-val">{{ number_format($vol, 0, ',', '.') }} Ton</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- BAGIAN ATAS: TOTAL --}}
                <div class="sidebar-section border-bottom" style="padding: 1.5rem;">
                    <p class="sidebar-title" style="margin-bottom: 1rem; font-weight: 600; color: #666;">Total</p>
                    <div class="stats-container2">
                        <div class="summary-item"><span class="sum-label">Real</span><span class="sum-val orange">{{ number_format($totalVolume, 0, ',', '.') }} <small>Ton</small></span></div>
                        <div class="summary-item"><span class="sum-label">RKAP</span><span class="sum-val dark">{{ number_format($rkapVolume, 0, ',', '.') }} <small>Ton</small></span></div>
                        <div class="summary-item"><span class="sum-label">Percentage</span><span class="sum-val huge">{{ $rkapVolume > 0 ? round(($totalVolume/$rkapVolume)*100, 0) : 0 }}%</span></div>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <div class="card-std p-0 full-row-card">
        <div class="layout-split-chart" style="display: flex; flex-wrap: wrap;">

            {{-- KOLOM KIRI: CHART --}}
            <div class="col-chart-main" style="flex: 3; min-width: 600px; border-right: 1px solid #eee;">
                <div class="chart-header-padded"><h3>Monthly Revenue</h3></div>
                <div class="chart-scroll-container">
                    <div id="chart-monthly-rev"></div>
                </div>
            </div>

            {{-- KOLOM KANAN: SIDEBAR --}}
            <div class="col-sidebar-right bg-light" style="flex: 1; min-width: 250px; display: flex; flex-direction: column;">

                {{-- RINCIAN --}}
                <div class="sidebar-section" style="padding: 1.5rem; flex-grow: 1;">
                    <p class="sidebar-title" style="margin-bottom: 1rem; font-weight: 600; color: #666;">Rincian</p>
                    <div class="mutu-list">
                        @foreach($mutu['label'] as $index => $label)
                            @if(strtoupper(trim($label)) === 'TOTAL') @continue @endif
                            @php
                                $rev = $mutu['revenue'][$index] ?? 0;
                                $totalR = $totalRevenue / 1000000000;
                                $pct = $totalR > 0 ? round(($rev/$totalR)*100, 1) : 0;
                            @endphp
                            <div class="mutu-item">
                                <div class="mutu-info">
                                    <span class="mutu-name">{{ $label }}</span>
                                    <span class="mutu-pct">{{ $pct }}%</span>
                                </div>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill orange" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="mutu-val">Rp {{ number_format($rev, 0, ',', '.') }} M</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- TOTAL --}}
                <div class="sidebar-section border-bottom" style="padding: 1.5rem;">
                    <p class="sidebar-title" style="margin-bottom: 1rem; font-weight: 600; color: #666;">Total</p>
                    <div class="stats-container2">
                        <div class="summary-item">
                            <span class="sum-label">Real</span>
                            <span class="sum-val orange">
                                Rp {{ number_format($totalRevenue/1000000000, 0, ',', '.') }} <small>Milyar</small>
                            </span>
                        </div>
                        <div class="summary-item">
                            <span class="sum-label">RKAP</span>
                            <span class="sum-val dark">
                                Rp {{ number_format($rkapRevenue/1000000000, 0, ',', '.') }} <small>Milyar</small>
                            </span>
                        </div>
                        <div class="summary-item">
                            <span class="sum-label">Percentage</span>
                            <span class="sum-val huge">
                                {{ $rkapRevenue > 0 ? round(($totalRevenue/$rkapRevenue)*100, 0) : 0 }}%
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script>
    window.dashboardData = {
        priceDaily: @json($trendPriceDaily),
        rawTopBuyers: @json($top5Buyers), rawTopProducts: @json($top5Products),
        topBuyers: @json(array_values($initBuyers)), topBuyersLabels: @json($buyerLabels), 
        topProducts: @json(array_values($initProducts)), topProductsLabels: @json($productLabels),
        volumeReal: @json($rekap4['volume_real']), rkapVol: @json($rekap4['volume_rkap']),
        revenueReal: @json($rekap4['revenue_real']), rkapRev: @json($rekap4['revenue_rkap']),
        monthLabels: @json($rekap4['labels']),
        chartColors: @json($chartColors), prodColors: @json($prodColors)
    };

        const warehouseData = {
        'unit': [
            { id: 'sir-20',  label: 'SIR 20' },
            { id: 'rss-1',   label: 'RSS 1' },   
            { id: 'sir-3wl', label: 'SIR 3WF' } // Sesuaikan dengan key controller kamu
        ],
        'ipmg': [
            { id: 'ipmg-sir', label: 'IPMG SIR' },
            { id: 'ipmg-rss', label: 'IPMG RSS' }
        ]
    };

    // 2. FUNGSI UPDATE DROPDOWN KEDUA
    function updateWarehouseTypes() {
        const groupSelect = document.getElementById('warehouseGroup');
        const typeSelect = document.getElementById('warehouseSelector');
        const selectedGroup = groupSelect.value;
        
        // Kosongkan dropdown kedua
        typeSelect.innerHTML = '';

        // Ambil data array berdasarkan grup yg dipilih
        const options = warehouseData[selectedGroup];

        // Loop untuk membuat <option> baru
        if (options) {
            options.forEach(opt => {
                const newOption = document.createElement('option');
                newOption.value = opt.id;
                newOption.text = opt.label;
                typeSelect.appendChild(newOption);
            });
        }

        // Otomatis trigger perubahan tab untuk opsi pertama
        if (typeSelect.options.length > 0) {
            changeWarehouseTab(typeSelect.value);
        }
    }

    // 3. FUNGSI GANTI TAMPILAN KONTEN (LOGIC SEBELUMNYA)
    function changeWarehouseTab(selectedId) {
        // Sembunyikan semua tab content
        const allTabs = document.querySelectorAll('.warehouse-tab-content');
        allTabs.forEach(tab => {
            tab.style.display = 'none';
        });

        // Tampilkan tab yang sesuai ID
        const activeTab = document.getElementById('warehouse-' + selectedId);
        if (activeTab) {
            activeTab.style.display = 'block';
        }
    }

    // 4. INISIALISASI SAAT HALAMAN DIMUAT
    document.addEventListener('DOMContentLoaded', function() {
        updateWarehouseTypes(); // Load default (Unit)
    });

</script>
<script src="{{ asset('js/dashboard-script.js') }}"></script>
@endsection