<?php

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Google_Service_Drive;

class GoogleSheetService
{
    protected $sheetsService;
    protected $driveService;
    protected $spreadsheetId;

    public function __construct()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/google/service-account.json'));
        $client->addScope(Google_Service_Sheets::SPREADSHEETS);
        $client->addScope(Google_Service_Drive::DRIVE_READONLY);

        $this->sheetsService = new Google_Service_Sheets($client);
        $this->driveService = new Google_Service_Drive($client);
        $this->spreadsheetId = env('GOOGLE_SHEET_ID');
    }

    // Mengambil data dari sheet tertentu
    public function getData($spreadsheetId = null, $range = "'SC Sudah Bayar'!A4:BA")
    {
        $id = $spreadsheetId ?? $this->spreadsheetId;
        $response = $this->sheetsService->spreadsheets_values->get($id, $range);
        return $response->getValues() ?? [];
    }

    // List spreadsheets dalam folder tertentu menggunakan Google Drive API
    public function listSpreadsheetsInFolder($folderId)
    {
        $query = "mimeType = 'application/vnd.google-apps.spreadsheet' and trashed = false and '{$folderId}' in parents";
        
        $optParams = [
            'q' => $query,
            'spaces' => 'drive',
            'fields' => 'files(id, name)',
            'pageSize' => 100,
        ];

        try {
            $results = $this->driveService->files->listFiles($optParams);
            $files = $results->getFiles();
            
            $spreadsheets = [];
            foreach ($files as $file) {
                $spreadsheets[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                ];
            }
            
            return $spreadsheets;
        } catch (\Exception $e) {
            throw new \Exception('Error fetching files from Google Drive: ' . $e->getMessage());
        }
    }

    // Update data berdasarkan nomor baris
    public function updateData($row, $data, $sheetName = 'SC Sudah Bayar')
    {
        $body = new Google_Service_Sheets_ValueRange([
            'values' => [$data]
        ]);

        $params = ['valueInputOption' => 'USER_ENTERED'];
        $range = "'{$sheetName}'!A{$row}:BA{$row}";

        $this->sheetsService->spreadsheets_values->update(
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

        // UBAH DISINI: 
        // Menggunakan A:A memaksa Google API mencari baris kosong pertama 
        // berdasarkan kolom A, lalu memasukkan array data mulai dari kolom A.
        $range = "'{$sheetName}'!A:AB"; 

        $this->sheetsService->spreadsheets_values->append(
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

        $this->sheetsService->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }
    public function batchGet($ranges)
    {
        $params = ['ranges' => $ranges];
        $result = $this->service->spreadsheets_values->batchGet($this->spreadsheetId, $params);
        
        $data = [];
        foreach ($result->getValueRanges() as $valueRange) {
            $data[$valueRange->getRange()] = $valueRange->getValues() ?? [];
        }
        return $data;
    }
}