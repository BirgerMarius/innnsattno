<?php

namespace Tests\Feature;

use App\Services\LocalNewsService;
use App\Services\WeatherForecastService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class DataAvailabilityStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function testHomepageExplainsAConfirmedEmptyLocalNewsResult(): void
    {
        Cache::put(app(LocalNewsService::class)->cacheKey('ringerike'), [
            'articles' => [], 'status' => 'empty', 'fetched_at' => '2026-11-10T09:00:00+01:00',
        ], 60);

        $this->get('/ringerike')
            ->assertOk()
            ->assertSee('Ingen lokale nyheter funnet akkurat nå.')
            ->assertDontSee('Lokale nyheter er ikke tilgjengelige akkurat nå.');
    }

    public function testHomepageExplainsMissingLocalNewsWithoutExposingSourceErrors(): void
    {
        Http::fake(['*' => Http::response('', 503)]);

        $this->get('/ilseng')
            ->assertOk()
            ->assertSee('Lokale nyheter er ikke tilgjengelige akkurat nå.')
            ->assertDontSee('HTTP-status 503')
            ->assertDontSee('feilteller');
    }

    public function testDisabledLocalNewsDoesNotShowAStatusMessage(): void
    {
        config()->set('prisons.all.ringerike.features.local_news', false);

        $this->get('/ringerike')
            ->assertOk()
            ->assertDontSee('Lokale nyheter er ikke tilgjengelige akkurat nå.')
            ->assertDontSee('Ingen lokale nyheter funnet akkurat nå.');
    }

    public function testHomepageUsesOnlyTheSelectedPrisonsEmptyNewsResult(): void
    {
        $service = app(LocalNewsService::class);
        Cache::put($service->cacheKey('ilseng'), [
            'articles' => [], 'status' => 'empty', 'fetched_at' => '2026-11-10T09:00:00+01:00',
        ], 60);
        Cache::put($service->cacheKey('kongsvinger'), [
            'articles' => [[
                'title' => 'Kongsvinger-sak', 'url' => 'https://www.glomdalen.no/sak',
                'published_at' => null, 'is_subscription' => false, 'image_url' => null,
            ]], 'status' => 'available', 'fetched_at' => '2026-11-10T09:00:00+01:00',
        ], 60);

        $this->get('/ilseng')
            ->assertOk()
            ->assertSee('Ingen lokale nyheter funnet akkurat nå.')
            ->assertDontSee('Kongsvinger-sak');
    }

    public function testUsableLocalNewsReserveHasNoErrorMessage(): void
    {
        $service = app(LocalNewsService::class);
        Cache::put($service->staleCacheKey('ringerike'), [[
            'title' => 'Lagret lokal sak', 'url' => 'https://www.ringblad.no/sak',
            'published_at' => null, 'is_subscription' => false, 'image_url' => null,
        ]], 60);
        Http::fake(['*' => Http::response('', 503)]);

        $this->get('/ringerike')
            ->assertOk()
            ->assertSee('Lagret lokal sak')
            ->assertDontSee('Lokale nyheter er ikke tilgjengelige akkurat nå.');
    }

    public function testWeatherPageMarksAForecastWithoutTodaysDateAsOutdated(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 12:00:00', 'Europe/Oslo'));
        $weather = Mockery::mock(WeatherForecastService::class);
        $weather->shouldReceive('forecast')->once()->with('ringerike')->andReturn($this->forecast());
        $weather->shouldReceive('coversToday')->once()->andReturnFalse();
        $this->app->instance(WeatherForecastService::class, $weather);

        $this->get('/vaer')
            ->assertOk()
            ->assertSee('Værvarselet er ikke lenger aktuelt.')
            ->assertSee('Sist oppdatert 9. november 2026 kl. 08:30.')
            ->assertSee('Værvarsel')
            ->assertDontSee('Dagens vær');

        Carbon::setTestNow();
    }

    public function testWeatherPageExplainsWhenThereAreNoUsableForecastData(): void
    {
        $weather = Mockery::mock(WeatherForecastService::class);
        $weather->shouldReceive('forecast')->once()->with('ringerike')->andReturn([
            'location' => 'Tyristrand / Ringerike fengsel', 'updated_at' => Carbon::now('Europe/Oslo'), 'days' => [],
        ]);
        $this->app->instance(WeatherForecastService::class, $weather);

        $this->get('/vaer')
            ->assertOk()
            ->assertSee('Værinformasjon er ikke tilgjengelig akkurat nå.');
    }

    public function testWeatherPageKeepsAReserveThatStillCoversToday(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 12:00:00', 'Europe/Oslo'));
        Cache::put('weather.ringerike.forecast.stale', [
            'location' => 'Tyristrand / Ringerike fengsel',
            'updated_at' => Carbon::parse('2026-11-10 08:30:00', 'Europe/Oslo'),
            'days' => [[
                'date' => Carbon::parse('2026-11-10', 'Europe/Oslo'), 'description' => 'Skyet', 'icon' => '☁️',
                'temperature_min' => 2, 'temperature_max' => 5, 'precipitation' => null, 'wind_speed' => null,
            ]],
        ], 60);
        Http::fake(['*' => Http::response([], 503)]);

        $this->get('/vaer')
            ->assertOk()
            ->assertSee('Dagens vær')
            ->assertDontSee('Værvarselet er ikke lenger aktuelt.');

        Carbon::setTestNow();
    }

    private function forecast(): array
    {
        return [
            'location' => 'Tyristrand / Ringerike fengsel',
            'updated_at' => Carbon::parse('2026-11-09 08:30:00', 'Europe/Oslo'),
            'days' => [[
                'date' => Carbon::parse('2026-11-09', 'Europe/Oslo'), 'description' => 'Skyet', 'icon' => '☁️',
                'temperature_min' => 2, 'temperature_max' => 5, 'precipitation' => null, 'wind_speed' => null,
            ]],
        ];
    }
}
