<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View; // Import View facade
use App\Models\Setting; // Import Setting model

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // ... existing boot code ...

        // Share year data to all views
        View::composer('*', function ($view) {
            // Fetch available years from settings (keys starting with google_sheet_id_)
            $availableYears = [];
            
            try {
                $settings = Setting::where('key', 'like', 'google_sheet_id_%')->get();
                
                foreach($settings as $s) {
                    // Extract year from key (google_sheet_id_2026 -> 2026)
                    $parts = explode('_', $s->key);
                    $year = end($parts);
                    
                    if (is_numeric($year) && strlen($year) == 4) {
                        $availableYears[] = $year;
                    }
                }
                
                // Sort years descending (newest first)
                rsort($availableYears);

            } catch (\Exception $e) {
                // Fail silently if table not ready (e.g., during migration)
                $availableYears = [];
            }

            // Share variables with view
            $view->with('sharedAvailableYears', $availableYears);
            $view->with('sharedCurrentYear', session('selected_year', 'Default'));
        });
    }
}