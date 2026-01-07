<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use App\Models\Kontrak;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;

class SheetController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

<<<<<<< HEAD
    /**
     * Halaman Manajemen Kontrak (Tabel CRUD)
     */
    public function index(Request $request, GoogleSheetService $sheetService)
=======
    public function index(Request $request)
>>>>>>> 5c984c84c0825f5cabf6bf50d6f620357da4288f
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $sort = $request->input('sort', 'nomor_dosi');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build query from Kontrak model
        $query = Kontrak::query();

<<<<<<< HEAD
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
=======
        // Apply search filter across multiple fields
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_kontrak', 'like', "%{$search}%")
                  ->orWhere('nama_pembeli', 'like', "%{$search}%")
                  ->orWhere('nomor_dosi', 'like', "%{$search}%")
                  ->orWhere('kontrak_sap', 'like', "%{$search}%")
                  ->orWhere('so_sap', 'like', "%{$search}%");
            });
>>>>>>> 5c984c84c0825f5cabf6bf50d6f620357da4288f
        }

        // Apply date range filter
        if ($startDate) {
            try {
                $start = Carbon::createFromFormat('Y-m-d', $startDate)->toDateString();
                $query->whereDate('tgl_kontrak', '>=', $start);
            } catch (\Exception $e) { }
        }
        if ($endDate) {
            try {
                $end = Carbon::createFromFormat('Y-m-d', $endDate)->toDateString();
                $query->whereDate('tgl_kontrak', '<=', $end);
            } catch (\Exception $e) { }
        }

        // Get all matching records (will sort in PHP for DO/SI if needed)
        $allRecords = $query->get();

        // Smart sorting for nomor_dosi - parse DO number and year in PHP
        if ($sort === 'nomor_dosi') {
            $allRecords = $allRecords->sort(function ($a, $b) use ($direction) {
                // Parse DO format: "807/KARET SC/2024" -> extract 807 and 2024
                $parseDoSi = function ($doSi) {
                    if (!$doSi) return [0, 0];
                    $parts = explode('/', $doSi);
                    $number = (int) ($parts[0] ?? 0);
                    $year = (int) ($parts[2] ?? 0);
                    return [$year, $number];
                };

                [$yearA, $numA] = $parseDoSi($a->nomor_dosi);
                [$yearB, $numB] = $parseDoSi($b->nomor_dosi);

                // Sort by year first, then by number
                if ($yearA !== $yearB) {
                    return $direction === 'asc' ? $yearA <=> $yearB : $yearB <=> $yearA;
                }
                return $direction === 'asc' ? $numA <=> $numB : $numB <=> $numA;
            });
        } else {
            // Standard column sorting
            $sortMap = [
                'nomor_kontrak' => 'nomor_kontrak',
                'tgl_kontrak' => 'tgl_kontrak',
                'created_at' => 'created_at',
            ];
            $sortField = $sortMap[$sort] ?? 'nomor_dosi';
            $allRecords = $allRecords->sortBy($sortField, options: 0, descending: $direction === 'desc');
        }

        // Paginate the sorted collection
        $page = request()->get('page', 1);
        $data = new LengthAwarePaginator(
            $allRecords->forPage($page, $perPage),
            count($allRecords),
            $perPage,
            $page,
            [
                'path' => route('kontrak'),
                'query' => $request->except('page'),
            ]
        );

        // Transform data untuk kompatibilitas dengan modal (map Eloquent properties ke struktur lama)
        $data->getCollection()->transform(function ($item) {
            // safe date formatting: handle DateTime/Carbon or string values
            $formatDate = function ($val) {
                if (!$val) return '';
                if ($val instanceof \DateTime) return $val->format('d-M-Y');
                try {
                    return Carbon::parse($val)->format('d-M-Y');
                } catch (\Exception $e) {
                    return (string) $val;
                }
            };

            // Format numbers with Indonesian thousand separator (dot)
            $formatNumber = function ($val) {
                if (!$val && $val !== 0 && $val !== '0') return '';
                return number_format((float)$val, 0, ',', '.');
            };

            return [
                'id' => $item->id,
                'H' => $item->loex,
                'I' => $item->nomor_kontrak,
                'J' => $item->nama_pembeli,
                'K' => $formatDate($item->tgl_kontrak),
                'L' => ($item->volume || $item->volume === 0) ? $formatNumber($item->volume) : '',
                'M' => ($item->harga || $item->harga === 0) ? $formatNumber($item->harga) : '',
                'N' => ($item->nilai || $item->nilai === 0) ? $formatNumber($item->nilai) : '',
                'O' => $item->inc_ppn ?? '',
                'P' => $formatDate($item->tgl_bayar),
                'Q' => $item->unit ?? '',
                'R' => $item->mutu ?? '',
                'S' => $item->nomor_dosi ?? '',
                'T' => $formatDate($item->tgl_dosi),
                'U' => $item->port ?? '',
                'V' => $item->kontrak_sap ?? '',
                'W' => $item->dp_sap ?? '',
                'X' => $item->so_sap ?? '',
                'Y' => $item->kode_do ?? '',
                'Z' => ($item->sisa_awal || $item->sisa_awal === 0) ? $formatNumber($item->sisa_awal) : '',
                'AA' => ($item->total_layan || $item->total_layan === 0) ? $formatNumber($item->total_layan) : '',
                'AB' => ($item->sisa_akhir || $item->sisa_akhir === 0) ? $formatNumber($item->sisa_akhir) : '',
                'BA' => $formatDate($item->jatuh_tempo),
                'row' => $item->id,
            ];
        });

