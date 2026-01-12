<?php

namespace App\Services;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Google_Service_Drive;
use Google_Service_Sheets_Request;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_BatchUpdateValuesRequest;
use App\Models\Setting; 

class GoogleSheetService
{
    protected $sheetsService;
    protected $driveService;
    protected $spreadsheetId;

    public function __construct()
    {
        try {
            // 1. Load Credentials
            $credentialsPath = storage_path('app/google/service-account.json');
            
            if (!file_exists($credentialsPath)) {
                throw new \Exception("Service account credentials file not found at: {$credentialsPath}");
            }
            
            $client = new Google_Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
            $client->addScope(Google_Service_Drive::DRIVE_READONLY);

            $this->sheetsService = new Google_Service_Sheets($client);
            $this->driveService = new Google_Service_Drive($client);
            
            // 2. Load Spreadsheet ID (Prioritas: Database -> Config -> Env)
            $setting = Setting::where('key', 'google_sheet_id')->first();
            $dbSheetId = $setting ? $setting->value : null;

            $this->spreadsheetId = $dbSheetId 
                                   ?? config('services.google.sheet_id') 
                                   ?? env('GOOGLE_SHEET_ID')
                                   ?? null;
            
            if (!$this->spreadsheetId) {
                \Log::warning('GOOGLE_SHEET_ID belum dikonfigurasi di Database maupun .env');
            }

        } catch (\Exception $e) {
            \Log::error('GoogleSheetService initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    // Helper: Cari Sheet ID berdasarkan Nama
    private function getSheetIdFromTitle($sheetName)
    {
        $spreadsheet = $this->sheetsService->spreadsheets->get($this->spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheet->getProperties()->getSheetId();
            }
        }
        return null;
    }

    // 1. GET DATA SINGLE
    public function getData($spreadsheetId = null, $range = "'SC Sudah Bayar'!A4:BA")
    {
        try {
            $id = $spreadsheetId ?? $this->spreadsheetId;
            if (empty($id)) throw new \Exception('Spreadsheet ID missing.');
            
            $response = $this->sheetsService->spreadsheets_values->get($id, $range);
            return $response->getValues() ?? [];
        } catch (\Exception $e) {
            \Log::error('Error fetching data: ' . $e->getMessage());
            throw $e;
        }
    }

    // 2. GET BATCH DATA (FIXED HERE)
    public function getBatchData($ranges)
    {
        try {
            if (empty($this->spreadsheetId)) throw new \Exception('Spreadsheet ID missing');

            $params = ['ranges' => $ranges];
            
            // --- PERBAIKAN DI SINI ---
            // Menggunakan method bawaan Google: batchGet
            $result = $this->sheetsService->spreadsheets_values->batchGet($this->spreadsheetId, $params);
            
            // Mapping hasil agar Key Array sesuai dengan Range yang diminta
            // Google mengembalikan hasil sesuai urutan request
            $mappedData = [];
            $valueRanges = $result->getValueRanges();

            foreach ($ranges as $index => $range) {
                // Pastikan ada datanya, jika tidak return array kosong
                if (isset($valueRanges[$index])) {
                    $mappedData[$range] = $valueRanges[$index]->getValues() ?? [];
                } else {
                    $mappedData[$range] = [];
                }
            }
            
            return $mappedData;

        } catch (\Exception $e) {
            \Log::error('Batch get failed: ' . $e->getMessage());
            return [];
        }
    }

    // 3. UPDATE DATA
    public function updateData($row, $data, $sheetName = 'SC Sudah Bayar')
    {
        if (empty($this->spreadsheetId)) throw new \Exception('Spreadsheet ID missing');

        $body = new Google_Service_Sheets_ValueRange(['values' => [$data]]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $range = "'{$sheetName}'!A{$row}:BA{$row}";

        $this->sheetsService->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);
    }

    // 4. STORE DATA
    public function storeData($data, $sheetName = 'SC Sudah Bayar')
    {
        if (empty($this->spreadsheetId)) throw new \Exception('Spreadsheet ID missing');

        $body = new Google_Service_Sheets_ValueRange(['values' => [$data]]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $range = "'{$sheetName}'!A:AB"; 

        $this->sheetsService->spreadsheets_values->append($this->spreadsheetId, $range, $body, $params);
    }

    // 5. DELETE DATA
    public function deleteData($row, $sheetName = 'SC Sudah Bayar')
    {
        if (empty($this->spreadsheetId)) throw new \Exception('Spreadsheet ID missing');

        $sheetId = $this->getSheetIdFromTitle($sheetName);
        if ($sheetId === null) {
            throw new \Exception("Sheet '{$sheetName}' not found.");
        }

        $requests = [
            new Google_Service_Sheets_Request([
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

        $batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(['requests' => $requests]);
        $this->sheetsService->spreadsheets->batchUpdate($this->spreadsheetId, $batchUpdateRequest);
    }

    // 6. BATCH UPDATE (WRITE)
    public function batchUpdate($updates, $sheetName = 'SC Sudah Bayar')
    {
        try {
            if (empty($this->spreadsheetId)) throw new \Exception('Spreadsheet ID missing');

            $data = [];
            foreach ($updates as $range => $value) {
                $actualRange = str_replace('{sheet}', $sheetName, $range);
                $data[] = new Google_Service_Sheets_ValueRange([
                    'range' => $actualRange,
                    'values' => [[$value]]
                ]);
            }

            if (empty($data)) return;

            $batchBody = new Google_Service_Sheets_BatchUpdateValuesRequest([
                'data' => $data,
                'valueInputOption' => 'USER_ENTERED'
            ]);

            return $this->sheetsService->spreadsheets_values->batchUpdate($this->spreadsheetId, $batchBody);
        } catch (\Exception $e) {
            \Log::error('Batch update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    // 7. LIST FILES
    public function listSpreadsheetsInFolder($folderId)
    {
        $query = "mimeType = 'application/vnd.google-apps.spreadsheet' and trashed = false and '{$folderId}' in parents";
        $optParams = ['q' => $query, 'spaces' => 'drive', 'fields' => 'files(id, name)', 'pageSize' => 100];

        try {
            $results = $this->driveService->files->listFiles($optParams);
            $spreadsheets = [];
            foreach ($results->getFiles() as $file) {
                $spreadsheets[] = ['id' => $file->getId(), 'name' => $file->getName()];
            }
            return $spreadsheets;
        } catch (\Exception $e) {
            throw new \Exception('Error fetching drive files: ' . $e->getMessage());
        }
    }
}