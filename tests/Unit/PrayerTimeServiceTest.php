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

    public function test_realistic_bonnetid_payload_is_accepted_mapped_for_the_views_and_cached(): void
    {
        $days = [$this->bonnetidDay()];
        Http::fake(['api.bonnetid.no/*' => Http::response($days)]);

        $first = $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);
        $second = $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);

        $this->assertSame($days, $first);
        $this->assertSame($days, $second);
        $this->assertSame('03:39', $first[0]['fajr']);
        $this->assertSame('13:29', $first[0]['duhr']);
        $this->assertSame('17:02', $first[0]['asr']);
        $this->assertSame('20:30', $first[0]['maghrib']);
        $this->assertSame('22:12', $first[0]['isha']);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.bonnetid.no/prayertimes/146/2026/9/'
            && parse_url($request->url(), PHP_URL_QUERY) === null
            && $request->hasHeader('api-token', 'test-token'));
    }

    public function test_documented_nullable_prayer_times_are_kept_for_the_views(): void
    {
        $day = $this->bonnetidDay([
            'fajr' => null,
            'isha' => null,
        ]);
        Http::fake(['api.bonnetid.no/*' => Http::response([$day])]);

        $result = $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);

        $this->assertSame([$day], $result);
        Mail::assertNothingSent();
    }

    public function test_documented_omitted_prayer_times_are_normalised_to_null_for_the_views(): void
    {
        $day = $this->bonnetidDay();
        unset($day['fajr'], $day['isha']);
        Http::fake(['api.bonnetid.no/*' => Http::response([$day])]);

        $result = $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH);

        $this->assertNull($result[0]['fajr']);
        $this->assertNull($result[0]['isha']);
        Mail::assertNothingSent();
    }

    public function test_representative_ringerike_and_ilseng_month_payloads_are_accepted(): void
    {
        $payloads = [
            '146/2026/9' => [$this->bonnetidDay()],
            // The public schema does not require the individual nullable fields.
            '146/2026/10' => [array_diff_key($this->bonnetidDay([
                'date' => '01-10-2026',
                'location' => 'Ringerike',
            ]), ['isha' => true])],
            '181/2026/9' => [$this->bonnetidDay([
                'date' => '01-09-2026',
                'location' => 'Ilseng',
                'fajr' => null,
                'isha' => null,
            ])],
            '181/2026/10' => [$this->bonnetidDay([
                'date' => '01-10-2026',
                'location' => 'Ilseng',
            ])],
        ];

        Http::fake(function ($request) use ($payloads) {
            foreach ($payloads as $path => $payload) {
                if (str_ends_with($request->url(), '/'.$path.'/')) {
                    return Http::response($payload);
                }
            }

            return Http::response([], 404);
        });

        foreach ([[146, 2026, 9], [146, 2026, 10], [181, 2026, 9], [181, 2026, 10]] as [$locationId, $year, $month]) {
            $result = $this->service()->getMonth($locationId, $year, $month);

            $this->assertNotSame([], $result, "Expected a result for {$locationId}/{$year}/{$month}.");
        }

        Http::assertSentCount(4);
        Mail::assertNothingSent();
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

    public function test_not_found_response_notifies_as_an_http_status_failure(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([], 404)]);

        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));

        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->service === 'bonnetid-no'
                && $mail->operation === 'prayer-times'
                && $mail->failureKind === 'http-status'
                && $mail->status === 404;
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

    public function test_invalid_prayer_time_value_notifies_and_returns_an_empty_result(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay(['maghrib' => 'etter solnedgang'])])]);

        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-payload'
            && $mail->diagnostics === [
                'location_id' => self::LOCATION_ID,
                'year' => self::YEAR,
                'month' => self::MONTH,
                'row_index' => 0,
                'date' => '01-09-2026',
                'invalid_field' => 'maghrib',
                'value_type' => 'string',
            ]);
    }

    public function test_missing_date_notifies_and_returns_an_empty_result(): void
    {
        $day = $this->bonnetidDay();
        unset($day['date']);
        Http::fake(['api.bonnetid.no/*' => Http::response([$day])]);

        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-payload');
    }

    public function test_invalid_calendar_date_notifies_and_returns_an_empty_result(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay(['date' => '31-02-2026'])])]);

        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-payload'
            && $mail->summary === 'Bønnetid.no returnerte ugyldig eller manglende dato.');
    }

    public function test_wrong_date_format_notifies_and_returns_an_empty_result(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay(['date' => '2026-09-01'])])]);

        $this->assertSame([], $this->service()->getMonth(self::LOCATION_ID, self::YEAR, self::MONTH));
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-payload'
            && $mail->summary === 'Bønnetid.no returnerte ugyldig eller manglende dato.');
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

    private function day(string $date = '01-09-2026'): array
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

    private function bonnetidDay(array $overrides = []): array
    {
        return array_replace([
            'location' => 'Ringerike',
            'date' => '01-09-2026',
            'district_code' => '30',
            'kommune' => 'Ringerike',
            'hijri_date' => '09 Safar 1448',
            'istiwa_noon' => '13:22:00',
            'duhr' => '13:29',
            'asr' => '17:02',
            'ghrub_sunset' => '20:24',
            'maghrib' => '20:30',
            'isha' => '22:12',
            'fajr_sadiq' => '03:39',
            'fajr' => '03:39',
            'shuruq_sunrise' => '06:31',
        ], $overrides);
    }

    private function service(): PrayerTimeService
    {
        return app(PrayerTimeService::class);
    }
}
