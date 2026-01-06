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
            $realRowIndex = $index + 4; // Karena mulai dari A2

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
        // Ambil nomor baris dari form hidden input
        $row = $request->row_index; 
        $manualInputs = $request->only([
            'loex', 'nomor_kontrak', 'nama_pembeli', 'tgl_kontrak', 
            'volume', 'harga', 'nilai', 'inc_ppn', 'tgl_bayar', 
            'unit', 'mutu', 'nomor_dosi', 'tgl_dosi', 'port', 
            'kontrak_sap', 'dp_sap', 'so_sap', 'jatuh_tempo'
        ]);

        if (empty(array_filter($manualInputs))) {
            return back()->with('error', 'Minimal harus mengisi satu data sebelum menyimpan.');
        }

        // Susun array 53 kolom (A sampai BA)
        $data = [
            "=CONCATENATE(I{$row};Q{$row};R{$row})", // A
            "=CONCATENATE(I{$row};Q{$row})",        // B
            "=CONCATENATE(D{$row};F{$row};H{$row})", // C
            "=IFERROR(E{$row}*1;0)",                // D
            "=IF(LEN(S{$row})=13;LEFT(S{$row};3);LEFT(S{$row};3))", // E
            "=RIGHT(S{$row};4)",                    // F
            "=G".($row-1)."+1",                     // G (Baris sebelumnya + 1)
            $request->loex ?? "",                   // H (Manual)
            $request->nomor_kontrak ?? "",          // I (Manual)
            $request->nama_pembeli ?? "",           // J (Manual)
            $request->tgl_kontrak ?? "",            // K (Manual)
            $request->volume ?? "",                 // L (Manual)
            $request->harga ?? "",                  // M (Manual)
            $request->nilai ?? "",                  // N (Manual)
            $request->inc_ppn ?? "",                // O (Manual)
            $request->tgl_bayar ?? "",              // P (Manual)
            $request->unit ?? "",                   // Q (Manual)
            $request->mutu ?? "",                   // R (Manual)
            $request->nomor_dosi ?? "",             // S (Manual)
            $request->tgl_dosi ?? "",               // T (Manual)
            $request->port ?? "",                   // U (Manual)
            $request->kontrak_sap ?? "",            // V (Manual)
            $request->dp_sap ?? "",                 // W (Manual)
            $request->so_sap ?? "",                 // X (Manual)
            "=C{$row}",                             // Y
            "=L{$row}",                             // Z
            "=(SUMPRODUCT((Panjang!\$P\$2:\$P\$5011='SC Sudah Bayar'!Y{$row})*Panjang!\$Q\$2:\$Q\$5011))+(SUMPRODUCT((Palembang!\$P\$2:\$P\$5003='SC Sudah Bayar'!Y{$row})*Palembang!\$Q\$2:\$Q\$5003))+(SUMPRODUCT((Bengkulu!\$P\$2:\$P\$5000='SC Sudah Bayar'!Y{$row})*Bengkulu!\$Q\$2:\$Q\$5000))",
            "=Z{$row}-AA{$row}",                    // AB
            "=M{$row}*1000",                        // AC
            "=VLOOKUP(J{$row};Katalog!\$D\$4:\$E\$101;2;FALSE)", // AD
            "=IF(H{$row}=\"LO\";\"LOKAL\";\"EKSPOR\")", // AE
            "=CONCATENATE(AE{$row};Q{$row})",       // AF
            "",                                     // AG (KOSONG)
            "=LEFT(I{$row};LEN(I{$row})-2)",        // AH
            "=J{$row}",                             // AI
            "",                                     // AJ (KOSONG)
            "",                                     // AK (KOSONG)
            "",                                     // AL (KOSONG)
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AM
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AN\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AN
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AO
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AP\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AP
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AQ
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AR\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AR
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AS
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AT\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AT
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AB$2:\$AB$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AB$2:\$AB$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AB$2:\$AB$7775))", // AU
            "=(SUMPRODUCT((Panjang!\$R$2:\$R$6779=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Panjang!\$AC$2:\$AC$6779))+(SUMPRODUCT((Palembang!\$R$2:\$R$7774=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Palembang!\$AC$2:\$AC$7774))+(SUMPRODUCT((Bengkulu!\$R$2:\$R$7775=CONCATENATE(\$I{$row};\$S{$row};AV\$2))*Bengkulu!\$AC$2:\$AC$7775))", // AV
            "=AV{$row}+AT{$row}+AR{$row}+AP{$row}+AN{$row}", // AW
            "=IF(Z{$row}>1;L{$row};0)",               // AX
            "=IF(AX{$row}>1;AX{$row}-AW{$row};0)",      // AY
            "=AW{$row}-AA{$row}",                       // AZ
            $request->jatuh_tempo ?? "",                // BA (Manual)
        ];

        try {
            $sheetService->updateData($row, $data);
            return back()->with('success', 'Data Berhasil Diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}