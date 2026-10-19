<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /** Populate the same local data that the scheduler provides in production. */
    protected function warmTv(\Carbon\CarbonInterface $start, int $days = 1, string $prison = 'ringerike'): void
    {
        $service = app(\App\Services\TvGuideService::class);
        $channels = $prison === 'ilseng' ? $service::ilsengChannels() : $service::ringerikeChannels();
        for ($i = 0; $i < $days; $i++) {
            $service->refreshSchedule($start->copy()->addDays($i), $channels, $prison);
        }
    }

    protected function warmCompetition(int $seasonId): void
    {
        $class = match ($seasonId) {
            8766 => \App\Services\EliteserienService::class,
            9186 => \App\Services\PremierLeagueService::class,
            9168 => \App\Services\ChampionsLeagueService::class,
            9170 => \App\Services\EuropaLeagueService::class,
            9171 => \App\Services\ConferenceLeagueService::class,
            8889 => \App\Services\NationsLeagueService::class,
        };
        app($class)->refreshCompetitionData();
    }
}
