<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class PlannedSiteChangesService
{
    public function __construct(
        private FrontPageThemeResolver $themes,
        private FlagDayService $flagDays
    ) {
    }

    /**
     * @return array<int, array{date: CarbonImmutable, title: string, description: string, type: string}>
     */
    public function upcoming(?CarbonInterface $from = null): array
    {
        $today = $from
            ? CarbonImmutable::instance($from)->setTimezone(FlagDayService::TIMEZONE)->startOfDay()
            : CarbonImmutable::now(FlagDayService::TIMEZONE)->startOfDay();

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
            array_merge($themeChanges, $mourningChanges),
            fn (array $change) => $change['date']->greaterThan($today)
        ));

        usort($changes, fn (array $a, array $b) => $a['date']->getTimestamp() <=> $b['date']->getTimestamp());

        return $changes;
    }

    public function manualTheme(): ?array
    {
        return $this->themes->manualTheme();
    }
}
