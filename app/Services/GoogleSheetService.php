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

            // Force IPv4 & timeout stability
            $client->setHttpClient(new \GuzzleHttp\Client([
                'force_ip_resolve' => 'v4',
                'verify' => false,
                'timeout' => 30,
                'connect_timeout' => 10
            ]));

            $this->sheetsService = new Google_Service_Sheets($client);
            $this->driveService  = new Google_Service_Drive($client);

            // =====================================================
            // DYNAMIC SPREADSHEET ID
            // =====================================================
            $targetId = null;

            // 1️⃣ Session year
            if (session()->has('selected_year')) {
                $year = session('selected_year');
                $setting = Setting::where('key', 'google_sheet_id_' . $year)->first();
                if ($setting) {
                    $targetId = $setting->value;
                }
            }

            // 2️⃣ Latest year from DB
            if (!$targetId) {
                $all = Setting::where('key', 'like', 'google_sheet_id_%')->get();
                $latestYear = 0;
                foreach ($all as $s) {
                    if (preg_match('/_(\d{4})$/', $s->key, $m)) {
                        if ((int)$m[1] > $latestYear) {
                            $latestYear = (int)$m[1];
                            $targetId = $s->value;
                        }
                    }
                }
            }

            // 3️⃣ Fallback
            $this->spreadsheetId = $targetId
                ?? optional(Setting::where('key', 'google_sheet_id')->first())->value
                ?? config('services.google.sheet_id')
                ?? env('GOOGLE_SHEET_ID');

            if (!$this->spreadsheetId) {
                \Log::warning('Spreadsheet ID not configured.');
            }

        } catch (\Exception $e) {
            \Log::error('GoogleSheetService init error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * =====================================================
     * GET DATA (FULL – NO LIMIT)
     * =====================================================
     */
    public function getData(
        ?string $spreadsheetId = null,
        string $sheetName = 'SC Sudah Bayar',
        string $range = 'A4:BA'
    ): array {
        try {
            $id = $spreadsheetId ?? $this->spreadsheetId;
            if (!$id) {
                throw new \Exception('Spreadsheet ID missing.');
            }

            $finalRange = "'{$sheetName}'!{$range}";

            $response = $this->sheetsService
                ->spreadsheets_values
                ->get($id, $finalRange);

            return $response->getValues() ?? [];
        } catch (\Exception $e) {
            \Log::error('Error fetching sheet data: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * =====================================================
     * BATCH GET
     * =====================================================
     */
    public function getBatchData(array $ranges): array
    {
        try {
            if (!$this->spreadsheetId) {
                throw new \Exception('Spreadsheet ID missing.');
            }

            $result = $this->sheetsService
                ->spreadsheets_values
                ->batchGet($this->spreadsheetId, ['ranges' => $ranges]);

            $mapped = [];
            foreach ($ranges as $i => $range) {
                $mapped[$range] = $result->getValueRanges()[$i]->getValues() ?? [];
            }

            return $mapped;
        } catch (\Exception $e) {
            \Log::error('Batch get failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * UPDATE SINGLE ROW
     */
    public function updateData(int $row, array $data, string $sheetName = 'SC Sudah Bayar')
    {
        $range = "'{$sheetName}'!A{$row}:BA{$row}";
        $body  = new Google_Service_Sheets_ValueRange(['values' => [$data]]);

        $this->sheetsService->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    /**
     * APPEND DATA
     */
    public function storeData(array $data, string $sheetName = 'SC Sudah Bayar')
    {
        $range = "'{$sheetName}'!A:BA";
        $body  = new Google_Service_Sheets_ValueRange(['values' => [$data]]);

        $this->sheetsService->spreadsheets_values->append(
            $this->spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'USER_ENTERED']
        );
    }

    /**
     * DELETE ROW
     */
    public function deleteData(int $row, string $sheetName = 'SC Sudah Bayar')
    {
        $sheetId = $this->getSheetIdFromTitle($sheetName);
        if ($sheetId === null) {
            throw new \Exception("Sheet {$sheetName} not found.");
        }

        $request = new Google_Service_Sheets_Request([
            'deleteDimension' => [
                'range' => [
                    'sheetId' => $sheetId,
                    'dimension' => 'ROWS',
                    'startIndex' => $row - 1,
                    'endIndex'   => $row
                ]
            ]
        ]);

        $this->sheetsService->spreadsheets->batchUpdate(
            $this->spreadsheetId,
            new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                'requests' => [$request]
            ])
        );
    }

    /**
     * HELPER: GET SHEET ID BY NAME
     */
    private function getSheetIdFromTitle(string $sheetName): ?int
    {
        $spreadsheet = $this->sheetsService->spreadsheets->get($this->spreadsheetId);
        foreach ($spreadsheet->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheet->getProperties()->getSheetId();
            }
        }
        return null;
    }

    /**
     * LIST SPREADSHEETS IN FOLDER
     */
    public function listSpreadsheetsInFolder(string $folderId): array
    {
        $query = "mimeType='application/vnd.google-apps.spreadsheet' and trashed=false and '{$folderId}' in parents";
        $files = $this->driveService->files->listFiles([
            'q' => $query,
            'fields' => 'files(id,name)',
            'pageSize' => 100
        ]);

        return collect($files->getFiles())->map(fn($f) => [
            'id' => $f->getId(),
            'name' => $f->getName()
        ])->toArray();
    }
}
