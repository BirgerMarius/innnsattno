<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TodayPageTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function testTodaysPageShowsLocalDateFactsAndExternalData(): void
    {
        Carbon::setTestNow('2026-08-01 12:00:00 Europe/Oslo');
        $this->fakeSources();

        $this->get('/dagen-i-dag')
            ->assertOk()
            ->assertSee('Lørdag 1. august 2026')
            ->assertSeeInOrder(['Uke', '31', 'Dag', '213 av 365', 'Igjen av året', '152'])
            ->assertSee('Peder og Petra')
            ->assertSee('Historiske hendelser')
            ->assertSee('Testhendelse')
            ->assertSee('Født denne dagen')
            ->assertSee('Testperson')
            ->assertSee('Døde denne dagen')
            ->assertSee('Avdød person')
            ->assertSee('Creative Commons Navngivelse-DelPåSammeVilkår 4.0')
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function testExplicitValidDateAndNavigationWork(): void
    {
        $this->fakeSources();

        $this->get('/dagen-i-dag/2026-05-17')
            ->assertOk()
            ->assertSee('Søndag 17. mai 2026')
            ->assertSee('Grunnlovsdagen')
            ->assertSee('href="'.route('today.show', ['date' => '2026-05-16']).'"', false)
            ->assertSee('href="'.route('today.show', ['date' => '2026-05-18']).'"', false)
            ->assertSee('href="'.route('today.show').'"', false);
    }

    public function testInvalidDatesAreHandledAsNotFound(): void
    {
        $this->get('/dagen-i-dag/ikke-en-dato')->assertNotFound();
        $this->get('/dagen-i-dag/2026-02-30')->assertNotFound();
    }

    public function testHomepageDateLinksToTodayPageAndShowsNamedays(): void
    {
        Carbon::setTestNow('2026-08-01 12:00:00 Europe/Oslo');
        $this->fakeSources();

        $this->get('/tv')
            ->assertOk()
            ->assertSee('href="'.route('today.show').'"', false)
            ->assertSee('Peder, Petra');
    }

    public function testSourcesAreCachedPerDate(): void
    {
        $this->fakeSources();

        $this->get('/dagen-i-dag/2026-08-01')->assertOk();
        $this->get('/dagen-i-dag/2026-08-01')->assertOk();

        Http::assertSentCount(3);
    }

    public function testLastSuccessfulDataIsUsedWhenSourcesFail(): void
    {
        $this->fakeSources();
        $this->get('/dagen-i-dag/2026-08-01')->assertSee('Testhendelse');

        Cache::forget('today.fresh.namedays.08-01');
        Cache::forget('today.fresh.wikimedia.08-01');
        Http::fake(fn () => throw new ConnectionException('Kilden er utilgjengelig'));

        $this->get('/dagen-i-dag/2026-08-01')
            ->assertOk()
            ->assertSee('Peder og Petra')
            ->assertSee('Testhendelse');
    }

    public function testEmptyOrUnavailableSourcesStillRenderLocalInformation(): void
    {
        Http::fake([
            'webapi.no/*' => Http::response(['data' => []]),
            'en.wikipedia.org/*' => Http::response(['events' => [], 'births' => [], 'deaths' => []]),
        ]);

        $this->get('/dagen-i-dag/2026-08-01')
            ->assertOk()
            ->assertSee('Lørdag 1. august 2026')
            ->assertSee('Navnedager er ikke tilgjengelige akkurat nå.')
            ->assertSee('Historiske hendelser er ikke tilgjengelige akkurat nå.');
    }

    private function fakeSources(): void
    {
        Http::fake([
            'webapi.no/*' => Http::response([
                'data' => [
                    ['month' => 8, 'day' => 1, 'names' => ['Peder', 'Petra']],
                    ['month' => 5, 'day' => 17, 'names' => ['Harald', 'Ragnhild']],
                ],
            ]),
            'en.wikipedia.org/*' => Http::response([
                'events' => [$this->entry(1901, 'Testhendelse', 'Testartikkel', 'Q1')],
                'births' => [$this->entry(1950, 'Testperson, norsk forfatter', 'Testperson', 'Q2')],
                'deaths' => [$this->entry(2000, 'Avdød person, norsk artist', 'Avdød person', 'Q3')],
            ]),
            'www.wikidata.org/*' => Http::response([
                'entities' => [
                    ['id' => 'Q1', 'sitelinks' => ['nowiki' => ['title' => 'Testartikkel']]],
                    ['id' => 'Q2', 'sitelinks' => ['nowiki' => ['title' => 'Testperson']]],
                ],
            ]),
        ]);
    }

    private function entry(int $year, string $text, string $title, string $id): array
    {
        return [
            'year' => $year,
            'text' => $text,
            'pages' => [[
                'wikibase_item' => $id,
                'titles' => ['normalized' => $title],
                'description' => 'Kort beskrivelse',
                'content_urls' => ['desktop' => ['page' => 'https://en.wikipedia.org/wiki/Test']],
            ]],
        ];
    }
}
