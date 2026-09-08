<?php

namespace Tests\Unit;

use App\Mail\ExternalDataSourceFailureMail;
use App\Services\PrayerTimeService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class PrayerTimeServiceTest extends TestCase
{
    private const LOCATION_ID = 146;
    private const YEAR = 2026;
    private const MONTH = 9;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
        config()->set('services.prayer_times.api_token', 'test-token');
    }

    public function test_successful_call_returns_prayer_times_uses_configured_token_and_caches_the_response(): void
    {
        $days = [$this->day()];
        Http::fake(['api.bonnetid.no/*' => Http::response($days)]);

        $first = $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);
        $second = $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);

        $this->assertSame($days, $first);
        $this->assertSame($days, $second);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->hasHeader('api-token', 'test-token'));
    }

    public function test_http_failure_notifies_and_uses_stale_data_for_the_same_period_and_location(): void
    {
        $stale = [$this->day('2026-09-02')];
        Cache::put($this->staleKey(), $stale, now()->addDays(7));
        Http::fake(['api.bonnetid.no/*' => Http::response([], 500)]);

        $result = $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);

        $this->assertSame($stale, $result);
        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->service === 'bonnetid-no'
                && $mail->operation === 'prayer-times'
                && $mail->failureKind === 'http-status'
                && $mail->status === 500;
        });
    }

    public function test_connection_failure_notifies_and_uses_stale_data(): void
    {
        $stale = [$this->day('2026-09-03')];
        Cache::put($this->staleKey(), $stale, now()->addDays(7));
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $this->assertSame($stale, $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'connection-exception');
    }

    public function test_invalid_json_notifies_and_returns_an_empty_result(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response('ikke json')]);
        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-json');

    }

    public function test_missing_prayer_times_notifies_and_returns_an_empty_result(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([['date' => '2026-09-01', 'fajr' => '05:00']])]);
        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-payload');
    }

    public function test_stale_data_from_another_period_or_location_is_not_used(): void
    {
        Cache::put($this->staleKey(), [$this->day()], now()->addDays(7));
        Http::fake(['api.bonnetid.no/*' => Http::response([], 500)]);

        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH + 1));
        $this->assertSame([], $this->service()->getMonth(181, self::YEAR, self::MONTH));
    }

    public function test_notifier_cooldown_suppresses_repeated_failures(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([], 500)]);

        $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);
        $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);

        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
    }

    public function test_missing_token_does_not_make_an_http_call_and_is_not_hardcoded_in_php(): void
    {
        config()->set('services.prayer_times.api_token', null);

        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));
        Http::assertNothingSent();

        $source = file_get_contents(app_path('Http/Controllers/PrayerController.php'))
            .file_get_contents(app_path('Services/PrayerTimeService.php'));
        $this->assertSame(0, preg_match('/[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}/i', $source));
    }

    public function test_configured_token_is_not_present_in_the_notification_email(): void
    {
        config()->set('services.prayer_times.api_token', 'token-that-must-not-appear');
        Http::fake(['api.bonnetid.no/*' => Http::response([], 500)]);
        Log::shouldReceive('warning')
            ->once()
            ->with('External data source failure.', Mockery::on(function (array $context): bool {
                return ! str_contains(json_encode($context), 'token-that-must-not-appear');
            }));

        $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);

        $mail = Mail::sent(ExternalDataSourceFailureMail::class)->first();
        $rendered = view('emails.external-data-source-failure', [
            'service' => $mail->service,
            'operation' => $mail->operation,
            'summary' => $mail->summary,
            'failureKind' => $mail->failureKind,
            'status' => $mail->status,
            'occurredAt' => $mail->occurredAt,
            'cooldownSeconds' => $mail->cooldownSeconds,
        ])->render();

        $this->assertStringNotContainsString('token-that-must-not-appear', $rendered);
    }

    private function staleKey(): string
    {
        return sprintf('prayer-times.stale.%04d-%02d.location-%d', self::YEAR, self::MONTH, self::LOCATION_ID);
    }

    private function day(string $date = '2026-09-01'): array
    {
        return [
            'date' => $date,
            'fajr' => '05:00',
            'duhr' => '12:30',
            'asr' => '15:45',
            'maghrib' => '19:15',
            'isha' => '21:00',
        ];
    }

    private function service(): PrayerTimeService
    {
        return app(PrayerTimeService::class);
    }
}
