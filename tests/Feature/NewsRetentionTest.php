<?php

namespace Tests\Feature;

use App\NewsArticle;
use App\NewsArticleTombstone;
use App\NewsSource;
use App\Services\News\NewsFeedService;
use App\Services\News\NewsArticleRetentionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsRetentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::create(2026, 10, 8, 12, 0, 0, 'Europe/Oslo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testPruneDeletesExpiredArticlesButKeepsMinimalDuplicateRecords(): void
    {
        $source = $this->source();
        $expired = $this->article($source, 'Utløpt', now('Europe/Oslo')->subDays(10)->subSecond());
        $current = $this->article($source, 'På grensen', now('Europe/Oslo')->subDays(10));

        $this->artisan('news:prune')->expectsOutput('Slettet 1 utløpte nyhetsartikler.')->assertExitCode(0);

        $this->assertDatabaseMissing('news_articles', ['id' => $expired->id]);
        $this->assertDatabaseHas('news_article_tombstones', ['news_source_id' => $source->id, 'external_id' => $expired->external_id, 'normalized_url_hash' => $expired->normalized_url_hash]);
        $this->assertDatabaseHas('news_articles', ['id' => $current->id]);
        $this->assertDatabaseHas('news_sources', ['id' => $source->id]);
    }

    public function testRejectingAnArticleDeletesItsContentAndPreventsItsReturn(): void
    {
        $source = $this->source();
        $article = $this->article($source, 'Avvist', now('Europe/Oslo')->subHour());

        $this->withSession(['admin_authenticated' => true])
            ->patch(route('admin.news.status', $article), ['status' => NewsArticle::STATUS_HIDDEN])
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('news_articles', ['id' => $article->id]);
        $this->assertDatabaseHas('news_article_tombstones', ['news_source_id' => $source->id, 'external_id' => $article->external_id]);
        Http::fake([$source->feed_url => Http::response($this->rss($article->external_id, $article->original_url), 200)]);
        $report = app(NewsFeedService::class)->fetch($source->fresh());
        $this->assertSame(0, $report['new']);
        $this->assertSame(1, $report['duplicates']);
        $this->assertDatabaseMissing('news_articles', ['original_title' => 'Avvist']);
    }

    public function testExpiredIncomingArticleIsNeverStoredAndItsIdentifierIsRemembered(): void
    {
        $source = $this->source();
        $url = 'https://example.test/old';
        Http::fake([$source->feed_url => Http::response($this->rss('old-item', $url, now('Europe/Oslo')->subDays(11)), 200)]);

        $first = app(NewsFeedService::class)->fetch($source);
        $second = app(NewsFeedService::class)->fetch($source->fresh());

        $this->assertSame(0, $first['new']);
        $this->assertSame(0, NewsArticle::count());
        $this->assertSame(1, NewsArticleTombstone::count());
        $this->assertSame(1, $second['duplicates']);
    }

    public function testArticleIsNotDeletedWhenRecordingItsTombstoneFails(): void
    {
        $source = $this->source();
        $article = $this->article($source, 'Behold ved feil', now('Europe/Oslo')->subDays(11));
        DB::unprepared("CREATE TRIGGER fail_news_tombstone BEFORE INSERT ON news_article_tombstones BEGIN SELECT RAISE(FAIL, 'tombstone failed'); END;");

        try {
            app(NewsArticleRetentionService::class)->discard($article);
            $this->fail('Forventet feil ved lagring av tombstone.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('tombstone failed', $exception->getMessage());
        }

        $this->assertDatabaseHas('news_articles', ['id' => $article->id]);
        $this->assertSame(0, NewsArticleTombstone::count());
    }

    private function source(): NewsSource
    {
        return NewsSource::create(['name' => 'Kilde', 'slug' => 'kilde', 'country' => 'Norge', 'website_url' => 'https://example.test', 'feed_url' => 'https://feed.test/rss', 'source_type' => 'rss', 'is_active' => true]);
    }

    private function article(NewsSource $source, string $title, Carbon $publishedAt): NewsArticle
    {
        $id = strtolower(str_replace(' ', '-', $title));
        return NewsArticle::create(['news_source_id' => $source->id, 'external_id' => $id, 'original_url' => 'https://example.test/'.$id, 'normalized_url' => 'https://example.test/'.$id, 'original_title' => $title, 'fetched_at' => now('Europe/Oslo'), 'published_at' => $publishedAt, 'status' => NewsArticle::STATUS_PUBLISHED]);
    }

    private function rss(string $externalId, string $url, ?Carbon $publishedAt = null): string
    {
        $date = $publishedAt ? '<pubDate>'.$publishedAt->format(DATE_RSS).'</pubDate>' : '';
        return '<rss version="2.0"><channel><item><guid>'.$externalId.'</guid><title>Avvist</title><link>'.$url.'</link>'.$date.'</item></channel></rss>';
    }
}
