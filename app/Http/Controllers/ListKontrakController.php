<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ListKontrakController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

    public function index(Request $request)
    {
        $yearToUse = session('year') ?? session('current_year') ?? session('selected_year') ?? date('Y');
        if ($yearToUse === 'default') {
            $yearToUse = date('Y');
        }
        $sheetName = 'List Kontrak ' . $yearToUse; 
        $allData = $this->googleSheetService->getData(null, $sheetName);

        $filteredData = [];
        
        // Ambil Parameter Filter
        $search = strtolower($request->input('search'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Default sort 'tgl_kontrak' agar data terbaru muncul duluan jika tidak ada pilihan
        $sort = $request->input('sort', 'tgl_kontrak'); 
        $direction = $request->input('direction', 'desc');

        // --- HELPER PARSING TANGGAL ---
        $parseDate = function($val) {
            if(empty($val)) return null;
            $val = trim($val);
            
            // Mapping bulan Indo -> Inggris (Jaga-jaga jika format Excel berubah ke Indo)
            $map = [
                'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'Mei' => 'May', 'Jun' => 'Jun',
                'Jul' => 'Jul', 'Agt' => 'Aug', 'Agu' => 'Aug', 'Sep' => 'Sep', 'Okt' => 'Oct', 'Nov' => 'Nov', 'Des' => 'Dec'
            ];
            $valEnglish = str_ireplace(array_keys($map), array_values($map), $val);

            try {
                // Prioritas 1: Format d-M-Y (Contoh: 18-Jan-2024)
                return Carbon::createFromFormat('d-M-Y', $valEnglish)->startOfDay();
            } catch (\Exception $e) {
                try {
                    // Prioritas 2: Format Universal (Y-m-d atau lainnya)
                    return Carbon::parse($valEnglish)->startOfDay();
                } catch (\Exception $e) { return null; }
            }
        };

        // Siapkan Filter Tanggal (PENTING: Gunakan startOfDay dan endOfDay)
        $startFilter = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $endFilter   = $endDate   ? Carbon::parse($endDate)->endOfDay() : null; // Fix: Sampai detik terakhir hari itu (23:59:59)

        // 2. Mapping Data & Filtering
        foreach ($allData as $index => $row) {
            // Validasi baris kosong (cek No Kontrak & Pembeli)
            if (empty($row[23]) && empty($row[6])) continue;

            $tglKontrakRaw = $row[5] ?? '';
            $tglKontrakObj = $parseDate($tglKontrakRaw);

            // --- LOGIKA FILTER TANGGAL (FIXED) ---
            if ($startFilter && $tglKontrakObj) {
                if ($tglKontrakObj->lt($startFilter)) continue;
            }
            if ($endFilter && $tglKontrakObj) {
                // Gunakan gt (Greater Than) terhadap End Of Day
                if ($tglKontrakObj->gt($endFilter)) continue;
            }

            // Mapping Data
            $item = [
                'no'            => $row[1] ?? '',  
                'tgl_kontrak'   => $tglKontrakRaw, 
                'tgl_obj'       => $tglKontrakObj, // Objek Carbon untuk sorting
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
                'jatuh_tempo'   => $row[31] ?? '', 
                'eudr'          => $row[34] ?? '', 
            ];

            // --- LOGIKA FILTER SEARCH ---
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

        // 3. Sorting Logic (FIXED)
        if ($sort) {
            usort($filteredData, function($a, $b) use ($sort, $direction) {
                $valA = $a[$sort] ?? null;
                $valB = $b[$sort] ?? null;

                // A. Sorting Tanggal
                if ($sort === 'tgl_kontrak') {
                    $dateA = $a['tgl_obj'] ? $a['tgl_obj']->timestamp : 0;
                    $dateB = $b['tgl_obj'] ? $b['tgl_obj']->timestamp : 0;
                    
                    if ($dateA == $dateB) return 0;
                    // Logic: Jika Ascending (A < B), jika Descending (B < A)
                    return ($direction === 'asc') ? ($dateA <=> $dateB) : ($dateB <=> $dateA);
                } 
                
                // B. Sorting Angka (Kuantum)
                elseif ($sort === 'kuantum') {
                    // Bersihkan format ribuan (titik) dan desimal (koma) jadi angka murni
                    // Asumsi format Indo: 1.000,00 -> jadi 1000.00
                    $numA = (float) str_replace(['.', ','], ['', '.'], $valA);
                    $numB = (float) str_replace(['.', ','], ['', '.'], $valB);
                    
                    if ($numA == $numB) return 0;
                    return ($direction === 'asc') ? ($numA <=> $numB) : ($numB <=> $numA);
                }
                
                // C. Sorting String Biasa (No Kontrak, Pembeli, dll)
                else {
                    // strcasecmp: return < 0 if str1 < str2
                    $cmp = strcasecmp((string)$valA, (string)$valB);
                    return ($direction === 'asc') ? $cmp : -$cmp;
                }
            });
        }

        // 4. Pagination
        $collection = collect($filteredData);
        $perPage = $request->input('per_page', 10);
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $data = new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('dashboard.list_kontrak.index', compact('data'));
    }
}