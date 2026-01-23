<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Date::use(Carbon::class);
        // Customize mail components to use our own styling
        Mail::alwaysFrom(env('MAIL_FROM_ADDRESS', 'noreply@ma3rood.com'), env('MAIL_FROM_NAME', 'Ma3rood'));
        
        // Override default mail components
        $this->publishes([
            __DIR__.'/../../resources/views/vendor/mail' => resource_path('views/vendor/mail'),
        ], 'laravel-mail');
    }
}
