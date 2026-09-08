<?php

namespace Tests\Unit;

use App\Mail\ExternalDataSourceFailureMail;
use App\Services\EliteserienService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SchibstedCompetitionNotifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
    }

    public function testProviderFailureNotifiesAndTheCompetitionPageReceivesControlledEmptyData(): void
    {
        Http::fake(['*/tournaments/seasons/8766/schedule' => Http::response([], 503)]);

        $data = app(EliteserienService::class)->getCompetitionData();

        $this->assertTrue($data['apiError']);
        $this->assertSame([], $data['standings']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->service === 'sportsnext-competition'
            && $mail->operation === 'eliteserien' && $mail->status === 503);
    }

    public function testRepeatedProviderFailuresRespectTheNotifierCooldown(): void
    {
        Http::fake(['*/tournaments/seasons/8766/schedule' => Http::response([], 503)]);
        $service = app(EliteserienService::class);

        $service->getCompetitionData();
        $service->getCompetitionData();

        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
    }

    public function testEmptySuccessfulCompetitionResponsesDoNotNotify(): void
    {
        Http::fake([
            '*/tournaments/seasons/8766/schedule' => Http::response([]),
            '*/tournaments/seasons/8766/standings' => Http::response([]),
        ]);

        $data = app(EliteserienService::class)->getCompetitionData();

        $this->assertFalse($data['apiError']);
        Mail::assertNothingSent();
    }
}
