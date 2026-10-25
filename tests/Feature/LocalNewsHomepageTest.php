<?php

namespace Tests\Feature;

use App\Services\LocalNewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocalNewsHomepageTest extends TestCase
{
    use RefreshDatabase;

    public function testEachPrisonHomepageUsesOnlyItsOwnConfiguredNewsAndText(): void
    {
        $service = app(LocalNewsService::class);
        foreach ([
            'ringerike' => ['Ringerike-sak', 'https://www.ringblad.no/ringerike/s/5-45-1', 'Ringerike fengsel', 'Ringerikes Blad'],
            'ilseng' => ['Ilseng-sak', 'https://www.h-a.no/ilseng/s/5-139-1', 'Ilseng fengsel', 'Hamar Arbeiderblad'],
            'kongsvinger' => ['Kongsvinger-sak', 'https://www.glomdalen.no/kongsvinger/s/80-19-1', 'Kongsvinger fengsel', 'Glåmdalen'],
        ] as $prison => [$title, $url, $name, $source]) {
            Cache::put($service->cacheKey($prison), [[
                'title' => $title, 'url' => $url, 'published_at' => '20. okt 2026',
                'is_subscription' => true, 'image_url' => null,
            ]], 60);

            $this->get('/'.$prison)
                ->assertOk()
                ->assertSee('Lokale nyheter om '.$name)
                ->assertSee('Lokale nyheter om '.$name.' fra '.$source)
                ->assertSee($title)
                ->assertSee($url, false)
                ->assertSee('Se flere lokale nyheter hos '.$source)
                ->assertSee('Abonnement');
        }

        $this->get('/ilseng')->assertDontSee('Ringerike-sak')->assertDontSee('Kongsvinger-sak');
        $this->get('/kongsvinger')->assertDontSee('Ringerike-sak')->assertDontSee('Ilseng-sak');
        $this->get('/kongsvinger')->assertSee('https://www.glomdalen.no/tag/kriminalomsorgen-innlandet', false);
    }

    public function testSchedulerRefreshesEveryConfiguredLocalSourceSeparately(): void
    {
        Http::fake([
            'https://www.ringblad.no/ringerike-fengsel/' => Http::response($this->teaser('/ringerike/s/5-45-1', 'Ringerike'), 200),
            'https://www.h-a.no/ilseng-fengsel/' => Http::response($this->teaser('/ilseng/s/5-139-1', 'Ilseng'), 200),
            'https://www.glomdalen.no/kongsvinger-fengsel/' => Http::response($this->teaser('/kongsvinger/s/80-19-1', 'Kongsvinger'), 200),
            'https://www.glomdalen.no/tag/kriminalomsorgen-innlandet' => Http::response($this->teaser('/innlandet/s/80-19-2', 'Innlandet'), 200),
            '*' => Http::response('', 404),
        ]);

        $this->artisan('external-data:check-others')->assertExitCode(0);

        foreach (['ringerike', 'ilseng'] as $prison) {
            $this->assertDatabaseHas('external_data_checks', [
                'service' => 'local-news-'.$prison.'-source-0',
                'coverage_key' => 'front-page-news-'.$prison.'-source-0',
                'failure_count' => 0,
            ]);
        }
        foreach ([0, 1] as $sourceIndex) {
            $this->assertDatabaseHas('external_data_checks', [
                'service' => 'local-news-kongsvinger-source-'.$sourceIndex,
                'coverage_key' => 'front-page-news-kongsvinger-source-'.$sourceIndex,
                'failure_count' => 0,
            ]);
        }
        $this->assertSame(4, DB::table('external_data_checks')->where('service', 'like', 'local-news-%')->count());
    }

    private function teaser(string $url, string $title): string
    {
        return '<brick-teaser-v23 data-teaser-type="story"><a itemprop="url" href="'.$url.'"><span itemprop="titleText">'.$title.'</span></a></brick-teaser-v23>';
    }
}
