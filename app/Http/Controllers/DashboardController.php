<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function setYear($year)
    {
        // Validate year format (4 digits)
        if (preg_match('/^\d{4}$/', $year)) {
            session(['selected_year' => $year]);
            return back()->with('success', "Data switched to Year $year");
        }
        
        // Reset to Default
        if ($year === 'default') {
            session()->forget('selected_year');
            return back()->with('success', "Data switched to Default");
        }

        return back()->with('error', 'Invalid Year');
    }

    public function dashboard(Request $request, GoogleSheetService $sheetService)
    {
        // 1. Ambil Data Batch
        $ranges = [
            // --- DATA GRAFIK BULANAN (DARI SHEET REKAP) ---
            "Rekap4!F77:F88", // Label Bulan
            "Rekap4!Z77:Z88", // Vol Real Monthly (Satuan di Excel: TON)
            "Rekap4!I190:I201", // Vol RKAP Monthly (Satuan di Excel: TON)
            "Rekap4!Z94:Z105", // Rev Real Monthly (Satuan di Excel: MILYAR)
            "Rekap4!K190:K201", // Rev RKAP Monthly (Satuan di Excel: MILYAR)

            // --- DATA TOTAL RKAP ---
            "Rekap4!E27:E27", "Rekap4!H27:H27", 
            "Rekap4!E48:E51", "Rekap4!H48:H51",

            // --- DATA RINCIAN MUTU (TETAP) ---
            "Rekap4!B23:B27", "Rekap4!E23:E27", "Rekap4!E48:E52",

            // --- DATA LAST TENDER PRICE ---
            "Katalog!T2:Y2", 

            // --- DATA UTAMA: SC SUDAH BAYAR (UNTUK TOP 5 & TOTAL REAL) ---
            "SC Sudah Bayar!AD4:AD6000", // Pembeli (Buyer)
            "SC Sudah Bayar!Q4:Q6000",   // Produk
            "SC Sudah Bayar!R4:R6000",   // Mutu
            "SC Sudah Bayar!M4:M6000",   // Harga Satuan per kg

            // --- 6 KOLOM PENYERAHAN (TANGGAL & VOLUME) ---
            "SC Sudah Bayar!AM4:AM6000",   // Penyerahan 1 tanggal
            "SC Sudah Bayar!AN4:AN6000",   // Penyerahan 1 kg
            "SC Sudah Bayar!AO4:AO6000",   // Penyerahan 2 tanggal
            "SC Sudah Bayar!AP4:AP6000",   // Penyerahan 2 kg
            "SC Sudah Bayar!AQ4:AQ6000",   // Penyerahan 3 tanggal
            "SC Sudah Bayar!AR4:AR6000",   // Penyerahan 3 kg
            "SC Sudah Bayar!AS4:AS6000",   // Penyerahan 4 tanggal
            "SC Sudah Bayar!AT4:AT6000",   // Penyerahan 4 kg
            "SC Sudah Bayar!AU4:AU6000",   // Penyerahan 5 tanggal
            "SC Sudah Bayar!AV4:AV6000",   // Penyerahan 5 kg
            "SC Sudah Bayar!AW4:AW6000",   // Penyerahan 6 tanggal
            "SC Sudah Bayar!AX4:AX6000",   // Penyerahan 6 kg
            
            // --- DATA LAINNYA (STOK, PRICE TREND, DLL) ---
            "Katalog!B4:B23", "Katalog!C4:C23", "Katalog!L4:L15", "Katalog!M4:M15",
            "Katalog!T3:U500", "Katalog!V3:W500", "Katalog!X3:Y500", 
            "Rekap3!I10:I10", "Rekap3!I38:I38", "Rekap3!D62:E62",
            "Rekap3!I20:I20", "Rekap3!I48:I48", "Rekap3!D72:E72",
            "Rekap3!I21:I21", "Rekap3!I49:I49", "Rekap3!D73:E73",
            "Rekap3!I27:I27",
            "Rekap3!K29:K33", "Rekap3!L29:L33", "Rekap3!M29:M33", "Rekap3!N29:N33",
            "Rekap3!K35:K39", "Rekap3!O35:O39", "Rekap3!P35:P39", "Rekap3!Q35:Q39",
            "Rekap3!K36:K36", "Rekap3!R36:R36", "Rekap3!S36:S36", "Rekap3!T36:T36",
            "Rekap3!K44:K46", "Rekap3!Q44:Q46", "Rekap3!R44:R46", "Rekap3!S44:S46",
            "Rekap3!T44:T46", "Rekap3!U44:U46", "Rekap3!V44:V46",
            "Rekap1!E36:I36", "Rekap1!E38:I38", "Rekap1!E39:I39", "Rekap1!E40:I40",
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

        $cleanNum = fn($v) => (float) str_replace(['.', ',', '-'], ['', '.', '0'], $v[0] ?? '0');

        // --- PENGOLAHAN LAST TENDER PRICE ---
        $cleanTenderPrice = function($val) {
            $cleaned = str_replace(['Rp', ' ', '.'], '', $val);
            $cleaned = str_replace(',', '.', $cleaned);
            return (float) $cleaned;
        };
        $tenderRow = $rawBatch["Katalog!T2:Y2"][0] ?? [];
        $lastTender = [
            'sir20' => ['date' => $tenderRow[0] ?? '-', 'price' => isset($tenderRow[1]) ? $cleanTenderPrice($tenderRow[1]) : 0],
            'rss'   => ['date' => $tenderRow[2] ?? '-', 'price' => isset($tenderRow[3]) ? $cleanTenderPrice($tenderRow[3]) : 0],
            'sir3l' => ['date' => $tenderRow[4] ?? '-', 'price' => isset($tenderRow[5]) ? $cleanTenderPrice($tenderRow[5]) : 0],
        ];

        // =========================================================================
        // FIX: NORMALISASI DATA EXCEL (TON -> KG, MILYAR -> RUPIAH)
        // =========================================================================
        $rekap4 = [
            'labels'       => array_column($rawBatch["Rekap4!F77:F88"] ?? [], 0) ?: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            // Kalikan 1.000 (Ton ke Kg)
            'volume_real'  => array_map(fn($v) => $cleanNum($v) * 1000, $rawBatch["Rekap4!Z77:Z88"] ?? []),
            'volume_rkap'  => array_map(fn($v) => $cleanNum($v) * 1000, $rawBatch["Rekap4!I190:I201"] ?? []),
            // Kalikan 1.000.000.000 (Milyar ke Rupiah Penuh)
            'revenue_real' => array_map(fn($v) => $cleanNum($v) * 1000000000, $rawBatch["Rekap4!Z94:Z105"] ?? []),
            'revenue_rkap' => array_map(fn($v) => $cleanNum($v) * 1000000000, $rawBatch["Rekap4!K190:K201"] ?? []),
        ];

        $rawLabels = array_column($rawBatch["Rekap4!B23:B27"] ?? [], 0);
        $hasLabels = !empty($rawLabels) && count(array_filter($rawLabels)) > 0;
        
        $mutu = [
            'label'   => $hasLabels ? array_values($rawLabels) : ['SIR 20', 'RSS 1', 'SIR 3L', 'SIR 3WF', 'TOTAL'],
            // Normalisasi Mutu juga ke Kg dan Rupiah Penuh
            'volume'  => (!empty($rawBatch["Rekap4!E23:E27"])) ? array_values(array_map(fn($v) => $cleanNum($v) * 1000, $rawBatch["Rekap4!E23:E27"])) : [0,0,0,0,0],
            'revenue' => (!empty($rawBatch["Rekap4!E48:E52"])) ? array_values(array_map(fn($v) => $cleanNum($v) * 1000000000, $rawBatch["Rekap4!E48:E52"])) : [0,0,0,0,0],
        ];

        // =========================================================================
        // LOGIKA FILTER (DIPERBARUI UNTUK DEFAULT JAN-DES)
        // =========================================================================

        // 1. Ambil Parameter Filter (Default 1 untuk Start, 12 untuk End)
        $reqStart = $request->input('start_month', 1);
        $reqEnd   = $request->input('end_month', 12);
        
        // Pastikan Integer
        $startMonth = (int)$reqStart;
        $endMonth   = (int)$reqEnd;
        
        // Logika "Seluruh Bulan" adalah jika user memilih 1 s/d 12
        $isAllMonths = ($startMonth === 1 && $endMonth === 12);

        // Validasi Swap Bulan (Jika Start > End)
        if ($startMonth > $endMonth) { 
            $temp = $startMonth; $startMonth = $endMonth; $endMonth = $temp; 
        }

        // 2. HITUNG TOTAL RKAP
        $rkapVolTotal = 0;
        $rkapRevTotal = 0;
        for ($m = $startMonth; $m <= $endMonth; $m++) {
            $idx = $m - 1; 
            // Karena $rekap4 sudah dikali 1000/1M, penjumlahan ini hasilnya sudah Kg dan Rupiah
            $rkapVolTotal += $rekap4['volume_rkap'][$idx] ?? 0;
            $rkapRevTotal += $rekap4['revenue_rkap'][$idx] ?? 0;
        }
        $rkapVolume  = $rkapVolTotal; 
        $rkapRevenue = $rkapRevTotal; // Tidak perlu dikali 1M lagi karena array sudah dikali di atas

        // 3. AMBIL DATA RAW DARI SHEET
        // Identitas & Harga
        $rawBuyers   = $rawBatch["SC Sudah Bayar!AD4:AD6000"] ?? [];
        $rawProducts = $rawBatch["SC Sudah Bayar!Q4:Q6000"] ?? [];
        $rawMutu     = $rawBatch["SC Sudah Bayar!R4:R6000"] ?? [];
        $rawPrices   = $rawBatch["SC Sudah Bayar!M4:M6000"] ?? [];

        // Data Penyerahan (Pasangan Tanggal & Volume)
        $deliveriesRaw = [
            ['date' => $rawBatch["SC Sudah Bayar!AM4:AM6000"] ?? [], 'vol' => $rawBatch["SC Sudah Bayar!AN4:AN6000"] ?? []], // Penyerahan 1
            ['date' => $rawBatch["SC Sudah Bayar!AO4:AO6000"] ?? [], 'vol' => $rawBatch["SC Sudah Bayar!AP4:AP6000"] ?? []], // Penyerahan 2
            ['date' => $rawBatch["SC Sudah Bayar!AQ4:AQ6000"] ?? [], 'vol' => $rawBatch["SC Sudah Bayar!AR4:AR6000"] ?? []], // Penyerahan 3
            ['date' => $rawBatch["SC Sudah Bayar!AS4:AS6000"] ?? [], 'vol' => $rawBatch["SC Sudah Bayar!AT4:AT6000"] ?? []], // Penyerahan 4
            ['date' => $rawBatch["SC Sudah Bayar!AU4:AU6000"] ?? [], 'vol' => $rawBatch["SC Sudah Bayar!AV4:AV6000"] ?? []], // Penyerahan 5
            ['date' => $rawBatch["SC Sudah Bayar!AW4:AW6000"] ?? [], 'vol' => $rawBatch["SC Sudah Bayar!AX4:AX6000"] ?? []], // Penyerahan 6
        ];

        // --- HELPER PARSING TANGGAL ---
        $getMonthFromRow = function($val) {
            if (empty($val)) return 0;
            $val = trim($val);

            // 1. Blokir tanggal sampah dari Excel
            if ($val === '30/12/99' || $val === '30/12/1899') return 0;

            // 2. Handle Numeric (Excel Serial Date)
            if (is_numeric($val)) {
                try {
                     return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($val)->format('n');
                } catch (\Exception $e) { return 0; }
            }

            // 3. Handle Text (Indonesia/Inggris)
            $map = [
                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'Mei' => 'May', 'Jun' => 'Jun',
                'Jul' => 'Jul', 'Agt' => 'Aug', 'Agu' => 'Aug', 'Sep' => 'Sep', 'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec',
                'Agustus' => 'Aug', 'September' => 'Sep', 'Oktober' => 'Oct', 'Desember' => 'Dec'
            ];
            $valClean = str_ireplace(array_keys($map), array_values($map), $val);

            // 4. COBA PARSE FORMAT INDONESIA (d/m/Y) DULUAN!
            $formats = ['d/m/Y', 'd-m-Y', 'd/m/y', 'd-m-y'];
            foreach ($formats as $fmt) {
                try {
                    $d = Carbon::createFromFormat($fmt, $valClean);
                    if ($d && $d->year > 1900) return $d->month; 
                } catch (\Exception $e) { continue; }
            }

            // Fallback ke auto-parse
            try { return Carbon::parse($valClean)->month; } catch (\Exception $e) { return 0; }
        };

        // 4. Variables Agregasi
        $calcTotalVol = 0;
        $calcTotalRev = 0;
        $buyersAgg    = [];
        $productsAgg  = [];

        // 5. LOOPING UTAMA (Iterasi Baris Kontrak)
        $rowCount = count($rawBuyers);
        
        for ($i = 0; $i < $rowCount; $i++) {
            // Ambil Data Identitas
            $buyer = isset($rawBuyers[$i][0]) ? strtoupper(trim($rawBuyers[$i][0])) : '';
            $prod  = isset($rawProducts[$i][0]) ? strtoupper(trim($rawProducts[$i][0])) : '';
            $mutuName = isset($rawMutu[$i][0]) ? strtoupper(trim($rawMutu[$i][0])) : '';
            
            // Ambil Harga Satuan (Rp/Kg)
            $priceStr = $rawPrices[$i][0] ?? '0';
            $unitPrice = (float) str_replace(['.', ','], ['', '.'], $priceStr);

            // Validasi Data Minimal
            if (empty($buyer) || empty($mutuName)) continue;

            // --- LOOPING 6 DATA PENYERAHAN ---
            foreach ($deliveriesRaw as $del) {
                $tglRaw = $del['date'][$i][0] ?? '';
                $volRaw = $del['vol'][$i][0] ?? '0';
                
                // Volume dalam Kg
                $volItem = (float) str_replace(['.', ','], ['', '.'], $volRaw);

                // LOGIKA UTAMA: Hanya hitung jika volume > 0
                if ($volItem <= 0) continue;

                // Cek Tanggal
                $month = $getMonthFromRow($tglRaw);

                // Filter Bulan (Jika tidak ALL, maka cek range)
                // Jika isAllMonths = true (Jan-Des), maka semua bulan (1-12) akan masuk kondisi di bawah
                // Namun untuk keamanan, kita tetap cek range start-end.
                if (!$isAllMonths) {
                    if ($month < $startMonth || $month > $endMonth || $month === 0) continue;
                } else {
                    // Jika All Months, pastikan bulan valid (1-12)
                    if ($month < 1 || $month > 12) continue;
                }

                // --- HITUNG REVENUE ITEM ---
                // Volume (Kg) * Harga (Rp/Kg) = Total Rupiah
                $revItem = $volItem * $unitPrice;

                // --- AGREGASI ---
                $calcTotalVol += $volItem;
                $calcTotalRev += $revItem;

                // Grouping Buyer
                if (!isset($buyersAgg[$mutuName][$buyer])) $buyersAgg[$mutuName][$buyer] = 0;
                $buyersAgg[$mutuName][$buyer] += $volItem;
                
                if (!isset($buyersAgg['TOTAL'][$buyer])) $buyersAgg['TOTAL'][$buyer] = 0;
                $buyersAgg['TOTAL'][$buyer] += $volItem;

                // Grouping Product
                if (!isset($productsAgg[$mutuName][$prod])) $productsAgg[$mutuName][$prod] = 0;
                $productsAgg[$mutuName][$prod] += $volItem;

                if (!isset($productsAgg['TOTAL'][$prod])) $productsAgg['TOTAL'][$prod] = 0;
                $productsAgg['TOTAL'][$prod] += $volItem;
            }
        }

        // Set Total Akhir (Sudah dalam KG dan RUPIAH)
        $totalVolume  = $calcTotalVol;
        $totalRevenue = $calcTotalRev;

        // 6. PROSES TOP 5 BUYERS
        $top5Buyers = [];
        $loopKeys = array_keys($buyersAgg);
        if(empty($loopKeys)) $loopKeys = ['TOTAL', 'SIR 20', 'RSS 1', 'SIR 3L', 'SIR 3WF'];
        
        foreach ($loopKeys as $kategori) {
            $data = $buyersAgg[$kategori] ?? [];
            if (empty($data)) { $top5Buyers[$kategori] = ['TOTAL' => 0]; continue; }
            
            $grandTotal = array_sum($data);
            arsort($data);
            $top5 = array_slice($data, 0, 5, true);
            $sumTop5 = array_sum($top5);
            $lainnya = $grandTotal - $sumTop5;
            
            if ($lainnya > 0) $top5['LAINNYA'] = $lainnya;
            $top5['TOTAL'] = $grandTotal;
            $top5Buyers[$kategori] = $top5;
        }
        $topBuyers = $buyersAgg;

        // 7. PROSES TOP 5 PRODUCTS
        $top5Products = [];
        $loopKeysProd = array_keys($productsAgg);
        if(empty($loopKeysProd)) $loopKeysProd = ['TOTAL', 'SIR 20', 'RSS 1', 'SIR 3L', 'SIR 3WF'];
        
        foreach ($loopKeysProd as $kategori) {
            $data = $productsAgg[$kategori] ?? [];
            if (empty($data)) { $top5Products[$kategori] = ['TOTAL' => 0]; continue; }
            
            $grandTotal = array_sum($data);
            arsort($data);
            $top5 = array_slice($data, 0, 5, true);
            $sumTop5 = array_sum($top5);
            $lainnya = $grandTotal - $sumTop5;
            
            if ($lainnya > 0) $top5['LAINNYA'] = $lainnya;
            $top5['TOTAL'] = $grandTotal;
            $top5Products[$kategori] = $top5;
        }
        $topProducts = $productsAgg;

        // --- FUNGSI BAWAAN LAINNYA ---
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
            'produksi'    => [ 'sir20' => $cleanNum([$rawBatch["Rekap3!I10:I10"][0][0] ?? 0] ?? []) ?: 0, 'rss' => $cleanNum([$rawBatch["Rekap3!I38:I38"][0][0] ?? 0] ?? []) ?: 0, 'sir3l' => $cleanNum([$rawBatch["Rekap3!D62:E62"][0][0] ?? 0] ?? []) ?: 0, 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D62:E62"][0][1] ?? 0] ?? []) ?: 0 ],
            'sudah_bayar' => [ 'sir20' => $cleanNum([$rawBatch["Rekap3!I20:I20"][0][0] ?? 0] ?? []) ?: 0, 'rss' => $cleanNum([$rawBatch["Rekap3!I48:I48"][0][0] ?? 0] ?? []) ?: 0, 'sir3l' => $cleanNum([$rawBatch["Rekap3!D72:E72"][0][0] ?? 0] ?? []) ?: 0, 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D72:E72"][0][1] ?? 0] ?? []) ?: 0 ],
            'belum_bayar' => [ 'sir20' => $cleanNum([$rawBatch["Rekap3!I21:I21"][0][0] ?? 0] ?? []) ?: 0, 'rss' => $cleanNum([$rawBatch["Rekap3!I49:I49"][0][0] ?? 0] ?? []) ?: 0, 'sir3l' => $cleanNum([$rawBatch["Rekap3!D73:E73"][0][0] ?? 0] ?? []) ?: 0, 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D73:E73"][0][1] ?? 0] ?? []) ?: 0 ],
            'bahan_baku'  => [ 'sir20' => $cleanNum([$rawBatch["Rekap3!I27:I27"][0][0] ?? 0] ?? []) ?: 0, 'rss' => 0, 'sir3l' => 0, 'sir3wf' => 0 ]
        ];

        $processWarehouse = function($rLabel, $rStock, $rCap, $rPct) use ($rawBatch, $cleanNum) {
            $labels = $rawBatch[$rLabel] ?? [];
            $stocks = $rawBatch[$rStock] ?? [];
            $caps   = $rawBatch[$rCap] ?? [];
            $pcts   = $rawBatch[$rPct] ?? [];
            $result = [];
            foreach ($labels as $i => $row) {
                $name = $row[0] ?? '-';
                $stock = isset($stocks[$i]) ? $cleanNum($stocks[$i]) : 0;
                $cap   = isset($caps[$i]) ? $cleanNum($caps[$i]) : 0;
                $rawPct = isset($pcts[$i]) ? $pcts[$i][0] : 0;
                $pctVal = $cleanNum([$rawPct]);
                if ($pctVal <= 1 && $pctVal > 0) { $pctVal = $pctVal * 100; }
                $result[] = ['name' => $name, 'stock' => $stock, 'capacity' => $cap, 'percent' => round($pctVal, 1)];
            }
            return $result;
        };

        $utilitasGudang = [
            'SIR 20' => $processWarehouse("Rekap3!K29:K33", "Rekap3!L29:L33", "Rekap3!M29:M33", "Rekap3!N29:N33"),
            'RSS 1'    => $processWarehouse("Rekap3!K35:K39", "Rekap3!O35:O39", "Rekap3!P35:P39", "Rekap3!Q35:Q39"),
            'SIR 3WL'=> $processWarehouse("Rekap3!K36:K36", "Rekap3!R36:R36", "Rekap3!S36:S36", "Rekap3!T36:T36"),
            'IPMG SIR' => $processWarehouse("Rekap3!K44:K46", "Rekap3!Q44:Q46", "Rekap3!R44:R46", "Rekap3!S44:S46"),
            'IPMG RSS' => $processWarehouse("Rekap3!K44:K46", "Rekap3!T44:T46", "Rekap3!U44:U46", "Rekap3!V44:V46"),
        ];

        $processPriceRow = function($rangeKey) use ($rawBatch, $cleanNum) {
            $row = $rawBatch[$rangeKey][0] ?? []; 
            return [
                'sir20'   => $cleanNum([$row[0] ?? 0]),
                'rss'     => $cleanNum([$row[1] ?? 0]),
                'sir3l'   => $cleanNum([$row[2] ?? 0]),
                'sir3wf'  => $cleanNum([$row[3] ?? 0]),
                'average' => $cleanNum([$row[4] ?? 0]),
            ];
        };

        $hargaRataRata = [
            'penyerahan'  => $processPriceRow("Rekap1!E36:I36"),
            'sudah_bayar' => $processPriceRow("Rekap1!E38:I38"),
            'belum_bayar' => $processPriceRow("Rekap1!E39:I39"),
            'total'       => $processPriceRow("Rekap1!E40:I40"),
        ];

        return view('dashboard.index', compact(
            'rekap4','totalVolume', 'totalRevenue', 'rkapVolume', 'rkapRevenue', 'mutu', 'lastTender',
            'topBuyers', 'top5Buyers', 'topProducts', 'top5Products', 'rekap4', 'stokData', 'trendPriceDaily', 'utilitasGudang',
            'hargaRataRata','useDbFallback'
        ));
    }
}