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

        $this->warmCompetition(8766);
        $data = app(EliteserienService::class)->getCompetitionData();

        $this->assertTrue($data['apiError']);
        $this->assertSame([], $data['standings']);
        Mail::assertNothingSent();
    }

    public function testRepeatedProviderFailuresRespectTheNotifierCooldown(): void
    {
        Http::fake(['*/tournaments/seasons/8766/schedule' => Http::response([], 503)]);
        $service = app(EliteserienService::class);

        $service->refreshCompetitionData();
        $service->refreshCompetitionData();

        Mail::assertNothingSent();
    }

    public function testEmptyCompetitionResponsesAreUnavailableButDoNotImmediatelyNotify(): void
    {
        Http::fake([
            '*/tournaments/seasons/8766/schedule' => Http::response(['events' => []]),
            '*/tournaments/seasons/8766/standings' => Http::response(['standings' => []]),
        ]);

        $this->warmCompetition(8766);
        $data = app(EliteserienService::class)->getCompetitionData();

        $this->assertTrue($data['apiError']);
        Mail::assertNothingSent();
    }
}