<<<<<<< HEAD
        $data = new LengthAwarePaginator($currentPageItems, $itemCollection->count(), $perPage);
        $data->setPath($request->url());
        $data->appends($request->all());

=======
>>>>>>> 5c984c84c0825f5cabf6bf50d6f620357da4288f
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

<<<<<<< HEAD
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

=======
    // Memperbarui Data Kontrak dari Database
    public function update(Request $request)
    {
>>>>>>> 5c984c84c0825f5cabf6bf50d6f620357da4288f
        try {
            $id = $request->input('id');
            
            // Clean number format (remove dots dari thousands separator)
            $volume = $request->input('volume') ? (float) str_replace(['.', ','], ['', '.'], $request->input('volume')) : null;
            $harga = $request->input('harga') ? (float) str_replace(['.', ','], ['', '.'], $request->input('harga')) : null;
            $nilai = $request->input('nilai') ? (float) str_replace(['.', ','], ['', '.'], $request->input('nilai')) : null;
            
            // Parse date jika ada
            $tglKontrak = null;
            if ($request->input('tgl_kontrak')) {
                try {
                    $tglKontrak = Carbon::createFromFormat('Y-m-d', $request->input('tgl_kontrak'))->format('Y-m-d');
                } catch (\Exception $e) {
                    $tglKontrak = null;
                }
            }
            
            // Update kontrak
            $kontrak = Kontrak::findOrFail($id);
            $kontrak->update([
                'loex' => $request->input('loex'),
                'nomor_kontrak' => $request->input('nomor_kontrak'),
                'nama_pembeli' => $request->input('nama_pembeli'),
                'tgl_kontrak' => $tglKontrak,
                'volume' => $volume,
                'harga' => $harga,
                'nilai' => $nilai,
                'total_layan' => $request->input('total_layan'),
                'sisa_akhir' => $request->input('sisa_akhir'),
            ]);
            
            return back()->with('success', 'Data Berhasil Diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Update gagal: ' . $e->getMessage());
        }
    }
