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
            ->assertSee('Denne dagen i Norge')
            ->assertSee('Testartikkel')
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

    public function testNavigationHandlesYearBoundaryAndLeapDay(): void
    {
        $this->fakeSources();

        $this->get('/dagen-i-dag/2024-02-29')
            ->assertOk()
            ->assertSee('Torsdag 29. februar 2024')
            ->assertSee('href="'.route('today.show', ['date' => '2024-03-01']).'"', false)
            ->assertSee('Skuddagen');

        $this->get('/dagen-i-dag/2026-12-31')
            ->assertOk()
            ->assertSee('href="'.route('today.show', ['date' => '2027-01-01']).'"', false);
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

    public function testTodayPageShowsTheActiveMourningPeriodNotice(): void
    {
        config([
            'mourning_flag.enabled' => true,
            'mourning_flag.from' => '2026-08-28',
            'mourning_flag.until' => null,
            'mourning_flag.title' => 'H.M. Kong Harald V er død.',
            'mourning_flag.message' => 'Norge er i en nasjonal sørgeperiode. Det flagges på halv stang fra statlige bygninger frem til gravferdsdagen.',
            'mourning_flag.source_url' => 'https://www.kongehuset.no/',
            'mourning_flag.source_name' => 'Kongehuset.no',
        ]);
        $this->fakeSources();

        $this->get('/dagen-i-dag/2026-09-01')
            ->assertOk()
            ->assertSee('H.M. Kong Harald V er død.')
            ->assertSee('Norge er i en nasjonal sørgeperiode. Det flagges på halv stang fra statlige bygninger frem til gravferdsdagen.')
            ->assertSee('Offisiell informasjon: Kongehuset.no')
            ->assertSee('href="https://www.kongehuset.no/"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function testSourcesAreCachedPerDate(): void
    {
        $this->fakeSources();

        $this->get('/dagen-i-dag/2026-08-01')->assertOk();
        $this->get('/dagen-i-dag/2026-08-01')->assertOk();

        Http::assertSentCount(4);
    }

    public function testLastSuccessfulDataIsUsedWhenSourcesFail(): void
    {
        $this->fakeSources();
        $this->get('/dagen-i-dag/2026-08-01')->assertSee('Testartikkel');

        Cache::forget('today.fresh.namedays.08-01');
        Cache::forget('today.fresh.curated.v3.08-01');
        Http::fake(fn () => throw new ConnectionException('Kilden er utilgjengelig'));

        $this->get('/dagen-i-dag/2026-08-01')
            ->assertOk()
            ->assertSee('Peder og Petra')
            ->assertSee('Testartikkel');
    }

    public function testEmptyOrUnavailableSourcesStillRenderLocalInformation(): void
    {
        Cache::forget('today.fresh.curated.v3.08-02');
        Cache::forget('today.stale.curated.v3.08-02');
        Cache::forget('today.fresh.namedays.08-02');
        Cache::forget('today.stale.namedays.08-02');
        Http::fake([
            'webapi.no/*' => Http::response(['data' => []]),
            'no.wikipedia.org/*' => Http::response([]),
            'en.wikipedia.org/*' => Http::response(['events' => [], 'births' => [], 'deaths' => []]),
        ]);

        $this->get('/dagen-i-dag/2026-08-02')
            ->assertOk()
            ->assertSee('Søndag 2. august 2026')
            ->assertSee('Navnedager er ikke tilgjengelige akkurat nå.')
            ->assertSee('Vi fant ingen historiske oppføringer med god nok norsk tekst for denne datoen.');
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
            'no.wikipedia.org/*' => Http::response([
                'events' => [$this->entry(1901, 'Testhendelse ble markert i Norge', 'Testartikkel', 'Q1')],
                'births' => [$this->entry(1950, 'Testperson er en norsk forfatter', 'Testperson', 'Q2')],
                'deaths' => [$this->entry(2000, 'Avdød person var en norsk artist', 'Avdød person', 'Q3')],
            ]),
            'www.wikidata.org/*' => Http::response([
                'entities' => [
                    $this->entity('Q1', 'Testartikkel', 'En norsk testhendelse'),
                    $this->entity('Q2', 'Testperson', 'Norsk forfatter', true),
                    $this->entity('Q3', 'Avdød person', 'Norsk artist', true),
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

    private function entity(string $id, string $title, string $description, bool $norwegian = false): array
    {
        return [
            'id' => $id,
            'labels' => ['nb' => ['value' => $title]],
            'descriptions' => ['nb' => ['value' => $description]],
            'sitelinks' => ['nowiki' => ['title' => $title]],
            'claims' => $norwegian ? ['P27' => [['mainsnak' => ['datavalue' => ['value' => ['id' => 'Q20']]]]]] : [],
        ];
    }
}
