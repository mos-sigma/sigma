<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\Cluster\UpdateCloudflareIps;
use App\Jobs\Notifications\CleanNotifications;
use App\Models\Cluster;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

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
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new CleanNotifications())
            ->before(fn () => $this->logTaskStart(\App\Jobs\Notifications\CleanNotifications::class))
            ->onFailure(fn () => $this->logTaskFailure(\App\Jobs\Notifications\CleanNotifications::class))
            ->onSuccess(fn () => $this->logTaskSuccess(\App\Jobs\Notifications\CleanNotifications::class))
            ->daily()
            ->onOneServer();

        $schedule->command('telescope:prune --hours=48')
            ->before(fn () => $this->logTaskStart('telescope:prune --hours=48'))
            ->onFailure(fn () => $this->logTaskFailure('telescope:prune --hours=48'))
            ->onSuccess(fn () => $this->logTaskSuccess('telescope:prune --hours=48'))
            ->daily()
            ->onOneServer();


        if (config('app.env') === 'production') {
            $schedule->command('backup:run --only-db')->daily()->at('01:30')->onOneServer();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        include base_path('routes/console.php');
    }

    private function logTaskStart(string $task)
    {
        Log::info('Schedule Task started', ['task' => $task]);
    }

    private function logTaskSuccess(string $task)
    {
        Log::info('Schedule Task is complete', ['task' => $task]);
    }

    private function logTaskFailure(string $task)
    {
        Log::info('Schedule Task failed', ['task' => $task]);
    }
}
