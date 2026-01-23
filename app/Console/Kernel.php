<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // 🏁 Schedule the auction closing command every minute
        // $schedule->command('auctions:close')->everyMinute();
        $schedule->command('app:close-expired-listings')->everyMinute();
        $schedule->command('offers:expire')->everyMinute();

        // * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1

    }
}
