<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleSheetService;
use App\Models\Kontrak;
use Carbon\Carbon;

class SyncDriveFolder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:drive-folder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync spreadsheets from Google Drive folder to database';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSheetService $googleSheetService)
    {
        $folderId = '16YHxer1RjXTmoNTY6NoUC5BjGJXzt2ja';

        try {
            $this->info('🔍 Fetching spreadsheets from Drive folder...');
            $spreadsheets = $googleSheetService->listSpreadsheetsInFolder($folderId);

            if (empty($spreadsheets)) {
                $this->warn('⚠️  No spreadsheets found in the folder.');
                return Command::SUCCESS;
            }

            $this->info("📊 Found " . count($spreadsheets) . " spreadsheet(s)");
            $progressBar = $this->output->createProgressBar(count($spreadsheets));
            $progressBar->start();

            foreach ($spreadsheets as $spreadsheet) {
                $fileId = $spreadsheet['id'];
                $fileName = $spreadsheet['name'];

                $this->line("\n📄 Processing file: {$fileName}");

                try {
                    // Get data from spreadsheet
                    $rows = $googleSheetService->getData($fileId);

                    if (empty($rows)) {
                        $this->warn("   ⚠️  No data found in {$fileName}");
                        $progressBar->advance();
                        continue;
                    }

                    $syncCount = 0;
                    $errorCount = 0;

                    // Loop through rows starting from index 4 (skip headers)
                    foreach ($rows as $index => $row) {
                        $rowNumber = $index + 4;

                        // Map columns (0-52) exactly like in SheetController::index
                        $H = $row[7] ?? '';   // loex
                        $I = $row[8] ?? '';   // nomor_kontrak
                        $J = $row[9] ?? '';   // nama_pembeli
                        $K = $row[10] ?? '';  // tgl_kontrak
                        $L = $row[11] ?? '';  // volume
                        $M = $row[12] ?? '';  // harga
                        $N = $row[13] ?? '';  // nilai
                        $O = $row[14] ?? '';  // inc_ppn
                        $P = $row[15] ?? '';  // tgl_bayar
                        $Q = $row[16] ?? '';  // unit
                        $R = $row[17] ?? '';  // mutu
                        $S = $row[18] ?? '';  // nomor_dosi
                        $T = $row[19] ?? '';  // tgl_dosi
                        $U = $row[20] ?? '';  // port
                        $V = $row[21] ?? '';  // kontrak_sap
                        $W = $row[22] ?? '';  // dp_sap
                        $X = $row[23] ?? '';  // so_sap
                        $Y = $row[24] ?? '';  // kode_do
                        $Z = $row[25] ?? '';  // sisa_awal
                        $AA = $row[26] ?? ''; // total_layan
                        $AB = $row[27] ?? ''; // sisa_akhir
                        $BA = $row[52] ?? ''; // jatuh_tempo

                        // Skip if nomor_kontrak is empty
                        if (empty($I)) {
                            continue;
                        }

                        try {
                            // Clean number format (remove dots for thousands separator)
                            $volume = !empty($L) ? (float) str_replace(['.', ','], ['', '.'], $L) : null;
                            $harga = !empty($M) ? (float) str_replace(['.', ','], ['', '.'], $M) : null;
                            $nilai = !empty($N) ? (float) str_replace(['.', ','], ['', '.'], $N) : null;
                            $sisaAwal = !empty($Z) ? (float) str_replace(['.', ','], ['', '.'], $Z) : null;
                            $totalLayan = !empty($AA) ? (float) str_replace(['.', ','], ['', '.'], $AA) : null;
                            $sisaAkhir = !empty($AB) ? (float) str_replace(['.', ','], ['', '.'], $AB) : null;

                            // Parse dates - try multiple common formats (e.g. 10/09/2024, 10-Sep-2024, Y-m-d)
                            $dateFormats = ['d/m/Y', 'd-M-Y', 'd-M-y', 'd F Y', 'Y-m-d'];

                            $tglKontrak = null;
                            if (!empty($K)) {
                                foreach ($dateFormats as $fmt) {
                                    try {
                                        $d = Carbon::createFromFormat($fmt, trim($K));
                                        $tglKontrak = $d->format('Y-m-d');
                                        break;
                                    } catch (\Exception $e) {
                                        // continue trying
                                    }
                                }
                                if (!$tglKontrak) {
                                    try {
                                        $d = Carbon::parse($K);
                                        $tglKontrak = $d->format('Y-m-d');
                                    } catch (\Exception $e) { $tglKontrak = null; }
                                }
                            }

                            $tglBayar = null;
                            if (!empty($P)) {
                                foreach ($dateFormats as $fmt) {
                                    try {
                                        $d = Carbon::createFromFormat($fmt, trim($P));
                                        $tglBayar = $d->format('Y-m-d');
                                        break;
                                    } catch (\Exception $e) {}
                                }
                                if (!$tglBayar) {
                                    try { $tglBayar = Carbon::parse($P)->format('Y-m-d'); } catch (\Exception $e) { $tglBayar = null; }
                                }
                            }

                            $tglDosi = null;
                            if (!empty($T)) {
                                foreach ($dateFormats as $fmt) {
                                    try {
                                        $d = Carbon::createFromFormat($fmt, trim($T));
                                        $tglDosi = $d->format('Y-m-d');
                                        break;
                                    } catch (\Exception $e) {}
                                }
                                if (!$tglDosi) {
                                    try { $tglDosi = Carbon::parse($T)->format('Y-m-d'); } catch (\Exception $e) { $tglDosi = null; }
                                }
                            }

                            $jatuhTempo = null;
                            if (!empty($BA)) {
                                foreach ($dateFormats as $fmt) {
                                    try {
                                        $d = Carbon::createFromFormat($fmt, trim($BA));
                                        $jatuhTempo = $d->format('Y-m-d');
                                        break;
                                    } catch (\Exception $e) {}
                                }
                                if (!$jatuhTempo) {
                                    try { $jatuhTempo = Carbon::parse($BA)->format('Y-m-d'); } catch (\Exception $e) { $jatuhTempo = null; }
                                }
                            }

                            // Use updateOrCreate to avoid duplicates
                            Kontrak::updateOrCreate(
                                ['nomor_kontrak' => $I],
                                [
                                    'loex' => $H,
                                    'nama_pembeli' => $J,
                                    'tgl_kontrak' => $tglKontrak,
                                    'volume' => $volume,
                                    'harga' => $harga,
                                    'nilai' => $nilai,
                                    'inc_ppn' => $O,
                                    'tgl_bayar' => $tglBayar,
                                    'unit' => $Q,
                                    'mutu' => $R,
                                    'nomor_dosi' => $S,
                                    'tgl_dosi' => $tglDosi,
                                    'port' => $U,
                                    'kontrak_sap' => $V,
                                    'dp_sap' => $W,
                                    'so_sap' => $X,
                                    'kode_do' => $Y,
                                    'sisa_awal' => $sisaAwal,
                                    'total_layan' => $totalLayan,
                                    'sisa_akhir' => $sisaAkhir,
                                    'jatuh_tempo' => $jatuhTempo,
                                    'origin_file' => $fileName,
                                ]
                            );

                            $syncCount++;
                        } catch (\Exception $e) {
                            $this->warn("   ❌ Error at row {$rowNumber}: " . $e->getMessage());
                            $errorCount++;
                        }
                    }

                    $this->line("   ✅ Synced {$syncCount} records" . ($errorCount > 0 ? " ({$errorCount} errors)" : ""));

                } catch (\Exception $e) {
                    $this->error("❌ Error processing {$fileName}: " . $e->getMessage());
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->info("\n\n✨ Sync completed successfully!");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
