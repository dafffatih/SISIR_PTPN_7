<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Services\GoogleSheetService;

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
     * NORMALISASI ANGKA FORMAT INDONESIA
     * 20.160 -> 20160
     * ===================================================== */
    private function numId($value)
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        $value = trim($value);
        $value = str_replace('.', '', $value); // hapus ribuan
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float) $value : '';
    }

    /* =====================================================
     * BERSIHKAN TANGGAL DUMMY & ERROR
     * ===================================================== */
    private function cleanTanggal($value): string
    {
        if (!$value) return '';

        $value = trim($value);

        if (
            $value === '30/12/99' ||
            $value === '#N/A' ||
            $value === '-'
        ) {
            return '';
        }

        return $value;
    }

    /* =====================================================
     * AMBIL DATA RAW DARI GOOGLE SHEET
     * TANPA FILTER TAHUN
     * ===================================================== */
    private function fetchFromGoogleSheets(): Collection
    {
        $service = new GoogleSheetService();

        // Sampai CA agar semua kolom terbaca
        $rows = $service->getData(null, 'SC Sudah Bayar', 'A4:CA');

        $data = [];

        foreach ($rows as $row) {

            if (empty(array_filter($row))) {
                continue;
            }

            $data[] = [

                /* ================= KONTRAK ================= */
                'lo_ex'            => $row[7]  ?? '',
                'nomor_kontrak'    => $row[1]  ?? '',
                'nama_pembeli'     => $row[9]  ?? '',
                'tanggal_kontrak'  => $row[10] ?? '',

                'volume'           => $this->numId($row[11] ?? ''),
                'harga'            => $this->numId($row[12] ?? ''),
                'nilai'            => $this->numId($row[13] ?? ''),
                'inc_ppn'          => $this->numId($row[14] ?? ''),

                'tanggal_bayar'    => $row[15] ?? '',
                'unit'             => $row[16] ?? '',
                'mutu'             => $row[17] ?? '',

                /* ================= PENGIRIMAN ================= */
                // Nomor DO/SI (kolom S)
                'nomor_do_si'      => $row[18] ?? '',
                'tanggal_do_si'    => $row[19] ?? '',
                'port'             => $row[20] ?? '',

                // Sisa & realisasi
                'sisa_awal'        => $row[25] ?? '',
                'total_dilayani'   => $row[26] ?? '',
                'sisa_akhir'       => $row[27] ?? '',

                /* ================= PENYERAHAN ================= */
                'p1_tgl' => $this->cleanTanggal($row[38] ?? ''),
                'p1_kg'  => $this->numId($row[39] ?? ''),

                'p2_tgl' => $this->cleanTanggal($row[40] ?? ''),
                'p2_kg'  => $this->numId($row[41] ?? ''),

                'p3_tgl' => $this->cleanTanggal($row[42] ?? ''),
                'p3_kg'  => $this->numId($row[43] ?? ''),

                'p4_tgl' => $this->cleanTanggal($row[44] ?? ''),
                'p4_kg'  => $this->numId($row[45] ?? ''),

                'p5_tgl' => $this->cleanTanggal($row[46] ?? ''),
                'p5_kg'  => $this->numId($row[47] ?? ''),

                // Total Penyerahan (AY)
                'total_penyerahan' => $this->numId($row[50] ?? ''),

                // Jatuh Tempo Pembayaran (BC)
                'jatuh_tempo'      => $this->cleanTanggal($row[54] ?? ''),
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

            fputcsv($out, $this->headers());

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

        foreach ($this->headers() as $i => $h) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $h);
            $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
        }

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
     * HEADER EXPORT (URUTAN SESUAI SPREADSHEET)
     * ===================================================== */
    private function headers(): array
    {
        return [
            'LO/EX','Nomor Kontrak','Nama Pembeli','Tanggal Kontrak',
            'Volume','Harga','Nilai','Inc PPN',
            'Tanggal Bayar','Unit','Mutu',

            'Nomor DO/SI','Tanggal DO/SI','Port',
            'Sisa Awal','Total Dilayani','Sisa Akhir',

            'Penyerahan 1 Tgl','Penyerahan 1 Kg',
            'Penyerahan 2 Tgl','Penyerahan 2 Kg',
            'Penyerahan 3 Tgl','Penyerahan 3 Kg',
            'Penyerahan 4 Tgl','Penyerahan 4 Kg',
            'Penyerahan 5 Tgl','Penyerahan 5 Kg',
            'Total Penyerahan Kg',

            'Jatuh Tempo Pembayaran',
        ];
    }

    /* =====================================================
     * MAP ROW → EXPORT
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

            $i['nomor_do_si'],
            $i['tanggal_do_si'],
            $i['port'],
            $i['sisa_awal'],
            $i['total_dilayani'],
            $i['sisa_akhir'],

            $i['p1_tgl'], $i['p1_kg'],
            $i['p2_tgl'], $i['p2_kg'],
            $i['p3_tgl'], $i['p3_kg'],
            $i['p4_tgl'], $i['p4_kg'],
            $i['p5_tgl'], $i['p5_kg'],
            $i['total_penyerahan'],

            $i['jatuh_tempo'],
        ];
    }
}
