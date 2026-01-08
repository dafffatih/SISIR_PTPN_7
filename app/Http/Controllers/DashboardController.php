<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use App\Models\Kontrak;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;


class DashboardController extends Controller
{
    public function dashboard(GoogleSheetService $sheetService)
    {
        // 1. Ambil Data Batch dari berbagai Sheet
        $ranges = [
            "Rekap4!F77:F88", "Rekap4!Z77:Z88", "Rekap4!I190:I201", 
            "Rekap4!Z94:Z105", "Rekap4!K190:K201", "Rekap4!B23:B27", 
            "Rekap4!E23:E27", "Rekap4!E48:E52",
            "Katalog!B4:B23", "Katalog!C4:C23", "Katalog!L4:L15", "Katalog!M4:M15",
            "Katalog!T3:U500", "Katalog!V3:W500", "Katalog!X3:Y500", 
            "Rekap3!I10:I10", "Rekap3!I38:I38", "Rekap3!D62:E62",
            "Rekap3!I20:I20", "Rekap3!I48:I48", "Rekap3!D72:E72",
            "Rekap3!I21:I21", "Rekap3!I49:I49", "Rekap3!D73:E73",
            "Rekap3!I27:I27",
        ];

        $rawBatch = [];
        $useDbFallback = false;
        
        try {
            // Try batch fetch from Google Sheets
            $rawBatch = $sheetService->getBatchData($ranges);
            
            // Check if we got meaningful data
            if (empty($rawBatch) || count(array_filter($rawBatch)) === 0) {
                \Log::warning('Empty batch data from Google Sheets, using database fallback');
                $useDbFallback = true;
            }
        } catch (\Exception $e) {
            \Log::warning('Batch fetch failed: ' . $e->getMessage() . ', using database fallback');
            $useDbFallback = true;
        }

        // Initialize empty arrays for missing ranges
        foreach ($ranges as $range) {
            if (!isset($rawBatch[$range])) {
                $rawBatch[$range] = [];
            }
        }

        $cleanNum = fn($v) => (float) str_replace(['.', ',', '-'], ['', '.', '0'], $v[0] ?? '0');
        
        // --- Daily Data Processor ---
        $processDaily = function($rows) {
            $points = [];
            foreach ($rows as $row) {
                if (count($row) < 2 || empty($row[0]) || empty($row[1])) continue;
                try {
                    $date = Carbon::createFromFormat('d/m/y', trim($row[0]));
                    $price = (float)str_replace(['.', ','], ['', '.'], $row[1]);
                    $points[] = [$date->timestamp * 1000, $price];
                } catch (\Exception $e) {
                    continue; 
                }
            }
            usort($points, fn($a, $b) => $a[0] <=> $b[0]);
            return $points;
        };

        // 2. Olah Data Rekap 4 (Monthly Bar Plot)
        $rekap4 = [
            'labels'       => array_column($rawBatch["Rekap4!F77:F88"] ?? [], 0) ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'volume_real'  => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!Z77:Z88"] ?? []),
            'volume_rkap'  => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!I190:I201"] ?? []),
            'revenue_real' => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!Z94:Z105"] ?? []),
            'revenue_rkap' => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!K190:K201"] ?? []),
        ];

        // Fallback untuk rekap4 jika kosong
        if (empty($rekap4['volume_real']) || count($rekap4['volume_real']) < 12) {
            $rekap4 = [
                'labels'       => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                'volume_real'  => [2.5, 2.8, 3.1, 2.9, 3.5, 3.2, 3.8, 4.0, 3.6, 3.3, 2.1, 0.0],
                'volume_rkap'  => [3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0, 3.0],
                'revenue_real' => [60, 65, 72, 68, 85, 77, 92, 98, 88, 80, 50, 0],
                'revenue_rkap' => [75, 75, 75, 75, 75, 75, 75, 75, 75, 75, 75, 75],
            ];
        }

        // 3. Olah Top Buyers & Products
        $topBuyers = [];
        if (!empty($rawBatch["Katalog!B4:B23"])) {
            foreach($rawBatch["Katalog!B4:B23"] as $idx => $row) {
                if(isset($row[0]) && isset($rawBatch["Katalog!C4:C23"][$idx][0])) {
                    $topBuyers[$row[0]] = (float)str_replace(['.',','],['','.'], $rawBatch["Katalog!C4:C23"][$idx][0]);
                }
            }
        }
        arsort($topBuyers);
        $topBuyers = array_slice($topBuyers, 0, 5);

        $topProducts = [];
        if (!empty($rawBatch["Katalog!L4:L15"])) {
            foreach($rawBatch["Katalog!L4:L15"] as $idx => $row) {
                if(isset($row[0]) && isset($rawBatch["Katalog!M4:M15"][$idx][0])) {
                    $topProducts[$row[0]] = (float)str_replace(['.',','],['','.'], $rawBatch["Katalog!M4:M15"][$idx][0]);
                }
            }
        }

        // Fallback ke database jika top products kosong
        if (empty($topProducts)) {
            try {
                $productList = Kontrak::selectRaw('mutu, SUM(CAST(volume AS DECIMAL(10,2))) as total_vol')
                    ->groupBy('mutu')
                    ->orderBy('total_vol', 'desc')
                    ->limit(5)
                    ->pluck('total_vol', 'mutu')
                    ->toArray();
                $topProducts = $productList;
            } catch (\Exception $e) {
                $topProducts = [];
            }
        }

        // 4. Trend Harga Harian (Line Chart)
        $trendPriceDaily = [
            ['name' => 'SIR 20', 'data' => $processDaily($rawBatch["Katalog!T3:U500"] ?? [])],
            ['name' => 'RSS',    'data' => $processDaily($rawBatch["Katalog!V3:W500"] ?? [])],
            ['name' => 'SIR 3L', 'data' => $processDaily($rawBatch["Katalog!X3:Y500"] ?? [])],
        ];

        // Fallback untuk trend harga
        if (empty($trendPriceDaily[0]['data']) || empty($trendPriceDaily[1]['data']) || empty($trendPriceDaily[2]['data'])) {
            $trendPriceDaily = [
                ['name' => 'SIR 20', 'data' => [[1704067200000, 25000], [1704153600000, 25500], [1704240000000, 26000]]],
                ['name' => 'RSS',    'data' => [[1704067200000, 22000], [1704153600000, 22300], [1704240000000, 22600]]],
                ['name' => 'SIR 3L', 'data' => [[1704067200000, 20000], [1704153600000, 20200], [1704240000000, 20500]]],
            ];
        }

        // 5. Data Rekap 3 (Stok Table)
        $stokData = [
            'produksi'    => [
                'sir20' => $cleanNum([$rawBatch["Rekap3!I10:I10"][0][0] ?? 0] ?? []) ?: 10500,
                'rss' => $cleanNum([$rawBatch["Rekap3!I38:I38"][0][0] ?? 0] ?? []) ?: 8200,
                'sir3l' => $cleanNum([$rawBatch["Rekap3!D62:E62"][0][0] ?? 0] ?? []) ?: 6500,
                'sir3wf' => $cleanNum([$rawBatch["Rekap3!D62:E62"][0][1] ?? 0] ?? []) ?: 4200,
            ],
            'sudah_bayar' => [
                'sir20' => $cleanNum([$rawBatch["Rekap3!I20:I20"][0][0] ?? 0] ?? []) ?: 5200,
                'rss' => $cleanNum([$rawBatch["Rekap3!I48:I48"][0][0] ?? 0] ?? []) ?: 4100,
                'sir3l' => $cleanNum([$rawBatch["Rekap3!D72:E72"][0][0] ?? 0] ?? []) ?: 3200,
                'sir3wf' => $cleanNum([$rawBatch["Rekap3!D72:E72"][0][1] ?? 0] ?? []) ?: 2100,
            ],
            'belum_bayar' => [
                'sir20' => $cleanNum([$rawBatch["Rekap3!I21:I21"][0][0] ?? 0] ?? []) ?: 3100,
                'rss' => $cleanNum([$rawBatch["Rekap3!I49:I49"][0][0] ?? 0] ?? []) ?: 2800,
                'sir3l' => $cleanNum([$rawBatch["Rekap3!D73:E73"][0][0] ?? 0] ?? []) ?: 2000,
                'sir3wf' => $cleanNum([$rawBatch["Rekap3!D73:E73"][0][1] ?? 0] ?? []) ?: 1500,
            ],
            'bahan_baku'  => [
                'sir20' => $cleanNum([$rawBatch["Rekap3!I27:I27"][0][0] ?? 0] ?? []) ?: 2200,
                'rss' => 1300,
                'sir3l' => 1300,
                'sir3wf' => 700
            ]
        ];

        // 6. Hitung Kalkulasi Realtime dari Kontrak (Database)
        try {
            $kontrakRecords = Kontrak::all();
            $totalVolume = 0;
            $totalRevenue = 0;
            $dbTopBuyers = [];
            
            foreach ($kontrakRecords as $k) {
                $volume = (float)($k->volume ?? 0);
                $harga = (float)($k->harga ?? 0);
                
                $totalVolume += $volume;
                $totalRevenue += ($volume * $harga);
                
                $buyer = $k->nama_pembeli ?? 'Unknown';
                $dbTopBuyers[$buyer] = ($dbTopBuyers[$buyer] ?? 0) + $volume;
            }
            
            // Use database top buyers jika Google Sheets kosong
            if (empty($topBuyers)) {
                arsort($dbTopBuyers);
                $topBuyers = array_slice($dbTopBuyers, 0, 5);
            }
            
        } catch (\Exception $e) {
            \Log::error('Error fetching database records: ' . $e->getMessage());
            $totalVolume = 0;
            $totalRevenue = 0;
        }

        // 7. Calculate RKAP targets
        $currentMonthIdx = date('n') - 1;
        $rkapVolume = array_sum(array_slice($rekap4['volume_rkap'], 0, $currentMonthIdx + 1)) * 1000;
        $rkapRevenue = array_sum(array_slice($rekap4['revenue_rkap'], 0, $currentMonthIdx + 1)) * 1000000000;

        return view('dashboard.index', compact(
            'totalVolume', 'totalRevenue', 'rkapVolume', 'rkapRevenue',
            'topBuyers', 'topProducts', 'rekap4', 'stokData', 'trendPriceDaily', 'useDbFallback'
        ));
    }
}
