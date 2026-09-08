<?php

namespace Tests\Unit;

use App\Services\NamedayService;
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
    }

    public function testItNormalizesAndDeduplicatesNamesForTheRequestedDate(): void
    {
        Http::fake(['webapi.no/*' => Http::response([
            'data' => [['month' => 8, 'day' => 1, 'names' => [' Peder ', 'Petra', 'Peder', '']]],
        ])]);

        $result = app(NamedayService::class)->forDate(CarbonImmutable::parse('2026-08-01'));

        $this->assertSame(['Peder', 'Petra'], $result['names']);
    }

    public function testFailedRequestNotifiesAndUsesStaleData(): void
    {
        $date = CarbonImmutable::parse('2026-08-01');
        Cache::forever('today.stale.namedays.08-01', ['names' => ['Peder'], 'source_url' => 'https://webapi.no/']);
        Http::fake(['webapi.no/*' => Http::response([], 503)]);

        $this->assertSame(['Peder'], app(NamedayService::class)->forDate($date)['names']);

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

    public function testConnectionFailuresAreDeduplicatedByTheNotifierCooldown(): void
    {
        Http::fake(fn () => throw new ConnectionException('nede'));
        $service = app(NamedayService::class);

        $service->forDate(CarbonImmutable::parse('2026-08-03'));
        $service->forDate(CarbonImmutable::parse('2026-08-04'));

        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
    }
}
