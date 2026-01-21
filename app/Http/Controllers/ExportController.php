<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;
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
            'year'   => 'required', // angka atau "all"
        ]);

        $data = $this->fetchFromGoogleSheets($request->year);

        return $request->format === 'csv'
            ? $this->exportToCsv($data)
            : $this->exportToExcel($data);
    }

    /* =====================================================
     * PARSE TANGGAL (UNTUK FILTER TAHUN SAJA)
     * ===================================================== */
    private function parseTanggal($value): ?Carbon
    {
        if (!$value) return null;

        $value = trim($value);

        $formats = [
            'd/m/y', 'd/m/Y',
            'd-M-Y', 'd-M-y',
            'd-m-Y',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value);
            } catch (\Exception $e) {}
        }

        return null;
    }

    /* =====================================================
     * BERSIHKAN TANGGAL DUMMY & #N/A
     * ===================================================== */
    private function cleanTanggal($value): string
    {
        if (!$value) return '';

        $value = trim($value);

        if ($value === '30/12/99' || $value === '#N/A') {
            return '';
        }

        return $value;
    }

    /* =====================================================
     * NORMALISASI ANGKA (FORMAT INDONESIA)
     * ===================================================== */
    private function num($value)
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value) ? (float)$value : null;
    }

    /* =====================================================
     * AMBIL DATA RAW + FILTER TAHUN
     * ===================================================== */
    private function fetchFromGoogleSheets($year): Collection
    {
        $service = new GoogleSheetService();

        // RANGE DIPERPANJANG (SAMPAI BC)
        $rows = $service->getData(null, 'SC Sudah Bayar', 'A4:CA');

        $data = [];

        foreach ($rows as $row) {

            if (empty(array_filter($row))) {
                continue;
            }

            /* ================= FILTER TAHUN ================= */
            $tanggalKontrak = $this->parseTanggal($row[10] ?? null);

            if ($year !== 'all') {
                if (!$tanggalKontrak || $tanggalKontrak->year !== (int)$year) {
                    continue;
                }
            }

            /* ================= DATA ================= */
            $data[] = [

                // KONTRAK
                'lo_ex'           => $row[7]  ?? '',
                'nomor_kontrak'   => $row[1]  ?? '',
                'nama_pembeli'    => $row[9]  ?? '',
                'tanggal_kontrak' => $row[10] ?? '',

                // ANGKA (DINORMALISASI)
                'volume'          => $this->num($row[11] ?? null),
                'harga'           => $this->num($row[12] ?? null),
                'nilai'           => $this->num($row[13] ?? null),
                'inc_ppn'         => $this->num($row[14] ?? null),

                'tanggal_bayar'   => $row[15] ?? '',
                'unit'            => $row[16] ?? '',
                'mutu'            => $row[17] ?? '',
                'kontrak_sap'     => $row[18] ?? '',

                // HASIL RUMUS → BIARKAN KOSONG JIKA KOSONG
                'sisa_awal'       => '',

                // PENGIRIMAN
                'nomor_do_si'     => $row[24] ?? '',
                'tanggal_do_si'   => $row[19] ?? '',
                'port'            => $row[20] ?? '',
                'kode_do'         => $row[29] ?? '',
                'total_dilayani'  => '',
                'sisa_akhir'      => '',

                // BC = INDEX 54
                'jatuh_tempo'     => $this->cleanTanggal($row[54] ?? ''),

                // PENYERAHAN
                'p1_tgl' => $this->cleanTanggal($row[38] ?? ''),
                'p1_kg'  => $this->num($row[39] ?? null),

                'p2_tgl' => $this->cleanTanggal($row[40] ?? ''),
                'p2_kg'  => $this->num($row[41] ?? null),

                'p3_tgl' => $this->cleanTanggal($row[42] ?? ''),
                'p3_kg'  => $this->num($row[43] ?? null),

                'p4_tgl' => $this->cleanTanggal($row[44] ?? ''),
                'p4_kg'  => $this->num($row[45] ?? null),

                'p5_tgl' => $this->cleanTanggal($row[46] ?? ''),
                'p5_kg'  => $this->num($row[47] ?? null),

                // AY = INDEX 50
                'total_penyerahan' => $this->num($row[50] ?? null),
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
     * HEADER EXPORT
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
     * MAP ROW
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
