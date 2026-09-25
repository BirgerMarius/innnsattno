<?php

namespace Tests\Feature;

use App\Services\RingbladNewsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HomepageTextTest extends TestCase
{
    private const OFFICER_TRIBUTE = 'Hver dag bidrar fengselsbetjenter til trygghet, håp og nye muligheter – med profesjonalitet, menneskelighet og mot gjør dere en uvurderlig forskjell for hele samfunnet.';

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function testHomepageContainsReorganizedActionsAndNoOfficerTribute(): void
    {
        $response = $this->get(route('tv'));

        $response
            ->assertOk()
            ->assertSee('Skriv ut TV-guide – Ringerike fengsel')
            ->assertSee('href="/print"', false)
            ->assertSee('Bønnetider – Ringerike fengsel')
            ->assertSee('href="/bonnetider"', false)
            ->assertSee('Værmelding – Tyristrand/Ringerike fengsel')
            ->assertSee('href="'.route('weather.index').'"', false)
            ->assertSee('Skriv ut TV-guide – Ilseng fengsel')
            ->assertSee('href="/print-ilseng"', false)
            ->assertSee('Bønnetider – Ilseng fengsel')
            ->assertSee('href="/bonnetider-ilseng"', false)
            ->assertSee('href="'.route('weather.ilseng').'"', false)
            ->assertSee('Værmelding – Ilseng fengsel')
            ->assertSee('href="'.route('visitation.index').'"', false)
            ->assertSee('ℹ️ Ringerike fengsel')
            ->assertSee('ℹ️ Ilseng fengsel')
            ->assertSee('class="prison-actions-placeholder"', false)
            ->assertDontSee('prison-actions-heading')
            ->assertSee('href="/oppdrag"', false)
            ->assertSee('Spinn hjulet')
            ->assertSee('href="'.route('feedback.create').'"', false)
            ->assertSee('Har du en idé?')
            ->assertDontSee(self::OFFICER_TRIBUTE);

        $this->assertSame(1, substr_count((string) $response->getContent(), 'Visitasjonsrullett'));
        $response->assertDontSee('Visitasjonsrullett – Ilseng');
        $this->assertMatchesRegularExpression(
            '/Værmelding – Ilseng fengsel.*prison-actions-placeholder.*ℹ️ Ilseng fengsel/s',
            (string) $response->getContent()
        );
    }

    public function testHomepageMarksQuizAsUnderDevelopment(): void
    {
        Cache::put(RingbladNewsService::CACHE_KEY, [[
            'title' => 'Lokal sak for rekkefølgetest',
            'url' => 'https://www.ringblad.no/lokal-sak/s/5-45-600',
            'published_at' => null,
            'is_subscription' => false,
            'image_url' => null,
        ]], 60);

        $response = $this->get(route('tv'));

        $response
            ->assertOk()
            ->assertSee('Lag en quiz')
            ->assertSee('Spillforslag – for utskrift')
            ->assertSee('href="'.route('activities.index').'"', false)
            ->assertSee('Under utvikling')
            ->assertDontSee('NYHET')
            ->assertDontSee('TEST')
            ->assertSee('front-page-btn--test', false)
            ->assertSeeInOrder([
                'Premier League',
                'Eliteserien',
                'Champions League',
                'Europa League',
                'Conference League',
                'Nations League',
                'Tidsfordriv – Ordjakt',
                'Tidsfordriv – Sudoku',
                'Månedskalender – For utskrift',
                'Lag en quiz',
                'Lær noe nytt',
                'Spinn hjulet',
            ]);

        $response->assertSeeInOrder([
            'Spinn hjulet',
            'Aktuelt fra kriminalomsorgen',
            'Anbefalt fagstoff',
            'Fagnyheter',
            'Har du en idé?',
        ]);

        $content = (string) $response->getContent();
        $this->assertSame(2, substr_count($content, 'front-page-btn--test'));
        $this->assertSame(3, substr_count($content, 'front-page-quiz-badges'));
        $this->assertSame(6, substr_count($content, 'front-page-btn--football'));
        $this->assertSame(0, preg_match('/href="\/(?:tidsfordriv|ordjakt)"[^>]*front-page-btn--football/', $content));
        $this->assertSame(0, preg_match('/href="\/ordjakt"[^>]*front-page-btn--wide/', $content));
        $this->assertSame(1, preg_match('/front-page-btn--wide" role="button">\s*<i class="far fa-calendar-alt">/s', $content));
        $this->assertSame(1, substr_count($content, 'Spillforslag – for utskrift'));
    }

    public function testHomepageShowsTheNationsLeagueNewBadgeOnlyDuringLaunchWeek(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-24 12:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertSee('front-page-new-badge', false)->assertSee('>NY<', false);

        Carbon::setTestNow(Carbon::parse('2026-09-28 00:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('front-page-new-badge', false);
        Carbon::setTestNow();
    }

    public function testActivitiesNewBadgeUsesTheFixedOsloPublicationTimeAndExpiresAfterFourteenDays(): void
    {
        Config::set('activities.published_at', '2026-10-09 09:00:00');

        Carbon::setTestNow(Carbon::parse('2026-10-09 08:59:59', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('>Nyhet<', false);

        Carbon::setTestNow(Carbon::parse('2026-10-09 09:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertSee('>Nyhet<', false)->assertSee('Under utvikling');

        Carbon::setTestNow(Carbon::parse('2026-10-23 08:59:59', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertSee('>Nyhet<', false)->assertSee('Under utvikling');

        Carbon::setTestNow(Carbon::parse('2026-10-23 09:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('>Nyhet<', false)->assertSee('Under utvikling');
        Carbon::setTestNow();
    }
}
