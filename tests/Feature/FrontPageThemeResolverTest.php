<?php

namespace Tests\Feature;

use App\Services\FrontPageThemeResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FrontPageThemeResolverTest extends TestCase
{
    private array $originalThemeConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalThemeConfig = config('front_page_themes');
        Http::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        config(['front_page_themes' => $this->originalThemeConfig]);

        parent::tearDown();
    }

    /**
     * @dataProvider automaticThemeDates
     */
    public function testItSelectsTheExpectedThemeThroughoutTheYear(string $date, string $expectedTheme): void
    {
        $theme = app(FrontPageThemeResolver::class)->resolve(Carbon::parse($date, 'Europe/Oslo'));

        $this->assertSame($expectedTheme, $theme['id']);
    }

    public static function automaticThemeDates(): array
    {
        return [
            'mørk vinter' => ['2026-01-05', 'mork-vinter'],
            'vinter' => ['2026-02-01', 'vinter'],
            'tidlig vår' => ['2026-03-01', 'tidlig-var'],
            'vanlig vår' => ['2026-03-15', 'var'],
            'palmesøndag' => ['2026-03-29', 'paske'],
            'andre påskedag' => ['2026-04-06', 'paske'],
            'etter påske' => ['2026-04-07', 'var'],
            'forsommer før nasjonaldagen' => ['2026-05-10', 'forsommer'],
            '17 mai' => ['2026-05-17', '17-mai'],
            'forsommer etter nasjonaldagen' => ['2026-05-20', 'forsommer'],
            'sommer i juni' => ['2026-06-15', 'sommer'],
            'midtsommer' => ['2026-07-01', 'midtsommer'],
            'sommer i juli' => ['2026-07-20', 'sommer'],
            'sensommer' => ['2026-08-25', 'sensommer'],
            'tidlig høst' => ['2026-08-26', 'tidlig-host'],
            'høst' => ['2026-10-01', 'host'],
            'senhøst' => ['2026-11-10', 'senhost'],
            'før advent' => ['2026-11-28', 'forjul'],
            'første advent' => ['2026-11-29', 'advent'],
            'jul' => ['2026-12-23', 'jul'],
            'nyttår i desember' => ['2026-12-30', 'nyttar'],
            'nyttår i januar' => ['2027-01-02', 'nyttar'],
            'mørk vinter etter nyttår' => ['2027-01-04', 'mork-vinter'],
        ];
    }

    public function testManualActiveThemeOverridesTheCalendar(): void
    {
        config(['front_page_themes.active' => 'jul']);

        $theme = app(FrontPageThemeResolver::class)->resolve(Carbon::parse('2026-08-25', 'Europe/Oslo'));

        $this->assertSame('jul', $theme['id']);
    }

    public function testInvalidManualThemeFallsBackToTheCalendar(): void
    {
        config(['front_page_themes.active' => 'ukjent']);

        $theme = app(FrontPageThemeResolver::class)->resolve(Carbon::parse('2026-08-25', 'Europe/Oslo'));

        $this->assertSame('sensommer', $theme['id']);
    }

    public function testCalendarHoleFallsBackToTheStandardTheme(): void
    {
        config(['front_page_themes.active' => null, 'front_page_themes.calendar' => []]);

        $this->assertNull(app(FrontPageThemeResolver::class)->resolve(Carbon::parse('2026-08-25', 'Europe/Oslo')));
    }

    public function testOrdinaryFrontPageUsesTheAutomaticThemeWithoutPreviewLabel(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 25, 12, 0, 0, 'Europe/Oslo'));

        $this->get('/tv')
            ->assertOk()
            ->assertSee('front-page-theme--sensommer', false)
            ->assertDontSee('Designforhåndsvisning');
    }

    public function testPreviewRouteAlwaysUsesItsExplicitTheme(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 25, 12, 0, 0, 'Europe/Oslo'));
        config(['front_page_themes.active' => 'jul']);

        $this->get('/design-test/host')
            ->assertOk()
            ->assertSee('front-page-theme--host', false)
            ->assertDontSee('front-page-theme--jul', false)
            ->assertSee('Designforhåndsvisning');
    }
}
