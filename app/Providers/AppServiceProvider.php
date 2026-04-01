<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <--- ESSA LINHA É ESSENCIAL

class AppServiceProvider extends ServiceProvider {
    public function boot(): void {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}