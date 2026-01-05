<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class SheetController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

    public function index()
    {
        $spreadsheetId = '1Vii1HvHtMB-zKf1xzpdkx-5R1eoNvhaC';
        $range = "'SC Sudah Bayar'!H1:BA"; // Mengambil sampai baris paling akhir

        try {
            $rawData = $this->googleSheetService->readSheet($spreadsheetId, $range);

            if (!$rawData) {
                return view('dashboard.upload_export', ['data' => []]);
            }

            $filteredData = [];

            foreach ($rawData as $row) {
                // Kita gunakan array_filter atau pengecekan sederhana 
                // agar baris yang benar-benar kosong tidak ikut masuk ke tampilan
                if (empty(array_filter($row))) {
                    continue; 
                }

                $filteredData[] = [
                    'H' => $row[0] ?? '', 'I' => $row[1] ?? '', 'J' => $row[2] ?? '',
                    'K' => $row[3] ?? '', 'L' => $row[4] ?? '', 'M' => $row[5] ?? '',
                    'Q' => $row[9] ?? '', 'R' => $row[10] ?? '', 'S' => $row[11] ?? '',
                    'U' => $row[13] ?? '', 'V' => $row[14] ?? '', 'W' => $row[15] ?? '', 'X' => $row[16] ?? '',
                    'Z' => $row[18] ?? '', 'AA' => $row[19] ?? '',
                    'AM' => $row[31] ?? '', 'AN' => $row[32] ?? '', 'AO' => $row[33] ?? '',
                    'AP' => $row[34] ?? '', 'AQ' => $row[35] ?? '', 'AR' => $row[36] ?? '',
                    'AS' => $row[37] ?? '', 'AT' => $row[38] ?? '', 'AU' => $row[39] ?? '',
                    'AV' => $row[40] ?? '',
                    'BA' => $row[45] ?? '',
                ];
            }

            return view('dashboard.upload_export', ['data' => $filteredData]);

        } catch (\Exception $e) {
            return view('dashboard.upload_export')->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}