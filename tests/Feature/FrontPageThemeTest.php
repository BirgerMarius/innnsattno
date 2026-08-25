<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FrontPageThemeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    public function testOrdinaryFrontPageDoesNotShowThePreviewLabel(): void
    {
        $this->assertNull(config('front_page_themes.active'));

        $this->get('/tv')
            ->assertOk()
            ->assertSee('Skriv ut TV-guide – Ringerike fengsel')
            ->assertDontSee('Designforhåndsvisning');
    }

    public function testThemeOverviewLinksToAllPreviews(): void
    {
        $response = $this->get('/design-test');

        $response->assertOk()->assertSee('Sesongtemaer for Innsatt.no');

        foreach (array_keys(config('front_page_themes.themes')) as $theme) {
            $response->assertSee(route('design-test.preview', $theme), false);
        }
    }

    public function testEverySeasonalPreviewUsesTheSharedFrontPageContent(): void
    {
        foreach (config('front_page_themes.themes') as $id => $theme) {
            $this->get(route('design-test.preview', $id))
                ->assertOk()
                ->assertSee('front-page-theme--'.$id, false)
                ->assertSee('front-page-theme-art--'.$id, false)
                ->assertSee('Designforhåndsvisning')
                ->assertSee($theme['name'])
                ->assertSee('Skriv ut TV-guide – Ringerike fengsel')
                ->assertSee('Skriv ut TV-guide – Ilseng fengsel')
                ->assertSee('Har du en idé?');
        }
    }

    public function testUnknownThemeIsHandledAsNotFound(): void
    {
        $this->get('/design-test/ikke-et-tema')->assertNotFound();
    }

    public function testNationalDayAndEasterPreviewsRenderTheirSpecificDecorations(): void
    {
        $this->get('/design-test/17-mai')
            ->assertOk()
            ->assertSee('theme-art-norwegian-flags', false)
            ->assertSee('theme-art-flag-red', false)
            ->assertSee('theme-art-flag-white', false)
            ->assertSee('theme-art-flag-blue', false);

        $this->get('/design-test/paske')
            ->assertOk()
            ->assertSee('theme-art-eggs', false)
            ->assertSee('theme-art-bunny', false)
            ->assertSee('theme-art-chick', false);
    }

    public function testThemesAreConfiguredAndDoNotDuplicateTheFrontPageView(): void
    {
        $expectedThemeIds = [
            'sensommer', 'tidlig-host', 'host', 'senhost', 'forjul', 'advent',
            'jul', 'nyttar', 'mork-vinter', 'vinter', 'tidlig-var', 'var',
            'paske', '17-mai', 'forsommer', 'sommer', 'midtsommer',
        ];

        $this->assertSame($expectedThemeIds, array_keys(config('front_page_themes.themes')));
        $this->assertCount(17, config('front_page_themes.themes'));

        foreach (config('front_page_themes.themes') as $theme) {
            $this->assertArrayHasKey('start_date', $theme);
            $this->assertArrayHasKey('end_date', $theme);
            $this->assertArrayHasKey('priority', $theme);
        }

        $this->assertFileExists(resource_path('views/tv/guide.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/tv/host.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/tv/advent.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/tv/jul.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/tv/vinter.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/tv/var.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/tv/sommer.blade.php'));
    }
}
