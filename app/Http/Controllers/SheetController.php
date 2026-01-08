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
    public function index(Request $request, GoogleSheetService $sheetService)
    {
        // Ambil SEMUA data langsung dari Google Sheets (real-time)
        $allData = $sheetService->getData();
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $sort = $request->input('sort', 'nomor_dosi');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $filteredData = [];

        // Process data dari Google Sheets dengan filtering
        foreach ($allData as $index => $row) {
            // Row number di Google Sheets dimulai dari A4 (index 0 = baris 4)
            $realRowIndex = $index + 4;

            // Kolom I = Nomor Kontrak (index 8)
            $I = $row[8] ?? '';
            if (empty($I)) continue; // Skip baris kosong

            // Format date helper
            $formatDate = function ($val) {
                if (!$val) return '';
                try {
                    return Carbon::parse($val)->format('d-M-Y');
                } catch (\Exception $e) {
                    return $val;
                }
            };

            // Format number helper
            $formatNumber = function ($val) {
                if (!$val && $val !== 0 && $val !== '0') return '';
                $cleaned = str_replace(['.', ','], ['', '.'], $val);
                return number_format((float)$cleaned, 0, ',', '.');
            };

            // Map data dari row Google Sheets
            $rowDataMapped = [
                'row' => $realRowIndex, // Row number untuk update/delete
                'id' => $realRowIndex, // Gunakan row number sebagai ID
                'H' => $row[7] ?? '',  // LO/EX
                'I' => $I,             // Nomor Kontrak
                'J' => $row[9] ?? '',  // Nama Pembeli
                'K' => $formatDate($row[10] ?? ''),  // Tgl Kontrak
                'K_raw' => $row[10] ?? '',           // Raw date for input
                'L' => $formatNumber($row[11] ?? '0'),  // Volume
                'M' => $formatNumber($row[12] ?? '0'),  // Harga
                'N' => $formatNumber($row[13] ?? '0'),  // Nilai
                'O' => $row[14] ?? '',  // Inc PPN
                'P' => $formatDate($row[15] ?? ''),  // Tgl Bayar
                'P_raw' => $row[15] ?? '',           // Raw date for input
                'Q' => $row[16] ?? '',  // Unit
                'R' => $row[17] ?? '',  // Mutu
                'S' => $row[18] ?? '',  // Nomor DO/SI
                'T' => $formatDate($row[19] ?? ''),  // Tgl DO/SI
                'T_raw' => $row[19] ?? '',           // Raw date for input
                'U' => $row[20] ?? '',  // PORT
                'V' => $row[21] ?? '',  // Kontrak SAP
                'W' => $row[22] ?? '',  // DP SAP
                'X' => $row[23] ?? '',  // SO SAP
                'Y' => $row[24] ?? '',  // Kode DO
                'Z' => $formatNumber($row[25] ?? '0'),  // Sisa Awal
                'AA' => $formatNumber($row[26] ?? '0'), // Total Layan
                'AB' => $formatNumber($row[27] ?? '0'), // Sisa Akhir
                'BA' => $formatDate($row[52] ?? ''),    // Jatuh Tempo
                'BA_raw' => $row[52] ?? '',             // Raw date for input
            ];

            // Filter berdasarkan search
            if ($search) {
                $searchLower = strtolower($search);
                if (!str_contains(strtolower($I), $searchLower) && 
                    !str_contains(strtolower($rowDataMapped['J']), $searchLower) &&
                    !str_contains(strtolower($rowDataMapped['S']), $searchLower) &&
                    !str_contains(strtolower($rowDataMapped['V']), $searchLower) &&
                    !str_contains(strtolower($rowDataMapped['X']), $searchLower)) {
                    continue;
                }
            }

            // Filter berdasarkan date range
            if ($startDate || $endDate) {
                try {
                    $tglKontrak = !empty($row[10]) ? Carbon::parse($row[10]) : null;
                    if ($startDate && $tglKontrak) {
                        $start = Carbon::createFromFormat('Y-m-d', $startDate);
                        if ($tglKontrak->lt($start)) continue;
                    }
                    if ($endDate && $tglKontrak) {
                        $end = Carbon::createFromFormat('Y-m-d', $endDate);
                        if ($tglKontrak->gt($end)) continue;
                    }
                } catch (\Exception $e) {}
            }

            $filteredData[] = $rowDataMapped;
        }

        // Sorting
        if ($sort === 'nomor_dosi') {
            usort($filteredData, function ($a, $b) use ($direction) {
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
            });
        } else {
            $sortMap = [
                'nomor_kontrak' => 'I',
                'tgl_kontrak' => 'K',
                'created_at' => 'K',
            ];
            $sortField = $sortMap[$sort] ?? 'S';
            usort($filteredData, function ($a, $b) use ($sortField, $direction) {
                $valA = $a[$sortField] ?? '';
                $valB = $b[$sortField] ?? '';
                $cmp = strcmp($valA, $valB);
                return $direction === 'asc' ? $cmp : -$cmp;
            });
        }

        // Pagination
        $currentPage = (int) $request->get('page', 1);
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
     */
    public function update(Request $request, GoogleSheetService $sheetService)
    {
        try {
            // Row index adalah nomor baris di Google Sheets (dari row di form)
            $row = $request->input('row_index');
            if (!$row) {
                return back()->with('error', 'Row index tidak ditemukan');
            }

            $manualInputs = $request->only([
                'loex', 'nomor_kontrak', 'nama_pembeli', 'tgl_kontrak',
                'volume', 'harga', 'nilai', 'inc_ppn', 'tgl_bayar',
                'unit', 'mutu', 'nomor_dosi', 'tgl_dosi', 'port',
                'kontrak_sap', 'dp_sap', 'so_sap', 'jatuh_tempo'
            ]);

            if (empty(array_filter($manualInputs))) {
                return back()->with('error', 'Minimal harus mengisi satu data.');
            }

            // Susun array 53 kolom untuk update ke Google Sheets (sama seperti store)
            $data = [
                "=CONCATENATE(I{$row};Q{$row};R{$row})",
                "=CONCATENATE(I{$row};Q{$row})",
                "=CONCATENATE(D{$row};F{$row};H{$row})",
                "=IFERROR(E{$row}*1;0)",
                "=IF(LEN(S{$row})=17;LEFT(S{$row};3);LEFT(S{$row};4))",
                "=RIGHT(S{$row};4)",
                "=G".($row-1)."+1",
                $request->loex ?? "",
                $request->nomor_kontrak ?? "",
                $request->nama_pembeli ?? "",
                $request->tgl_kontrak ?? "",
                $request->volume ?? "",
                $request->harga ?? "",
                $request->nilai ?? "",
                $request->inc_ppn ?? "",
                $request->tgl_bayar ?? "",
                $request->unit ?? "",
                $request->mutu ?? "",
                $request->nomor_dosi ?? "",
                $request->tgl_dosi ?? "",
                $request->port ?? "",
                $request->kontrak_sap ?? "",
                $request->dp_sap ?? "",
                $request->so_sap ?? "",
                "=C{$row}",
                "=L{$row}",
                "=(SUMPRODUCT((Panjang!\$P\$2:\$P\$5011='SC Sudah Bayar'!Y{$row})*Panjang!\$Q\$2:\$Q\$5011))+(SUMPRODUCT((Palembang!\$P\$2:\$P\$5003='SC Sudah Bayar'!Y{$row})*Palembang!\$Q\$2:\$Q\$5003))+(SUMPRODUCT((Bengkulu!\$P\$2:\$P\$5000='SC Sudah Bayar'!Y{$row})*Bengkulu!\$Q\$2:\$Q\$5000))",
                "=Z{$row}-AA{$row}",
                "=M{$row}*1000",
                "=VLOOKUP(J{$row};Katalog!\$D$4:\$E$101;2;FALSE)",
                "=IF(H{$row}=\"LO\";\"LOKAL\";\"EKSPOR\")",
                "=CONCATENATE(AE{$row};Q{$row})",
                "", "", "", "", "", "", "",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AB$2:\$AB$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AC$2:\$AB$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AB$2:\$AB$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AC$2:\$AC$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AB$2:\$AB$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AC$2:\$AC$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AB$2:\$AB$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AC$2:\$AC$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AB$2:\$AB$7775))",
                "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AC$2:\$AC$7775))",
                "=AV{$row}+AT{$row}+AR{$row}+AP{$row}+AN{$row}",
                "=IF(Z{$row}>1;L{$row};0)",
                "=IF(AX{$row}>1;AX{$row}-AW{$row};0)",
                "=AW{$row}-AA{$row}",
                $request->jatuh_tempo ?? "",
            ];

            // Update langsung ke Google Sheets (bukan database)
            $sheetService->updateData($row, $data);

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
