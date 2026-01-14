<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use App\Models\Kontrak;
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

            // --- DATA TOP BUYER DAN TOP PRODUCT ---
            "SC Sudah Bayar!AD4:AD5359", // data pembeli
            "SC Sudah Bayar!Q4:Q5359", // data unit
            "SC Sudah Bayar!AA4:AA5359", // data volume
            "SC Sudah Bayar!R4:R5359" // data mutu
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
        $rawLabels = array_column($rawBatch["Rekap4!B23:B27"] ?? [], 0);
        // Cek apakah label dari sheet benar-benar ada isinya (bukan array kosong atau null semua)
        $hasLabels = !empty($rawLabels) && count(array_filter($rawLabels)) > 0;

        $mutu = [
            'label'   => $hasLabels 
                        ? array_values($rawLabels) 
                        : ['SIR 20', 'RSS 1', 'SIR 3L', 'SIR 3WF', 'TOTAL'],
            
            'volume'  => (!empty($rawBatch["Rekap4!E23:E27"])) 
                        ? array_values(array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!E23:E27"])) 
                        : [0, 0, 0, 0, 0],
            
            'revenue' => (!empty($rawBatch["Rekap4!E48:E52"])) 
                        ? array_values(array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!E48:E52"])) 
                        : [0, 0, 0, 0, 0],
        ];
        $totalVolume = $cleanNum($rawBatch["Rekap4!E27:E27"][0] ?? [0]); 
        $rkapVolume  = $cleanNum($rawBatch["Rekap4!H27:H27"][0] ?? [0]);
        
        $revRealSum = 0; foreach(($rawBatch["Rekap4!E48:E51"] ?? []) as $r) $revRealSum += $cleanNum($r);
        $totalRevenue = $revRealSum * 1000000000;
        
        $revRkapSum = 0; foreach(($rawBatch["Rekap4!H48:H51"] ?? []) as $r) $revRkapSum += $cleanNum($r);
        $rkapRevenue = $revRkapSum * 1000000000;

        // HITUNG TOP BUYER
        // =======================
        // MASTER DATA (PATEN)
        // =======================

        $buyersList = [
            "AIC","ASR","AKR","BGS","BIN","IKD","IDB","JAN","KJA","KIJ",
            "KTK","KTI","KSM","MJI","MOP","OGA","SMN","STT","SNI","TCS",
            "TKS","UKL","VME","WGT","WLK","WTP"
        ];

        $mutuList = ["SIR 20", "RSS 1", "SIR 3L", "SIR 3WF"];

        // Normalisasi
        $buyersList = array_map('strtoupper', $buyersList);
        $mutuList   = array_map('strtoupper', $mutuList);

        // =======================
        // 1. INISIALISASI
        // =======================

        $topBuyers = [];

        foreach ($mutuList as $kategoriMutu) { 
            foreach ($buyersList as $buyer) {
                $topBuyers[$kategoriMutu][$buyer] = 0;
            }
        }

        // =======================
        // 2. LOOP TRANSAKSI
        // =======================

        if (!empty($rawBatch["SC Sudah Bayar!AD4:AD5359"])) {
            foreach ($rawBatch["SC Sudah Bayar!AD4:AD5359"] as $idx => $row) {

                $buyer = isset($row[0]) ? strtoupper(trim($row[0])) : null;

                $volumeStr = isset($rawBatch["SC Sudah Bayar!AA4:AA5359"][$idx][0])
                    ? trim($rawBatch["SC Sudah Bayar!AA4:AA5359"][$idx][0])
                    : null;

                $jenisMutu = isset($rawBatch["SC Sudah Bayar!R4:R5359"][$idx][0])
                    ? strtoupper(trim($rawBatch["SC Sudah Bayar!R4:R5359"][$idx][0]))
                    : null;

                if (!$buyer || !$volumeStr || !$jenisMutu) continue;
                if (!in_array($buyer, $buyersList) || !in_array($jenisMutu, $mutuList)) continue;

                $volume = (float) str_replace(['.', ','], ['', '.'], $volumeStr);

                $topBuyers[$jenisMutu][$buyer] += $volume;
            }
        }

        // =======================
        // 3. HITUNG TOTAL PER BUYER
        // =======================

        $topBuyers["TOTAL"] = array_fill_keys($buyersList, 0);

        foreach ($mutuList as $kategoriMutu) {
            foreach ($buyersList as $buyer) {
                $topBuyers["TOTAL"][$buyer] += $topBuyers[$kategoriMutu][$buyer];
            }
        }

        // =======================
        // 4. FILTER 0, SORTING, TAMBAH TOTAL
        // =======================

        foreach ($topBuyers as $key => $buyersData) {

            // Hitung total sebelum difilter
            $grandTotal = array_sum($buyersData);

            // Hapus buyer dengan volume 0
            $buyersData = array_filter($buyersData, function ($volume) {
                return $volume > 0;
            });

            // Sorting DESC
            arsort($buyersData);

            // Tambahkan TOTAL seperti buyer lain
            $buyersData["TOTAL"] = $grandTotal;

            $topBuyers[$key] = $buyersData;
        }

        // =======================
        // 5. TOP 5 BUYERS + LAINNYA
        // =======================

        $top5Buyers = [];
        $topN = 5;

        foreach ($topBuyers as $kategoriMutu => $buyers) {

            // Buang key TOTAL dulu
            $buyersOnly = $buyers;
            unset($buyersOnly["TOTAL"]);

            // Hapus buyer dengan volume 0
            $buyersOnly = array_filter($buyersOnly, fn($v) => $v > 0);

            // Jika tidak ada data, skip
            if (empty($buyersOnly)) continue;

            // Sort descending
            arsort($buyersOnly);

            // Hitung TOTAL mutu
            $totalMutu = array_sum($buyersOnly);

            // Jika jumlah buyer > 5 → Top 5 + LAINNYA
            if (count($buyersOnly) > $topN) {

                $topOnly = array_slice($buyersOnly, 0, $topN, true);
                $others  = array_slice($buyersOnly, $topN, null, true);

                $top5Buyers[$kategoriMutu] = $topOnly;
                $top5Buyers[$kategoriMutu]["LAINNYA"] = array_sum($others);

            } 
            // Jika ≤ 5 → ambil semua, TANPA LAINNYA
            else {
                $top5Buyers[$kategoriMutu] = $buyersOnly;
            }

            // Tambahkan TOTAL
            $top5Buyers[$kategoriMutu]["TOTAL"] = $totalMutu;
        }


        // =======================
        // HASIL AKHIR
        // =======================
        // $topBuyers   -> data lengkap semua buyer + TOTAL
        // $top5Buyers  -> Top 5 + LAINNYA + TOTAL


        $productList = [
            "SBQ","SEL","SDZ","SEG","SEP","KETA","KEDA",
            "WABE","TUBU","MULA","SDU"
        ];

        $mutuList = ["SIR 20", "RSS 1", "SIR 3L", "SIR 3WF"];

        // Normalisasi
        $productList = array_map('strtoupper', $productList);
        $mutuList    = array_map('strtoupper', $mutuList);

        // =======================
        // 1. INISIALISASI TOP PRODUCTS
        // =======================

        $topProducts = [];

        foreach ($mutuList as $kategoriMutu) { // Ubah jadi $kategoriMutu
            foreach ($productList as $product) {
                $topProducts[$kategoriMutu][$product] = 0;
            }
        }

        // =======================
        // 2. LOOP DATA TRANSAKSI
        // =======================

        if (!empty($rawBatch["SC Sudah Bayar!Q4:Q5359"])) {
            foreach ($rawBatch["SC Sudah Bayar!Q4:Q5359"] as $idx => $row) {

                $product = isset($row[0]) ? strtoupper(trim($row[0])) : null;

                $volumeStr = isset($rawBatch["SC Sudah Bayar!AA4:AA5359"][$idx][0])
                    ? trim($rawBatch["SC Sudah Bayar!AA4:AA5359"][$idx][0])
                    : null;

                $jenisMutu = isset($rawBatch["SC Sudah Bayar!R4:R5359"][$idx][0])
                    ? strtoupper(trim($rawBatch["SC Sudah Bayar!R4:R5359"][$idx][0]))
                    : null;

                // Skip data tidak valid
                if (!$product || !$volumeStr || !$jenisMutu) continue;
                if (!in_array($product, $productList) || !in_array($jenisMutu, $mutuList)) continue;

                // Konversi volume
                $volume = (float) str_replace(['.', ','], ['', '.'], $volumeStr);

                // Akumulasi
                $topProducts[$jenisMutu][$product] += $volume;
            }
        }

        // =======================
        // 3. TOTAL SEMUA MUTU (TOP PRODUCTS TOTAL)
        // =======================

        $topProducts["TOTAL"] = array_fill_keys($productList, 0);

        foreach ($mutuList as $kategoriMutu) {
            foreach ($productList as $product) {
                $topProducts["TOTAL"][$product] += $topProducts[$kategoriMutu][$product];
            }
        }

        // =======================
        // 4. FILTER 0, SORTING, TAMBAH TOTAL
        // =======================

        foreach ($topProducts as $key => $productsData) {

            // Hitung total sebelum difilter
            $grandTotal = array_sum($productsData);

            // Hapus volume 0
            $productsData = array_filter($productsData, fn($v) => $v > 0);

            // Sorting DESC
            arsort($productsData);

            // Tambahkan TOTAL
            $productsData["TOTAL"] = $grandTotal;

            $topProducts[$key] = $productsData;
        }

        // =======================
        // 4. TOP 5 PRODUCTS (PER MUTU + TOTAL)
        // =======================

        $top5Products = [];

        foreach ($topProducts as $kategoriMutu => $products) {

            if ($kategoriMutu === "TOTAL") continue;

            // Ambil total mutu
            $totalMutu = $products["TOTAL"] ?? 0;

            // Buang TOTAL
            $items = $products;
            unset($items["TOTAL"]);

            // Urutkan DESC
            arsort($items);

            // Ambil top 5
            $top5 = array_slice($items, 0, 5, true);

            // Hitung lainnya
            $sumTop5 = array_sum($top5);
            $lainnya = $totalMutu - $sumTop5;

            if ($lainnya > 0) {
                $top5["LAINNYA"] = $lainnya;
            }

            // Tambahkan TOTAL
            $top5["TOTAL"] = $totalMutu;

            $top5Products[$kategoriMutu] = $top5;
        }

        // =======================
        // TOTAL (AKUMULASI SEMUA MUTU)
        // =======================

        $totalProducts = $topProducts["TOTAL"];
        $totalAll = $totalProducts["TOTAL"];

        unset($totalProducts["TOTAL"]);

        arsort($totalProducts);

        $top5Total = array_slice($totalProducts, 0, 5, true);

        $sumTop5Total = array_sum($top5Total);
        $lainnyaTotal = $totalAll - $sumTop5Total;

        if ($lainnyaTotal > 0) {
            $top5Total["LAINNYA"] = $lainnyaTotal;
        }

        $top5Total["TOTAL"] = $totalAll;

        $top5Products["TOTAL"] = $top5Total;
        // dd($topBuyers, $topProducts, $top5Buyers, $top5Products);

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

        // dd([
        //     '1. CEK DATA MENTAH DARI GOOGLE SHEET' => [
        //         'Label (B23:B27)'   => $rawBatch["Rekap4!B23:B27"] ?? 'TIDAK DITEMUKAN / NULL',
        //         'Volume (E23:E27)'  => $rawBatch["Rekap4!E23:E27"] ?? 'TIDAK DITEMUKAN / NULL',
        //         'Revenue (E48:E52)' => $rawBatch["Rekap4!E48:E52"] ?? 'TIDAK DITEMUKAN / NULL',
        //     ],
        //     '2. CEK VARIABEL $mutu SETELAH DIOLAH' => $mutu,
        //     '3. CEK TOTAL VOLUME' => $totalVolume,
        // ]);

        // Tambahkan lastTender ke view
        return view('dashboard.index', compact(
            'totalVolume', 'totalRevenue', 'rkapVolume', 'rkapRevenue', 'mutu', 'lastTender',
            'topBuyers', 'top5Buyers', 'topProducts', 'top5Products', 'rekap4', 'stokData', 'trendPriceDaily', 'useDbFallback'
        ));
    }
}