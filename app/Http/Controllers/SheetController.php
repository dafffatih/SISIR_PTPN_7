<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;

class SheetController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }
    private function sanitizeInput($value)
    {
        if (is_string($value)) {
            if (preg_match('/^[\=\+\-\@]/', $value)) {
                return "'" . $value;
            }
        }
        return $value;
    }

    // ========================================================================
    // 1. INDEX (MENAMPILKAN DATA)
    // ========================================================================
    public function index(Request $request, GoogleSheetService $sheetService)
    {
        $search     = $request->input('search');
        $perPage    = (int) $request->input('per_page', 10);
        $sort       = $request->input('sort', 'nomor_dosi');
        $direction  = $request->input('direction', 'desc');
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');

        // Ambil Data Mentah dari Google Sheet
        $allData = $sheetService->getData();
        $filteredData = [];

        // Helper untuk parsing tanggal format Indonesia (Jan, Mei, Agt, dll)
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

        // Looping Data
        foreach ($allData as $index => $row) {
            $realRowIndex = $index + 4; // Data dimulai dari baris ke-4 (karena header)
            
            // Kolom I (Nomor Kontrak) adalah kunci utama
            $nomorKontrak = $row[8] ?? ''; 

            if (empty($nomorKontrak)) continue;

            $rawTgl = $row[10] ?? '';
            $tglKontrakObj = $parseDate($rawTgl);

            // Filter Berdasarkan Tanggal
            if ($startDate || $endDate) {
                if (!$tglKontrakObj) continue;
                if ($startDate && $tglKontrakObj->lt(Carbon::parse($startDate)->startOfDay())) continue;
                if ($endDate && $tglKontrakObj->gt(Carbon::parse($endDate)->endOfDay())) continue;
            }

            // Mapping Data Sheet ke Array PHP
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
                'S'   => $row[18] ?? '', // DO/SI
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

            // Filter Search (Pencarian Teks)
            if ($search) {
                $searchLower = strtolower($search);
                if (
                    !str_contains(strtolower($item['I']), $searchLower) &&
                    !str_contains(strtolower($item['J']), $searchLower) &&
                    !str_contains(strtolower($item['S']), $searchLower) &&
                    !str_contains(strtolower($item['V']), $searchLower)
                ) {
                    continue;
                }
            }

            $filteredData[] = $item;
        }

        // Logic Sorting (Pengurutan)
        usort($filteredData, function ($a, $b) use ($sort, $direction) {
            if ($sort === 'tgl_kontrak' || $sort === 'K_date') {
                $valA = $a['K_date']; $valB = $b['K_date'];
                $nullA = is_null($valA); $nullB = is_null($valB);
                if ($nullA && $nullB) return 0;
                if ($nullA) return ($direction === 'asc') ? -1 : 1;
                if ($nullB) return ($direction === 'asc') ? 1 : -1;
                if ($valA->eq($valB)) return 0;
                $comparison = $valA->lt($valB) ? -1 : 1;
                return ($direction === 'asc') ? $comparison : -$comparison;
            } elseif ($sort === 'nomor_dosi') {
                 // Logic parsing DO/SI (misal: 10/DO/2026)
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

        // Pagination Manual
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($filteredData);
        $currentPageItems = $itemCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $data = new LengthAwarePaginator($currentPageItems, $itemCollection->count(), $perPage, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        return view('dashboard.kontrak.index', compact('data'));
    }

    // ========================================================================
    // 2. STORE (SIMPAN DATA BARU) - AMAN DARI SERANGAN
    // ========================================================================
    public function store(Request $request, GoogleSheetService $sheetService)
    {
        // 1. VALIDASI: Pastikan data wajib terisi
        $request->validate([
            'nomor_kontrak' => 'required|string|max:100',
            'nama_pembeli'  => 'required|string|max:255',
            'tgl_kontrak'   => 'required', 
        ]);

        try {
            // Cari baris kosong di kolom I (mulai baris 5)
            $colData = $sheetService->getData(null, 'SC Sudah Bayar', 'I:I');
            $startRow = 5; 
            $targetRow = null;

            foreach ($colData as $index => $row) {
                if ($index < 4) continue; // Skip header
                $val = $row[0] ?? '';
                if (trim($val) === '') {
                    $targetRow = $index + 1;
                    break;
                }
            }

            if ($targetRow === null) {
                $targetRow = count($colData) + 1;
                if ($targetRow < 5) $targetRow = 5;
            }

            Log::info("Menyimpan data di baris: " . $targetRow);

            // 2. MAPPING & SANITASI INPUT
            // Kita petakan input dari form ke kolom Excel secara manual
            $sheetName = 'SC Sudah Bayar';
            $updates = [];

            // Daftar input sesuai name="" di modal-tambah.blade.php
            $map = [
                'H' => $request->loex,
                'I' => $request->nomor_kontrak,
                'J' => $request->nama_pembeli,
                'K' => $request->tgl_kontrak,
                'L' => $request->volume,
                'M' => $request->harga,
                'N' => $request->nilai,
                'O' => $request->inc_ppn,
                'P' => $request->tgl_bayar,
                'Q' => $request->unit,
                'R' => $request->mutu,
                'S' => $request->nomor_dosi,
                'T' => $request->tgl_dosi,
                'U' => $request->port,
                'V' => $request->kontrak_sap,
                'W' => $request->dp_sap,
                'X' => $request->so_sap,
                'BC'=> $request->jatuh_tempo,
            ];

            foreach ($map as $col => $val) {
                // Terapkan fungsi sanitizeInput() agar rumus tidak rusak
                $cleanVal = $this->sanitizeInput($val ?? ""); 
                $updates["'{$sheetName}'!{$col}{$targetRow}"] = $cleanVal;
            }

            // Kirim ke Google Sheet
            $sheetService->batchUpdate($updates);

            return back()->with('success', 'Data Berhasil Ditambahkan di Baris ' . $targetRow);

        } catch (\Exception $e) {
            Log::error("Error store data: " . $e->getMessage());
            return back()->with('error', 'Gagal menambah data: ' . $e->getMessage());
        }
    }

    // ========================================================================
    // 3. UPDATE (EDIT DATA) - AMAN DARI SERANGAN
    // ========================================================================
    public function update(Request $request, GoogleSheetService $sheetService)
    {
        try {
            $row = $request->input('row_index');
            if (!$row) return back()->with('error', 'Row index tidak ditemukan');

            // 1. Validasi
            $request->validate([
                'nomor_kontrak' => 'required|string|max:100',
            ]);

            $sheetName = 'SC Sudah Bayar';
            $updates = [];

            // 2. Mapping Input ke Kolom Excel
            // Key sebelah kiri = name="" di modal-edit.blade.php
            // Value sebelah kanan = Kolom Excel
            $columnMap = [
                'loex'          => 'H', 'nomor_kontrak' => 'I', 'nama_pembeli'  => 'J',
                'tgl_kontrak'   => 'K', 'volume'        => 'L', 'harga'         => 'M',
                'nilai'         => 'N', 'inc_ppn'       => 'O', 'tgl_bayar'     => 'P',
                'unit'          => 'Q', 'mutu'          => 'R', 'nomor_dosi'    => 'S',
                'tgl_dosi'      => 'T', 'port'          => 'U', 'kontrak_sap'   => 'V',
                'dp_sap'        => 'W', 'so_sap'        => 'X', 'jatuh_tempo'   => 'BC',
            ];

            foreach ($columnMap as $inputName => $colExcel) {
                if ($request->has($inputName)) {
                    $rawValue = $request->input($inputName, '');
                    // Terapkan sanitasi
                    $updates["'{$sheetName}'!{$colExcel}{$row}"] = $this->sanitizeInput($rawValue);
                }
            }

            if (empty($updates)) return back()->with('error', 'Tidak ada perubahan data');

            $sheetService->batchUpdate($updates);

            return back()->with('success', 'Data Berhasil Diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Update gagal: ' . $e->getMessage());
        }
    }

    // ========================================================================
    // 4. DESTROY (HAPUS DATA)
    // ========================================================================
    public function destroy($row, GoogleSheetService $sheetService)
    {
        try {
            $sheetService->deleteData($row);
            return back()->with('success', 'Data Berhasil Dihapus');
        } catch (\Exception $e) {
            return back()->with('error', 'Hapus gagal: ' . $e->getMessage());
        }
    }

    // ========================================================================
    // 5. SYNC MANUAL
    // ========================================================================
    public function syncManual(Request $request)
    {
        try {
            Log::info('Starting manual sync');
            $exitCode = Artisan::call('sync:drive-folder');
            if ($exitCode === 0) return back()->with('success', 'Sinkronisasi berhasil');
            return back()->with('error', 'Sinkronisasi peringatan');
        } catch (\Exception $e) {
            return back()->with('error', 'Sinkronisasi gagal: ' . $e->getMessage());
        }
    }

    // ========================================================================
    // 6. EXPORT DATA
    // ========================================================================
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

        if (empty($exportData)) return back()->with('error', 'Tidak ada data pada periode ini');

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