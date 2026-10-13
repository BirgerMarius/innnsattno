<?php

namespace App\Console;

use App\Console\Commands\DiscoverSchibstedFootball;
use App\Console\Commands\ExploreSchibstedFootball;
use App\Console\Commands\ProbeSchibstedFootball;
use App\Console\Commands\FetchNews;
use App\Console\Commands\FetchCorrectionalNews;
use App\Console\Commands\PruneNewsArticles;
use App\Console\Commands\SendExternalDataFailureDigest;
use App\Console\Commands\PruneExternalDataSourceEvents;
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
        DiscoverSchibstedFootball::class,
        ExploreSchibstedFootball::class,
        ProbeSchibstedFootball::class,
        FetchNews::class,
        FetchCorrectionalNews::class,
        PruneNewsArticles::class,
        SendExternalDataFailureDigest::class,
        PruneExternalDataSourceEvents::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // RSS feeds are not time-critical enough to justify polling every 15/30 minutes.
        $schedule->command('correctional-news:fetch --source=kdi')->hourly()->withoutOverlapping();
        $schedule->command('correctional-news:fetch --source=nff')->hourly()->withoutOverlapping();
        $schedule->command('correctional-news:fetch --source=ky')->hourly()->withoutOverlapping();
        $schedule->command('correctional-news:fetch --source=sivilombudet')->hourly()->withoutOverlapping();
        $schedule->command('news:prune')->hourly()->withoutOverlapping();
        $schedule->command('external-data:send-failure-digest')->dailyAt('06:45')->timezone('Europe/Oslo')->withoutOverlapping();
        $schedule->command('external-data:prune-events')->dailyAt('07:05')->timezone('Europe/Oslo')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
