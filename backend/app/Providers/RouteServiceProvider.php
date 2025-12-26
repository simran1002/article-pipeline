<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $basePath = dirname(__DIR__, 2);
        
        // Load routes directly
        if (file_exists($basePath . '/routes/api.php')) {
            Route::middleware('api')
                ->prefix('api')
                ->group($basePath . '/routes/api.php');
        }

        if (file_exists($basePath . '/routes/web.php')) {
            Route::middleware('web')
                ->group($basePath . '/routes/web.php');
        }
    }
}
