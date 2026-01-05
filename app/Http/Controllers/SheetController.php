<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class SheetController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

    public function index(Request $request)
    {
        $spreadsheetId = '19CsFz8MQ9XvPX9VYqKtRfy1ZT04gNJgyvS3oQC4ijaw';
        $range = "'SC Sudah Bayar'!H1:BA";
        $search = $request->input('search');
        
        // Ambil input per_page dari user, default ke 10
        $perPage = $request->input('per_page', 10);

        $filteredData = [];

        try {
            $rawData = $this->googleSheetService->readSheet($spreadsheetId, $range);
            if ($rawData) {
                foreach ($rawData as $index => $row) {
                    if ($index < 3) continue; 
                    if (empty($row[1])) continue; 

                    $rowDataMapped = [
                        'no_kontrak'   => $row[1] ?? '-',
                        'pembeli'      => $row[2] ?? '-',
                        'tgl_kontrak'  => $row[3] ?? '-',
                        'volume'       => $row[4] ?? 0,
                        'harga'        => $row[5] ?? 0,
                        'unit'         => $row[9] ?? '-',
                        'mutu'         => $row[10] ?? '-',
                        'total_layan'  => $row[19] ?? 0,
                        'sisa_akhir'   => $row[20] ?? 0,
                        'jatuh_tempo'  => $row[45] ?? '-',
                    ];

                    if ($search) {
                        if (!str_contains(strtolower($rowDataMapped['no_kontrak']), strtolower($search)) && 
                            !str_contains(strtolower($rowDataMapped['pembeli']), strtolower($search))) {
                            continue;
                        }
                    }
                    $filteredData[] = $rowDataMapped;
                }
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }

        // Pagination Manual
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $itemCollection = collect($filteredData);
        
        // Gunakan variabel $perPage yang dinamis
        $currentPageItems = $itemCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

        $data = new LengthAwarePaginator($currentPageItems, $itemCollection->count(), $perPage);
        
        // Penting: appends agar filter search dan per_page tidak hilang saat pindah halaman
        $data->setPath($request->url());
        $data->appends($request->all()); 

        return view('dashboard.kontrak.index', compact('data'));
    }
}