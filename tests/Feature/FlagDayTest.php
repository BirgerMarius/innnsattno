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
        $response->assertDontSee('<span class="front-page-date-item front-page-flag-today">', false);
        $response->assertDontSee('norwegian-flag.svg');
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

    public function testFlagDayShowsTodayMessageWithLocalFlagsAndLinkedName(): void
    {
        Carbon::setTestNow('2026-07-29 12:00:00 Europe/Oslo');

        $response = $this->get('/tv');

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'class="front-page-flag-icon"',
                'Det er flaggdag i dag:',
                'href="https://snl.no/olsok"',
                'Olsokdagen',
                'class="front-page-flag-icon"',
            ], false)
            ->assertDontSee('Neste flaggdag:');

        $this->assertSame(2, substr_count($response->getContent(), 'src="'.asset('img/norwegian-flag.svg').'"'));
    }

    public function testTodayMarkingIsAbsentDayBeforeAndDayAfterFlagDay(): void
    {
        foreach (['2026-07-28 12:00:00', '2026-07-30 12:00:00'] as $time) {
            Carbon::setTestNow($time.' Europe/Oslo');

            $this->get('/tv')
                ->assertOk()
                ->assertDontSee('Det er flaggdag i dag:')
                ->assertDontSee('<span class="front-page-date-item front-page-flag-today">', false)
                ->assertDontSee('norwegian-flag.svg');
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

    public function testRoyalBirthdayAgesAreCalculatedFromBirthYearForEachCalendarYear(): void
    {
        $ingridIn2027 = collect(app(FlagDayService::class)->forYear(2027))
            ->firstWhere('name', 'H.K.H. Prinsesse Ingrid Alexandra');
        $ingridIn2028 = collect(app(FlagDayService::class)->forYear(2028))
            ->firstWhere('name', 'H.K.H. Prinsesse Ingrid Alexandra');
        $newYearsDay = collect(app(FlagDayService::class)->forYear(2027))
            ->firstWhere('name', '1. nyttårsdag');

        $this->assertSame(2004, $ingridIn2027['birth_year']);
        $this->assertSame(23, $ingridIn2027['age']);
        $this->assertSame(24, $ingridIn2028['age']);
        $this->assertArrayNotHasKey('birth_year', $newYearsDay);
        $this->assertArrayNotHasKey('age', $newYearsDay);
    }

    public function testRoyalBirthdayAgeIsShownForNextFlagDay(): void
    {
        Carbon::setTestNow('2027-01-20 12:00:00 Europe/Oslo');

        $this->get('/tv')
            ->assertOk()
            ->assertSeeInOrder([
                'Neste flaggdag:',
                '21. januar',
                'H.K.H. Prinsesse Ingrid Alexandra (23 år)',
            ]);
    }

    public function testRoyalBirthdayAgeIsShownOnTheBirthdayInsideTheInformationLink(): void
    {
        Carbon::setTestNow('2027-01-21 12:00:00 Europe/Oslo');

        $this->get('/tv')
            ->assertOk()
            ->assertSeeInOrder([
                'Det er flaggdag i dag:',
                'href="https://www.kongehuset.no/kongehuset/hennes-kongelige-hoyhet-prinsessen/prinsesse-ingrid-alexandras-biografi"',
                'H.K.H. Prinsesse Ingrid Alexandra (23 år)',
                '</a>',
            ], false);
    }

    public function testOrdinaryFlagDayDoesNotShowAnAge(): void
    {
        Carbon::setTestNow('2027-01-01 12:00:00 Europe/Oslo');

        $this->get('/tv')
            ->assertOk()
            ->assertSee('1. nyttårsdag')
            ->assertDontSee('1. nyttårsdag (');
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
            ->assertSeeInOrder(['class="front-page-flag-icon"', 'Det er flaggdag i dag:', 'Olsokdagen', 'class="front-page-flag-icon"'], false);
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

    public function testNextFlagDayIsExcludedFromThreeDistinctFollowingFlagDaysAcrossNewYear(): void
    {
        Carbon::setTestNow('2026-12-24 12:00:00 Europe/Oslo');

        $response = $this->get('/tv');

        $response->assertOk();
        $response->assertSeeInOrder([
            'Neste flaggdag:',
            '25. desember',
            '1. juledag',
            'Kommende flaggdager:',
            '<strong>1. jan. 2027</strong>:',
            'href="https://snl.no/nytt%C3%A5rsdag"',
            '1. nyttårsdag',
            '<strong>21. jan. 2027</strong>:',
            'href="https://www.kongehuset.no/kongehuset/hennes-kongelige-hoyhet-prinsessen/prinsesse-ingrid-alexandras-biografi"',
            'H.K.H. Prinsesse Ingrid Alexandra (23 år)',
            '<strong>6. feb. 2027</strong>:',
            'href="https://snl.no/samenes_nasjonaldag"',
            'Samenes nasjonaldag',
        ], false);

        $content = $response->getContent();
        $overview = app(FlagDayService::class)->overview();
        $upcomingDates = array_map(fn (array $flagDay) => $flagDay['date']->toDateString(), $overview['upcoming']);
        $upcomingNames = array_column($overview['upcoming'], 'name');

        $this->assertSame('1. juledag', $overview['next']['name']);
        $this->assertSame(['1. nyttårsdag', 'H.K.H. Prinsesse Ingrid Alexandra', 'Samenes nasjonaldag'], $upcomingNames);
        $this->assertSame(['2027-01-01', '2027-01-21', '2027-02-06'], $upcomingDates);
        $this->assertCount(3, array_unique($upcomingDates));
        $this->assertCount(3, array_unique($upcomingNames));
        $this->assertNotContains($overview['next']['date']->toDateString(), $upcomingDates);
        $this->assertNotContains($overview['next']['name'], $upcomingNames);
        $this->assertSame(1, substr_count($content, '>1. juledag<'));
        $this->assertSame(3, substr_count($content, 'class="front-page-upcoming-flag-day"'));
        $this->assertSame(2, substr_count($content, 'class="front-page-upcoming-flag-day-separator"'));
        $this->assertSame(2, substr_count($content, 'aria-hidden="true">|</span>'));
        $this->assertSame(3, preg_match_all(
            '/class="front-page-upcoming-flag-day".*?<a class="front-page-flag-link"\s+href="[^"]+"\s+target="_blank"\s+rel="noopener noreferrer">/s',
            $content
        ));
        $response->assertDontSee('<strong>25. des. 2026</strong>:', false);
        $response->assertDontSee('H.M. Kong Harald V');
    }

    public function testCurrentFlagDayIsExcludedFromThreeDistinctFollowingFlagDaysAcrossNewYear(): void
    {
        Carbon::setTestNow('2026-12-25 12:00:00 Europe/Oslo');

        $response = $this->get('/tv');
        $overview = app(FlagDayService::class)->overview();
        $upcomingDates = array_map(fn (array $flagDay) => $flagDay['date']->toDateString(), $overview['upcoming']);
        $upcomingNames = array_column($overview['upcoming'], 'name');

        $response->assertOk()
            ->assertSeeInOrder([
                'Det er flaggdag i dag:',
                '1. juledag',
                'Kommende flaggdager:',
                '<strong>1. jan. 2027</strong>:',
                '1. nyttårsdag',
                '<strong>21. jan. 2027</strong>:',
                'H.K.H. Prinsesse Ingrid Alexandra (23 år)',
                '<strong>6. feb. 2027</strong>:',
                'Samenes nasjonaldag',
            ], false)
            ->assertDontSee('<strong>25. des. 2026</strong>:', false);

        $this->assertTrue($overview['is_flag_day']);
        $this->assertSame('1. juledag', $overview['next']['name']);
        $this->assertSame(['2027-01-01', '2027-01-21', '2027-02-06'], $upcomingDates);
        $this->assertSame(['1. nyttårsdag', 'H.K.H. Prinsesse Ingrid Alexandra', 'Samenes nasjonaldag'], $upcomingNames);
        $this->assertCount(3, array_unique($upcomingDates));
        $this->assertCount(3, array_unique($upcomingNames));
        $this->assertNotContains($overview['next']['date']->toDateString(), $upcomingDates);
        $this->assertNotContains($overview['next']['name'], $upcomingNames);
        $this->assertSame(1, substr_count($response->getContent(), '>1. juledag<'));
        $this->assertSame(3, substr_count($response->getContent(), 'class="front-page-upcoming-flag-day"'));
    }
}
