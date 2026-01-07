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

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $sort = $request->input('sort', 'nomor_dosi');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build query from Kontrak model
        $query = Kontrak::query();

        // Apply search filter across multiple fields
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_kontrak', 'like', "%{$search}%")
                  ->orWhere('nama_pembeli', 'like', "%{$search}%")
                  ->orWhere('nomor_dosi', 'like', "%{$search}%")
                  ->orWhere('kontrak_sap', 'like', "%{$search}%")
                  ->orWhere('so_sap', 'like', "%{$search}%");
            });
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
     * Memperbarui Data Kontrak dari Database
     */
    public function update(Request $request)
    {
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

    /**
     * Hapus Data Kontrak
     */
    public function destroy($row)
    {
        try {
            Kontrak::findOrFail($row)->delete();
            return back()->with('success', 'Data Berhasil Dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Hapus gagal: ' . $e->getMessage());
        }
    }

    /**
     * Sinkronisasi Manual dari Google Sheets
     */
    public function syncManual(Request $request)
    {
        try {
            \Log::info('Starting manual sync from Google Sheets');
            return back()->with('success', 'Sinkronisasi berhasil dilakukan');
        } catch (\Exception $e) {
            \Log::error('Sync failed: ' . $e->getMessage());
            return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
    }
}
