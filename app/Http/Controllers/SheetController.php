<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use App\Models\Kontrak;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Artisan;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;

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
    /**
     * Halaman Manajemen Kontrak - Data REALTIME dari Google Sheets
     */
    public function index(Request $request, GoogleSheetService $sheetService)
    {
        // 1. Ambil Parameter Request
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $sort = $request->input('sort', 'nomor_dosi'); 
        $direction = $request->input('direction', 'asc');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // 2. Ambil Data Raw dari Google Sheets
        $allData = $sheetService->getData();
        $filteredData = [];

        // --- HELPER TRANSLATE BULAN (INDO -> ENG) ---
        // Ini kunci perbaikannya agar "Okt", "Agu", "Mei" terbaca
        $translateMonth = function($dateStr) {
            $map = [
                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
                'Mei' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 
                'Agt' => 'Aug', 'Agu' => 'Aug', // Handle Agt & Agu
                'Sep' => 'Sep', 'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec'
            ];
            // Case insensitive replace
            return str_ireplace(array_keys($map), array_values($map), $dateStr);
        };

        // --- HELPER DATE PARSING ---
        $parseDate = function ($val) use ($translateMonth) {
            if (empty($val) || $val === '-' || $val === '0') return null;
            
            // Bersihkan spasi berlebih
            $val = trim($val);
            
            // Terjemahkan dulu ke Inggris biar Carbon ngerti
            $valEnglish = $translateMonth($val);

            try {
                // Prioritas 1: Format "18-Jul-2024" (d-M-Y)
                return Carbon::createFromFormat('d-M-Y', $valEnglish);
            } catch (\Exception $e) {
                try {
                    // Prioritas 2: Format Excel/Universal
                    return Carbon::parse($valEnglish);
                } catch (\Exception $ex) {
                    return null; 
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
            $I = $row[8] ?? ''; 

            if (empty($I)) continue; 

            // Parsing Tanggal Kontrak
            $rawTgl = $row[10] ?? '';
            $tglKontrakObj = $parseDate($rawTgl); // Menggunakan helper baru

            // --- FILTERING TANGGAL (RANGE) ---
            if ($startDate || $endDate) {
                if (!$tglKontrakObj) continue; 

                if ($startDate) {
                    $startFilter = Carbon::parse($startDate)->startOfDay();
                    if ($tglKontrakObj->lt($startFilter)) continue;
                }

                if ($endDate) {
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
                'K' => $tglKontrakObj ? $tglKontrakObj->format('d-M-Y') : $rawTgl, 
                'K_date' => $tglKontrakObj, // Object Carbon yg sudah valid
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
                    !str_contains(strtolower($item['V']), $searchLower) && 
                    !str_contains(strtolower($item['X']), $searchLower)) {
                    continue;
                }
            }

            $filteredData[] = $item;
        }

        // 4. Sorting Logic (Fixed for Ascending Nulls First)
        usort($filteredData, function ($a, $b) use ($sort, $direction) {
            
            // A. Sorting Tanggal
            if ($sort === 'tgl_kontrak' || $sort === 'K_date') {
                $valA = $a['K_date'];
                $valB = $b['K_date'];

                // Cek Null
                $nullA = is_null($valA);
                $nullB = is_null($valB);

                if ($nullA && $nullB) return 0;

                // LOGIKA UTAMA: Data kosong ditaruh DI ATAS saat Ascending
                if ($nullA) {
                    // Jika Ascending, A dianggap lebih kecil (-1) -> Naik ke atas
                    return ($direction === 'asc') ? -1 : 1;
                }
                if ($nullB) {
                    return ($direction === 'asc') ? 1 : -1;
                }

                // Bandingkan Tanggal Valid (Agustus < Oktober)
                if ($valA->eq($valB)) return 0;
                
                // Logic dasar: A < B return -1
                $comparison = $valA->lt($valB) ? -1 : 1;
                
                return ($direction === 'asc') ? $comparison : -$comparison;
            } 
            
            // B. Sorting Nomor DO/SI
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
            
            // C. Sorting Lainnya
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

    /**
 * =====================================================
 * EXPORT DETAIL KONTRAK PENJUALAN (EXCEL & CSV)
 * Sumber data: Google Sheets (realtime)
 * Dipanggil dari halaman Upload & Export
 * =====================================================
 */
public function exportDetailKontrak(Request $request, GoogleSheetService $sheetService)
{
    $request->validate([
        'format'     => 'required|in:excel,csv',
        'start_date' => 'required',
        'end_date'   => 'required',
    ]);

    // Flatpickr: MM/DD/YYYY
    $start = Carbon::createFromFormat('m/d/Y', $request->start_date)->startOfDay();
    $end   = Carbon::createFromFormat('m/d/Y', $request->end_date)->endOfDay();

    /**
     * ======================================================
     * AMBIL DATA FULL DARI SHEET "SC Sudah Bayar"
     * KUNCI RANGE → TIDAK AKAN TERPOTONG
     * ======================================================
     */
    $rows = $sheetService->getData(
        null,
        'SC Sudah Bayar',
        'A4:BA5359' // ⬅️ SESUAI DATA ASLI SHEET
    );

    $exportData = [];

    foreach ($rows as $row) {

        // Kolom K (index 10) = Tanggal Kontrak
        if (empty($row[10])) {
            continue;
        }

        try {
            $tglKontrak = Carbon::parse($row[10]);
        } catch (\Exception $e) {
            continue;
        }

        // Filter tanggal
        if (!$tglKontrak->between($start, $end)) {
            continue;
        }

        // Mapping DETAIL KONTRAK PENJUALAN
        $exportData[] = [
            'LO / EX'         => $row[7]  ?? '',
            'Nomor Kontrak'   => $row[8]  ?? '',
            'Nama Pembeli'    => $row[9]  ?? '',
            'Tanggal Kontrak' => $row[10] ?? '',
            'Volume (Kg)'     => $row[11] ?? '',
            'Harga'           => $row[12] ?? '',
            'Nilai'           => $row[13] ?? '',
            'Inc PPN'         => $row[14] ?? '',
            'Tanggal Bayar'   => $row[15] ?? '',
            'Unit'            => $row[16] ?? '',
            'Mutu'            => $row[17] ?? '',
            'Nomor DO/SI'     => $row[18] ?? '',
            'Tanggal DO/SI'   => $row[19] ?? '',
            'Port'            => $row[20] ?? '',
            'Kontrak SAP'     => $row[21] ?? '',
            'DP SAP'          => $row[22] ?? '',
            'SO SAP'          => $row[23] ?? '',
            'Kode DO'         => $row[24] ?? '',
            'Sisa Awal'       => $row[25] ?? '',
            'Total Dilayani'  => $row[26] ?? '',
            'Sisa Akhir'      => $row[27] ?? '',
            'Jatuh Tempo'     => $row[52] ?? '',
        ];
    }

    if (empty($exportData)) {
        return back()->with('error', 'Tidak ada data pada rentang tanggal tersebut');
    }

    /**
     * ================= CSV =================
     */
    if ($request->format === 'csv') {
        return response()->streamDownload(function () use ($exportData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_keys($exportData[0]));
            foreach ($exportData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        }, 'Detail_Kontrak_' . now()->format('Ymd_His') . '.csv');
    }

    /**
     * ================= EXCEL =================
     */
    return Excel::download(
        new \App\Exports\ArrayExport($exportData),
        'Detail_Kontrak_' . now()->format('Ymd_His') . '.xlsx'
    );
}


    
}
