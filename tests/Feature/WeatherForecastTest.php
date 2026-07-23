<?php

namespace Tests\Feature;

use App\Services\WeatherForecastService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class WeatherForecastTest extends TestCase
{
    public function testWeatherPageShowsForecastAndFrontPageLink(): void
    {
        $service = Mockery::mock(WeatherForecastService::class);
        $service->shouldReceive('forecast')->once()->andReturn([
            'location' => 'Tyristrand / Ringerike fengsel',
            'updated_at' => Carbon::parse('2026-07-23 08:30', 'Europe/Oslo'),
            'days' => [
                $this->day('2026-07-23', 'Delvis skyet', '⛅', 12, 21, 1.4, 3.6),
                $this->day('2026-07-24', 'Regn', '🌧️', 10, 17, 6.2, 4.8),
            ],
        ]);
        $this->app->instance(WeatherForecastService::class, $service);

        $this->get('/vaer')
            ->assertOk()
            ->assertSee('Tyristrand / Ringerike fengsel')
            ->assertSee('Dagens vær')
            ->assertSee('Delvis skyet')
            ->assertSee('12–21 °C')
            ->assertSee('Nedbør: 1,4 mm')
            ->assertSee('Vind: 3,6 m/s')
            ->assertSee('Sist oppdatert:');

        $this->get(route('tv'))
            ->assertOk()
            ->assertSee('Værmelding – Tyristrand/Ringerike Fengsel')
            ->assertSee('href="'.route('weather.index').'"', false);
    }

    public function testWeatherProviderFailureShowsFriendlyMessage(): void
    {
        $service = Mockery::mock(WeatherForecastService::class);
        $service->shouldReceive('forecast')->once()->andThrow(new RuntimeException('API unavailable'));
        $this->app->instance(WeatherForecastService::class, $service);

        $this->get('/vaer')
            ->assertOk()
            ->assertSee('Vi klarte dessverre ikke å hente værmeldingen akkurat nå.')
            ->assertDontSee('API unavailable');
    }

    public function testWeatherServiceProcessesMetForecastWithoutDoubleCountingPrecipitation(): void
    {
        Cache::flush();
        config([
            'services.weather.base_url' => 'https://api.met.no/weatherapi/locationforecast/2.0/compact',
            'services.weather.latitude' => 60.087,
            'services.weather.longitude' => 10.099,
            'services.weather.user_agent' => 'innsatt.no-test',
        ]);

        Http::fake([
            'api.met.no/*' => Http::response([
                'properties' => [
                    'meta' => ['updated_at' => '2026-07-23T06:00:00Z'],
                    'timeseries' => [
                        $this->metPoint('2026-07-23T08:00:00Z', 12.4, 3.6, 0.4, 4.0),
                        $this->metPoint('2026-07-23T09:00:00Z', 14.2, 4.1, 0.6, 5.0),
                        $this->metPoint('2026-07-23T10:00:00Z', 16.8, 4.8, 1.0, 6.0),
                        $this->metFallbackPoint('2026-07-23T16:00:00Z', 13.0, 3.2, 3.0),
                        $this->metFallbackPoint('2026-07-23T17:00:00Z', 12.0, 2.8, 20.0),
                        $this->metFallbackPoint('2026-07-23T20:00:00Z', 12.0, 2.6, 30.0),
                        $this->metFallbackPoint('2026-07-23T22:00:00Z', 10.2, 2.5, 1.0),
                        $this->metFallbackPoint('2026-07-24T01:00:00Z', 11.5, 3.0, 10.0),
                        $this->metFallbackPoint('2026-07-24T04:00:00Z', 13.1, 3.5, 2.0),
                    ],
                ],
            ], 200),
        ]);

        $forecast = app(WeatherForecastService::class)->forecast();
        $today = $forecast['days'][0];

        $this->assertSame('Tyristrand / Ringerike fengsel', $forecast['location']);
        $this->assertSame(12.0, $today['temperature_min']);
        $this->assertSame(17.0, $today['temperature_max']);
        $this->assertSame(4.8, $today['wind_speed']);
        $this->assertSame(5.0, $today['precipitation']);
        $this->assertSame('Regn', $today['description']);
        $this->assertSame(3.0, $forecast['days'][1]['precipitation']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=60.087&lon=10.099'
                && $request->hasHeader('User-Agent', 'innsatt.no-test');
        });
    }

    private function day(
        string $date,
        string $description,
        string $icon,
        int $minimum,
        int $maximum,
        float $precipitation,
        float $wind
    ): array {
        return [
            'date' => Carbon::parse($date, 'Europe/Oslo'),
            'description' => $description,
            'icon' => $icon,
            'temperature_min' => $minimum,
            'temperature_max' => $maximum,
            'precipitation' => $precipitation,
            'wind_speed' => $wind,
        ];
    }

    private function metPoint(
        string $time,
        float $temperature,
        float $wind,
        float $precipitationOneHour,
        float $precipitationSixHours
    ): array {
        return [
            'time' => $time,
            'data' => [
                'instant' => [
                    'details' => [
                        'air_temperature' => $temperature,
                        'wind_speed' => $wind,
                    ],
                ],
                'next_1_hours' => [
                    'summary' => ['symbol_code' => 'rain'],
                    'details' => ['precipitation_amount' => $precipitationOneHour],
                ],
                'next_6_hours' => [
                    'summary' => ['symbol_code' => 'rain'],
                    'details' => ['precipitation_amount' => $precipitationSixHours],
                ],
            ],
        ];
    }

    private function metFallbackPoint(
        string $time,
        float $temperature,
        float $wind,
        float $precipitationSixHours
    ): array {
        return [
            'time' => $time,
            'data' => [
                'instant' => [
                    'details' => [
                        'air_temperature' => $temperature,
                        'wind_speed' => $wind,
                    ],
                ],
                'next_6_hours' => [
                    'summary' => ['symbol_code' => 'rain'],
                    'details' => ['precipitation_amount' => $precipitationSixHours],
                ],
            ],
        ];
    }
}
