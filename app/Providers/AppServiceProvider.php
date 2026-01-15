<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('*', function ($view) {
            
            $availableYears = [];
            $latestYear = '2025'; // Default fallback ke tahun sekarang jika DB kosong

            try {
                // Ambil semua setting tahunan
                $settings = Setting::where('key', 'like', 'google_sheet_id_%')->get();
                
                foreach($settings as $s) {
                    $parts = explode('_', $s->key);
                    $year = end($parts);
                    
                    if (is_numeric($year) && strlen($year) == 4) {
                        $availableYears[] = $year;
                    }
                }
                
                // Urutkan (Terbaru Paling Atas)
                rsort($availableYears);
                
                // Ambil tahun terbaru yang nyata dari database
                if (!empty($availableYears)) {
                    $latestYear = $availableYears[0];
                }

            } catch (\Exception $e) {
                // Fail silent
            }

            // Cek Session
            $sessionYear = session('selected_year');
            
            // Logic Display:
            // Jika user belum pilih tahun (session kosong), kita anggap statusnya 'default'
            // Tapi nanti di View, label 'default' itu akan kita ganti teksnya jadi tahun terbaru.
            $currentDisplay = $sessionYear ? $sessionYear : 'default';

            $view->with('sharedAvailableYears', $availableYears);
            $view->with('sharedCurrentYear', $currentDisplay);
            $view->with('sharedLatestYear', $latestYear); // <--- INI VARIABEL BARUNYA
        });
    }
}