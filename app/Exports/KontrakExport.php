<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class KontrakExport implements FromArray, WithHeadings
{
    protected $data;
    protected $includeTables;
    protected $includeCharts;

    public function __construct($data, $includeTables = true, $includeCharts = false)
    {
        $this->data = $data;
        $this->includeTables = $includeTables;
        $this->includeCharts = $includeCharts;
    }

    public function array(): array
    {
        $rows = [];

        if ($this->includeTables) {
            foreach ($this->data as $item) {
                if (is_array($item)) {
                    $rows[] = [
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
                    ];
                } else {
                    $rows[] = [
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
                    ];
                }
            }
        }

        if ($this->includeCharts) {
            $rows[] = [];
            $summary = $this->generateSummary($this->data);
            $rows[] = ['Total Records', count($this->data)];
            $rows[] = ['Total Nilai', $summary['total_nilai']];
            $rows[] = ['Total Volume', $summary['total_volume']];
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Nomor DO/SI',
            'Nomor Kontrak',
            'Nama Pembeli',
            'Tanggal Kontrak',
            'Volume',
            'Harga',
            'Nilai',
            'Inc. PPN',
            'Tanggal Bayar',
            'Unit',
            'Mutu',
            'Tanggal DO/SI',
            'Port',
        ];
    }

    public function generateSummary($data)
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
            $mutu = is_array($item) ? ($item['mutu'] ?? 'Unknown') : ($item->mutu ?? 'Unknown');

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

        $summary['avg_harga'] = count($data) > 0 ? $summary['total_nilai'] / count($data) : 0;

        return $summary;
    }
}
