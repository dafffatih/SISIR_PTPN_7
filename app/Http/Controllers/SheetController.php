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

    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $sort = $request->input('sort', 'nomor_dosi');
        $direction = strtolower($request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build query from Kontrak model
        $query = Kontrak::query();

        // Apply search filter across multiple fields
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_kontrak', 'like', "%{$search}%")
                  ->orWhere('nama_pembeli', 'like', "%{$search}%")
                  ->orWhere('nomor_dosi', 'like', "%{$search}%")
                  ->orWhere('kontrak_sap', 'like', "%{$search}%")
                  ->orWhere('so_sap', 'like', "%{$search}%");
            });
        }

        // Apply date range filter
        if ($startDate) {
            try {
                $start = Carbon::createFromFormat('Y-m-d', $startDate)->toDateString();
                $query->whereDate('tgl_kontrak', '>=', $start);
            } catch (\Exception $e) { }
        }
        if ($endDate) {
            try {
                $end = Carbon::createFromFormat('Y-m-d', $endDate)->toDateString();
                $query->whereDate('tgl_kontrak', '<=', $end);
            } catch (\Exception $e) { }
        }

        // Get all matching records (will sort in PHP for DO/SI if needed)
        $allRecords = $query->get();

        // Smart sorting for nomor_dosi - parse DO number and year in PHP
        if ($sort === 'nomor_dosi') {
            $allRecords = $allRecords->sort(function ($a, $b) use ($direction) {
                // Parse DO format: "807/KARET SC/2024" -> extract 807 and 2024
                $parseDoSi = function ($doSi) {
                    if (!$doSi) return [0, 0];
                    $parts = explode('/', $doSi);
                    $number = (int) ($parts[0] ?? 0);
                    $year = (int) ($parts[2] ?? 0);
                    return [$year, $number];
                };

                [$yearA, $numA] = $parseDoSi($a->nomor_dosi);
                [$yearB, $numB] = $parseDoSi($b->nomor_dosi);

                // Sort by year first, then by number
                if ($yearA !== $yearB) {
                    return $direction === 'asc' ? $yearA <=> $yearB : $yearB <=> $yearA;
                }
                return $direction === 'asc' ? $numA <=> $numB : $numB <=> $numA;
            });
        } else {
            // Standard column sorting
            $sortMap = [
                'nomor_kontrak' => 'nomor_kontrak',
                'tgl_kontrak' => 'tgl_kontrak',
                'created_at' => 'created_at',
            ];
            $sortField = $sortMap[$sort] ?? 'nomor_dosi';
            $allRecords = $allRecords->sortBy($sortField, options: 0, descending: $direction === 'desc');
        }

        // Paginate the sorted collection
        $page = request()->get('page', 1);
        $data = new LengthAwarePaginator(
            $allRecords->forPage($page, $perPage),
            count($allRecords),
            $perPage,
            $page,
            [
                'path' => route('kontrak'),
                'query' => $request->except('page'),
            ]
        );

        // Transform data untuk kompatibilitas dengan modal (map Eloquent properties ke struktur lama)
        // Also include raw ISO date fields (e.g., K_raw) for edit inputs to work with <input type="date">.
        $data->getCollection()->transform(function ($item) {
            // safe date formatting: handle DateTime/Carbon or string values
            $formatDate = function ($val) {
                if (!$val) return '';
                if ($val instanceof \DateTime) return $val->format('d-M-Y');
                try {
                    return Carbon::parse($val)->format('d-M-Y');
                } catch (\Exception $e) {
                    return (string) $val;
                }
            };

            // Format numbers with Indonesian thousand separator (dot)
            $formatNumber = function ($val) {
                if (!$val && $val !== 0 && $val !== '0') return '';
                return number_format((float)$val, 0, ',', '.');
            };

            return [
                'id' => $item->id,
                'H' => $item->loex,
                'I' => $item->nomor_kontrak,
                'J' => $item->nama_pembeli,
                'K' => $formatDate($item->tgl_kontrak),
                'K_raw' => $item->tgl_kontrak ? (is_string($item->tgl_kontrak) ? Carbon::parse($item->tgl_kontrak)->format('Y-m-d') : $item->tgl_kontrak->format('Y-m-d')) : '',
                'L' => ($item->volume || $item->volume === 0) ? $formatNumber($item->volume) : '',
                'M' => ($item->harga || $item->harga === 0) ? $formatNumber($item->harga) : '',
                'N' => ($item->nilai || $item->nilai === 0) ? $formatNumber($item->nilai) : '',
                'O' => $item->inc_ppn ?? '',
                'P' => $formatDate($item->tgl_bayar),
                'P_raw' => $item->tgl_bayar ? (is_string($item->tgl_bayar) ? Carbon::parse($item->tgl_bayar)->format('Y-m-d') : $item->tgl_bayar->format('Y-m-d')) : '',
                'Q' => $item->unit ?? '',
                'R' => $item->mutu ?? '',
                'S' => $item->nomor_dosi ?? '',
                'T' => $formatDate($item->tgl_dosi),
                'T_raw' => $item->tgl_dosi ? (is_string($item->tgl_dosi) ? Carbon::parse($item->tgl_dosi)->format('Y-m-d') : $item->tgl_dosi->format('Y-m-d')) : '',
                'U' => $item->port ?? '',
                'V' => $item->kontrak_sap ?? '',
                'W' => $item->dp_sap ?? '',
                'X' => $item->so_sap ?? '',
                'Y' => $item->kode_do ?? '',
                'Z' => ($item->sisa_awal || $item->sisa_awal === 0) ? $formatNumber($item->sisa_awal) : '',
                'AA' => ($item->total_layan || $item->total_layan === 0) ? $formatNumber($item->total_layan) : '',
                'AB' => ($item->sisa_akhir || $item->sisa_akhir === 0) ? $formatNumber($item->sisa_akhir) : '',
                'BA' => $formatDate($item->jatuh_tempo),
                'BA_raw' => $item->jatuh_tempo ? (is_string($item->jatuh_tempo) ? Carbon::parse($item->jatuh_tempo)->format('Y-m-d') : $item->jatuh_tempo->format('Y-m-d')) : '',
                'row' => $item->id,
            ];
        });

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
     * Memperbarui Data Kontrak dari Database
     */
    public function update(Request $request)
    {
        try {
            $id = $request->input('id');
            
            // Clean number format (remove dots dari thousands separator)
            $volume = $request->input('volume') ? (float) str_replace(['.', ','], ['', '.'], $request->input('volume')) : null;
            $harga = $request->input('harga') ? (float) str_replace(['.', ','], ['', '.'], $request->input('harga')) : null;
            $nilai = $request->input('nilai') ? (float) str_replace(['.', ','], ['', '.'], $request->input('nilai')) : null;
            
            // Parse date jika ada
            $tglKontrak = null;
            if ($request->input('tgl_kontrak')) {
                try {
                    $tglKontrak = Carbon::createFromFormat('Y-m-d', $request->input('tgl_kontrak'))->format('Y-m-d');
                } catch (\Exception $e) {
                    $tglKontrak = null;
                }
            }
            
            // Update kontrak
            $kontrak = Kontrak::findOrFail($id);
            $kontrak->update([
                'loex' => $request->input('loex'),
                'nomor_kontrak' => $request->input('nomor_kontrak'),
                'nama_pembeli' => $request->input('nama_pembeli'),
                'tgl_kontrak' => $tglKontrak,
                'volume' => $volume,
                'harga' => $harga,
                'nilai' => $nilai,
                'total_layan' => $request->input('total_layan'),
                'sisa_akhir' => $request->input('sisa_akhir'),
            ]);
            
            return back()->with('success', 'Data Berhasil Diperbarui');
        } catch (\Exception $e) {
            return back()->with('error', 'Update gagal: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Data Kontrak
     */
    public function destroy($row)
    {
        try {
            Kontrak::findOrFail($row)->delete();
            return back()->with('success', 'Data Berhasil Dihapus');
        } catch (\Exception $e) {
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
