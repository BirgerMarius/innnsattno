<?php

namespace Tests\Feature;

use App\Services\FlagDayService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlagDayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testOrdinaryDayShowsLinkedNextFlagDayWithoutTodayFlags(): void
    {
        Carbon::setTestNow('2026-07-28 12:00:00 Europe/Oslo');

        $response = $this->get('/tv');

        $response->assertOk();
        $response->assertSee('Neste flaggdag:');
        $response->assertSee('29. juli');
        $response->assertSee('Olsokdagen');
        $response->assertDontSee('I dag:');
        $response->assertDontSee('🇳🇴');
        $response->assertSee(
            'href="'.FlagDayService::OFFICIAL_OVERVIEW_URL.'"',
            false
        );
        $response->assertSee(
            'href="https://snl.no/olsok"',
            false
        );
        $response->assertSee('target="_blank"', false);
        $response->assertSee('rel="noopener noreferrer"', false);
    }

    public function testFlagDayShowsFlagsBeforeAndAfterLinkedName(): void
    {
        Carbon::setTestNow('2026-07-29 12:00:00 Europe/Oslo');

        $this->get('/tv')
            ->assertOk()
            ->assertSeeInOrder([
                '🇳🇴',
                'I dag:',
                'href="https://snl.no/olsok"',
                'Olsokdagen',
                '🇳🇴',
            ], false)
            ->assertDontSee('Neste flaggdag:');
    }

    public function testTodayMarkingIsAbsentDayBeforeAndDayAfterFlagDay(): void
    {
        foreach (['2026-07-28 12:00:00', '2026-07-30 12:00:00'] as $time) {
            Carbon::setTestNow($time.' Europe/Oslo');

            $this->get('/tv')
                ->assertOk()
                ->assertDontSee('I dag:')
                ->assertDontSee('🇳🇴');
        }
    }

    public function testRoyalBirthdaysUseTheSpecifiedRoyalHousePages(): void
    {
        $flagDays = collect(app(FlagDayService::class)->forYear(2026))->keyBy('name');

        $expected = [
            'H.K.H. Prinsesse Ingrid Alexandra' => 'https://www.kongehuset.no/kongehuset/hennes-kongelige-hoyhet-prinsessen/prinsesse-ingrid-alexandras-biografi',
            'H.M. Kong Harald V' => 'https://www.kongehuset.no/kongehuset/hans-majestet-kongen/kong-haralds-biografi',
            'H.M. Dronning Sonja' => 'https://www.kongehuset.no/kongehuset/hennes-majestet-dronningen/dronning-sonjas-biografi',
            'H.K.H. Kronprins Haakon' => 'https://www.kongehuset.no/kongehuset/hans-kongelige-hoyhet-kronprinsen/kronprins-haakons-biografi',
            'H.K.H. Kronprinsesse Mette-Marit' => 'https://www.kongehuset.no/kongehuset/hennes-kongelige-hoyhet-kronprinsessen/kronprinsesse-mette-marits-biografi',
        ];

        foreach ($expected as $name => $url) {
            $this->assertSame($url, $flagDays[$name]['information_url']);
        }
    }

    public function testMovingFlagDaysAreCalculatedInALaterCalendarYear(): void
    {
        $flagDays = collect(app(FlagDayService::class)->forYear(2030))->keyBy('name');

        $this->assertSame('2030-04-21', $flagDays['1. påskedag']['date']->toDateString());
        $this->assertSame('2030-06-09', $flagDays['1. pinsedag']['date']->toDateString());
    }

    public function testFlagDayUsesOsloDateWhenServerDateIsStillPreviousDay(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-28 22:30:00', 'UTC'));

        $this->get('/tv')
            ->assertOk()
            ->assertSeeInOrder(['🇳🇴', 'I dag:', 'Olsokdagen', '🇳🇴']);
    }

    public function testInformationLinkHasSafeExternalLinkAttributesOnFlagDay(): void
    {
        Carbon::setTestNow('2026-01-21 12:00:00 Europe/Oslo');

        $this->get('/tv')
            ->assertOk()
            ->assertSee(
                'href="https://www.kongehuset.no/kongehuset/hennes-kongelige-hoyhet-prinsessen/prinsesse-ingrid-alexandras-biografi"',
                false
            )
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false);
    }

    public function testExactlyThreeFollowingFlagDaysAndTheirInformationLinksAreShownAcrossNewYear(): void
    {
        Carbon::setTestNow('2026-12-24 12:00:00 Europe/Oslo');

        $response = $this->get('/tv');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Neste flaggdag:',
            '25. desember',
            '1. juledag',
            'Kommende flaggdager:',
            '1. januar',
            'href="https://snl.no/nytt%C3%A5rsdag"',
            '1. nyttårsdag',
            '21. januar',
            'href="https://www.kongehuset.no/kongehuset/hennes-kongelige-hoyhet-prinsessen/prinsesse-ingrid-alexandras-biografi"',
            'H.K.H. Prinsesse Ingrid Alexandra',
            '6. februar',
            'href="https://snl.no/samenes_nasjonaldag"',
            'Samenes nasjonaldag',
        ], false);

        $content = $response->getContent();

        $this->assertSame(3, substr_count($content, 'class="front-page-upcoming-flag-day"'));
        $this->assertSame(3, preg_match_all(
            '/class="front-page-upcoming-flag-day".*?<a class="front-page-flag-link"\s+href="[^"]+"\s+target="_blank"\s+rel="noopener noreferrer">/s',
            $content
        ));
        $response->assertDontSee('21. februar');
        $response->assertDontSee('H.M. Kong Harald V');
    }
}
