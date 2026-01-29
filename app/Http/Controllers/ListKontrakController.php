<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache; // [BARU] Import Cache

class ListKontrakController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

    // --- [BARU] HELPER CACHE ---
    // Fungsi untuk mendapatkan nama key cache yang unik per tahun sheet
    private function getCacheKey()
    {
        return 'list_kontrak_data_' . Str::slug($this->getSheetName());
    }

    // Fungsi untuk menghapus cache agar data realtime
    private function clearRelatedCaches()
    {
        // 1. Hapus cache tabel list kontrak ini
        Cache::forget($this->getCacheKey());
        
        // 2. Hapus cache dashboard (PENTING)
        // Karena Dashboard mungkin mengambil angka summary dari data ini
        $year = session('selected_year') ?? date('Y');
        Cache::forget('dashboard_raw_data_' . $year);
        Cache::forget('dashboard_raw_data_default');
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

    private function getSheetName()
    {
        $year = session('year') ?? session('current_year') ?? session('selected_year') ?? date('Y');
        if ($year === 'default') $year = date('Y');
        return 'List Kontrak ' . $year;
    }

    // ==========================================================
    // 1. INDEX (READ DENGAN CACHE)
    // ==========================================================
    public function index(Request $request)
    {
        $sheetName = $this->getSheetName();
        $cacheKey = $this->getCacheKey();

        // [BARU] FITUR PAKSA REFRESH
        if ($request->has('refresh')) {
            $this->clearRelatedCaches();
            return redirect()->route('list-kontrak.index')->with('success', 'Data berhasil di-refresh dari Server Google.');
        }
        
        // [BARU] LOGIKA CACHING (30 MENIT)
        // Data disimpan di RAM/Database Cache agar loading super cepat
        $allData = Cache::remember($cacheKey, 1800, function () use ($sheetName) {
            try {
                return $this->googleSheetService->getData(null, $sheetName, 'A:AZ');
            } catch (\Exception $e) {
                return [];
            }
        });

        // --- FILTERING (Dilakukan di Server PHP) ---
        $filteredData = [];
        
        $search = strtolower($request->input('search'));
        $perPage = (int) $request->input('per_page', 10);
        $sort = $request->input('sort', 'tgl_kontrak'); 
        $direction = $request->input('direction', 'desc');
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $startFilter = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $endFilter   = $endDate   ? Carbon::parse($endDate)->endOfDay() : null;

        // Helper Parsing Tanggal
        $parseDate = function($val) {
            if(empty($val)) return null;
            $val = trim($val);
            $map = ['Jan'=>'Jan','Feb'=>'Feb','Mar'=>'Mar','Apr'=>'Apr','Mei'=>'May','Jun'=>'Jun','Jul'=>'Jul','Agt'=>'Aug','Agu'=>'Aug','Sep'=>'Sep','Okt'=>'Oct','Nov'=>'Nov','Des'=>'Dec'];
            $valEnglish = str_ireplace(array_keys($map), array_values($map), $val);
            $formats = ['d-M-Y', 'd M Y', 'Y-m-d', 'd/m/Y'];
            foreach ($formats as $fmt) {
                try { return Carbon::createFromFormat($fmt, $valEnglish)->startOfDay(); } catch (\Exception $e) { continue; }
            }
            try { return Carbon::parse($valEnglish)->startOfDay(); } catch (\Exception $e) { return null; }
        };

        foreach ($allData as $index => $row) {
            if ($index < 4) continue; 
            if (empty($row[23]) && empty($row[6])) continue;

            $tglKontrakRaw = $row[5] ?? '';
            $tglKontrakObj = $parseDate($tglKontrakRaw);
            $jatuhTempoRaw = $row[31] ?? '';
            $jatuhTempoObj = $parseDate($jatuhTempoRaw);

            if ($startFilter && $tglKontrakObj && $tglKontrakObj->lt($startFilter)) continue;
            if ($endFilter && $tglKontrakObj && $tglKontrakObj->gt($endFilter)) continue;

            $item = [
                'row'           => $index + 1,
                'id'            => $index + 1,
                'no'            => $row[1] ?? '',  
                'tgl_kontrak'   => $tglKontrakObj ? $tglKontrakObj->format('d M Y') : $tglKontrakRaw,
                'tgl_input'     => $tglKontrakObj ? $tglKontrakObj->format('Y-m-d') : '',
                'tgl_obj'       => $tglKontrakObj,
                'pembeli'       => $row[6] ?? '',  
                'kategori'      => $row[7] ?? '',  
                'mutu'          => $row[8] ?? '',  
                'bln_kontrak'   => $row[9] ?? '',  
                'bln_shipment'  => $row[10] ?? '', 
                'kuantum'       => $row[11] ?? 0,  
                'simbol'        => $row[12] ?? '', 
                'penetapan'     => $row[17] ?? '', 
                'harga_usd'     => $row[18] ?? 0,  
                'nilai_usd'     => $row[19] ?? 0,  
                'kurs'          => $row[20] ?? 0,  
                'harga_rp'      => $row[21] ?? 0,  
                'nilai_rp'      => $row[22] ?? 0,  
                'no_kontrak'    => $row[23] ?? '', 
                'no_sap'        => $row[24] ?? '', 
                'lokal_ekspor'  => $row[30] ?? '', 
                'jatuh_tempo'   => $jatuhTempoObj ? $jatuhTempoObj->format('d M Y') : $jatuhTempoRaw,
                'jatuh_tempo_in'=> $jatuhTempoObj ? $jatuhTempoObj->format('Y-m-d') : '',
                'eudr'          => $row[34] ?? '', 
            ];

            if ($search) {
                if (!str_contains(strtolower($item['no_kontrak']), $search) && 
                    !str_contains(strtolower($item['pembeli']), $search) &&
                    !str_contains(strtolower($item['no_sap']), $search) &&
                    !str_contains(strtolower($item['kategori']), $search)) {
                    continue;
                }
            }
            $filteredData[] = $item;
        }

        usort($filteredData, function($a, $b) use ($sort, $direction) {
            $valA = $a[$sort] ?? null;
            $valB = $b[$sort] ?? null;
            if ($sort === 'tgl_kontrak') {
                $dateA = $a['tgl_obj'] ? $a['tgl_obj']->timestamp : 0;
                $dateB = $b['tgl_obj'] ? $b['tgl_obj']->timestamp : 0;
                if ($dateA == $dateB) return 0;
                return ($direction === 'asc') ? ($dateA <=> $dateB) : ($dateB <=> $dateA);
            } elseif ($sort === 'kuantum') {
                $numA = (float) str_replace(['.', ','], ['', '.'], $valA);
                $numB = (float) str_replace(['.', ','], ['', '.'], $valB);
                if ($numA == $numB) return 0;
                return ($direction === 'asc') ? ($numA <=> $numB) : ($numB <=> $numA);
            } else {
                return ($direction === 'asc') ? strcasecmp((string)$valA, (string)$valB) : strcasecmp((string)$valB, (string)$valA);
            }
        });

        $collection = collect($filteredData);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $data = new LengthAwarePaginator(
            $currentPageItems, $collection->count(), $perPage, $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.list_kontrak.index', compact('data'));
    }

    private function formatDateForSheet($dateString) {
        if (!$dateString) return '';
        try {
            return Carbon::parse($dateString)->format('d M Y');
        } catch (\Exception $e) {
            return $dateString;
        }
    }

    // ==========================================================
    // 2. STORE (TAMBAH DATA) - CACHE DIHAPUS OTOMATIS
    // ==========================================================
    public function store(Request $request)
    {
        $request->validate([
            'no_kontrak' => 'required|string|max:100',
            'pembeli'    => 'required|string|max:255',
            'kuantum'    => 'nullable|numeric', 
            'harga_usd'  => 'nullable|numeric',
            'harga_rp'   => 'nullable|numeric',
        ]);

        $sheetName = $this->getSheetName();

        try {
            $colData = $this->googleSheetService->getData(null, $sheetName, 'X:X');
            
            $targetRow = null;
            foreach ($colData as $index => $row) {
                if ($index < 4) continue;
                if (empty($row[0])) {
                    $targetRow = $index + 1;
                    break;
                }
            }

            if (!$targetRow) {
                $targetRow = count($colData) + 1;
                if ($targetRow < 5) $targetRow = 5;
            }

            $inputs = [
                'F'  => $this->formatDateForSheet($request->tgl_kontrak),
                'G'  => $request->pembeli,
                'H'  => $request->kategori,
                'I'  => $request->mutu,
                'J'  => $request->bln_kontrak,
                'K'  => $request->bln_shipment,
                'L'  => $request->kuantum,
                'M'  => $request->simbol,
                'R'  => $request->penetapan,
                'S'  => $request->harga_usd,
                'T'  => $request->nilai_usd,
                'U'  => $request->kurs,
                'V'  => $request->harga_rp,
                'W'  => $request->nilai_rp,
                'X'  => $request->no_kontrak, 
                'Y'  => $request->no_sap,
                'AE' => $request->lokal_ekspor,
                'AF' => $this->formatDateForSheet($request->jatuh_tempo),
                'AI' => $request->eudr
            ];

            $updates = [];
            foreach ($inputs as $col => $val) {
                if ($val !== null) {
                    $cleanVal = $this->sanitizeInput($val);
                    $updates["'{$sheetName}'!{$col}{$targetRow}"] = $cleanVal;
                }
            }

            $this->googleSheetService->batchUpdate($updates);

            // [BARU] 🔥 HAPUS CACHE AGAR REALTIME UPDATE
            $this->clearRelatedCaches();

            return back()->with('success', "Data berhasil ditambahkan di baris {$targetRow}");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    // ==========================================================
    // 3. UPDATE (EDIT DATA) - CACHE DIHAPUS OTOMATIS
    // ==========================================================
    public function update(Request $request)
    {
        $row = $request->input('row_index');
        if (!$row) return back()->with('error', 'Row index hilang.');
        $request->validate(['no_kontrak' => 'required|string']);

        $sheetName = $this->getSheetName();

        try {
            $map = [
                'tgl_kontrak' => 'F', 'pembeli' => 'G', 'kategori' => 'H', 'mutu' => 'I',
                'bln_kontrak' => 'J', 'bln_shipment' => 'K', 'kuantum' => 'L', 'simbol' => 'M',
                'penetapan' => 'R', 'harga_usd' => 'S', 'nilai_usd' => 'T', 'kurs' => 'U',
                'harga_rp' => 'V', 'nilai_rp' => 'W', 'no_kontrak' => 'X', 'no_sap' => 'Y',
                'lokal_ekspor' => 'AE', 'jatuh_tempo' => 'AF', 'eudr' => 'AI'
            ];

            $updates = [];
            foreach ($map as $reqKey => $col) {
                if ($request->has($reqKey)) {
                    $val = $request->input($reqKey);
                    if (($reqKey == 'tgl_kontrak' || $reqKey == 'jatuh_tempo') && $val) {
                        $val = $this->formatDateForSheet($val);
                    }
                    $cleanVal = $this->sanitizeInput($val ?? '');
                    $updates["'{$sheetName}'!{$col}{$row}"] = $cleanVal;
                }
            }

            $this->googleSheetService->batchUpdate($updates);

            // [BARU] 🔥 HAPUS CACHE AGAR REALTIME UPDATE
            $this->clearRelatedCaches();

            return back()->with('success', 'Data berhasil diperbarui.');

        } catch (\Exception $e) {
            return back()->with('error', 'Update gagal: ' . $e->getMessage());
        }
    }

    // ==========================================================
    // 4. DESTROY (HAPUS DATA) - CACHE DIHAPUS OTOMATIS
    // ==========================================================
    public function destroy($row)
    {
        $sheetName = $this->getSheetName();
        try {
            $this->googleSheetService->deleteData($row, $sheetName);

            // [BARU] 🔥 HAPUS CACHE AGAR REALTIME UPDATE
            $this->clearRelatedCaches();

            return back()->with('success', 'Data berhasil dihapus.');
        } catch (\Exception $e) {
            return back()->with('error', 'Hapus gagal: ' . $e->getMessage());
        }
    }
}