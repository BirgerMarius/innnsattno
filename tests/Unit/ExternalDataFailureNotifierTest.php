<?php

namespace Tests\Unit;

use App\ExternalDataFailureDigest;
use App\Mail\ExternalDataFailureDigestMail;
use App\Services\ExternalDataFailureDigestService;
use App\Services\ExternalDataFailureNotifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ExternalDataFailureNotifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
        Carbon::setTestNow(Carbon::parse('2026-10-13 06:45:00', 'Europe/Oslo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_failures_are_persisted_but_do_not_send_individual_email(): void
    {
        app(ExternalDataFailureNotifier::class)->report('correctional-news', 'Kilden svarte ikke.', [
            'operation' => 'kdi', 'failure_kind' => 'connection_failure',
        ]);

        $this->assertDatabaseHas('external_data_source_events', ['service' => 'correctional-news', 'operation' => 'kdi', 'outcome' => 'failure']);
        Mail::assertNothingSent();
    }

    public function test_digest_groups_failures_uses_oslo_window_and_shows_recovery(): void
    {
        $notifier = app(ExternalDataFailureNotifier::class);
        Carbon::setTestNow(Carbon::parse('2026-10-12 06:44:59', 'Europe/Oslo'));
        $notifier->report('weather', 'For gammel.', ['operation' => 'ringerike', 'failure_kind' => 'http_status']);
        Carbon::setTestNow(Carbon::parse('2026-10-12 08:00:00', 'Europe/Oslo'));
        $notifier->report('correctional-news', 'RSS kunne ikke hentes.', ['operation' => 'kdi', 'failure_kind' => 'connection_failure']);
        Carbon::setTestNow(Carbon::parse('2026-10-13 05:00:00', 'Europe/Oslo'));
        $notifier->report('correctional-news', 'RSS kunne ikke hentes.', ['operation' => 'kdi', 'failure_kind' => 'connection_failure']);
        $notifier->reportSuccess('correctional-news', 'kdi');
        Carbon::setTestNow(Carbon::parse('2026-10-13 06:45:00', 'Europe/Oslo'));

        $this->assertTrue(app(ExternalDataFailureDigestService::class)->send());
        Mail::assertSent(ExternalDataFailureDigestMail::class, function ($mail) {
            $this->assertCount(1, $mail->groups);
            $this->assertSame(2, $mail->groups[0]['count']);
            $this->assertSame('Vellykket oppdatering etter siste feil', $mail->groups[0]['recovery']);
            $this->assertSame('RSS kunne ikke hentes.', $mail->groups[0]['summary']);
            $this->assertStringContainsString('CEST', $mail->periodEnd);
            return $mail->hasTo('varsling@example.test');
        });
    }

    public function test_empty_digest_is_not_sent(): void
    {
        $this->assertFalse(app(ExternalDataFailureDigestService::class)->send());
        Mail::assertNothingSent();
    }

    public function test_same_daily_digest_is_sent_once(): void
    {
        app(ExternalDataFailureNotifier::class)->report('tv-guide-vg', 'Kilden svarte ikke.', ['operation' => 'ringerike', 'failure_kind' => 'http_status']);
        $service = app(ExternalDataFailureDigestService::class);
        $this->assertTrue($service->send());
        $this->assertFalse($service->send());
        Mail::assertSent(ExternalDataFailureDigestMail::class, 1);
        $this->assertSame('sent', ExternalDataFailureDigest::firstOrFail()->status);
    }

    public function test_failed_mail_keeps_events_and_allows_retry(): void
    {
        app(ExternalDataFailureNotifier::class)->report('met-weather', 'Kilden svarte ikke.', ['operation' => 'ilseng', 'failure_kind' => 'connection_failure']);
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Transportfeil'));
        $service = app(ExternalDataFailureDigestService::class);
        $this->assertFalse($service->send());
        $this->assertDatabaseCount('external_data_source_events', 1);
        $this->assertSame('failed', ExternalDataFailureDigest::firstOrFail()->status);
    }

    public function test_retry_sends_the_original_failed_window_after_the_next_day_has_started(): void
    {
        app(ExternalDataFailureNotifier::class)->report('met-weather', 'Kilden svarte ikke.', ['operation' => 'ilseng', 'failure_kind' => 'connection_failure']);
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('Transportfeil'));
        $service = app(ExternalDataFailureDigestService::class);
        $this->assertFalse($service->send());

        Carbon::setTestNow(Carbon::parse('2026-10-14 06:45:00', 'Europe/Oslo'));
        Mail::fake();
        $this->assertTrue($service->send());
        Mail::assertSent(ExternalDataFailureDigestMail::class, function ($mail) {
            $this->assertSame('2026-10-13 06:45:00 CEST', $mail->periodEnd);
            return $mail->groups[0]['service'] === 'met-weather';
        });
    }
}
