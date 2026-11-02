<?php

namespace Tests\Feature;

use App\Services\BundesligaService;
use App\Services\ChampionsLeagueService;
use App\Services\ConferenceLeagueService;
use App\Services\CorrectionalNewsService;
use App\Services\EliteserienService;
use App\Services\EuropaLeagueService;
use App\Services\FootballWorldCupService;
use App\Services\LaLigaService;
use App\Services\LocalNewsService;
use App\Services\NamedayService;
use App\Services\NationsLeagueService;
use App\Services\News\NewsFeedService;
use App\Services\ObosLigaenService;
use App\Services\PrayerTimeService;
use App\Services\PremierLeagueService;
use App\Services\TodayContentService;
use App\Services\TvGuideService;
use App\Services\WeatherForecastService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ManualExternalDataRefreshCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prison_refresh_is_limited_to_the_selected_prison_and_skips_shared_data(): void
    {
        $this->app->instance(TvGuideService::class, $this->makeMock(TvGuideService::class, function ($mock) {
            $mock->shouldReceive('refreshSchedule')->once()->andReturnTrue();
        }));
        $this->app->instance(LocalNewsService::class, $this->makeMock(LocalNewsService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()->with('ringerike')));
        $this->app->instance(WeatherForecastService::class, $this->makeMock(WeatherForecastService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()->with('ringerike')));
        $this->app->instance(PrayerTimeService::class, $this->makeMock(PrayerTimeService::class, fn ($mock) => $mock->shouldReceive('refreshMonth')->once()));

        $this->artisan('external-data:refresh', ['--prison' => 'ringerike'])
            ->expectsOutputToContain('Felles')
            ->assertExitCode(0);
    }

    public function test_kongsvinger_refresh_uses_its_verified_prayer_location(): void
    {
        $this->app->instance(TvGuideService::class, $this->makeMock(TvGuideService::class, fn ($mock) => $mock->shouldReceive('refreshSchedule')->once()->andReturnTrue()));
        $this->app->instance(LocalNewsService::class, $this->makeMock(LocalNewsService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()->with('kongsvinger')));
        $this->app->instance(WeatherForecastService::class, $this->makeMock(WeatherForecastService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()->with('kongsvinger')));
        $this->app->instance(PrayerTimeService::class, $this->makeMock(PrayerTimeService::class, fn ($mock) => $mock->shouldReceive('refreshMonth')->once()->with(153, Mockery::type('int'), Mockery::type('int'))));

        $this->artisan('external-data:refresh', ['--prison' => 'kongsvinger'])->assertExitCode(0);
    }

    public function test_all_refreshes_every_prison_and_shared_data_once(): void
    {
        $this->app->instance(TvGuideService::class, $this->makeMock(TvGuideService::class, fn ($mock) => $mock->shouldReceive('refreshSchedule')->times(3)->andReturnTrue()));
        $this->app->instance(LocalNewsService::class, $this->makeMock(LocalNewsService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()->with('ringerike')->ordered()->shouldReceive('refresh')->once()->with('ilseng')->ordered()->shouldReceive('refresh')->once()->with('kongsvinger')->ordered()));
        $this->app->instance(WeatherForecastService::class, $this->makeMock(WeatherForecastService::class, fn ($mock) => $mock->shouldReceive('refresh')->times(3)));
        $this->app->instance(PrayerTimeService::class, $this->makeMock(PrayerTimeService::class, fn ($mock) => $mock->shouldReceive('refreshMonth')->times(3)));
        foreach ([PremierLeagueService::class, EliteserienService::class, ObosLigaenService::class, LaLigaService::class, BundesligaService::class, ChampionsLeagueService::class, EuropaLeagueService::class, ConferenceLeagueService::class, NationsLeagueService::class] as $class) {
            $this->app->instance($class, $this->makeMock($class, function ($mock) use ($class) {
                $mock->shouldReceive('competitionKey')->once()->andReturn(class_basename($class));
                $mock->shouldReceive('refreshCompetitionData')->once()->andReturnTrue();
            }));
        }
        $this->app->instance(FootballWorldCupService::class, $this->makeMock(FootballWorldCupService::class, fn ($mock) => $mock->shouldReceive('refreshEndpoint')->once()->with('schedule')->andReturnTrue()->shouldReceive('refreshEndpoint')->once()->with('standings')->andReturnTrue()));
        $this->app->instance(NamedayService::class, $this->makeMock(NamedayService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()));
        $this->app->instance(TodayContentService::class, $this->makeMock(TodayContentService::class, fn ($mock) => $mock->shouldReceive('forDate')->once()->andReturn([])));
        $this->app->instance(CorrectionalNewsService::class, $this->makeMock(CorrectionalNewsService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()->andReturn([])));
        $this->app->instance(NewsFeedService::class, $this->makeMock(NewsFeedService::class, fn ($mock) => $mock->shouldReceive('fetchActive')->once()->andReturn([])));

        $this->artisan('external-data:refresh', ['--all' => true])->assertExitCode(0);
    }

    public function test_invalid_combinations_and_source_failures_return_non_zero(): void
    {
        $this->artisan('external-data:refresh', ['--all' => true, '--prison' => 'ringerike'])->assertExitCode(2);
        $this->artisan('external-data:refresh', ['--prison' => 'ukjent'])->assertExitCode(2);

        $this->app->instance(TvGuideService::class, $this->makeMock(TvGuideService::class, fn ($mock) => $mock->shouldReceive('refreshSchedule')->once()->andReturnFalse()));
        $this->app->instance(LocalNewsService::class, $this->makeMock(LocalNewsService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()));
        $this->app->instance(WeatherForecastService::class, $this->makeMock(WeatherForecastService::class, fn ($mock) => $mock->shouldReceive('refresh')->once()));
        $this->app->instance(PrayerTimeService::class, $this->makeMock(PrayerTimeService::class, fn ($mock) => $mock->shouldReceive('refreshMonth')->once()));

        $this->artisan('external-data:refresh', ['--prison' => 'ringerike'])
            ->expectsOutputToContain('fullført med feil')
            ->assertExitCode(1);
    }

    private function makeMock(string $class, callable $expectations)
    {
        $mock = Mockery::mock($class);
        $expectations($mock);
        return $mock;
    }
}
