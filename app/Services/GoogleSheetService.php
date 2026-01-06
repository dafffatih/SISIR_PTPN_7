<?php

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;

class GoogleSheetService
{
    protected $service;
    protected $spreadsheetId;

    public function __construct()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(Google_Service_Sheets::SPREADSHEETS);

        $this->service = new Google_Service_Sheets($client);
        $this->spreadsheetId = env('GOOGLE_SHEET_ID');
    }

    // Mengambil data dari sheet tertentu
    public function getData($range = "'SC Sudah Bayar'!A4:BA")
    {
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
        return $response->getValues() ?? [];
    }

    // Update data berdasarkan nomor baris
    public function updateData($row, $data, $sheetName = 'SC Sudah Bayar')
    {
        $body = new Google_Service_Sheets_ValueRange([
            'values' => [$data]
        ]);

        $params = ['valueInputOption' => 'USER_ENTERED'];
        $range = "'{$sheetName}'!A{$row}:BA{$row}";

        $this->service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    // Tambah data baru (Append)
    public function storeData($data, $sheetName = 'SC Sudah Bayar')
    {
        $body = new Google_Service_Sheets_ValueRange([
            'values' => [$data]
        ]);

        $params = ['valueInputOption' => 'USER_ENTERED'];
        $range = "'{$sheetName}'!A:BA";

        $this->service->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            $params
        );
    }

    public function deleteData($row)
    {
        $sheetId = 2061910826; // GANTI dengan GID tab 'SC Sudah Bayar' Anda

        $requests = [
            new \Google_Service_Sheets_Request([
                'deleteDimension' => [
                    'range' => [
                        'sheetId' => $sheetId,
                        'dimension' => 'ROWS',
                        'startIndex' => $row - 1,
                        'endIndex' => $row
                    ]
                ]
            ])
        ];

        $batchUpdateRequest = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => $requests
        ]);

        $this->service->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }
}