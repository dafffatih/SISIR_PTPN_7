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

            // Add Guzzle Client for stability (IPv4 force)
            $guzzleClient = new \GuzzleHttp\Client([
                'force_ip_resolve' => 'v4',
                'verify' => false, 
                'timeout' => 30,
                'connect_timeout' => 10
            ]);
            $client->setHttpClient($guzzleClient);

            $this->sheetsService = new Google_Service_Sheets($client);
            $this->driveService = new Google_Service_Drive($client);
            
            // ---------------------------------------------------------
            // 2. DYNAMIC SPREADSHEET ID LOGIC (UPDATED)
            // ---------------------------------------------------------
            
            $targetId = null;

            // A. Cek apakah User memilih Tahun di Session (Prioritas 1)
            if (session()->has('selected_year')) {
                $year = session('selected_year');
                $key  = 'google_sheet_id_' . $year;
                
                // Find ID for that year in DB
                $settingYear = Setting::where('key', $key)->first();
                if ($settingYear) {
                    $targetId = $settingYear->value;
                }
            }

            // B. Jika Session Kosong => Cari Tahun TERBARU di Database (Prioritas 2)
            if (!$targetId) {
                // Ambil semua setting yang formatnya google_sheet_id_XXXX
                $allSettings = Setting::where('key', 'like', 'google_sheet_id_%')->get();
                
                $latestYear = 0;
                $latestId = null;

                foreach ($allSettings as $setting) {
                    // Regex untuk ambil angka tahun dari key (misal: google_sheet_id_2026 -> 2026)
                    if (preg_match('/^google_sheet_id_(\d{4})$/', $setting->key, $matches)) {
                        $currentYear = (int)$matches[1];
                        
                        // Logika mencari tahun terbesar (terbaru)
                        if ($currentYear > $latestYear) {
                            $latestYear = $currentYear;
                            $latestId = $setting->value;
                        }
                    }
                }

                if ($latestId) {
                    $targetId = $latestId;
                } else {
                    // Fallback ke key 'google_sheet_id' polos (tanpa tahun) jika tidak ada data tahunan
                    $settingDefault = Setting::where('key', 'google_sheet_id')->first();
                    $targetId = $settingDefault ? $settingDefault->value : null;
                }
            }

            // C. Fallback Terakhir ke Config / Env (Prioritas 3)
            $this->spreadsheetId = $targetId 
                                ?? config('services.google.sheet_id') 
                                ?? env('GOOGLE_SHEET_ID')
                                ?? null;
            
            if (!$this->spreadsheetId) {
                \Log::warning('GOOGLE_SHEET_ID belum dikonfigurasi.');
            }

        } catch (\Exception $e) {
            \Log::error('GoogleSheetService initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fitur Ganti ID Spreadsheet Berdasarkan Tahun
     * Dipanggil di Controller: $sheetService->setSheetByYear('2024');
     */
    public function setSheetByYear($year)
    {
        // Cek Database untuk key spesifik, misal: 'google_sheet_id_2024'
        $key = 'google_sheet_id_' . $year;
        $setting = Setting::where('key', $key)->first();

        if ($setting && !empty($setting->value)) {
            // Jika ketemu, TIMPA spreadsheetId yang sedang aktif
            $this->spreadsheetId = $setting->value;
            \Log::info("GoogleSheetService switched to Year: {$year} (ID: {$this->spreadsheetId})");
        } else {
            \Log::warning("Spreadsheet ID untuk tahun {$year} tidak ditemukan. Menggunakan ID Default/Env.");
        }
        
        return $this; // Return $this agar bisa chaining method
    }

    // Helper: Cari Sheet ID (Angka) berdasarkan Nama Sheet (String)
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

    // 2. GET BATCH DATA (Fixed & Optimized)
    public function getBatchData($ranges)
    {
        try {
            if (empty($this->spreadsheetId)) throw new \Exception('Spreadsheet ID missing');

            $params = ['ranges' => $ranges];
            
            // Menggunakan method bawaan Google: batchGet
            $result = $this->sheetsService->spreadsheets_values->batchGet($this->spreadsheetId, $params);
            
            // Mapping hasil agar Key Array sesuai dengan Range yang diminta
            $mappedData = [];
            $valueRanges = $result->getValueRanges();

            foreach ($ranges as $index => $range) {
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

    // 4. STORE DATA (APPEND)
    public function storeData($data, $sheetName = 'SC Sudah Bayar')
    {
        if (empty($this->spreadsheetId)) throw new \Exception('Spreadsheet ID missing');

        $body = new Google_Service_Sheets_ValueRange(['values' => [$data]]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        $range = "'{$sheetName}'!A:AB"; 

        $this->sheetsService->spreadsheets_values->append($this->spreadsheetId, $range, $body, $params);
    }

    // 5. DELETE DATA (Dinamis cari ID Sheet)
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

    // 6. BATCH UPDATE (WRITE - Multiple Cells)
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

    // 7. LIST FILES IN DRIVE FOLDER
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