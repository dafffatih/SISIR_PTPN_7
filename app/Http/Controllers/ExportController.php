<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Services\GoogleSheetService;

// PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportController extends Controller
{
    /* =====================================================
     * ENTRY POINT
     * ===================================================== */
    public function export(Request $request)
    {
        $request->validate([
            'format' => 'required|in:csv,excel',
        ]);

        $data = $this->fetchFromGoogleSheets();

        return $request->format === 'csv'
            ? $this->exportToCsv($data)
            : $this->exportToExcel($data);
    }

    /* =====================================================
     * AMBIL DATA LANGSUNG DARI SPREADSHEET (INDEX BASED)
     * ===================================================== */
    private function fetchFromGoogleSheets(): Collection
    {
        $service = new GoogleSheetService();

        // Ambil semua data (TANPA HEADER)
        $rows = $service->getData(null, 'SC Sudah Bayar', 'A4:AW');

        $data = [];

        foreach ($rows as $row) {
            if (empty(array_filter($row))) continue;

            $data[] = [
                // ================= KONTRAK =================
                'lo_ex'            => $row[7]  ?? '',
                'nomor_kontrak'    => $row[1]  ?? '',
                'nama_pembeli'     => $row[9]  ?? '',
                'tanggal_kontrak'  => $row[10] ?? '',
                'volume'           => $row[11] ?? '',
                'harga'            => $row[12] ?? '',
                'nilai'            => $row[13] ?? '',
                'inc_ppn'          => $row[14] ?? '',
                'tanggal_bayar'    => $row[15] ?? '',
                'unit'             => $row[16] ?? '',
                'mutu'             => $row[17] ?? '',
                'kontrak_sap'      => $row[18] ?? '',
                'sisa_awal'        => $row[21] ?? '',

                // ================= PENGIRIMAN =================
                'nomor_do_si'      => $row[24] ?? '',
                'tanggal_do_si'    => $row[19] ?? '',
                'port'             => $row[20] ?? '',
                'kode_do'          => $row[29] ?? '',
                'total_dilayani'   => $row[22] ?? '',
                'sisa_akhir'       => $row[23] ?? '',
                'jatuh_tempo'      => '',

                // ================= PENYERAHAN =================
                'p1_tgl' => $row[38] ?? '',
                'p1_kg'  => $row[39] ?? '',
                'p2_tgl' => $row[40] ?? '',
                'p2_kg'  => $row[41] ?? '',
                'p3_tgl' => $row[42] ?? '',
                'p3_kg'  => $row[43] ?? '',
                'p4_tgl' => $row[44] ?? '',
                'p4_kg'  => $row[45] ?? '',
                'p5_tgl' => $row[46] ?? '',
                'p5_kg'  => $row[47] ?? '',
                'total_penyerahan' => $row[48] ?? '',
            ];
        }

        return collect($data);
    }

    /* =====================================================
     * CSV EXPORT
     * ===================================================== */
    private function exportToCsv(Collection $data)
    {
        $filename = 'Kontrak_Export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            // HEADER
            fputcsv($out, $this->headers());

            // DATA
            foreach ($data as $item) {
                fputcsv($out, $this->mapRow($item));
            }

            fclose($out);
        }, $filename);
    }

    /* =====================================================
     * EXCEL EXPORT
     * ===================================================== */
    private function exportToExcel(Collection $data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // HEADER
        foreach ($this->headers() as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
        }

        // DATA
        $rowNum = 2;
        foreach ($data as $item) {
            foreach ($this->mapRow($item) as $c => $val) {
                $sheet->setCellValueByColumnAndRow($c + 1, $rowNum, $val);
            }
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(
            fn () => $writer->save('php://output'),
            'Kontrak_Export_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    /* =====================================================
     * HEADER EXPORT (SESUAI UI)
     * ===================================================== */
    private function headers(): array
    {
        return [
            'LO/EX','Nomor Kontrak','Nama Pembeli','Tanggal Kontrak','Volume',
            'Harga','Nilai','Inc PPN','Tanggal Bayar','Unit','Mutu',
            'Kontrak SAP','Sisa Awal',
            'Nomor DO/SI','Tanggal DO/SI','Port','Kode DO',
            'Total Dilayani','Sisa Akhir','Jatuh Tempo',
            'Penyerahan 1 Tgl','Penyerahan 1 Kg',
            'Penyerahan 2 Tgl','Penyerahan 2 Kg',
            'Penyerahan 3 Tgl','Penyerahan 3 Kg',
            'Penyerahan 4 Tgl','Penyerahan 4 Kg',
            'Penyerahan 5 Tgl','Penyerahan 5 Kg',
            'Total Penyerahan Kg',
        ];
    }

    /* =====================================================
     * MAP DATA → SATU BARIS EXPORT
     * ===================================================== */
    private function mapRow(array $i): array
    {
        return [
            $i['lo_ex'],
            $i['nomor_kontrak'],
            $i['nama_pembeli'],
            $i['tanggal_kontrak'],
            $i['volume'],
            $i['harga'],
            $i['nilai'],
            $i['inc_ppn'],
            $i['tanggal_bayar'],
            $i['unit'],
            $i['mutu'],
            $i['kontrak_sap'],
            $i['sisa_awal'],
            $i['nomor_do_si'],
            $i['tanggal_do_si'],
            $i['port'],
            $i['kode_do'],
            $i['total_dilayani'],
            $i['sisa_akhir'],
            $i['jatuh_tempo'],
            $i['p1_tgl'], $i['p1_kg'],
            $i['p2_tgl'], $i['p2_kg'],
            $i['p3_tgl'], $i['p3_kg'],
            $i['p4_tgl'], $i['p4_kg'],
            $i['p5_tgl'], $i['p5_kg'],
            $i['total_penyerahan'],
        ];
    }
}
