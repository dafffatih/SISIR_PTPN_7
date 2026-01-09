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

    /**
     * Halaman Manajemen Kontrak - Data REALTIME dari Google Sheets
     */
    /**
     * Halaman Manajemen Kontrak - Data REALTIME dari Google Sheets
     */
    public function index(Request $request, GoogleSheetService $sheetService)
    {
        // 1. Ambil Parameter Request
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $sort = $request->input('sort', 'nomor_dosi'); // Default sort
        $direction = $request->input('direction', 'asc'); // asc atau desc
        $startDate = $request->input('start_date'); // Input Y-m-d dari HTML5
        $endDate = $request->input('end_date');

        // 2. Ambil Data Raw dari Google Sheets
        $allData = $sheetService->getData();
        $filteredData = [];

        // --- HELPER DATE PARSING (FIXED) ---
        // Menangani format "18-Jul-2024" dan variasi lainnya
        $parseDate = function ($val) {
            if (empty($val) || $val === '-' || $val === '0') return null;
            
            try {
                // Prioritas 1: Format spesifik "18-Jul-2024" (d-M-Y)
                // Hati-hati: Carbon butuh locale English untuk "Jul", "Aug", "Oct".
                // Jika sheet menggunakan bahasa Indo (Mei, Agt, Okt), perlu mapping manual atau set locale.
                return Carbon::createFromFormat('d-M-Y', $val);
            } catch (\Exception $e) {
                try {
                    // Prioritas 2: Format tanggal Excel / Universal (Y-m-d atau d/m/Y)
                    return Carbon::parse($val);
                } catch (\Exception $ex) {
                    return null; // Gagal parsing
                }
            }
        };

        $formatNumber = function ($val) {
            if (!$val && $val !== 0 && $val !== '0') return '';
            $cleaned = str_replace(['.', ','], ['', '.'], $val);
            return number_format((float)$cleaned, 0, ',', '.');
        };

        // 3. Mapping & Filtering
        foreach ($allData as $index => $row) {
            $realRowIndex = $index + 4; 
            $I = $row[8] ?? ''; // Nomor Kontrak

            if (empty($I)) continue; // Skip baris kosong

            // Parsing Tanggal Kontrak
            $rawTgl = $row[10] ?? '';
            $tglKontrakObj = $parseDate($rawTgl);

            // --- FILTERING TANGGAL (RANGE) ---
            if ($startDate || $endDate) {
                // Jika filter aktif tapi data ini tdk punya tanggal valid, skip
                if (!$tglKontrakObj) {
                    continue; 
                }

                if ($startDate) {
                    // Start of Day agar inklusif
                    $startFilter = Carbon::parse($startDate)->startOfDay();
                    if ($tglKontrakObj->lt($startFilter)) continue;
                }

                if ($endDate) {
                    // End of Day agar inklusif sampai jam 23:59
                    $endFilter = Carbon::parse($endDate)->endOfDay();
                    if ($tglKontrakObj->gt($endFilter)) continue;
                }
            }

            // Mapping Data
            $item = [
                'row' => $realRowIndex,
                'id' => $realRowIndex,
                'H' => $row[7] ?? '',
                'I' => $I,
                'J' => $row[9] ?? '',
                'K' => $tglKontrakObj ? $tglKontrakObj->format('d-M-Y') : $rawTgl, // Tampilan UI
                'K_date' => $tglKontrakObj, // Object Carbon untuk sorting/filtering
                'L' => $formatNumber($row[11] ?? '0'),
                'M' => $formatNumber($row[12] ?? '0'),
                'N' => $formatNumber($row[13] ?? '0'),
                'O' => $row[14] ?? '',
                'P' => $row[15] ?? '', 
                'Q' => $row[16] ?? '',
                'R' => $row[17] ?? '',
                'S' => $row[18] ?? '', 
                'T' => $row[19] ?? '',
                'U' => $row[20] ?? '',
                'V' => $row[21] ?? '',
                'W' => $row[22] ?? '',
                'X' => $row[23] ?? '',
                'Y' => $row[24] ?? '',
                'Z' => $formatNumber($row[25] ?? '0'),
                'AA' => $formatNumber($row[26] ?? '0'),
                'AB' => $formatNumber($row[27] ?? '0'),
                'BA' => $row[52] ?? '',
            ];

            // --- FILTERING SEARCH ---
            if ($search) {
                $searchLower = strtolower($search);
                if (!str_contains(strtolower($item['I']), $searchLower) && 
                    !str_contains(strtolower($item['J']), $searchLower) &&
                    !str_contains(strtolower($item['S']), $searchLower) &&
                    !str_contains(strtolower($item['V']), $searchLower) && // Tambahan search SAP
                    !str_contains(strtolower($item['X']), $searchLower)) {
                    continue;
                }
            }

            $filteredData[] = $item;
        }

        // 4. Sorting Logic (FIXED)
        usort($filteredData, function ($a, $b) use ($sort, $direction) {
            // A. Sorting Tanggal
            if ($sort === 'tgl_kontrak' || $sort === 'K_date') {
                $valA = $a['K_date'];
                $valB = $b['K_date'];

                // 1. Cek Null (Kosong)
                $nullA = is_null($valA);
                $nullB = is_null($valB);

                if ($nullA && $nullB) return 0;
                
                // Jika Ascending: Null ditaruh paling ATAS (-1)
                // Jika Descending: Null ditaruh paling BAWAH (1)
                if ($nullA) return ($direction === 'asc') ? -1 : 1;
                if ($nullB) return ($direction === 'asc') ? 1 : -1;

                // 2. Bandingkan Value Valid
                if ($valA->eq($valB)) return 0;
                
                // Logika dasar compare: A < B return -1
                $comparison = $valA->lt($valB) ? -1 : 1;
                
                // Balik jika Descending
                return ($direction === 'asc') ? $comparison : -$comparison;
            } 
            
            // B. Sorting Nomor DO/SI (Logic String Parsed)
            elseif ($sort === 'nomor_dosi') {
                 $parseDoSi = function ($doSi) {
                    if (!$doSi) return [0, 0];
                    $parts = explode('/', $doSi);
                    $number = (int) ($parts[0] ?? 0);
                    $year = (int) ($parts[2] ?? 0);
                    return [$year, $number];
                 };
                 [$yearA, $numA] = $parseDoSi($a['S']);
                 [$yearB, $numB] = $parseDoSi($b['S']);
                 
                 if ($yearA !== $yearB) {
                    return $direction === 'asc' ? $yearA <=> $yearB : $yearB <=> $yearA;
                 }
                 return $direction === 'asc' ? $numA <=> $numB : $numB <=> $numA;
            } 
            
            // C. Sorting String Biasa (No Kontrak, dll)
            else {
                $keyMap = ['nomor_kontrak' => 'I', 'pembeli' => 'J'];
                $key = $keyMap[$sort] ?? 'I';
                $valA = strtolower($a[$key] ?? '');
                $valB = strtolower($b[$key] ?? '');
                
                if ($valA == $valB) return 0;
                $cmp = strcmp($valA, $valB);
                return ($direction === 'asc') ? $cmp : -$cmp;
            }
        });

        // 5. Pagination
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($filteredData);
        $currentPageItems = $itemCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $data = new LengthAwarePaginator($currentPageItems, $itemCollection->count(), $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('dashboard.kontrak.index', compact('data'));
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
     * Fitur CRUD: Update Data - REALTIME ke Google Sheets
     * Update hanya kolom-kolom yang dapat diedit (H, I, J, K, L, M, N, O, P, Q, R, S, T, U, V, W, X, BA)
     */
    public function update(Request $request, GoogleSheetService $sheetService)
    {
        try {
            // Row index adalah nomor baris di Google Sheets
            $row = $request->input('row_index');
            if (!$row) {
                return back()->with('error', 'Row index tidak ditemukan');
            }

            \Log::info("Update request for row: {$row}");

            $manualInputs = $request->only([
                'loex', 'nomor_kontrak', 'nama_pembeli', 'tgl_kontrak',
                'volume', 'harga', 'nilai', 'inc_ppn', 'tgl_bayar',
                'unit', 'mutu', 'nomor_dosi', 'tgl_dosi', 'port',
                'kontrak_sap', 'dp_sap', 'so_sap', 'jatuh_tempo'
            ]);

            if (empty(array_filter($manualInputs))) {
                return back()->with('error', 'Minimal harus mengisi satu data.');
            }

            // Update individual cells/columns yang dapat diedit
            // Format: update single cell untuk setiap kolom yang berubah
            
            $updates = [];
            
            // H = LO/EX
            if ($request->has('loex')) {
                $updates["'{sheet}'!H{$row}"] = $request->input('loex', '');
            }
            
            // I = Nomor Kontrak
            if ($request->has('nomor_kontrak')) {
                $updates["'{sheet}'!I{$row}"] = $request->input('nomor_kontrak', '');
            }
            
            // J = Nama Pembeli
            if ($request->has('nama_pembeli')) {
                $updates["'{sheet}'!J{$row}"] = $request->input('nama_pembeli', '');
            }
            
            // K = Tgl Kontrak
            if ($request->has('tgl_kontrak')) {
                $updates["'{sheet}'!K{$row}"] = $request->input('tgl_kontrak', '');
            }
            
            // L = Volume
            if ($request->has('volume')) {
                $updates["'{sheet}'!L{$row}"] = $request->input('volume', '');
            }
            
            // M = Harga
            if ($request->has('harga')) {
                $updates["'{sheet}'!M{$row}"] = $request->input('harga', '');
            }
            
            // N = Nilai
            if ($request->has('nilai')) {
                $updates["'{sheet}'!N{$row}"] = $request->input('nilai', '');
            }
            
            // O = Inc PPN
            if ($request->has('inc_ppn')) {
                $updates["'{sheet}'!O{$row}"] = $request->input('inc_ppn', '');
            }
            
            // P = Tgl Bayar
            if ($request->has('tgl_bayar')) {
                $updates["'{sheet}'!P{$row}"] = $request->input('tgl_bayar', '');
            }
            
            // Q = Unit
            if ($request->has('unit')) {
                $updates["'{sheet}'!Q{$row}"] = $request->input('unit', '');
            }
            
            // R = Mutu
            if ($request->has('mutu')) {
                $updates["'{sheet}'!R{$row}"] = $request->input('mutu', '');
            }
            
            // S = Nomor DO/SI
            if ($request->has('nomor_dosi')) {
                $updates["'{sheet}'!S{$row}"] = $request->input('nomor_dosi', '');
            }
            
            // T = Tgl DO/SI
            if ($request->has('tgl_dosi')) {
                $updates["'{sheet}'!T{$row}"] = $request->input('tgl_dosi', '');
            }
            
            // U = PORT
            if ($request->has('port')) {
                $updates["'{sheet}'!U{$row}"] = $request->input('port', '');
            }
            
            // V = Kontrak SAP
            if ($request->has('kontrak_sap')) {
                $updates["'{sheet}'!V{$row}"] = $request->input('kontrak_sap', '');
            }
            
            // W = DP SAP
            if ($request->has('dp_sap')) {
                $updates["'{sheet}'!W{$row}"] = $request->input('dp_sap', '');
            }
            
            // X = SO SAP
            if ($request->has('so_sap')) {
                $updates["'{sheet}'!X{$row}"] = $request->input('so_sap', '');
            }
            
            // BA = Jatuh Tempo
            if ($request->has('jatuh_tempo')) {
                $updates["'{sheet}'!BA{$row}"] = $request->input('jatuh_tempo', '');
            }

            // Jika tidak ada yang diupdate, return error
            if (empty($updates)) {
                return back()->with('error', 'Tidak ada perubahan data');
            }

            // Update multiple ranges sekaligus
            $sheetService->batchUpdate($updates);

            \Log::info("Update successful for row: {$row}");
            return back()->with('success', 'Data Berhasil Diperbarui');
        } catch (\Exception $e) {
            \Log::error('Update gagal: ' . $e->getMessage());
            return back()->with('error', 'Update gagal: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Data Kontrak dari Google Sheets
     */
    public function destroy($row, GoogleSheetService $sheetService)
    {
        try {
            // Parameter row adalah nomor baris di Google Sheets
            $sheetService->deleteData($row);
            return back()->with('success', 'Data Berhasil Dihapus');
        } catch (\Exception $e) {
            \Log::error('Delete gagal: ' . $e->getMessage());
            return back()->with('error', 'Hapus gagal: ' . $e->getMessage());
        }
    }

    /**
     * Sinkronisasi Manual dari Google Sheets
     */
    public function syncManual(Request $request)
    {
        try {
            \Log::info('Starting manual sync from Google Sheets (triggered by user)');

            // Call existing console command that already contains robust sync logic
            $exitCode = Artisan::call('sync:drive-folder');
            $output = trim(Artisan::output());

            if ($exitCode === 0) {
                \Log::info('Manual sync completed: ' . $output);
                return back()->with('success', 'Sinkronisasi berhasil dilakukan');
            }

            \Log::warning('Manual sync finished with non-zero exit code: ' . $exitCode . ' output:' . $output);
            return back()->with('error', 'Sinkronisasi selesai dengan peringatan. Periksa log.');
        } catch (\Exception $e) {
            \Log::error('Sync failed: ' . $e->getMessage());
            return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
    }
}
