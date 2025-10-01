<?php

namespace App\Console;

use App\Services\FileService;
use App\Services\UserService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            FileService::forceRemoveDueTrashedFiles();
        })->everyMinute();

        $schedule->call(function () {
            UserService::updateStatusesOfUsers();
        })->daily();

        $schedule->call(function () {
            UserService::sendSubscriptionReminder();
        })->daily();

        $schedule->call(function () {
            UserService::updateStatusesOfUsers();
        })->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
