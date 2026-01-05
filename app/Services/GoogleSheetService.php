<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Client();
        // Mengambil path file json dari folder storage
        $this->client->setAuthConfig(storage_path('app/google/service-account.json'));
        $this->client->addScope(Sheets::SPREADSHEETS);
        
        $this->service = new Sheets($this->client);
    }

    public function readSheet($spreadsheetId, $range)
    {
        $response = $this->service->spreadsheets_values->get($spreadsheetId, $range);
        return $response->getValues();
    }

    public function updateSheet($spreadsheetId, $range, $values)
    {
        $body = new Sheets\ValueRange([
            'values' => $values
        ]);
        $params = ['valueInputOption' => 'RAW'];

        return $this->service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
    }
}