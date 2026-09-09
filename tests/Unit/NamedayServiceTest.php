<?php

namespace Tests\Unit;

use App\Services\NamedayService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use App\Mail\ExternalDataSourceFailureMail;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NamedayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
        Carbon::setTestNow(Carbon::parse('2026-08-02 12:00:00', 'Europe/Oslo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testItNormalizesAndDeduplicatesNamesForTheRequestedDate(): void
    {
        Http::fake(['webapi.no/*' => Http::response([
            'data' => [['month' => 8, 'day' => 1, 'names' => [' Peder ', 'Petra', 'Peder', '']]],
        ])]);

        $result = app(NamedayService::class)->forDate(CarbonImmutable::parse('2026-08-01'));

        $this->assertSame(['Peder', 'Petra'], $result['names']);
    }

    public function testFreshNamedaysAreUsedForRepeatedRequests(): void
    {
        Http::fake(['webapi.no/*' => Http::response([
            'data' => [['month' => 8, 'day' => 1, 'names' => ['Peder']]],
        ])]);

        $service = app(NamedayService::class);
        $date = CarbonImmutable::parse('2026-08-01');

        $this->assertSame(['Peder'], $service->forDate($date)['names']);
        $this->assertSame(['Peder'], $service->forDate($date)['names']);

        Http::assertSentCount(1);
    }

    public function testFailedRequestNotifiesAndUsesStaleData(): void
    {
        $date = CarbonImmutable::parse('2026-08-01');
        Cache::forever('today.stale.namedays.08-01', ['names' => ['Peder'], 'source_url' => 'https://webapi.no/']);
        Http::fake(['webapi.no/*' => Http::response([], 503)]);

        $service = app(NamedayService::class);

        $this->assertSame(['Peder'], $service->forDate($date)['names']);
        $this->assertSame(['Peder'], $service->forDate($date)['names']);
        Http::assertSentCount(1);

        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->service === 'namedays-webapi'
            && $mail->operation === 'namedays' && $mail->status === 503);
    }

    public function testInvalidPayloadNotifiesAndNoDataReturnsAnEmptyList(): void
    {
        Http::fake(['webapi.no/*' => Http::response(['unexpected' => []])]);

        $this->assertSame([], app(NamedayService::class)->forDate(CarbonImmutable::parse('2026-08-02')));

        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->service === 'namedays-webapi'
            && $mail->failureKind === 'invalid-payload');
    }

    public function testFailedRequestIsBackedOffForTheSameDate(): void
    {
        $date = CarbonImmutable::parse('2026-08-02');
        Http::fake(['webapi.no/*' => Http::response([], 503)]);
        $service = app(NamedayService::class);

        $this->assertSame([], $service->forDate($date));
        $this->assertSame([], $service->forDate($date));

        Http::assertSentCount(1);
        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
    }

    public function testNamedaysCanRetryAfterTheFailureBackoffExpires(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 12:00:00', 'Europe/Oslo'));
        config()->set('services.today.namedays_failure_cache_ttl', 86400);
        $date = CarbonImmutable::parse('2026-08-02');
        Http::fake(['webapi.no/*' => Http::response([], 503)]);
        $service = app(NamedayService::class);

        $this->assertSame([], $service->forDate($date));
        Carbon::setTestNow(Carbon::parse('2026-08-03 12:00:01', 'Europe/Oslo'));
        $this->assertSame([], $service->forDate($date));

        Http::assertSentCount(2);
    }

    public function testConnectionFailuresAreDeduplicatedByTheNotifierCooldown(): void
    {
        Http::fake(fn () => throw new ConnectionException('nede'));
        $service = app(NamedayService::class);

        $service->forDate(CarbonImmutable::parse('2026-08-03'));
        $service->forDate(CarbonImmutable::parse('2026-08-04'));

        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
    }
}
