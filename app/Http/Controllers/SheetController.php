<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SheetController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

    /**
     * Halaman Manajemen Kontrak (Tabel CRUD)
     */
    public function index(Request $request, GoogleSheetService $sheetService)
    {
        $allData = $sheetService->getData();
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

        $filteredData = [];

        foreach ($allData as $index => $row) {
            $realRowIndex = $index + 4; // Dimulai dari A4

            $I = $row[8] ?? ''; // Nomor Kontrak
            if (empty($I)) continue;

            $rowDataMapped = [
                'row'         => $realRowIndex,
                'no_kontrak'  => $I,
                'pembeli'     => $row[9] ?? '',
                'tgl_kontrak' => $row[10] ?? '',
                'volume'      => $row[11] ?? '0',
                'harga'       => $row[12] ?? '0',
                'total_layan' => $row[26] ?? '0',
                'sisa_akhir'  => $row[27] ?? '0',
                'jatuh_tempo' => $row[52] ?? '',
                'unit'        => $row[16] ?? '',
                'mutu'        => $row[17] ?? '',
            ];

            if ($search) {
                if (!str_contains(strtolower($I), strtolower($search)) && 
                    !str_contains(strtolower($rowDataMapped['pembeli']), strtolower($search))) {
                    continue;
                }
            }
            $filteredData[] = $rowDataMapped;
        }

        $currentPage = (int) $request->get('page', 1);
        $itemCollection = collect($filteredData);
        $currentPageItems = $itemCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $data = new LengthAwarePaginator($currentPageItems, $itemCollection->count(), $perPage);
        $data->setPath($request->url());
        $data->appends($request->all());

        return view('dashboard.kontrak.index', compact('data'));
    }

    /**
     * Halaman Dashboard (Grafik & Ringkasan)
     */
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
        foreach ($ranges as $range) {
            $rawBatch[$range] = $sheetService->getData($range);
        }
        $cleanNum = fn($v) => (float) str_replace(['.', ',', '-'], ['', '.', '0'], $v[0] ?? '0');
        // --- Daily Data Processor ---
        $processDaily = function($rows) {
            $points = [];
            foreach ($rows as $row) {
                if (count($row) < 2 || empty($row[0]) || empty($row[1])) continue;
                try {
                    // PAKSA format DD/MM/YY (y kecil untuk 2 digit tahun, Y besar untuk 4 digit)
                    // Sesuaikan: Jika di sheet 24-05-24 gunakan 'd-m-y', jika 24/05/24 gunakan 'd/m/y'
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
            'labels'       => array_column($rawBatch["Rekap4!F77:F88"], 0),
            'volume_real'  => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!Z77:Z88"]),
            'volume_rkap'  => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!I190:I201"]),
            'revenue_real' => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!Z94:Z105"]),
            'revenue_rkap' => array_map(fn($v) => $cleanNum($v), $rawBatch["Rekap4!K190:K201"]),
        ];

        // 3. Olah Top Buyers & Products
        $topBuyers = [];
        foreach($rawBatch["Katalog!B4:B23"] as $idx => $row) {
            if(isset($row[0])) $topBuyers[$row[0]] = (float)str_replace(['.',','],['','.'], $rawBatch["Katalog!C4:C23"][$idx][0] ?? 0);
        }
        arsort($topBuyers);
        $topBuyers = array_slice($topBuyers, 0, 5);

        $topProducts = [];
        foreach($rawBatch["Katalog!L4:L15"] as $idx => $row) {
            if(isset($row[0])) $topProducts[$row[0]] = (float)str_replace(['.',','],['','.'], $rawBatch["Katalog!M4:M15"][$idx][0] ?? 0);
        }

        // 4. Trend Harga Harian (Line Chart)
        $trendPriceDaily = [
            ['name' => 'SIR 20', 'data' => $processDaily($rawBatch["Katalog!T3:U500"])],
            ['name' => 'RSS',    'data' => $processDaily($rawBatch["Katalog!V3:W500"])],
            ['name' => 'SIR 3L', 'data' => $processDaily($rawBatch["Katalog!X3:Y500"])],
        ];

        // 5. Data Rekap 3 (Stok Table)
        $stokData = [
            'produksi'    => ['sir20' => $cleanNum([$rawBatch["Rekap3!I10:I10"][0][0] ?? 0]), 'rss' => $cleanNum([$rawBatch["Rekap3!I38:I38"][0][0] ?? 0]), 'sir3l' => $cleanNum([$rawBatch["Rekap3!D62:E62"][0][0] ?? 0]), 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D62:E62"][0][1] ?? 0])],
            'sudah_bayar' => ['sir20' => $cleanNum([$rawBatch["Rekap3!I20:I20"][0][0] ?? 0]), 'rss' => $cleanNum([$rawBatch["Rekap3!I48:I48"][0][0] ?? 0]), 'sir3l' => $cleanNum([$rawBatch["Rekap3!D72:E72"][0][0] ?? 0]), 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D72:E72"][0][1] ?? 0])],
            'belum_bayar' => ['sir20' => $cleanNum([$rawBatch["Rekap3!I21:I21"][0][0] ?? 0]), 'rss' => $cleanNum([$rawBatch["Rekap3!I49:I49"][0][0] ?? 0]), 'sir3l' => $cleanNum([$rawBatch["Rekap3!D73:E73"][0][0] ?? 0]), 'sir3wf' => $cleanNum([$rawBatch["Rekap3!D73:E73"][0][1] ?? 0])],
            'bahan_baku'  => ['sir20' => $cleanNum([$rawBatch["Rekap3!I27:I27"][0][0] ?? 0]), 'rss' => 0, 'sir3l' => 0, 'sir3wf' => 0]
        ];

        // 6. Hitung Kalkulasi Realtime dari Kontrak
        $kontrakData = $sheetService->getData();
        $totalVolume = 0; $totalRevenue = 0;
        foreach ($kontrakData as $row) {
            $v = (float)str_replace(['.',','],['','.'], $row[11] ?? 0);
            $h = (float)str_replace(['.',','],['','.'], $row[12] ?? 0);
            $totalVolume += $v;
            $totalRevenue += ($v * $h);
        }

        // KOREKSI DISINI: Ambil target akumulatif sampai bulan berjalan 
        // agar perbandingan volume real vs rkap apple-to-apple
        $currentMonthIdx = date('n') - 1; // 0 untuk Januari, 11 untuk Desember
        $rkapVolume = array_sum(array_slice($rekap4['volume_rkap'], 0, $currentMonthIdx + 1)) * 1000;
        $rkapRevenue = array_sum(array_slice($rekap4['revenue_rkap'], 0, $currentMonthIdx + 1)) * 1000000000;

        return view('dashboard.index', compact(
            'totalVolume', 'totalRevenue', 'rkapVolume', 'rkapRevenue',
            'topBuyers', 'topProducts', 'rekap4', 'stokData', 'trendPriceDaily'
        ));
    }

    /**
     * Fitur CRUD: Simpan Data
     */
    public function store(Request $request, GoogleSheetService $sheetService)
    {
        $manualInputs = $request->except(['_token']);
        if (empty(array_filter($manualInputs))) {
            return back()->with('error', 'Minimal harus mengisi satu data.');
        }

        $existingData = $sheetService->getData();
        $row = count($existingData) + 4; 

        $data = [
            "=CONCATENATE(I{$row};Q{$row};R{$row})", // A
            "=CONCATENATE(I{$row};Q{$row})",        // B
            "=CONCATENATE(D{$row};F{$row};H{$row})", // C
            "=IFERROR(E{$row}*1;0)",                // D
            "=IF(LEN(S{$row})=17;LEFT(S{$row};3);LEFT(S{$row};4))", // E
            "=RIGHT(S{$row};4)",                    // F
            "=G".($row-1)."+1",                     // G
            $request->loex ?? "",                   // H
            $request->nomor_kontrak ?? "",          // I
            $request->nama_pembeli ?? "",           // J
            $request->tgl_kontrak ?? "",            // K
            $request->volume ?? "",                 // L
            $request->harga ?? "",                  // M
            $request->nilai ?? "",                  // N
            $request->inc_ppn ?? "",                // O
            $request->tgl_bayar ?? "",              // P
            $request->unit ?? "",                   // Q
            $request->mutu ?? "",                   // R
            $request->nomor_dosi ?? "",             // S
            $request->tgl_dosi ?? "",               // T
            $request->port ?? "",                   // U
            $request->kontrak_sap ?? "",            // V
            $request->dp_sap ?? "",                 // W
            $request->so_sap ?? "",                 // X
            "=C{$row}", "=L{$row}",                 // Y, Z
            "=(SUMPRODUCT((Panjang!\$P\$2:\$P\$5011='SC Sudah Bayar'!Y{$row})*Panjang!\$Q\$2:\$Q\$5011))+(SUMPRODUCT((Palembang!\$P\$2:\$P\$5003='SC Sudah Bayar'!Y{$row})*Palembang!\$Q\$2:\$Q\$5003))+(SUMPRODUCT((Bengkulu!\$P\$2:\$P\$5000='SC Sudah Bayar'!Y{$row})*Bengkulu!\$Q\$2:\$Q\$5000))", // AA
            "=Z{$row}-AA{$row}",                    // AB
            "=M{$row}*1000",                        // AC
            "=VLOOKUP(J{$row};Katalog!\$D$4:\$E$101;2;FALSE)", // AD
            "=IF(H{$row}=\"LO\";\"LOKAL\";\"EKSPOR\")", // AE
            "=CONCATENATE(AE{$row};Q{$row})",       // AF
            "", "", "", "", "", "", "",             // AG-AL
            // Rumus AM - AV (Penyerahan)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=AV{$row}+AT{$row}+AR{$row}+AP{$row}+AN{$row}", // AW
            "=IF(Z{$row}>1;L{$row};0)",               // AX
            "=IF(AX{$row}>1;AX{$row}-AW{$row};0)",      // AY
            "=AW{$row}-AA{$row}",                       // AZ
            $request->jatuh_tempo ?? "",                // BA
        ];

        try {
            $sheetService->storeData($data);
            return back()->with('success', 'Data Berhasil Ditambahkan');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Fitur CRUD: Update Data
     */
    public function update(Request $request, GoogleSheetService $sheetService)
    {
        $row = $request->row_index; 
        $manualInputs = $request->only([
            'loex', 'nomor_kontrak', 'nama_pembeli', 'tgl_kontrak', 
            'volume', 'harga', 'nilai', 'inc_ppn', 'tgl_bayar', 
            'unit', 'mutu', 'nomor_dosi', 'tgl_dosi', 'port', 
            'kontrak_sap', 'dp_sap', 'so_sap', 'jatuh_tempo'
        ]);

        if (empty(array_filter($manualInputs))) {
            return back()->with('error', 'Minimal harus mengisi satu data.');
        }

        // Susun array 53 kolom (Logika sama dengan store)
        $data = [
            "=CONCATENATE(I{$row};Q{$row};R{$row})", "=CONCATENATE(I{$row};Q{$row})", "=CONCATENATE(D{$row};F{$row};H{$row})",
            "=IFERROR(E{$row}*1;0)", "=IF(LEN(S{$row})=17;LEFT(S{$row};3);LEFT(S{$row};4))", "=RIGHT(S{$row};4)", "=G".($row-1)."+1",
            $request->loex ?? "", $request->nomor_kontrak ?? "", $request->nama_pembeli ?? "", $request->tgl_kontrak ?? "",
            $request->volume ?? "", $request->harga ?? "", $request->nilai ?? "", $request->inc_ppn ?? "",
            $request->tgl_bayar ?? "", $request->unit ?? "", $request->mutu ?? "", $request->nomor_dosi ?? "",
            $request->tgl_dosi ?? "", $request->port ?? "", $request->kontrak_sap ?? "", $request->dp_sap ?? "",
            $request->so_sap ?? "", "=C{$row}", "=L{$row}",
            "=(SUMPRODUCT((Panjang!\$P\$2:\$P\$5011='SC Sudah Bayar'!Y{$row})*Panjang!\$Q\$2:\$Q\$5011))+(SUMPRODUCT((Palembang!\$P\$2:\$P\$5003='SC Sudah Bayar'!Y{$row})*Palembang!\$Q\$2:\$Q\$5003))+(SUMPRODUCT((Bengkulu!\$P\$2:\$P\$5000='SC Sudah Bayar'!Y{$row})*Bengkulu!\$Q\$2:\$Q\$5000))",
            "=Z{$row}-AA{$row}", "=M{$row}*1000", "=VLOOKUP(J{$row};Katalog!\$D$4:\$E$101;2;FALSE)",
            "=IF(H{$row}=\"LO\";\"LOKAL\";\"EKSPOR\")", "=CONCATENATE(AE{$row};Q{$row})",
            "", "", "", "", "", "", "", // AG-AL
            // AM-AV (Sama dengan store)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AB$2:\$AB$7775))",
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AC$2:\$AC$7775))",
            "=AV{$row}+AT{$row}+AR{$row}+AP{$row}+AN{$row}", // AW
            "=IF(Z{$row}>1;L{$row};0)", "=IF(AX{$row}>1;AX{$row}-AW{$row};0)", "=AW{$row}-AA{$row}",
            $request->jatuh_tempo ?? "",
        ];

        try {
            $sheetService->updateData($row, $data);
            return back()->with('success', 'Data Berhasil Diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}