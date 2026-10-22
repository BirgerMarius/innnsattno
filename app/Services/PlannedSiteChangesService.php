<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PlannedSiteChangesService
{
    public function __construct(
        private FrontPageThemeResolver $themes,
        private FlagDayService $flagDays,
        private ClockChangeReminderService $clockChanges,
        private TimedVisibilityService $timedVisibility,
    ) {
    }

    /**
     * @return array<int, array{date: CarbonImmutable, title: string, description: string, type: string}>
     */
    public function upcoming(?CarbonInterface $from = null): array
    {
        $now = $from
            ? CarbonImmutable::instance($from)->setTimezone(FlagDayService::TIMEZONE)
            : CarbonImmutable::now(FlagDayService::TIMEZONE);
        $today = $now->startOfDay();

        $themeChanges = array_map(function (array $change): array {
            $fromTheme = $change['from']['name'] ?? 'Ingen tema';
            $toTheme = $change['to']['name'] ?? 'Standardtema';

            return [
                'date' => $change['date'],
                'title' => 'Grafisk tema endres',
                'description' => $fromTheme.' → '.$toTheme,
                'type' => 'theme',
            ];
        }, $this->themes->upcomingChanges($today));

        $mourningChanges = array_map(fn (array $change): array => [
            'date' => $change['date'],
            'title' => $change['title'],
            'description' => $change['description'],
            'type' => 'mourning',
        ], $this->flagDays->upcomingMourningChanges($today));

        $changes = array_values(array_filter(
            array_merge($themeChanges, $mourningChanges, $this->clockChanges->upcomingChanges($now), $this->timedVisibilityChanges()),
            fn (array $change) => $change['date']->greaterThan($now)
        ));

        usort($changes, fn (array $a, array $b) => $a['date']->getTimestamp() <=> $b['date']->getTimestamp());

        return $changes;
    }

    public function manualTheme(): ?array
    {
        return $this->themes->manualTheme();
    }

    /**
     * Register timed public elements here through TimedVisibilityService, so their
     * on-page visibility rule and their admin events always share one time window.
     *
     * @return array<int, array{date: CarbonImmutable, title: string, description: string, type: string}>
     */
    private function timedVisibilityChanges(): array
    {
        return array_merge(
            $this->windowChanges(config('activities.published_at'), 14, 'Spillforslag', 'Nyhet-merket'),
            $this->windowChanges(config('fangenytt.published_at'), 14, 'Fangenytt', 'Nyhet-merket'),
            $this->windowChanges(config('weekly_football_tv.published_at'), 14, 'Fotball på TV', 'Nyhet-merket'),
            $this->windowChanges(config('podcast_recommendation.published_at'), 28, 'Anbefalt podkast', 'Anbefalingen'),
            $this->windowChanges(config('nations_league_badge.starts_at'), (int) config('nations_league_badge.duration_days', 7), 'Nations League', 'Nyhet-merket'),
        );
    }

    /** @return array<int, array{date: CarbonImmutable, title: string, description: string, type: string}> */
    private function windowChanges(mixed $startsAt, int $durationInDays, string $name, string $element): array
    {
        $window = $this->timedVisibility->window($startsAt, $durationInDays);

        if ($window === null) {
            return [];
        }

        return [
            ['date' => $window['starts_at'], 'title' => $element.' vises', 'description' => $name.' blir synlig automatisk.', 'type' => 'timed-visibility'],
            ['date' => $window['ends_at'], 'title' => $element.' skjules', 'description' => $name.' skjules automatisk.', 'type' => 'timed-visibility'],
        ];
    }
}
