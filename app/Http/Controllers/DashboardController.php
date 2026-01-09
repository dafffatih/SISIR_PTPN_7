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
            // --- DATA GRAFIK BULANAN (TETAP/JANGAN DIGANTI) ---
            "Rekap4!F77:F88", // Label Bulan
            "Rekap4!Z77:Z88", // Vol Real Monthly
            "Rekap4!I190:I201", // Vol RKAP Monthly
            "Rekap4!Z94:Z105", // Rev Real Monthly (Grafik)
            "Rekap4!K190:K201", // Rev RKAP Monthly (Grafik)

            // --- DATA TOTAL VOLUME (TETAP) ---
            "Rekap4!E27:E27", // Total Volume Real (S.d Hi)
            "Rekap4!H27:H27", // Total Volume RKAP (S.d Hi)

            // --- DATA TOTAL REVENUE (SUMBER BARU SESUAI REQUEST) ---
            "Rekap4!E48:E51", // Komponen Revenue Real (S.d Bi)
            "Rekap4!H48:H51", // Komponen Revenue RKAP (S.d Bi)

            // --- DATA LAINNYA ---
            "Rekap4!B23:B27", 
            "Rekap4!E23:E27", 
            "Rekap4!E48:E52",
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
            $rawBatch = $sheetService->getBatchData($ranges);
            if (empty($rawBatch)) $useDbFallback = true;
        } catch (\Exception $e) {
            \Log::warning('Batch fetch failed: ' . $e->getMessage());
            $useDbFallback = true;
        }

        // Helper untuk membersihkan angka
        $cleanNum = fn($v) => (float) str_replace(['.', ',', '-'], ['', '.', '0'], $v[0] ?? '0');

        // 2. Olah Data Rekap 4 (GRAFIK BULANAN - LOGIC TETAP)
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

        // --- TOTAL VOLUME (TETAP: Ambil E27 & H27) ---
        $totalVolume = $cleanNum($rawBatch["Rekap4!E27:E27"][0] ?? [0]); 
        $rkapVolume  = $cleanNum($rawBatch["Rekap4!H27:H27"][0] ?? [0]);

        // --- TOTAL REVENUE (LOGIC BARU: Sum E48:E51 & H48:H51) ---
        // Kita ambil array data E48-E51, bersihkan angkanya, lalu jumlahkan.
        // Dikali 1 Milyar karena satuan di Excel adalah "Rp Milyar" sedangkan View membagi dengan 1 Milyar.
        
        // 1. Revenue Real
        $revRealRaw = $rawBatch["Rekap4!E48:E51"] ?? [];
        $revRealSum = 0;
        foreach($revRealRaw as $row) {
            $revRealSum += $cleanNum($row);
        }
        $totalRevenue = $revRealSum * 1000000000;

        // 2. Revenue RKAP
        $revRkapRaw = $rawBatch["Rekap4!H48:H51"] ?? [];
        $revRkapSum = 0;
        foreach($revRkapRaw as $row) {
            $revRkapSum += $cleanNum($row);
        }
        $rkapRevenue = $revRkapSum * 1000000000;


        // --- LOGIC BAWAAN (TIDAK DIUBAH) ---
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
        // Fallback DB Top Products
        if (empty($topProducts)) {
            try {
                $topProducts = Kontrak::selectRaw('mutu, SUM(CAST(volume AS DECIMAL(10,2))) as total_vol')
                    ->groupBy('mutu')->orderBy('total_vol', 'desc')->limit(5)
                    ->pluck('total_vol', 'mutu')->toArray();
            } catch (\Exception $e) { $topProducts = []; }
        }

        // 4. Trend Harga
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
        if (empty($trendPriceDaily[0]['data'])) {
             $trendPriceDaily = [['name'=>'SIR 20', 'data'=>[]], ['name'=>'RSS', 'data'=>[]], ['name'=>'SIR 3L', 'data'=>[]]];
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
                'rss' => 1300, 'sir3l' => 1300, 'sir3wf' => 700
            ]
        ];

        $mutu = [
            'label' => array_column($rawBatch["Rekap4!B23:B27"] ?? [], 0) ?: ['SIR 20', 'RSS 1', 'SIR 3L', 'SIR 3WF', 'TOTAL'],
            'volume' => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!E23:E27"] ?? []) ?: [0, 0, 0, 0, 0],
            'revenue' =>  array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!E48:E52"] ?? []) ?: [0, 0, 0, 0, 0],
        ];

        return view('dashboard.index', compact(
            'totalVolume', 'totalRevenue', 'rkapVolume', 'rkapRevenue',
            'topBuyers', 'topProducts', 'rekap4', 'stokData', 'trendPriceDaily', 'useDbFallback','mutu'
        ));
    }
}