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

        // Whitelist sortable columns to avoid SQL injection
        $allowedSorts = ['nomor_dosi', 'nomor_kontrak', 'tgl_kontrak', 'created_at'];
        if (!in_array($sort, $allowedSorts)) $sort = 'nomor_dosi';

        // Order and paginate
        $data = $query->orderBy($sort, $direction)->paginate($perPage)->appends($request->except('page'));

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

            return [
                'id' => $item->id,
                'H' => $item->loex,
                'I' => $item->nomor_kontrak,
                'J' => $item->nama_pembeli,
                'K' => $formatDate($item->tgl_kontrak),
                'L' => $item->volume ?? 0,
                'M' => $item->harga ?? 0,
                'N' => $item->nilai ?? 0,
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
                'Z' => $item->sisa_awal ?? 0,
                'AA' => $item->total_layan ?? 0,
                'AB' => $item->sisa_akhir ?? 0,
                'BA' => $formatDate($item->jatuh_tempo),
                'row' => $item->id,
            ];
        });

        return view('dashboard.kontrak.index', compact('data'));
    }

    // Menambah Data Baru (A-BA)
    public function store(Request $request, GoogleSheetService $sheetService)
    {
        $manualInputs = $request->except(['_token']);
        if (empty(array_filter($manualInputs))) {
            return back()->with('error', 'Minimal harus mengisi satu data sebelum menyimpan.');
        }

        $existingData = $sheetService->getData();
        $row = count($existingData) + 4; 

        // Susun array 53 kolom (Index 0 sampai 52)
        $data = [
            "=CONCATENATE(I{$row};Q{$row};R{$row})", // A (0)
            "=CONCATENATE(I{$row};Q{$row})",        // B (1)
            "=CONCATENATE(D{$row};F{$row};H{$row})", // C (2)
            "=IFERROR(E{$row}*1;0)",                // D (3)
            "=IF(LEN(S{$row})=17;LEFT(S{$row};3);LEFT(S{$row};4))", // E (4)
            "=RIGHT(S{$row};4)",                    // F (5)
            "=G".($row-1)."+1",                     // G (6)
            $request->loex ?? "",                   // H (7) - KOLOM INI YANG ANDA MAKSUD
            $request->nomor_kontrak ?? "",          // I (8)
            $request->nama_pembeli ?? "",           // J (9)
            $request->tgl_kontrak ?? "",            // K (10)
            $request->volume ?? "",                 // L (11)
            $request->harga ?? "",                  // M (12)
            $request->nilai ?? "",                  // N (13)
            $request->inc_ppn ?? "",                // O (14)
            $request->tgl_bayar ?? "",              // P (15)
            $request->unit ?? "",                   // Q (16)
            $request->mutu ?? "",                   // R (17)
            $request->nomor_dosi ?? "",             // S (18)
            $request->tgl_dosi ?? "",               // T (19)
            $request->port ?? "",                   // U (20)
            $request->kontrak_sap ?? "",            // V (21)
            $request->dp_sap ?? "",                 // W (22)
            $request->so_sap ?? "",                 // X (23)
            "=C{$row}",                             // Y (24)
            "=L{$row}",                             // Z (25)
            "=(SUMPRODUCT((Panjang!\$P\$2:\$P\$5011='SC Sudah Bayar'!Y{$row})*Panjang!\$Q\$2:\$Q\$5011))+(SUMPRODUCT((Palembang!\$P\$2:\$P\$5003='SC Sudah Bayar'!Y{$row})*Palembang!\$Q\$2:\$Q\$5003))+(SUMPRODUCT((Bengkulu!\$P\$2:\$P\$5000='SC Sudah Bayar'!Y{$row})*Bengkulu!\$Q\$2:\$Q\$5000))", // AA (26)
            "=Z{$row}-AA{$row}",                    // AB (27)
            "=M{$row}*1000",                        // AC (28)
            "=VLOOKUP(J{$row};Katalog!\$D$4:\$E\$101;2;FALSE)", // AD (29)
            "=IF(H{$row}=\"LO\";\"LOKAL\";\"EKSPOR\")", // AE (30)
            "=CONCATENATE(AE{$row};Q{$row})",       // AF (31)
            "", "", "", "", "", "", "",             // AG s/d AL (32-38) - KOSONG
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AM (39)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AN (40)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AO (41)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AP (42)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AQ (43)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AR (44)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AS (45)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AT (46)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AU (47)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AV (48)
            "=AV{$row}+AT{$row}+AR{$row}+AP{$row}+AN{$row}", // AW (49)
            "=IF(Z{$row}>1;L{$row};0)",               // AX (50)
            "=IF(AX{$row}>1;AX{$row}-AW{$row};0)",      // AY (51)
            "=AW{$row}-AA{$row}",                       // AZ (52)
            $request->jatuh_tempo ?? "",                // BA (53)
        ];

        try {
            $sheetService->storeData($data);
            return back()->with('success', 'Data Baru Berhasil Ditambahkan');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // Memperbarui Data Kontrak dari Database
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
