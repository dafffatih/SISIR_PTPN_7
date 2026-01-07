<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kontrak;
use App\Exports\KontrakExport;
use Illuminate\Filesystem\Filesystem;
use Maatwebsite\Excel\Facades\Excel;
use Dompdf\Dompdf;

class TestExportCommand extends Command
{
    protected $signature = 'test:export';
    protected $description = 'Generate test CSV, Excel, and PDF exports to storage/app/test_exports';

    public function handle()
    {
        $this->info('Starting export tests...');

        $data = Kontrak::orderBy('tgl_kontrak','desc')->get()->toArray();

        $fs = new Filesystem();
        $dir = storage_path('app/test_exports');
        if (! $fs->exists($dir)) {
            $fs->makeDirectory($dir, 0755, true);
        }

        // CSV
        $csvFile = $dir . DIRECTORY_SEPARATOR . 'kontrak_test.csv';
        $handle = fopen($csvFile, 'w');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'Nomor DO/SI','Nomor Kontrak','Nama Pembeli','Tanggal Kontrak','Volume','Harga','Nilai','Inc. PPN','Tanggal Bayar','Unit','Mutu','Tanggal DO/SI','Port'
        ]);
        foreach ($data as $item) {
            fputcsv($handle, [
                $item['nomor_dosi'] ?? '',
                $item['nomor_kontrak'] ?? '',
                $item['nama_pembeli'] ?? '',
                $item['tgl_kontrak'] ?? '',
                $item['volume'] ?? '',
                $item['harga'] ?? '',
                $item['nilai'] ?? '',
                $item['inc_ppn'] ?? '',
                $item['tgl_bayar'] ?? '',
                $item['unit'] ?? '',
                $item['mutu'] ?? '',
                $item['tgl_dosi'] ?? '',
                $item['port'] ?? '',
            ]);
        }
        fclose($handle);
        $this->info('CSV written: ' . $csvFile . ' (rows: ' . count($data) . ')');

        // Excel
        try {
            $excelFile = $dir . DIRECTORY_SEPARATOR . 'kontrak_test.xlsx';
            $excel = new \PHPExcel();
            $sheet = $excel->getActiveSheet();
            $sheet->setTitle('Data');

            $headers = [
                'Nomor DO/SI', 'Nomor Kontrak', 'Nama Pembeli', 'Tanggal Kontrak', 'Volume',
                'Harga', 'Nilai', 'Inc. PPN', 'Tanggal Bayar', 'Unit', 'Mutu', 'Tanggal DO/SI',
                'Port', 'Kontrak SAP', 'DP SAP', 'SO SAP', 'Kode DO', 'Sisa Awal',
                'Total Layanan', 'Sisa Akhir', 'Jatuh Tempo',
            ];

            for ($col = 0; $col < count($headers); $col++) {
                $sheet->setCellValueByColumnAndRow($col + 1, 1, $headers[$col]);
                $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
            }

            $row = 2;
            foreach ($data as $item) {
                $values = [
                    $item['nomor_dosi'] ?? '',
                    $item['nomor_kontrak'] ?? '',
                    $item['nama_pembeli'] ?? '',
                    $item['tgl_kontrak'] ?? '',
                    $item['volume'] ?? '',
                    $item['harga'] ?? '',
                    $item['nilai'] ?? '',
                    $item['inc_ppn'] ?? '',
                    $item['tgl_bayar'] ?? '',
                    $item['unit'] ?? '',
                    $item['mutu'] ?? '',
                    $item['tgl_dosi'] ?? '',
                    $item['port'] ?? '',
                    $item['kontrak_sap'] ?? '',
                    $item['dp_sap'] ?? '',
                    $item['so_sap'] ?? '',
                    $item['kode_do'] ?? '',
                    $item['sisa_awal'] ?? '',
                    $item['total_layan'] ?? '',
                    $item['sisa_akhir'] ?? '',
                    $item['jatuh_tempo'] ?? '',
                ];

                for ($col = 0; $col < count($values); $col++) {
                    $sheet->setCellValueByColumnAndRow($col + 1, $row, $values[$col]);
                }
                $row++;
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $writer->save($excelFile);

            $this->info('Excel written: ' . $excelFile . ' (rows: ' . count($data) . ')');
        } catch (\Exception $e) {
            $this->error('Excel export failed: ' . $e->getMessage());
        }

        // PDF
        try {
            $pdfFile = $dir . DIRECTORY_SEPARATOR . 'kontrak_test.pdf';
            
            // Calculate summary stats
            $summary = [
                'total_nilai' => 0,
                'total_volume' => 0,
                'by_mutu' => []
            ];

            foreach ($data as $item) {
                $nilai = $item['nilai'] ?? 0;
                $volume = $item['volume'] ?? 0;
                $mutu = $item['mutu'] ?? 'Unknown';

                $nilai = (float)str_replace(['.', ','], ['', '.'], $nilai);
                $volume = (float)str_replace(['.', ','], ['', '.'], $volume);

                $summary['total_nilai'] += $nilai;
                $summary['total_volume'] += $volume;

                if (!isset($summary['by_mutu'][$mutu])) {
                    $summary['by_mutu'][$mutu] = ['count' => 0, 'nilai' => 0, 'volume' => 0];
                }
                $summary['by_mutu'][$mutu]['count']++;
                $summary['by_mutu'][$mutu]['nilai'] += $nilai;
                $summary['by_mutu'][$mutu]['volume'] += $volume;
            }

            $html = view('exports.kontrak-pdf', [
                'data' => $data,
                'includeTables' => true,
                'includeCharts' => true,
                'summary' => $summary,
                'generatedAt' => now()->format('d-m-Y H:i:s'),
            ])->render();

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            file_put_contents($pdfFile, $dompdf->output());

            $this->info('PDF written: ' . $pdfFile . ' (rows: ' . count($data) . ')');
        } catch (\Exception $e) {
            $this->error('PDF export failed: ' . $e->getMessage());
        }

        $this->info('Test export completed.');
        return 0;
    }
}
