<?php

namespace Tests\Unit;

use App\Services\PlannedSiteChangesService;
use Carbon\Carbon;
use Tests\TestCase;

class PlannedSiteChangesServiceTest extends TestCase
{
    private array $originalThemeConfig;
    private array $originalMourningConfig;
    private array $originalTimedConfigs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalThemeConfig = config('front_page_themes');
        $this->originalMourningConfig = config('mourning_flag');
        $this->originalTimedConfigs = [
            'activities' => config('activities'),
            'fangenytt' => config('fangenytt'),
            'weekly_football_tv' => config('weekly_football_tv'),
            'podcast_recommendation' => config('podcast_recommendation'),
            'nations_league_badge' => config('nations_league_badge'),
        ];

        config([
            'activities.published_at' => null,
            'fangenytt.published_at' => null,
            'weekly_football_tv.published_at' => null,
            'podcast_recommendation.published_at' => null,
            'nations_league_badge.starts_at' => null,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        config(['front_page_themes' => $this->originalThemeConfig]);
        config(['mourning_flag' => $this->originalMourningConfig]);
        foreach ($this->originalTimedConfigs as $key => $value) {
            config([$key => $value]);
        }

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

        $this->assertSame('2026-09-10', $changes[0]['date']->toDateString());
        $this->assertNotContains('2026-09-09', array_map(fn (array $change) => $change['date']->toDateString(), $changes));
        $this->assertSame('Jul', $service->manualTheme()['name']);
    }

    public function testItListsClockReminderVisibilityTextAndRemovalUsingOsloTransitions(): void
    {
        $changes = app(PlannedSiteChangesService::class)->upcoming(
            Carbon::parse('2026-10-23 12:00:00', 'Europe/Oslo')
        );
        $clockChanges = array_values(array_filter($changes, fn (array $change) => $change['type'] === 'clock-change'));

        $this->assertSame([
            'Påminnelse om å stille klokken vises',
            'Teksten i klokkepåminnelsen endres',
            'Påminnelse om å stille klokken skjules',
        ], array_column(array_slice($clockChanges, 0, 3), 'title'));
        $this->assertSame([
            '2026-10-24 00:00',
            '2026-10-25 02:00',
            '2026-10-26 00:00',
        ], array_map(fn (array $change) => $change['date']->format('Y-m-d H:i'), array_slice($clockChanges, 0, 3)));
    }

    public function testItUsesTheSamePublicationWindowForVisibleElementsAndRemovesPassedEvents(): void
    {
        config(['activities.published_at' => '2026-10-25 09:00:00']);

        $service = app(PlannedSiteChangesService::class);
        $before = $service->upcoming(Carbon::parse('2026-10-24 12:00:00', 'Europe/Oslo'));
        $after = $service->upcoming(Carbon::parse('2026-11-09 12:00:00', 'Europe/Oslo'));

        $this->assertContains('Nyhet-merket vises', array_column($before, 'title'));
        $this->assertContains('Nyhet-merket skjules', array_column($before, 'title'));
        $this->assertNotContains('Spillforslag blir synlig automatisk.', array_column($after, 'description'));
    }
}
