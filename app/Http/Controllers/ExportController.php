<?php

namespace App\Http\Controllers;

use App\Models\Kontrak;
use App\Services\GoogleSheetService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            $request->validate([
                'format' => 'required|in:csv,excel,pdf',
                'start_date' => 'nullable|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'data_source' => 'required|in:sheets,database',
                'include_charts' => 'nullable|boolean',
                'include_tables' => 'nullable|boolean',
            ]);

            $format = $request->input('format');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $dataSource = $request->input('data_source');
            $includeCharts = $request->boolean('include_charts', false);
            $includeTables = $request->boolean('include_tables', true);

            if ($dataSource === 'sheets') {
                $data = $this->fetchFromGoogleSheets($startDate, $endDate);
            } else {
                $data = $this->fetchFromDatabase($startDate, $endDate);
            }

            return match ($format) {
                'csv' => $this->exportToCsv($data, $startDate, $endDate, $dataSource, $includeCharts, $includeTables),
                'excel' => $this->exportToExcel($data, $startDate, $endDate, $dataSource, $includeCharts, $includeTables),
                'pdf' => $this->exportToPdf($data, $startDate, $endDate, $dataSource, $includeCharts, $includeTables),
            };

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Export error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Export gagal: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function penyerahanHeaders($max = 5)
    {
        $headers = [];

        for ($i = 1; $i <= $max; $i++) {
            $headers[] = "Penyerahan {$i} Tgl";
            $headers[] = "Penyerahan {$i} Kg";
        }

        $headers[] = "Total Penyerahan (Kg)";

        return $headers;
    }


    private function fetchFromGoogleSheets($startDate, $endDate)
    {
        try {
            $service = new GoogleSheetService();
            $spreadsheetId = env('GOOGLE_SHEET_ID');

            $allData = [];

            $sheetRanges = [
                "'SC Sudah Bayar'!A4:BA",
    
            ];

            foreach ($sheetRanges as $range) {
                try {
                    $raw = $service->getData($spreadsheetId, $range);
                    if (empty($raw) || count($raw) < 2) {
                        continue;
                    }

                    $headers = $raw[0];

                    for ($i = 1; $i < count($raw); $i++) {
                        $row = $raw[$i];
                        if (empty(array_filter($row))) {
                            continue;
                        }

                        $item = [];
                        for ($j = 0; $j < count($headers); $j++) {
                            $h = trim($headers[$j] ?? 'col_' . $j);
                            $item[$h] = trim($row[$j] ?? '');
                        }

                        $allData[] = $item;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Could not fetch sheet range ' . $range . ': ' . $e->getMessage());
                    continue;
                }
            }

            if (empty($allData)) {
                return collect([]);
            }

            if ($startDate && $endDate) {
                $start = Carbon::createFromFormat('Y-m-d', $startDate)->startOfDay();
                $end = Carbon::createFromFormat('Y-m-d', $endDate)->endOfDay();

                $allData = collect($allData)->filter(function ($item) use ($start, $end) {
                    $dateVal = $item['Tanggal Kontrak'] ?? $item['tgl_kontrak'] ?? $item['Tanggal'] ?? null;
                    $itemDate = $this->parseDate($dateVal);
                    return $itemDate && $itemDate->between($start, $end);
                })->values()->toArray();
            }

            return collect($allData);
        } catch (\Exception $e) {
            \Log::error('Error fetching from Google Sheets: ' . $e->getMessage());
            throw new \Exception('Gagal mengambil data dari Google Sheets: ' . $e->getMessage());
        }
    }

    private function fetchFromDatabase($startDate, $endDate)
    {
        try {
            $query = Kontrak::query();

            if ($startDate && $endDate) {
                $query->whereBetween('tgl_kontrak', [$startDate, $endDate]);
            }

            $data = $query->orderBy('tgl_kontrak', 'desc')
                ->orderBy('nomor_dosi', 'desc')
                ->get()
                ->toArray();

            \Log::info('Database export: fetched ' . count($data) . ' records');

            return collect($data);
        } catch (\Exception $e) {
            \Log::error('Error fetching from database: ' . $e->getMessage());
            throw new \Exception('Gagal mengambil data dari database: ' . $e->getMessage());
        }
    }

    private function parseDate($dateString)
    {
        if (!$dateString) {
            return null;
        }

        $formats = ['d/m/Y', 'd-M-Y', 'd-M-y', 'd F Y', 'Y-m-d', 'd.m.Y', 'Y/m/d'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($dateString));
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function exportToCsv($data, $startDate, $endDate, $dataSource, $includeCharts, $includeTables)
    {
        $filename = 'Kontrak_Export_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($data, $includeCharts, $includeTables) {
            $file = fopen('php://output', 'w');

            fwrite($file, "\xEF\xBB\xBF");

            if ($includeTables) {
                $csvHeaders = array_merge([
                    'Nomor DO/SI', 'Nomor Kontrak', 'Nama Pembeli', 'Tanggal Kontrak', 'Volume',
                    'Harga', 'Nilai', 'Inc. PPN', 'Tanggal Bayar', 'Unit', 'Mutu', 'Tanggal DO/SI',
                    'Port', 'Kontrak SAP', 'DP SAP', 'SO SAP', 'Kode DO', 'Sisa Awal',
                    'Total Layanan', 'Sisa Akhir', 'Jatuh Tempo',
                ], $this->penyerahanHeaders(5));

                fputcsv($file, $csvHeaders);

                foreach ($data as $item) {
                    $row = $this->extractDataRow($item);
                    fputcsv($file, $row);
                }
            }

            if ($includeCharts) {
                fputcsv($file, ['']);
                fputcsv($file, ['RINGKASAN DATA']);
                fputcsv($file, ['']);

                $summary = $this->generateSummary($data);
                fputcsv($file, ['Total Records', count($data)]);
                fputcsv($file, ['Total Nilai', $summary['total_nilai']]);
                fputcsv($file, ['Total Volume', $summary['total_volume']]);
                fputcsv($file, ['Average Harga', $summary['avg_harga']]);

                fputcsv($file, ['']);
                fputcsv($file, ['BREAKDOWN PER MUTU']);
                fputcsv($file, ['Mutu', 'Jumlah', 'Total Nilai', 'Total Volume']);

                foreach ($summary['by_mutu'] as $mutu => $stats) {
                    fputcsv($file, [$mutu, $stats['count'], $stats['nilai'], $stats['volume']]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportToExcel($data, $startDate, $endDate, $dataSource, $includeCharts, $includeTables)
    {
        $filename = 'Kontrak_Export_' . now()->format('Ymd_His') . '.xlsx';

        try {
            $excel = new \PHPExcel();

            if ($includeTables) {
                $sheet = $excel->getActiveSheet();
                $sheet->setTitle('Data');

                $headers = array_merge([
                    'Nomor DO/SI', 'Nomor Kontrak', 'Nama Pembeli', 'Tanggal Kontrak', 'Volume',
                    'Harga', 'Nilai', 'Inc. PPN', 'Tanggal Bayar', 'Unit', 'Mutu', 'Tanggal DO/SI',
                    'Port', 'Kontrak SAP', 'DP SAP', 'SO SAP', 'Kode DO', 'Sisa Awal',
                    'Total Layanan', 'Sisa Akhir', 'Jatuh Tempo',
                ], $this->penyerahanHeaders(5));


                for ($col = 0; $col < count($headers); $col++) {
                    $sheet->setCellValueByColumnAndRow($col + 1, 1, $headers[$col]);
                    $sheet->getColumnDimensionByColumn($col + 1)->setWidth(15);
                }

                $row = 2;
                foreach ($data as $item) {
                    $values = $this->extractDataRow($item);

                    for ($col = 0; $col < count($values); $col++) {
                        $sheet->setCellValueByColumnAndRow($col + 1, $row, $values[$col]);
                    }
                    $row++;
                }
            }

            if ($includeCharts) {
                $summary = $this->generateSummary($data);
                $summarySheet = $excel->createSheet();
                $summarySheet->setTitle('Summary');

                $row = 1;
                $summarySheet->setCellValue('A' . $row, 'STATISTIK KESELURUHAN');
                $row++;
                $summarySheet->setCellValue('A' . $row, 'Total Records');
                $summarySheet->setCellValue('B' . $row, count($data));
                $row++;
                $summarySheet->setCellValue('A' . $row, 'Total Nilai');
                $summarySheet->setCellValue('B' . $row, $summary['total_nilai']);
                $row++;
                $summarySheet->setCellValue('A' . $row, 'Total Volume');
                $summarySheet->setCellValue('B' . $row, $summary['total_volume']);
                $row++;
                $summarySheet->setCellValue('A' . $row, 'Average Harga');
                $summarySheet->setCellValue('B' . $row, $summary['avg_harga']);
                $row += 2;

                $summarySheet->setCellValue('A' . $row, 'BREAKDOWN PER MUTU');
                $row++;
                $summarySheet->setCellValue('A' . $row, 'Mutu');
                $summarySheet->setCellValue('B' . $row, 'Jumlah');
                $summarySheet->setCellValue('C' . $row, 'Total Nilai');
                $summarySheet->setCellValue('D' . $row, 'Total Volume');
                $row++;

                foreach ($summary['by_mutu'] as $mutu => $stats) {
                    $summarySheet->setCellValue('A' . $row, $mutu);
                    $summarySheet->setCellValue('B' . $row, $stats['count']);
                    $summarySheet->setCellValue('C' . $row, $stats['nilai']);
                    $summarySheet->setCellValue('D' . $row, $stats['volume']);
                    $row++;
                }

                $summarySheet->getColumnDimension('A')->setWidth(20);
                $summarySheet->getColumnDimension('B')->setWidth(15);
                $summarySheet->getColumnDimension('C')->setWidth(15);
                $summarySheet->getColumnDimension('D')->setWidth(15);
            }

            $writer = \PHPExcel_IOFactory::createWriter($excel, 'Excel2007');

            $headers = [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Cache-Control' => 'max-age=0',
            ];

            $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
            $writer->save($tmpFile);

            $content = file_get_contents($tmpFile);
            @unlink($tmpFile);

            return response($content, 200, $headers);
        } catch (\Exception $e) {
            \Log::error('Excel Export Error: ' . $e->getMessage());
            throw new \Exception('Excel export error: ' . $e->getMessage());
        }
    }

    private function exportToPdf($data, $startDate, $endDate, $dataSource, $includeCharts, $includeTables)
    {
        $filename = 'Kontrak_Export_' . now()->format('Ymd_His') . '.pdf';

        try {
            $html = view('exports.kontrak-pdf', [
                'data' => $data,
                'includeTables' => $includeTables,
                'includeCharts' => $includeCharts,
                'summary' => $this->generateSummary($data),
                'generatedAt' => now()->format('d-m-Y H:i:s'),
            ])->render();

            $dompdf = new \Dompdf\Dompdf();
            $dompdf->setBasePath(public_path());
            $dompdf->loadHtml($html);
            $dompdf->setPaper('a4', 'landscape');
            $dompdf->render();

            return response($dompdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                ->header('Cache-Control', 'public, must-revalidate, max-age=0')
                ->header('Pragma', 'public')
                ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
        } catch (\Exception $e) {
            \Log::error('PDF Export Error: ' . $e->getMessage());
            throw new \Exception('PDF export error: ' . $e->getMessage());
        }
    }

    private function extractDataRow($item)
    {
        // ===============================
        // JIKA DATA ARRAY (Google Sheet / DB toArray)
        // ===============================
        if (is_array($item)) {
            $row = [
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

            // 🔽 PENYERAHAN 1–5
            for ($i = 1; $i <= 5; $i++) {
                $row[] = $item["penyerahan_{$i}_tgl"] ?? '';
                $row[] = $item["penyerahan_{$i}_kg"] ?? '';
            }

            // 🔽 TOTAL PENYERAHAN
            $row[] = $item['total_penyerahan'] ?? '';

            return $row;
        }

        // ===============================
        // JIKA DATA OBJECT (Eloquent)
        // ===============================
        $row = [
            $item->nomor_dosi ?? '',
            $item->nomor_kontrak ?? '',
            $item->nama_pembeli ?? '',
            $item->tgl_kontrak ?? '',
            $item->volume ?? '',
            $item->harga ?? '',
            $item->nilai ?? '',
            $item->inc_ppn ?? '',
            $item->tgl_bayar ?? '',
            $item->unit ?? '',
            $item->mutu ?? '',
            $item->tgl_dosi ?? '',
            $item->port ?? '',
            $item->kontrak_sap ?? '',
            $item->dp_sap ?? '',
            $item->so_sap ?? '',
            $item->kode_do ?? '',
            $item->sisa_awal ?? '',
            $item->total_layan ?? '',
            $item->sisa_akhir ?? '',
            $item->jatuh_tempo ?? '',
        ];

        for ($i = 1; $i <= 5; $i++) {
            $row[] = $item->{"penyerahan_{$i}_tgl"} ?? '';
            $row[] = $item->{"penyerahan_{$i}_kg"} ?? '';
        }

        $row[] = $item->total_penyerahan ?? '';

        return $row;
    }


    private function generateSummary($data)
    {
        $summary = [
            'total_nilai' => 0,
            'total_volume' => 0,
            'avg_harga' => 0,
            'by_mutu' => []
        ];

        foreach ($data as $item) {
            $nilai = is_array($item) ? ($item['nilai'] ?? 0) : ($item->nilai ?? 0);
            $volume = is_array($item) ? ($item['volume'] ?? 0) : ($item->volume ?? 0);
            $harga = is_array($item) ? ($item['harga'] ?? 0) : ($item->harga ?? 0);
            $mutu = is_array($item) ? ($item['mutu'] ?? 'Unknown') : ($item->mutu ?? 'Unknown');

            $nilai = (float)str_replace(['.', ','], ['', '.'], $nilai);
            $volume = (float)str_replace(['.', ','], ['', '.'], $volume);
            $harga = (float)str_replace(['.', ','], ['', '.'], $harga);

            $summary['total_nilai'] += $nilai;
            $summary['total_volume'] += $volume;

            if (!isset($summary['by_mutu'][$mutu])) {
                $summary['by_mutu'][$mutu] = ['count' => 0, 'nilai' => 0, 'volume' => 0];
            }
            $summary['by_mutu'][$mutu]['count']++;
            $summary['by_mutu'][$mutu]['nilai'] += $nilai;
            $summary['by_mutu'][$mutu]['volume'] += $volume;
        }

        $summary['avg_harga'] = count($data) > 0 ? $summary['total_nilai'] / count($data) : 0;

        return $summary;
    }
}
