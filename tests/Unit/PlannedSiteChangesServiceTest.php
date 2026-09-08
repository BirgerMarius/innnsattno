<?php

namespace Tests\Unit;

use App\Services\PlannedSiteChangesService;
use Carbon\Carbon;
use Tests\TestCase;

class PlannedSiteChangesServiceTest extends TestCase
{
    private array $originalThemeConfig;
    private array $originalMourningConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalThemeConfig = config('front_page_themes');
        $this->originalMourningConfig = config('mourning_flag');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        config(['front_page_themes' => $this->originalThemeConfig]);
        config(['mourning_flag' => $this->originalMourningConfig]);

        parent::tearDown();
    }

    public function testItCombinesAndChronologicallySortsFutureMourningAndThemeChanges(): void
    {
        config([
            'mourning_flag.enabled' => true,
            'mourning_flag.from' => '2026-08-28',
            'mourning_flag.until' => '2026-09-09',
            'mourning_flag.funeral_date' => '2026-09-09',
            'front_page_themes.active' => null,
        ]);

        $changes = app(PlannedSiteChangesService::class)->upcoming(
            Carbon::parse('2026-09-08 12:00:00', 'Europe/Oslo')
        );

        $this->assertSame(['2026-09-09', '2026-09-10', '2026-09-16'], array_map(
            fn (array $change) => $change['date']->toDateString(),
            array_slice($changes, 0, 3)
        ));
        $this->assertSame(['mourning', 'mourning', 'theme'], array_column(array_slice($changes, 0, 3), 'type'));
        $this->assertSame('Tidlig høst → Høst', $changes[2]['description']);
    }

    public function testItFiltersPassedChangesAndExposesManualThemeStatus(): void
    {
        config([
            'mourning_flag.enabled' => true,
            'mourning_flag.from' => '2026-08-28',
            'mourning_flag.until' => '2026-09-09',
            'mourning_flag.funeral_date' => '2026-09-09',
            'front_page_themes.active' => 'jul',
        ]);

        $service = app(PlannedSiteChangesService::class);
        $changes = $service->upcoming(Carbon::parse('2026-09-09 12:00:00', 'Europe/Oslo'));

        $this->assertSame(['2026-09-10'], array_map(fn (array $change) => $change['date']->toDateString(), $changes));
        $this->assertSame('Jul', $service->manualTheme()['name']);
    }
}
