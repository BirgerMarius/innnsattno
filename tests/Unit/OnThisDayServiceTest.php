<?php

namespace Tests\Unit;

use App\Mail\ExternalDataSourceFailureMail;
use App\Services\OnThisDayService;
use Carbon\CarbonImmutable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OnThisDayServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
        Carbon::setTestNow(Carbon::parse('2026-08-01 12:00:00', 'Europe/Oslo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testNorwegianMediaWikiContentIsUsedWhenAvailable(): void
    {
        Http::fake([
            'no.wikipedia.org/w/api.php*' => Http::sequence()
                ->push(['parse' => ['wikitext' => "== Hendelser ==\n* 1905 – [[Testartikkel]]: En norsk hendelse"]])
                ->push(['query' => ['pages' => [['title' => 'Testartikkel', 'pageprops' => ['wikibase_item' => 'Q1'], 'length' => 1000]]]]),
        ]);

        $result = app(OnThisDayService::class)->forDate(CarbonImmutable::parse('2026-08-01'));

        $this->assertSame('Testartikkel', $result['events'][0]['title']);
        $this->assertTrue($result['events'][0]['norwegian_source']);
    }

    public function testMediaWikiFailureNotifiesAndFallsBackToNorwegianRestSource(): void
    {
        Http::fake([
            'no.wikipedia.org/w/api.php*' => Http::response([], 503),
            'no.wikipedia.org/api/rest_v1/*' => Http::response($this->historyPayload()),
        ]);

        $result = app(OnThisDayService::class)->forDate(CarbonImmutable::parse('2026-08-01'));

        $this->assertNotEmpty($result['events']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->service === 'wikipedia-on-this-day'
            && $mail->operation === 'no-mediawiki' && $mail->status === 503);
    }

    public function testNorwegianRestFailureNotifiesAndEnglishFallbackStillWorks(): void
    {
        Http::fake([
            'no.wikipedia.org/w/api.php*' => Http::response([], 503),
            'no.wikipedia.org/api/rest_v1/*' => Http::response([], 503),
            'en.wikipedia.org/api/rest_v1/*' => Http::response($this->historyPayload()),
        ]);

        $result = app(OnThisDayService::class)->forDate(CarbonImmutable::parse('2026-08-01'));

        $this->assertNotEmpty($result['events']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->operation === 'no-onthisday' && $mail->status === 503);
    }

    public function testInvalidRestPayloadNotifiesAndTotalExternalFailureRemainsControlled(): void
    {
        Http::fake([
            'no.wikipedia.org/w/api.php*' => Http::response([], 503),
            'no.wikipedia.org/api/rest_v1/*' => Http::response(['unexpected' => []]),
            'en.wikipedia.org/api/rest_v1/*' => Http::response(['unexpected' => []]),
        ]);

        try {
            app(OnThisDayService::class)->forDate(CarbonImmutable::parse('2026-08-01'));
            $this->fail('Total ekstern feil skal ikke gi brukbare oppføringer.');
        } catch (\RuntimeException) {
            // TodayContentService/ResilientDateCache turns this into its controlled empty fallback.
        }

        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->operation === 'no-onthisday'
            && $mail->failureKind === 'invalid-payload');
    }

    public function test_total_failure_is_backed_off_per_date_without_blocking_first_request_fallbacks(): void
    {
        Http::fake([
            'no.wikipedia.org/w/api.php*' => Http::response([], 503),
            'no.wikipedia.org/api/rest_v1/*' => Http::response([], 503),
            'en.wikipedia.org/api/rest_v1/*' => Http::response([], 503),
        ]);
        $service = app(OnThisDayService::class);
        $date = CarbonImmutable::parse('2026-08-01');

        try { $service->forDate($date); } catch (\RuntimeException) { }
        try { $service->forDate($date); } catch (\RuntimeException) { }

        // The first request tried all fallbacks; their date-specific failure
        // markers make the later request entirely local.
        Http::assertSentCount(3);
        Mail::assertSent(ExternalDataSourceFailureMail::class, 3);
    }

    private function historyPayload(): array
    {
        return ['events' => [[
            'year' => 1905,
            'text' => 'En norsk hendelse',
            'pages' => [[
                'titles' => ['normalized' => 'Testartikkel'],
                'description' => 'Norsk hendelse',
                'content_urls' => ['desktop' => ['page' => 'https://example.test/test']],
            ]],
        ]], 'births' => [], 'deaths' => []];
    }
}
