<?php

namespace Tests\Unit;

use App\CorrectionalNewsCluster;
use App\CorrectionalNewsItem;
use App\Mail\ExternalDataSourceFailureMail;
use App\Services\CorrectionalNewsRelevanceScorer;
use App\Services\CorrectionalNewsNationalSignificanceScorer;
use App\Services\CorrectionalNewsService;
use App\Services\CorrectionalNews\NffNewsSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CorrectionalNewsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
    }

    public function testNffUsesTheConfirmedDedicatedFeedAndParsesItsArticle(): void
    {
        $source = app(NffNewsSource::class);
        $this->assertSame('https://www.frifagbevegelse.no/nff-magasinet/?lab_viewport=rss', $source->url());
        $this->assertStringNotContainsString('/nffmagasinet', $source->url());

        $article = $source->parse($this->fixture('nff.xml'))[0];
        $this->assertSame('Fengslene må spare 83 millioner', $article['title']);
        $this->assertSame('Tue, 15 Sep 2026 13:45:55 +0200', $article['published_at']);
        $this->assertContains('Kriminalomsorg', $article['labels']);
        $this->assertStringContainsString('/nff-magasinet/', $article['url']);
    }

    public function testRefreshParsesAllFourFeedsAndClustersTheSharedSavingsStory(): void
    {
        $this->fakeFeeds();
        $reports = app(CorrectionalNewsService::class)->refresh();

        $this->assertCount(4, $reports);
        $this->assertDatabaseCount('correctional_news_items', 5);
        $cluster = CorrectionalNewsCluster::where('source_count', 2)->firstOrFail();
        $this->assertSame('union', $cluster->category);
        $this->assertSame(2, $cluster->source_count);
        $this->assertSame('https://kysiden.no/2026/09/15/fengslene-sparer/', $cluster->primary_url);
        $this->assertGreaterThanOrEqual(80, $cluster->relevance_score);
        $this->assertContains('Norske fengsler må spare 83 millioner kroner', array_column(app(CorrectionalNewsService::class)->frontPage()['national'], 'title'));
    }

    public function testScorerRejectsOrdinaryCrimeButKeepsRelevantStoryWithNegativeWords(): void
    {
        $scorer = app(CorrectionalNewsRelevanceScorer::class);
        $this->assertLessThan(80, $scorer->score(['title' => 'Tiltalt krever lavere dom og fengselsstraff'], 'nff'));
        $this->assertGreaterThanOrEqual(80, $scorer->score(['title' => 'Dømt innsatt får bedre soningsforhold i Kriminalomsorgen'], 'nff'));
        $this->assertGreaterThanOrEqual(80, $scorer->score(['title' => 'Fengslene må spare 83 millioner', 'labels' => ['Kriminalomsorg']], 'nff'));
    }

    public function testOpinionAndRoutineUpdatesDoNotQualifyAsNationalButMajorCasesDo(): void
    {
        $scorer = app(CorrectionalNewsNationalSignificanceScorer::class);
        $this->assertSame(0, $scorer->assess(['title' => 'Kriminalomsorgen trenger en plan', 'url' => 'https://example.test/debatt/plan'])['score']);
        $this->assertSame('opinion', $scorer->assess(['title' => 'Kriminalomsorgen trenger en plan', 'labels' => ['Debatt']])['content_type']);
        $this->assertSame(0, $scorer->assess(['title' => 'Nøkkeltall fra kriminalomsorgen for august'])['score']);
        $this->assertSame(0, $scorer->assess(['title' => 'Bastøy fengsel anmelder egen ansatt'])['score']);
        $this->assertGreaterThanOrEqual(60, $scorer->assess(['title' => 'Fengslene må spare 83 millioner'])['score']);
        $this->assertGreaterThanOrEqual(60, $scorer->assess(['title' => 'Nasjonal plan for fengselskapasitet'])['score']);
        $this->assertGreaterThanOrEqual(60, $scorer->assess(['title' => 'Tilsyn avdekker ulovlig isolasjon i norske fengsler'])['score']);
    }

    public function testDebateStaysInUnionWhileTheLargerSavingsNewsIsNational(): void
    {
        $nff = app(NffNewsSource::class);
        Http::fake([$nff->url() => Http::response($this->rss([
            ['Kriminalomsorgen trenger en tydelig plan', 'https://frifagbevegelse.test/debatt/plan', 'Tue, 15 Sep 2026 10:00:00 +0200', 'Debatt'],
            ['Fengslene må spare 83 millioner', 'https://frifagbevegelse.test/nff-magasinet/sparing', 'Tue, 15 Sep 2026 09:00:00 +0200', 'Kriminalomsorg'],
        ]), 200)]);
        app(CorrectionalNewsService::class)->refresh('nff');
        $frontPage = app(CorrectionalNewsService::class)->frontPage();
        $this->assertSame(['Fengslene må spare 83 millioner'], array_column($frontPage['national'], 'title'));
        $this->assertContains('Kriminalomsorgen trenger en tydelig plan', array_column($frontPage['union'], 'title'));
    }

    public function testExistingUrlIsReassessedWithoutCreatingADuplicate(): void
    {
        $this->fakeFeeds();
        $service = app(CorrectionalNewsService::class);
        $service->refresh('nff');
        $report = $service->refresh('nff')[0];
        $this->assertSame(1, $report['updated']);
        $this->assertDatabaseCount('correctional_news_items', 1);
        $this->assertDatabaseHas('correctional_news_items', ['national_significance_score' => 80]);
    }

    public function testDifferentEventsAtTheSamePrisonAreNotMerged(): void
    {
        $service = app(CorrectionalNewsService::class);
        $nff = app(NffNewsSource::class);
        Http::fake([
            $nff->url() => Http::response($this->rss([
                ['Bemanning ved Oslo fengsel styrkes', 'https://nff.test/a', 'Tue, 15 Sep 2026 10:00:00 +0200', 'Kriminalomsorg'],
                ['Tilsyn med isolasjon i Oslo fengsel', 'https://nff.test/b', 'Tue, 15 Sep 2026 11:00:00 +0200', 'Kriminalomsorg'],
            ]), 200),
        ]);
        $service->refresh('nff');
        $this->assertSame(2, CorrectionalNewsCluster::count());
    }

    public function testUnionListIsBalancedLimitedAndSuppressesNationalClusters(): void
    {
        $now = now();
        foreach (['nff', 'ky', 'nff', 'nff', 'nff', 'nff'] as $index => $sourceKey) {
            $cluster = CorrectionalNewsCluster::create([
                'category' => $index === 0 ? 'national' : 'union', 'display_title' => 'Sak '.$index,
                'primary_source' => strtoupper($sourceKey), 'primary_url' => 'https://example.test/'.$index,
                'primary_published_at' => $now->copy()->subHours($index), 'relevance_score' => 20,
                'source_count' => 1, 'first_seen_at' => $now, 'last_seen_at' => $now,
                'expires_at' => $now->copy()->addDays(10),
            ]);
            CorrectionalNewsItem::create([
                'correctional_news_cluster_id' => $cluster->id, 'source_key' => $sourceKey, 'source_name' => strtoupper($sourceKey),
                'original_title' => 'Sak '.$index, 'normalized_title' => 'sak '.$index,
                'original_url' => 'https://example.test/'.$index, 'normalized_url' => 'https://example.test/'.$index,
                'normalized_url_hash' => hash('sha256', 'https://example.test/'.$index), 'published_at' => $now->copy()->subHours($index),
                'fetched_at' => $now, 'labels' => [],
            ]);
        }
        $result = app(CorrectionalNewsService::class)->frontPage();
        $this->assertCount(5, $result['union']);
        $this->assertContains('KY', array_column($result['union'], 'label'));
        $this->assertNotContains('Sak 0', array_column($result['union'], 'title'));
    }

    public function testExpiredNationalAndUnionStoriesAreNotShown(): void
    {
        $old = now()->subSecond();
        foreach (['national', 'union'] as $index => $category) {
            CorrectionalNewsCluster::create([
                'category' => $category, 'display_title' => 'Gammel '.$category, 'primary_source' => 'NFF',
                'primary_url' => 'https://example.test/old-'.$index, 'source_count' => 0, 'first_seen_at' => $old,
                'last_seen_at' => $old, 'expires_at' => $old,
            ]);
        }
        $this->assertSame(['national' => [], 'union' => []], app(CorrectionalNewsService::class)->frontPage());
    }

    public function testFailureInOneFeedDoesNotStopOtherSources(): void
    {
        $this->fakeFeeds(['https://kysiden.no/feed/' => Http::response('unavailable', 500)]);
        $reports = app(CorrectionalNewsService::class)->refresh();
        $this->assertNotNull(collect($reports)->firstWhere('source', 'ky')['error']);
        $this->assertSame(1, collect($reports)->firstWhere('source', 'kdi')['stored']);
    }

    public function testConnectionTimeoutKeepsPreviouslyStoredNewsAvailable(): void
    {
        Http::fake(['https://kommunikasjon.ntb.no/rss/releases/latest?publisherId=17847130' => Http::response($this->fixture('kdi.xml'), 200)]);
        $service = app(CorrectionalNewsService::class);
        $service->refresh('kdi');

        Http::fake(['https://kommunikasjon.ntb.no/rss/releases/latest?publisherId=17847130' => function () {
            throw new ConnectionException('Connection timed out');
        }]);
        $report = $service->refresh('kdi')[0];

        $this->assertNotNull($report['error']);
        $this->assertDatabaseHas('correctional_news_items', ['original_title' => 'Nye tiltak for bedre soningsforhold']);
    }

    public function testEmptyFeedIsAValidNoNewsResultButInvalidXmlIsReported(): void
    {
        Http::fake([
            'https://kysiden.no/feed/' => Http::response('<rss version="2.0"><channel><title>KY</title></channel></rss>', 200),
            'https://www.frifagbevegelse.no/nff-magasinet/?lab_viewport=rss' => Http::response('<rss><broken>', 200),
        ]);
        $service = app(CorrectionalNewsService::class);
        $empty = $service->refresh('ky')[0];
        $invalid = $service->refresh('nff')[0];
        $this->assertSame(0, $empty['found']);
        $this->assertNull($empty['error']);
        $this->assertNotNull($invalid['error']);
    }

    public function testRepeatedHttpFailureUsesTheExistingNotifierCooldown(): void
    {
        Http::fake(['https://kommunikasjon.ntb.no/rss/releases/latest?publisherId=17847130' => Http::response('slow down', 429)]);
        $service = app(CorrectionalNewsService::class);
        $service->refresh('kdi');
        $service->refresh('kdi');
        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->service === 'correctional-news' && $mail->operation === 'kdi' && $mail->status === 429;
        });
        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
    }

    private function fakeFeeds(array $overrides = []): void
    {
        Http::fake(array_merge([
            'https://www.frifagbevegelse.no/nff-magasinet/?lab_viewport=rss' => Http::response($this->fixture('nff.xml'), 200),
            'https://kysiden.no/feed/' => Http::response($this->fixture('ky.xml'), 200),
            'https://kommunikasjon.ntb.no/rss/releases/latest?publisherId=17847130' => Http::response($this->fixture('kdi.xml'), 200),
            'https://www.sivilombudet.no/feed/' => Http::response($this->fixture('sivilombudet.xml'), 200),
        ], $overrides));
    }

    private function fixture(string $name): string
    {
        return file_get_contents(base_path('tests/Fixtures/correctional-news/'.$name));
    }

    private function rss(array $items): string
    {
        $xml = '<rss version="2.0"><channel>';
        foreach ($items as [$title, $url, $date, $category]) {
            $xml .= "<item><title>{$title}</title><link>{$url}</link><pubDate>{$date}</pubDate><category>{$category}</category></item>";
        }
        return $xml.'</channel></rss>';
    }
}
