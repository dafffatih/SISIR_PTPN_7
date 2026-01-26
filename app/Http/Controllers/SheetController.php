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

    public function index(Request $request, GoogleSheetService $sheetService)
    {
        $search     = $request->input('search');
        $perPage    = (int) $request->input('per_page', 10);
        $sort       = $request->input('sort', 'nomor_dosi');
        $direction  = $request->input('direction', 'desc');
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');

        $allData = $sheetService->getData();
        $filteredData = [];

        $translateMonth = function ($dateStr) {
            $map = [
                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr',
                'Mei' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul',
                'Agt' => 'Aug', 'Agu' => 'Aug',
                'Sep' => 'Sep', 'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec'
            ];
            return str_ireplace(array_keys($map), array_values($map), $dateStr);
        };

        $parseDate = function ($val) use ($translateMonth) {
            if (empty($val) || $val === '-' || $val === '0') return null;
            $val = trim($val);
            $valEnglish = $translateMonth($val);
            try {
                return Carbon::createFromFormat('d-M-Y', $valEnglish);
            } catch (\Exception $e) {
                try {
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

        foreach ($allData as $index => $row) {
            $realRowIndex = $index + 4;
            $nomorKontrak = $row[8] ?? '';

            if (empty($nomorKontrak)) continue;

            $rawTgl = $row[10] ?? '';
            $tglKontrakObj = $parseDate($rawTgl);

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

            $item = [
                'row' => $realRowIndex,
                'id'  => $realRowIndex,
                'H'   => $row[7] ?? '',
                'I'   => $nomorKontrak,
                'J'   => $row[9] ?? '',
                'K'   => $tglKontrakObj ? $tglKontrakObj->format('d-M-Y') : $rawTgl,
                'K_date' => $tglKontrakObj,
                'L'   => $formatNumber($row[11] ?? '0'),
                'M'   => $formatNumber($row[12] ?? '0'),
                'N'   => $formatNumber($row[13] ?? '0'),
                'O'   => $row[14] ?? '',
                'P'   => $row[15] ?? '',
                'Q'   => $row[16] ?? '',
                'R'   => $row[17] ?? '',
                'S'   => $row[18] ?? '',
                'T'   => $row[19] ?? '',
                'U'   => $row[20] ?? '',
                'V'   => $row[21] ?? '',
                'W'   => $row[22] ?? '',
                'X'   => $row[23] ?? '',
                'Y'   => $row[24] ?? '',
                'Z'   => $formatNumber($row[25] ?? '0'),
                'AA'  => $formatNumber($row[26] ?? '0'),
                'AB'  => $formatNumber($row[27] ?? '0'),
                'BA'  => $row[52] ?? '',
            ];

            if ($search) {
                $searchLower = strtolower($search);
                if (
                    !str_contains(strtolower($item['I']), $searchLower) &&
                    !str_contains(strtolower($item['J']), $searchLower) &&
                    !str_contains(strtolower($item['S']), $searchLower) &&
                    !str_contains(strtolower($item['V']), $searchLower) &&
                    !str_contains(strtolower($item['X']), $searchLower)
                ) {
                    continue;
                }
            }

            $filteredData[] = $item;
        }

        usort($filteredData, function ($a, $b) use ($sort, $direction) {
            if ($sort === 'tgl_kontrak' || $sort === 'K_date') {
                $valA = $a['K_date'];
                $valB = $b['K_date'];
                $nullA = is_null($valA);
                $nullB = is_null($valB);

                if ($nullA && $nullB) return 0;
                if ($nullA) return ($direction === 'asc') ? -1 : 1;
                if ($nullB) return ($direction === 'asc') ? 1 : -1;
                if ($valA->eq($valB)) return 0;
                $comparison = $valA->lt($valB) ? -1 : 1;
                return ($direction === 'asc') ? $comparison : -$comparison;
            } elseif ($sort === 'nomor_dosi') {
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
            } else {
                $keyMap = ['nomor_kontrak' => 'I', 'pembeli' => 'J'];
                $key = $keyMap[$sort] ?? 'I';
                $valA = strtolower($a[$key] ?? '');
                $valB = strtolower($b[$key] ?? '');
                if ($valA == $valB) return 0;
                $cmp = strcmp($valA, $valB);
                return ($direction === 'asc') ? $cmp : -$cmp;
            }
        });

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
     * Fitur CRUD: Simpan Data Baru
     * SOLUSI: Mengisi HANYA kolom input (H-X, BC) pada baris yang kosong.
     * Tidak menyentuh kolom rumus (A-G, Y-BB) agar rumus di sheet tidak rusak.
     */
    public function store(Request $request, GoogleSheetService $sheetService)
    {
        $manualInputs = $request->except(['_token']);
        if (empty(array_filter($manualInputs))) {
            return back()->with('error', 'Minimal harus mengisi satu data.');
        }

        try {
            // 1. CARI BARIS KOSONG (Looping Kolom I)
            // Ambil hanya kolom I untuk efisiensi dan mencari slot kosong
            $colData = $sheetService->getData(null, 'SC Sudah Bayar', 'I:I');
            $startRow = 5; // Data dimulai dari baris 5
            $targetRow = null;

            // Loop array. $index 0 = Baris 1
            foreach ($colData as $index => $row) {
                // Skip header (Baris 1-4)
                if ($index < 4) continue;

                $val = $row[0] ?? '';
                // Jika kolom I kosong, ini target kita (mengisi slot kosong di tengah)
                if (trim($val) === '') {
                    $targetRow = $index + 1; // Konversi Index ke Row Number
                    break;
                }
            }

            // Jika tidak ada baris bolong, target adalah baris setelah data terakhir
            if ($targetRow === null) {
                $targetRow = count($colData) + 1;
                if ($targetRow < 5) $targetRow = 5;
            }

            \Log::info("Menyimpan data (Tanpa Rumus) di baris: " . $targetRow);

            // 2. SIAPKAN DATA INPUT (MAPPING KOLOM KE NILAI)
            // Kita gunakan batchUpdate untuk mengisi sel spesifik satu per satu.
            // Ini mencegah kita menimpa kolom A-G yang berisi rumus.
            
            $updates = [];
            $sheetName = 'SC Sudah Bayar';

            // Mapping Kolom Input (H sampai X)
            $map = [
                'H' => $request->loex ?? "",
                'I' => $request->nomor_kontrak ?? "",
                'J' => $request->nama_pembeli ?? "",
                'K' => $request->tgl_kontrak ?? "",
                'L' => $request->volume ?? "",
                'M' => $request->harga ?? "",
                'N' => $request->nilai ?? "",
                'O' => $request->inc_ppn ?? "",
                'P' => $request->tgl_bayar ?? "",
                'Q' => $request->unit ?? "",
                'R' => $request->mutu ?? "",
                'S' => $request->nomor_dosi ?? "",
                'T' => $request->tgl_dosi ?? "",
                'U' => $request->port ?? "",
                'V' => $request->kontrak_sap ?? "",
                'W' => $request->dp_sap ?? "",
                'X' => $request->so_sap ?? "",
            ];

            // Masukkan data H-X ke array updates
            foreach ($map as $col => $val) {
                $updates["'{$sheetName}'!{$col}{$targetRow}"] = $val;
            }

            // Tambahan: Kolom BC (Jatuh Tempo)
            if ($request->has('jatuh_tempo')) {
                $updates["'{$sheetName}'!BC{$targetRow}"] = $request->jatuh_tempo;
            }

            // 3. EKSEKUSI PENYIMPANAN
            // batchUpdate akan mengisi H107, I107, J107... dst tanpa merusak A107, B107 (rumus)
            $sheetService->batchUpdate($updates);

            return back()->with('success', 'Data Berhasil Ditambahkan di Baris ' . $targetRow);

        } catch (\Exception $e) {
            \Log::error("Error store data: " . $e->getMessage());
            return back()->with('error', 'Gagal menambah data: ' . $e->getMessage());
        }
    }

    /**
     * Fitur CRUD: Update Data
     */
    public function update(Request $request, GoogleSheetService $sheetService)
    {
        try {
            $row = $request->input('row_index');
            if (!$row) return back()->with('error', 'Row index tidak ditemukan');

            $inputKeys = [
                'loex', 'nomor_kontrak', 'nama_pembeli', 'tgl_kontrak',
                'volume', 'harga', 'nilai', 'inc_ppn', 'tgl_bayar',
                'unit', 'mutu', 'nomor_dosi', 'tgl_dosi', 'port',
                'kontrak_sap', 'dp_sap', 'so_sap', 'jatuh_tempo'
            ];
            
            $manualInputs = $request->only($inputKeys);
            if (empty(array_filter($manualInputs))) return back()->with('error', 'Minimal harus mengisi satu data.');

            $updates = [];
            $sheetName = 'SC Sudah Bayar';

            $columnMap = [
                'loex'          => 'H', 'nomor_kontrak' => 'I', 'nama_pembeli'  => 'J',
                'tgl_kontrak'   => 'K', 'volume'        => 'L', 'harga'         => 'M',
                'nilai'         => 'N', 'inc_ppn'       => 'O', 'tgl_bayar'     => 'P',
                'unit'          => 'Q', 'mutu'          => 'R', 'nomor_dosi'    => 'S',
                'tgl_dosi'      => 'T', 'port'          => 'U', 'kontrak_sap'   => 'V',
                'dp_sap'        => 'W', 'so_sap'        => 'X', 'jatuh_tempo'   => 'BC',
            ];

            foreach ($columnMap as $input => $col) {
                if ($request->has($input)) {
                    $updates["'{$sheetName}'!{$col}{$row}"] = $request->input($input, '');
                }
            }

            if (empty($updates)) return back()->with('error', 'Tidak ada perubahan data');

            $sheetService->batchUpdate($updates);

            return back()->with('success', 'Data Berhasil Diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Update gagal: ' . $e->getMessage());
        }
    }

    public function destroy($row, GoogleSheetService $sheetService)
    {
        try {
            $sheetService->deleteData($row);
            return back()->with('success', 'Data Berhasil Dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Hapus gagal: ' . $e->getMessage());
        }
    }

    public function syncManual(Request $request)
    {
        try {
            \Log::info('Starting manual sync');
            $exitCode = Artisan::call('sync:drive-folder');
            if ($exitCode === 0) return back()->with('success', 'Sinkronisasi berhasil');
            return back()->with('error', 'Sinkronisasi peringatan');
        } catch (\Exception $e) {
            return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
    }

    public function exportDetailKontrak(Request $request, GoogleSheetService $sheetService)
    {
        $request->validate([
            'format'     => 'required|in:excel,csv',
            'start_date' => 'required',
            'end_date'   => 'required',
        ]);

        $start = Carbon::createFromFormat('m/d/Y', $request->start_date)->startOfDay();
        $end   = Carbon::createFromFormat('m/d/Y', $request->end_date)->endOfDay();

        $rows = $sheetService->getData(null, 'SC Sudah Bayar', 'A4:BC5359'); 
        $exportData = [];

        foreach ($rows as $row) {
            if (empty($row[10])) continue;
            try { $tgl = Carbon::parse($row[10]); } catch (\Exception $e) { continue; }
            if (!$tgl->between($start, $end)) continue;

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
                'Jatuh Tempo'     => $row[54] ?? '', 
            ];
        }

        if (empty($exportData)) return back()->with('error', 'Tidak ada data');

        if ($request->format === 'csv') {
            return response()->streamDownload(function () use ($exportData) {
                $f = fopen('php://output', 'w');
                fputcsv($f, array_keys($exportData[0]));
                foreach ($exportData as $r) fputcsv($f, $r);
                fclose($f);
            }, 'Detail_Kontrak_' . now()->format('Ymd_His') . '.csv');
        }

        return Excel::download(new \App\Exports\ArrayExport($exportData), 'Detail_Kontrak_' . now()->format('Ymd_His') . '.xlsx');
    }
}