<<<<<<< HEAD
}
=======



    public function dashboard(GoogleSheetService $sheetService)
{
    $allData = $sheetService->getData();
    
    // Inisialisasi Variabel
    $totalVolume = 0;
    $totalRevenue = 0;
    $volumePerMonth = array_fill(1, 12, 0); 
    $revenuePerMonth = array_fill(1, 12, 0);
    $topBuyers = [];
    $topProducts = [];
    $dailyPrices = []; 

    // Loop Data (Mulai index 4 karena asumsi header row 1-4)
    foreach ($allData as $row) {
        $tglKontrak = $row[10] ?? null; // Kolom K
        $volumeStr  = $row[11] ?? '0';  // Kolom L
        $hargaStr   = $row[12] ?? '0';  // Kolom M
        $pembeli    = $row[9] ?? 'Unknown'; // Kolom J
        $produk     = $row[17] ?? 'Other'; // Kolom R
        $jenis      = $row[16] ?? 'Other'; // Kolom Q

        if (!$tglKontrak) continue;

        // Bersihkan format angka
        $volume = (float) str_replace(['.', ','], ['', '.'], $volumeStr);
        $harga  = (float) str_replace(['.', ','], ['', '.'], $hargaStr);
        $revenue = $volume * $harga;

        // Agregasi Total
        $totalVolume += $volume;
        $totalRevenue += $revenue;

        try {
            $date = Carbon::parse($tglKontrak);
            
            // Per Bulan
            $volumePerMonth[$date->month] += $volume;
            $revenuePerMonth[$date->month] += $revenue;

            // Per Hari (Untuk Grafik Harga)
            $dayKey = $date->format('d/m/Y');
            if (!isset($dailyPrices[$dayKey][$produk])) $dailyPrices[$dayKey][$produk] = [];
            $dailyPrices[$dayKey][$produk][] = $harga;

        } catch (\Exception $e) { continue; }

        // Top Buyers & Products
        if (!isset($topBuyers[$pembeli])) $topBuyers[$pembeli] = 0;
        $topBuyers[$pembeli] += $volume;

        $prodKey = $jenis . '/' . $produk;
        if (!isset($topProducts[$prodKey])) $topProducts[$prodKey] = 0;
        $topProducts[$prodKey] += $volume;
    }

    // Sorting & Limit Top 5
    arsort($topBuyers);
    $topBuyers = array_slice($topBuyers, 0, 5);
    arsort($topProducts);
    $topProducts = array_slice($topProducts, 0, 5);

    // Proses Data Grafik Harga
    $chartDates = array_keys($dailyPrices);
    usort($chartDates, fn($a, $b) => Carbon::createFromFormat('d/m/Y', $a)->timestamp <=> Carbon::createFromFormat('d/m/Y', $b)->timestamp);

    $priceSeries = [];
    $allProducts = []; // Cari semua jenis produk unik
    foreach($dailyPrices as $d) foreach($d as $p => $v) $allProducts[] = $p;
    $allProducts = array_unique($allProducts);

    foreach ($allProducts as $prodName) {
        $dataPoints = [];
        foreach ($chartDates as $date) {
            if (isset($dailyPrices[$date][$prodName])) {
                $avg = array_sum($dailyPrices[$date][$prodName]) / count($dailyPrices[$date][$prodName]);
                $dataPoints[] = round($avg);
            } else {
                $dataPoints[] = 0;
            }
        }
        $priceSeries[] = ['name' => $prodName, 'data' => $dataPoints];
    }

    // Dummy RKAP (Target)
    $rkapVolume = 60000000; 
    $rkapRevenue = 90200000000;

    // Arahkan ke view dashboard/index.blade.php
    return view('dashboard.index', compact(
        'totalVolume', 'totalRevenue', 'rkapVolume', 'rkapRevenue',
        'topBuyers', 'topProducts', 'volumePerMonth', 'revenuePerMonth',
        'chartDates', 'priceSeries'
    ));
}

    // Sync data manually dari Google Drive
    public function syncManual()
    {
        try {
            Artisan::call('sync:drive-folder');
            return back()->with('success', 'Sinkronisasi data dari Google Drive berhasil!');
        } catch (\Exception $e) {
            return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
    }
}
>>>>>>> 5c984c84c0825f5cabf6bf50d6f620357da4288f
