<?php

namespace Tests\Unit;

use App\Mail\ExternalDataSourceFailureMail;
use App\Services\WeatherForecastService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use UnexpectedValueException;
use Tests\TestCase;

class WeatherForecastNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
        config()->set('services.weather.base_url', 'https://api.met.no/weatherapi/locationforecast/2.0/compact');
        config()->set('services.weather.user_agent', 'innsatt.no-test');
    }

    public function test_successful_forecast_uses_fresh_cache(): void
    {
        Http::fake(['api.met.no/*' => Http::response($this->payload())]);

        $first = $this->service()->forecast('ringerike');
        $second = $this->service()->forecast('ringerike');

        $this->assertSame($first['location'], $second['location']);
        Http::assertSentCount(1);
    }

    public function test_http_failure_notifies_and_uses_stale_forecast_for_the_same_location(): void
    {
        $stale = $this->forecast('Lagret Ringerike');
        Cache::put('weather.ringerike.forecast.stale', $stale, now()->addHours(6));
        Http::fake(['api.met.no/*' => Http::response([], 500)]);

        $result = $this->service()->forecast('ringerike');

        $this->assertSame($stale, $result);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->service === 'met-weather'
            && $mail->operation === 'ringerike'
            && $mail->failureKind === 'http-status'
            && $mail->status === 500);
    }

    public function test_connection_failure_notifies_and_uses_stale_forecast(): void
    {
        $stale = $this->forecast('Lagret Ringerike');
        Cache::put('weather.ringerike.forecast.stale', $stale, now()->addHours(6));
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $this->assertSame($stale, $this->service()->forecast('ringerike'));
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'connection-exception');
    }

    public function test_invalid_payload_notifies_and_throws_when_no_stale_forecast_exists(): void
    {
        Http::fake(['api.met.no/*' => Http::response(['properties' => ['timeseries' => []]])]);

        $this->expectException(RuntimeException::class);

        try {
            $this->service()->forecast('ringerike');
        } finally {
            Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-payload');
        }
    }

    public function test_invalid_json_notifies_and_throws_when_no_stale_forecast_exists(): void
    {
        Http::fake(['api.met.no/*' => Http::response('ikke json')]);

        $this->expectException(UnexpectedValueException::class);

        try {
            $this->service()->forecast('ringerike');
        } finally {
            Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-json');
        }
    }

    public function test_locations_use_separate_fresh_and_stale_cache_identities(): void
    {
        $ringerike = $this->forecast('Lagret Ringerike');
        $ilseng = $this->forecast('Lagret Ilseng');
        Cache::put('weather.ringerike.forecast.stale', $ringerike, now()->addHours(6));
        Cache::put('weather.ilseng.forecast.stale', $ilseng, now()->addHours(6));
        Http::fake(['api.met.no/*' => Http::response([], 500)]);

        $this->assertSame('Lagret Ringerike', $this->service()->forecast('ringerike')['location']);
        $this->assertSame('Lagret Ilseng', $this->service()->forecast('ilseng')['location']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->operation === 'ringerike');
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->operation === 'ilseng');
    }

    public function test_notifier_cooldown_suppresses_repeated_weather_failures(): void
    {
        Cache::put('weather.ringerike.forecast.stale', $this->forecast('Lagret Ringerike'), now()->addHours(6));
        Http::fake(['api.met.no/*' => Http::response([], 500)]);

        $this->service()->forecast('ringerike');
        $this->service()->forecast('ringerike');

        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
    }

    public function test_weather_page_renders_a_friendly_error_when_met_has_no_fallback(): void
    {
        Http::fake(['api.met.no/*' => Http::response([], 500)]);

        $this->get('/vaer')
            ->assertOk()
            ->assertSee('Vi klarte dessverre ikke å hente værmeldingen akkurat nå.');
    }

    private function payload(string $location = 'Ringerike'): array
    {
        return [
            'properties' => [
                'meta' => ['updated_at' => '2026-09-08T06:00:00Z'],
                'timeseries' => [[
                    'time' => '2026-09-08T12:00:00Z',
                    'data' => [
                        'instant' => ['details' => ['air_temperature' => 12.0, 'wind_speed' => 3.0]],
                        'next_1_hours' => ['summary' => ['symbol_code' => 'fair_day'], 'details' => ['precipitation_amount' => 0.0]],
                    ],
                ]],
            ],
        ];
    }

    private function forecast(string $location): array
    {
        return [
            'location' => $location,
            'updated_at' => now('Europe/Oslo'),
            'days' => [],
        ];
    }

    private function service(): WeatherForecastService
    {
        return app(WeatherForecastService::class);
    }
}
