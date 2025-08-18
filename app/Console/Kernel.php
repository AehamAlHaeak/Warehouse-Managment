<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new \App\Jobs\chec_inv_periodicaly)->everyTwoHours();
        $schedule->job(new \App\Jobs\calculate_sold_quantity_freq)->monthlyOn(15, '13:00');

    }

    
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
