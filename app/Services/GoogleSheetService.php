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
        try {
            // Load credentials path
            $credentialsPath = storage_path('app/google/service-account.json');
            
            // Validate credentials file exists
            if (!file_exists($credentialsPath)) {
                throw new \Exception("Service account credentials file not found at: {$credentialsPath}");
            }
            
            $client = new Google_Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(Google_Service_Sheets::SPREADSHEETS);
            $client->addScope(Google_Service_Drive::DRIVE_READONLY);

            $this->sheetsService = new Google_Service_Sheets($client);
            $this->driveService = new Google_Service_Drive($client);
            
            // Load spreadsheet ID - try multiple methods
            $this->spreadsheetId = config('services.google.sheet_id') 
                                  ?? env('GOOGLE_SHEET_ID')
                                  ?? $_ENV['GOOGLE_SHEET_ID']
                                  ?? null;
            
            // Debug log
            \Log::info('GoogleSheetService initialized - Sheet ID: ' . ($this->spreadsheetId ? 'SET' : 'NOT SET'));
            
            // Throw error jika spreadsheetId tidak ditemukan
            if (!$this->spreadsheetId) {
                throw new \Exception('GOOGLE_SHEET_ID is not configured. Please add it to .env file with key: GOOGLE_SHEET_ID');
            }
        } catch (\Exception $e) {
            \Log::error('GoogleSheetService initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    // Mengambil data dari sheet tertentu dengan error handling yang lebih baik
    public function getData($spreadsheetId = null, $range = "'SC Sudah Bayar'!A4:BA")
    {
        try {
            $id = $spreadsheetId ?? $this->spreadsheetId;
            
            // Validate spreadsheetId
            if (empty($id)) {
                throw new \Exception('Spreadsheet ID is empty. Please check GOOGLE_SHEET_ID in .env');
            }
            
            \Log::info('Fetching data from Google Sheets - ID: ' . $id . ' - Range: ' . $range);
            
            $response = $this->sheetsService->spreadsheets_values->get($id, $range);
            $values = $response->getValues() ?? [];
            
            if (empty($values)) {
                \Log::warning('Empty response from Google Sheets for range: ' . $range);
            }
            
            return $values;
        } catch (\Exception $e) {
            \Log::error('Error fetching data from Google Sheets - Range: ' . $range . ' - Error: ' . $e->getMessage());
            throw $e;
        }
    }

    // Fungsi helper untuk get multiple ranges sekaligus (batch)
    public function getBatchData($ranges)
    {
        try {
            $response = $this->sheetsService->spreadsheets_values->batchGet($this->spreadsheetId, ['ranges' => $ranges]);
            
            $result = [];
            $valueRanges = $response->getValueRanges();
            
            if (!empty($valueRanges)) {
                foreach ($valueRanges as $index => $valueRange) {
                    $result[$ranges[$index]] = $valueRange->getValues() ?? [];
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            \Log::error('Error fetching batch data from Google Sheets: ' . $e->getMessage());
            return [];
        }
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

    /**
     * Batch update multiple cells - update hanya kolom yang berubah
     * Format: ['range' => value, 'range2' => value2]
     * Range contoh: "'{sheet}'!H4" atau "'{sheet}'!I5:J5"
     */
    public function batchUpdate($updates, $sheetName = 'SC Sudah Bayar')
    {
        try {
            $body = new \Google_Service_Sheets_ValueRange();
            $data = [];

            // Build requests untuk setiap range
            foreach ($updates as $range => $value) {
                // Replace '{sheet}' dengan actual sheet name
                $actualRange = str_replace('{sheet}', $sheetName, $range);
                
                $valueRange = new \Google_Service_Sheets_ValueRange([
                    'range' => $actualRange,
                    'values' => [[$value]]  // Wrap dalam array untuk single cell
                ]);
                $data[] = $valueRange;
            }

            if (empty($data)) {
                throw new \Exception('No data to update');
            }

            // Use batchUpdate untuk update multiple ranges
            $batchBody = new \Google_Service_Sheets_BatchUpdateValuesRequest([
                'data' => $data,
                'valueInputOption' => 'USER_ENTERED'
            ]);

            $response = $this->sheetsService->spreadsheets_values->batchUpdate(
                $this->spreadsheetId,
                $batchBody
            );

            \Log::info('Batch update successful - Response: ' . json_encode($response));
            return $response;
        } catch (\Exception $e) {
            \Log::error('Batch update failed: ' . $e->getMessage());
            throw $e;
        }
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