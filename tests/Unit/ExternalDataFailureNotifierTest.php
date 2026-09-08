<?php

namespace Tests\Unit;

use App\Mail\ExternalDataSourceFailureMail;
use App\Services\ExternalDataFailureNotifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class ExternalDataFailureNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
        config()->set('external_data.cooldown_seconds', 3600);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_first_failure_sends_an_email(): void
    {
        $this->report('dw-news', 'tv-guide-ringerike');

        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->hasTo('varsling@example.test')
                && $mail->service === 'dw-news'
                && $mail->operation === 'tv-guide-ringerike'
                && $mail->failureKind === 'http-status'
                && $mail->status === 503;
        });
    }

    public function test_same_service_and_operation_are_suppressed_during_cooldown(): void
    {
        $this->report('dw-news', 'tv-guide-ringerike');
        $this->report('dw-news', 'tv-guide-ringerike');

        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
    }

    public function test_another_service_or_operation_can_send_its_own_email(): void
    {
        $this->report('dw-news', 'tv-guide-ringerike');
        $this->report('dw-news', 'tv-guide-ilseng');
        $this->report('weather', 'ringerike');

        Mail::assertSent(ExternalDataSourceFailureMail::class, 3);
    }

    public function test_a_new_email_can_be_sent_after_the_cooldown(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-08 10:00:00', 'Europe/Oslo'));
        $this->report('dw-news', 'tv-guide-ringerike');

        Carbon::setTestNow(Carbon::parse('2026-09-08 11:00:01', 'Europe/Oslo'));
        $this->report('dw-news', 'tv-guide-ringerike');

        Mail::assertSent(ExternalDataSourceFailureMail::class, 2);
    }

    public function test_mail_failures_are_not_thrown_to_the_caller(): void
    {
        Mail::shouldReceive('to')
            ->once()
            ->with('varsling@example.test')
            ->andThrow(new RuntimeException('Mail transport failed'));

        $notifier = app(ExternalDataFailureNotifier::class);
        $notifier->report('dw-news', 'DW News kunne ikke hentes', ['operation' => 'tv-guide-ringerike']);

        $this->addToAssertionCount(1);
    }

    public function test_sensitive_data_is_not_included_in_the_email(): void
    {
        app(ExternalDataFailureNotifier::class)->report(
            'dw-news',
            'Authorization: Bearer top-secret-token https://dw.example/api?api_key=also-secret',
            ['operation' => 'tv-guide-ringerike', 'api_key' => 'never-include-this']
        );

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

        $this->assertStringNotContainsString('top-secret-token', $rendered);
        $this->assertStringNotContainsString('also-secret', $rendered);
        $this->assertStringNotContainsString('never-include-this', $rendered);
        $this->assertStringNotContainsString('https://dw.example', $rendered);
        $this->assertStringContainsString('[skjult]', $rendered);
    }

    private function report(string $service, string $operation): void
    {
        app(ExternalDataFailureNotifier::class)->report($service, 'Datakilden svarte ikke.', [
            'operation' => $operation,
            'failure_kind' => 'http_status',
            'status' => 503,
        ]);
    }
}
