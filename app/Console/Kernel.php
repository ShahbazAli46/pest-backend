<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('sal_com:generate')->monthlyOn(1, '00:00'); // Runs on the 1st of each month at midnight
        // $schedule->command('invoices:send-monthly')->monthlyOn(1, '00:00'); // Runs on the 1st of each month at midnight
        // $schedule->command('invoices:send-daily')->dailyAt('00:00');
        $schedule->command('contracts:check-expiration')->dailyAt('00:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
