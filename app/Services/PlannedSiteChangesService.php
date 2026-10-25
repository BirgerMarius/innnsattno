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
        private DailyWordService $dailyWords,
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
                'preview' => ['kind' => 'theme', 'theme' => $change['to']],
            ];
        }, $this->themes->upcomingChanges($today));

        $mourningChanges = array_map(function (array $change): array {
            $notice = $this->flagDays->mourningFlagging($change['date']);

            return [
                'date' => $change['date'],
                'title' => $change['title'],
                'description' => $change['description'],
                'type' => 'mourning',
                'preview' => ['kind' => 'mourning', 'title' => $notice['title'] ?? $change['title'], 'message' => $notice['message'] ?? null, 'hidden' => $notice === null],
            ];
        }, $this->flagDays->upcomingMourningChanges($today));

        $changes = array_values(array_filter(
            array_merge($themeChanges, $mourningChanges, $this->dailyWordChanges($today), $this->clockChanges->upcomingChanges($now), $this->timedVisibilityChanges()),
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
     * Use the same 400-day planning horizon as FrontPageThemeResolver.
     * Clock-change notices replace Dagens ord in the shared header, so those
     * calendar days are intentionally absent from this public-visibility list.
     *
     * @return array<int, array{date: CarbonImmutable, title: string, description: string, type: string, word_id: string}>
     */
    private function dailyWordChanges(CarbonImmutable $today): array
    {
        $changes = [];

        for ($offset = 1; $offset <= 400; $offset++) {
            $date = $today->addDays($offset);
            $replacementAt = $this->clockChanges->dailyWordReplacementAt($date);

            if ($replacementAt === null && $this->clockChanges->forDate($date) !== null) {
                continue;
            }

            $word = $this->dailyWords->forDate($date);

            if ($word !== null) {
                $changes[] = [
                    'date' => $replacementAt ?? $date,
                    'title' => $word['title'],
                    'description' => $word['message'],
                    'type' => 'daily-word',
                    'word_id' => $word['id'],
                ];
            }
        }

        return $changes;
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
            ['date' => $window['starts_at'], 'title' => $element.' vises', 'description' => $name.' blir synlig automatisk.', 'type' => 'timed-visibility', 'preview' => ['kind' => 'element', 'element' => $name]],
            ['date' => $window['ends_at'], 'title' => $element.' skjules', 'description' => $name.' skjules automatisk.', 'type' => 'timed-visibility', 'preview' => ['kind' => 'element', 'element' => $name, 'hidden' => true]],
        ];
    }
}
