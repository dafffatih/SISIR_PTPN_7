@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('page_title', 'Dashboard Overview')

{{-- Filter di Topbar (Opsional, sesuai kode lama kamu) --}}
@section('topbar_filters')
  <div class="flex gap-2">
      <select class="px-3 py-2 border border-slate-200 rounded-lg bg-white text-sm focus:outline-none focus:border-orange-500">
        <option>2025</option>
        <option>2024</option>
      </select>

      <select class="px-3 py-2 border border-slate-200 rounded-lg bg-white text-sm focus:outline-none focus:border-orange-500">
        <option>All Units</option>
        <option>Unit A</option>
        <option>Unit B</option>
      </select>
  </div>
@endsection

@section('content')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<style>
    /* Helper kecil untuk card */
    .card-shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
</style>

<div class="space-y-6">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl p-6 card-shadow relative overflow-hidden border border-slate-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wide">Total Volume</p>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2">
                        {{ number_format($totalVolume / 1000, 3, ',', '.') }} <span class="text-lg text-slate-400 font-medium">Ton</span>
                    </h3>
                    <div class="mt-4 flex items-center gap-2">
                        <span class="text-green-700 bg-green-100 px-2 py-1 rounded text-xs font-bold">
                            Progress: {{ $rkapVolume > 0 ? round(($totalVolume/$rkapVolume)*100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-slate-400 text-xs">Target RKAP</p>
                    <p class="text-slate-600 font-bold text-lg">{{ number_format($rkapVolume/1000, 0, ',', '.') }} <span class="text-xs font-normal">Ton</span></p>
                    <div class="bg-slate-800 p-2 rounded-lg mt-3 inline-block">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                    </div>
                </div>
            </div>
            <div class="w-full bg-slate-100 h-1.5 mt-6 rounded-full overflow-hidden">
                <div class="bg-slate-800 h-1.5 rounded-full" style="width: {{ $rkapVolume > 0 ? ($totalVolume/$rkapVolume)*100 : 0 }}%"></div>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 card-shadow relative overflow-hidden border border-slate-100">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-slate-500 text-xs font-bold uppercase tracking-wide">Total Revenue</p>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2">
                        Rp {{ number_format($totalRevenue / 1000000000, 1, ',', '.') }} <span class="text-lg text-slate-400 font-medium">B</span>
                    </h3>
                    <div class="mt-4 flex items-center gap-2">
                        <span class="text-green-700 bg-green-100 px-2 py-1 rounded text-xs font-bold">
                            Progress: {{ $rkapRevenue > 0 ? round(($totalRevenue/$rkapRevenue)*100, 1) : 0 }}%
                        </span>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-slate-400 text-xs">Target RKAP</p>
                    <p class="text-slate-600 font-bold text-lg">Rp {{ number_format($rkapRevenue/1000000000, 1, ',', '.') }}B</p>
                    <div class="bg-orange-500 p-2 rounded-lg mt-3 inline-block">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>
            <div class="w-full bg-slate-100 h-1.5 mt-6 rounded-full overflow-hidden">
                <div class="bg-orange-500 h-1.5 rounded-full" style="width: {{ $rkapRevenue > 0 ? ($totalRevenue/$rkapRevenue)*100 : 0 }}%"></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl card-shadow border border-slate-100">
            <h3 class="font-bold text-slate-800 mb-4">Daily Selling Price Trend</h3>
            <div id="priceChart" style="min-height: 320px;"></div>
        </div>

        <div class="space-y-6">
            <div class="bg-white p-6 rounded-2xl card-shadow border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-4">Top 5 Buyers</h3>
                <div class="flex items-center">
                    <div id="buyerChart" style="width: 140px;"></div>
                    <div class="flex-1 pl-4 space-y-2">
                        @php $colors = ['#1e293b', '#0f766e', '#14b8a6', '#fb923c', '#fcd34d']; $i=0; @endphp
                        @foreach($topBuyers as $buyer => $vol)
                        <div class="flex justify-between items-center text-xs">
                            <div class="flex items-center gap-2 overflow-hidden">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $colors[$i++ % 5] }}"></span>
                                <span class="text-slate-600 truncate" title="{{ $buyer }}">{{ Str::limit($buyer, 15) }}</span>
                            </div>
                            <span class="font-bold text-slate-800">{{ $totalVolume > 0 ? round(($vol/$totalVolume)*100, 1) : 0 }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl card-shadow border border-slate-100">
                <h3 class="font-bold text-slate-800 mb-4">Top 5 Product Symbols</h3>
                <div class="flex items-center">
                    <div id="productChart" style="width: 140px;"></div>
                    <div class="flex-1 pl-4 space-y-2">
                        @php $i=0; @endphp
                        @foreach($topProducts as $prod => $vol)
                        <div class="flex justify-between items-center text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $colors[$i++ % 5] }}"></span>
                                <span class="text-slate-600 truncate">{{ $prod }}</span>
                            </div>
                            <span class="font-bold text-slate-800">{{ $totalVolume > 0 ? round(($vol/$totalVolume)*100, 1) : 0 }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6">
        <div class="bg-white p-6 rounded-2xl card-shadow border border-slate-100">
            <h3 class="font-bold text-slate-800 mb-4">Monthly Volume (Real vs RKAP)</h3>
            <div id="monthlyVolChart" style="height: 300px;"></div>
        </div>
    </div>

</div>

<script>
    // 1. Line Chart (Prices)
    var priceOptions = {
        series: @json($priceSeries),
        chart: { type: 'line', height: 320, toolbar: { show: false }, zoom: { enabled: false } },
        colors: ['#1e293b', '#fb923c', '#14b8a6'],
        stroke: { curve: 'smooth', width: 3 },
        xaxis: { 
            categories: @json($chartDates),
            labels: { style: { colors: '#94a3b8', fontSize: '10px' } }
        },
        yaxis: { labels: { formatter: (val) => 'Rp ' + (val/1000).toFixed(0) + 'k' } },
        grid: { borderColor: '#f1f5f9' },
        tooltip: { y: { formatter: (val) => "Rp " + val.toLocaleString('id-ID') } }
    };
    new ApexCharts(document.querySelector("#priceChart"), priceOptions).render();

    // 2. Donut Buyer
    var buyerOptions = {
        series: @json(array_values($topBuyers)),
        labels: @json(array_keys($topBuyers)),
        chart: { type: 'donut', height: 160 },
        colors: ['#1e293b', '#0f766e', '#14b8a6', '#fb923c', '#fcd34d'],
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '65%' } } },
        legend: { show: false },
        stroke: { show: false }
    };
    new ApexCharts(document.querySelector("#buyerChart"), buyerOptions).render();

    // 3. Donut Product
    var prodOptions = {
        series: @json(array_values($topProducts)),
        labels: @json(array_keys($topProducts)),
        chart: { type: 'donut', height: 160 },
        colors: ['#1e293b', '#0f766e', '#14b8a6', '#fb923c', '#fcd34d'],
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '65%' } } },
        legend: { show: false },
        stroke: { show: false }
    };
    new ApexCharts(document.querySelector("#productChart"), prodOptions).render();

    // 4. Bar Chart Bulanan
    var monthlyVolOptions = {
        series: [{
            name: 'Real',
            data: @json(array_values($volumePerMonth))
        }],
        chart: { type: 'bar', height: 300, toolbar: { show: false } },
        colors: ['#f97316'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] }
    };
    new ApexCharts(document.querySelector("#monthlyVolChart"), monthlyVolOptions).render();
</script>
@endsection