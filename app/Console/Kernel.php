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
        $schedule->command('tickets:check-escalations')->everyMinute();
        $schedule->command('check:inactive-users-employees')->daily();
        $schedule->command('queue:restart')
            ->everySixHours();

        // Queue workers should run persistently using Supervisor/Horizon, but fallback in case
        $schedule->command('queue:work --queue=default,retries --daemon --tries=3 --timeout=180')
            ->everyMinute()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/queue_worker.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}