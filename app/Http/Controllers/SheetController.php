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

    public function index(Request $request, GoogleSheetService $sheetService)
    {
        $allData = $sheetService->getData();
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);

        $filteredData = [];

        foreach ($allData as $index => $row) {
            $realRowIndex = $index + 2; // Karena mulai dari A2

            // Mapping Kolom A sampai BA ke variabel huruf
            // Index array dimulai dari 0 (A=0, B=1, dst)
            $A = $row[0] ?? '';   $B = $row[1] ?? '';   $C = $row[2] ?? '';
            $D = $row[3] ?? '';   $E = $row[4] ?? '';   $F = $row[5] ?? '';
            $G = $row[6] ?? '';   $H = $row[7] ?? '';   $I = $row[8] ?? ''; // Nomor Kontrak
            $J = $row[9] ?? '';   $K = $row[10] ?? '';  $L = $row[11] ?? ''; // Volume
            $M = $row[12] ?? '';  $N = $row[13] ?? '';  $O = $row[14] ?? '';
            $P = $row[15] ?? '';  $Q = $row[16] ?? '';  $R = $row[17] ?? '';
            $S = $row[18] ?? '';  $T = $row[19] ?? '';  $U = $row[20] ?? '';
            $V = $row[21] ?? '';  $W = $row[22] ?? '';  $X = $row[23] ?? '';
            $Y = $row[24] ?? '';  $Z = $row[25] ?? '';  $AA = $row[26] ?? ''; // Total Dilayani
            $AB = $row[27] ?? ''; // Sisa Akhir
            
            // BA adalah index ke-52
            $BA = $row[52] ?? ''; // Jatuh Tempo

            // Skip jika Nomor Kontrak (I) kosong
            if (empty($I)) continue;

            $rowDataMapped = [
                'row' => $realRowIndex,
                'A' => $A, 'B' => $B, 'C' => $C, 'D' => $D, 'E' => $E, 'F' => $F,
                'G' => $G, 'H' => $H, 'I' => $I, 'J' => $J, 'K' => $K, 'L' => $L,
                'M' => $M, 'N' => $N, 'O' => $O, 'P' => $P, 'Q' => $Q, 'R' => $R,
                'S' => $S, 'T' => $T, 'U' => $U, 'V' => $V, 'W' => $W, 'X' => $X,
                'Y' => $Y, 'Z' => $Z, 'AA' => $AA, 'AB' => $AB, 'BA' => $BA,
                // Properti alias untuk memudahkan di Blade
                'no_kontrak'  => $I,
                'pembeli'     => $J,
                'tgl_kontrak' => $K,
                'volume'      => $L,
                'harga'       => $M,
                'total_layan' => $AA,
                'sisa_akhir'  => $AB,
                'jatuh_tempo' => $BA,
                'unit'        => $Q,
                'mutu'        => $R,
            ];

            if ($search) {
                if (!str_contains(strtolower($I), strtolower($search)) && 
                    !str_contains(strtolower($J), strtolower($search))) {
                    continue;
                }
            }

            $filteredData[] = $rowDataMapped;
        }

        $currentPage = (int) $request->get('page', 1);
        $itemCollection = collect($filteredData);
        $currentPageItems = $itemCollection->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $data = new LengthAwarePaginator($currentPageItems, $itemCollection->count(), $perPage);
        $data->setPath($request->url());
        $data->appends($request->all());

        return view('dashboard.kontrak.index', compact('data'));
    }

    // Menambah Data Baru (A-BA)
    public function store(Request $request, GoogleSheetService $sheetService)
    {
        // Susun array 53 kolom (Index 0-52) sesuai urutan A-BA
        $data = [
            $request->A ?? "", $request->B ?? "", $request->C ?? "", 
            $request->D ?? "", $request->E ?? "", $request->F ?? "", 
            $request->G ?? "", 
            $request->loex ?? "",           // H (Index 7)
            $request->nomor_kontrak ?? "",  // I (Index 8)
            $request->nama_pembeli ?? "",   // J
            $request->tgl_kontrak ?? "",    // K
            $request->volume ?? "",         // L
            $request->harga ?? "",          // M
            $request->nilai ?? "",          // N
            "",                             // O
            "",                             // P
            $request->unit ?? "",           // Q
            $request->mutu ?? "",           // R
            $request->nomor_dosi ?? "",     // S
            "",                             // T
            $request->port ?? "",           // U
            "",                             // V
            "",                             // W
            "",                             // X
            "",                             // Y
            $request->sisa_awal ?? "",      // Z
            $request->total_dilayani ?? "", // AA
            $request->sisa_akhir ?? "",     // AB
            // ... teruskan sampai index 52 (BA)
        ];

        try {
            $sheetService->storeData($data);
            return back()->with('success', 'Kontrak berhasil ditambahkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menambah data: ' . $e->getMessage());
        }
    }

    // Memperbarui Data Berdasarkan Nomor Baris
    public function update(Request $request, GoogleSheetService $sheetService)
    {
        $row = $request->row_index; // Diambil dari hidden input modal edit

        $data = [
            $request->A ?? "", $request->B ?? "", // ... petakan semua kolom A-BA
            $request->loex, $request->nomor_kontrak, $request->nama_pembeli,
            // ... (urutan harus sama persis dengan fungsi store)
        ];

        try {
            $sheetService->updateData($row, $data);
            return back()->with('success', 'Kontrak berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }
}