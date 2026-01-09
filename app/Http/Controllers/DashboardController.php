<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use App\Models\Kontrak;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function dashboard(GoogleSheetService $sheetService)
    {
        // 1. Ambil Data Batch
        $ranges = [
            // --- DATA GRAFIK BULANAN (TETAP) ---
            "Rekap4!F77:F88", // Label Bulan
            "Rekap4!Z77:Z88", // Vol Real Monthly
            "Rekap4!I190:I201", // Vol RKAP Monthly
            "Rekap4!Z94:Z105", // Rev Real Monthly (Grafik)
            "Rekap4!K190:K201", // Rev RKAP Monthly (Grafik)

            // --- DATA TOTAL (TETAP) ---
            "Rekap4!E27:E27", "Rekap4!H27:H27", 
            "Rekap4!E48:E51", "Rekap4!H48:H51",

            // --- DATA RINCIAN MUTU (TETAP) ---
            "Rekap4!B23:B27", "Rekap4!E23:E27", "Rekap4!E48:E52",

            // --- DATA LAST TENDER PRICE (BARU) ---
            // T2: Tgl SIR20, U2: Harga SIR20
            // V2: Tgl RSS,   W2: Harga RSS
            // X2: Tgl SIR3L, Y2: Harga SIR3L
            "Katalog!T2:Y2", 

            // --- DATA LAINNYA ---
            "Katalog!B4:B23", "Katalog!C4:C23", "Katalog!L4:L15", "Katalog!M4:M15",
            "Katalog!T3:U500", "Katalog!V3:W500", "Katalog!X3:Y500", 
            "Rekap3!I10:I10", "Rekap3!I38:I38", "Rekap3!D62:E62",
            "Rekap3!I20:I20", "Rekap3!I48:I48", "Rekap3!D72:E72",
            "Rekap3!I21:I21", "Rekap3!I49:I49", "Rekap3!D73:E73",
            "Rekap3!I27:I27",
        ];

        // ... (Kode fetch data sama seperti sebelumnya) ...
        $rawBatch = [];
        $useDbFallback = false;
        try {
            $rawBatch = $sheetService->getBatchData($ranges);
            if (empty($rawBatch)) $useDbFallback = true;
        } catch (\Exception $e) {
            \Log::warning('Batch fetch failed: ' . $e->getMessage());
            $useDbFallback = true;
        }

        $cleanNum = fn($v) => (float) str_replace(['.', ',', '-'], ['', '.', '0'], $v[0] ?? '0');

        // --- PENGOLAHAN DATA LAST TENDER PRICE (BARU) ---
        // Mengambil baris pertama dari range Katalog!T2:Y2
        $cleanTenderPrice = function($val) {
            // 1. Hapus 'Rp', ' ' (spasi), dan '.' (titik ribuan)
            $cleaned = str_replace(['Rp', ' ', '.'], '', $val);
            // 2. Ganti ',' (koma) jadi '.' (titik desimal) untuk float PHP
            $cleaned = str_replace(',', '.', $cleaned);
            return (float) $cleaned;
        };
        $tenderRow = $rawBatch["Katalog!T2:Y2"][0] ?? [];
        
        $lastTender = [
            'sir20' => [
                'date'  => $tenderRow[0] ?? '-', 
                'price' => isset($tenderRow[1]) ? $cleanTenderPrice($tenderRow[1]) : 0
            ],
            'rss' => [
                'date'  => $tenderRow[2] ?? '-', 
                'price' => isset($tenderRow[3]) ? $cleanTenderPrice($tenderRow[3]) : 0
            ],
            'sir3l' => [
                'date'  => $tenderRow[4] ?? '-', 
                'price' => isset($tenderRow[5]) ? $cleanTenderPrice($tenderRow[5]) : 0
            ],
        ];

        // --- LOGIC BAWAAN (TIDAK DIUBAH) ---
        // 2. Olah Data Rekap 4
        $rekap4 = [
            'labels'       => array_column($rawBatch["Rekap4!F77:F88"] ?? [], 0) ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            'volume_real'  => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!Z77:Z88"] ?? []),
            'volume_rkap'  => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!I190:I201"] ?? []),
            'revenue_real' => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!Z94:Z105"] ?? []),
            'revenue_rkap' => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!K190:K201"] ?? []),
        ];
        if (empty($rekap4['volume_real'])) {
            $rekap4['volume_real'] = array_fill(0, 12, 0);
            $rekap4['volume_rkap'] = array_fill(0, 12, 0);
        }
        $mutu = [
            'label'   => array_column($rawBatch["Rekap4!B23:B27"] ?? [], 0) ?: ['SIR 20', 'RSS 1', 'SIR 3L', 'SIR 3WF', 'TOTAL'],
            'volume'  => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!E23:E27"] ?? []) ?: [0, 0, 0, 0, 0],
            'revenue' => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!E48:E52"] ?? []) ?: [0, 0, 0, 0, 0],
        ];
        $totalVolume = $cleanNum($rawBatch["Rekap4!E27:E27"][0] ?? [0]); 
        $rkapVolume  = $cleanNum($rawBatch["Rekap4!H27:H27"][0] ?? [0]);
        
        $revRealSum = 0; foreach(($rawBatch["Rekap4!E48:E51"] ?? []) as $r) $revRealSum += $cleanNum($r);
        $totalRevenue = $revRealSum * 1000000000;
        
        $revRkapSum = 0; foreach(($rawBatch["Rekap4!H48:H51"] ?? []) as $r) $revRkapSum += $cleanNum($r);
        $rkapRevenue = $revRkapSum * 1000000000;

        $topBuyers = [];
        if (!empty($rawBatch["Katalog!B4:B23"])) {
            foreach($rawBatch["Katalog!B4:B23"] as $idx => $row) {
                if(isset($row[0]) && isset($rawBatch["Katalog!C4:C23"][$idx][0])) {
                    $topBuyers[$row[0]] = (float)str_replace(['.',','],['','.'], $rawBatch["Katalog!C4:C23"][$idx][0]);
                }
            }
        }
        arsort($topBuyers); $topBuyers = array_slice($topBuyers, 0, 5);

        $topProducts = [];
        if (!empty($rawBatch["Katalog!L4:L15"])) {
            foreach($rawBatch["Katalog!L4:L15"] as $idx => $row) {
                if(isset($row[0]) && isset($rawBatch["Katalog!M4:M15"][$idx][0])) {
                    $topProducts[$row[0]] = (float)str_replace(['.',','],['','.'], $rawBatch["Katalog!M4:M15"][$idx][0]);
                }
            }
        }
        if (empty($topProducts)) {
            try { $topProducts = Kontrak::selectRaw('mutu, SUM(CAST(volume AS DECIMAL(10,2))) as total_vol')->groupBy('mutu')->orderBy('total_vol', 'desc')->limit(5)->pluck('total_vol', 'mutu')->toArray(); } catch (\Exception $e) { $topProducts = []; }
        }

        $processDaily = function($rows) {
            $points = [];
            foreach ($rows as $row) {
                if (count($row) < 2 || empty($row[0]) || empty($row[1])) continue;
                try {
                    $date = Carbon::createFromFormat('d/m/y', trim($row[0]));
                    $price = (float)str_replace(['.', ','], ['', '.'], $row[1]);
                    $points[] = [$date->timestamp * 1000, $price];
                } catch (\Exception $e) { continue; }
            }
            usort($points, fn($a, $b) => $a[0] <=> $b[0]);
            return $points;
        };
        $trendPriceDaily = [
            ['name' => 'SIR 20', 'data' => $processDaily($rawBatch["Katalog!T3:U500"] ?? [])],
            ['name' => 'RSS',    'data' => $processDaily($rawBatch["Katalog!V3:W500"] ?? [])],
            ['name' => 'SIR 3L', 'data' => $processDaily($rawBatch["Katalog!X3:Y500"] ?? [])],
        ];
        if (empty($trendPriceDaily[0]['data'])) { $trendPriceDaily = [['name'=>'SIR 20', 'data'=>[]], ['name'=>'RSS', 'data'=>[]], ['name'=>'SIR 3L', 'data'=>[]]]; }

        $stokData = [
            'produksi'    => [ 'sir20' => $cleanNum([$rawBatch["Rekap3!I10:I10"][0][0] ?? 0] ?? []) ?: 10500, 'rss' => $cleanNum([$rawBatch["Rekap3!I38:I38"][0][0] ?? 0] ?? []) ?: 8200, 'sir3l' => $cleanNum([$rawBatch["Rekap3!D62:E62"][0][0] ?? 0] ?? []) ?: 6500, 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D62:E62"][0][1] ?? 0] ?? []) ?: 4200 ],
            'sudah_bayar' => [ 'sir20' => $cleanNum([$rawBatch["Rekap3!I20:I20"][0][0] ?? 0] ?? []) ?: 5200, 'rss' => $cleanNum([$rawBatch["Rekap3!I48:I48"][0][0] ?? 0] ?? []) ?: 4100, 'sir3l' => $cleanNum([$rawBatch["Rekap3!D72:E72"][0][0] ?? 0] ?? []) ?: 3200, 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D72:E72"][0][1] ?? 0] ?? []) ?: 2100 ],
            'belum_bayar' => [ 'sir20' => $cleanNum([$rawBatch["Rekap3!I21:I21"][0][0] ?? 0] ?? []) ?: 3100, 'rss' => $cleanNum([$rawBatch["Rekap3!I49:I49"][0][0] ?? 0] ?? []) ?: 2800, 'sir3l' => $cleanNum([$rawBatch["Rekap3!D73:E73"][0][0] ?? 0] ?? []) ?: 2000, 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D73:E73"][0][1] ?? 0] ?? []) ?: 1500 ],
            'bahan_baku'  => [ 'sir20' => $cleanNum([$rawBatch["Rekap3!I27:I27"][0][0] ?? 0] ?? []) ?: 2200, 'rss' => 1300, 'sir3l' => 1300, 'sir3wf' => 700 ]
        ];

        // Tambahkan lastTender ke view
        return view('dashboard.index', compact(
            'totalVolume', 'totalRevenue', 'rkapVolume', 'rkapRevenue', 'mutu', 'lastTender',
            'topBuyers', 'topProducts', 'rekap4', 'stokData', 'trendPriceDaily', 'useDbFallback'
        ));
    }
}