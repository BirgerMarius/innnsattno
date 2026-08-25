<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class FrontPageThemeResolver
{
    public const TIMEZONE = 'Europe/Oslo';

    public function resolve(?CarbonInterface $date = null): ?array
    {
        $themes = config('front_page_themes.themes', []);
        $activeTheme = config('front_page_themes.active');

        if (is_string($activeTheme) && isset($themes[$activeTheme])) {
            return $themes[$activeTheme];
        }

        $localDate = $date
            ? CarbonImmutable::instance($date)->setTimezone(self::TIMEZONE)->startOfDay()
            : CarbonImmutable::now(self::TIMEZONE)->startOfDay();

        $matches = collect(config('front_page_themes.calendar', []))
            ->filter(fn (array $period) => $this->matches($period, $localDate))
            ->sortByDesc(fn (array $period) => $period['priority'] ?? 0);

        foreach ($matches as $period) {
            if (isset($themes[$period['theme']])) {
                return $themes[$period['theme']];
            }
        }

        return null;
    }

    private function matches(array $period, CarbonImmutable $date): bool
    {
        return match ($period['type'] ?? 'fixed') {
            'easter' => $this->isWithin($date, $this->palmSunday($date->year), $this->easterMonday($date->year)),
            'advent' => $this->isWithin($date, $this->firstAdvent($date->year), $date->setDate($date->year, 12, 22)),
            'before_advent' => $this->isWithin($date, $date->setDate($date->year, 11, 24), $this->firstAdvent($date->year)->subDay()),
            default => $this->matchesFixedRange($period, $date),
        };
    }

    private function matchesFixedRange(array $period, CarbonImmutable $date): bool
    {
        if (empty($period['start']) || empty($period['end'])) {
            return false;
        }

        $monthDay = $date->format('m-d');

        if ($period['start'] <= $period['end']) {
            return $monthDay >= $period['start'] && $monthDay <= $period['end'];
        }

        return $monthDay >= $period['start'] || $monthDay <= $period['end'];
    }

    private function palmSunday(int $year): CarbonImmutable
    {
        return $this->easterSunday($year)->subDays(7);
    }

    private function easterMonday(int $year): CarbonImmutable
    {
        return $this->easterSunday($year)->addDay();
    }

    private function easterSunday(int $year): CarbonImmutable
    {
        return CarbonImmutable::create($year, 3, 21, 0, 0, 0, self::TIMEZONE)->addDays(easter_days($year));
    }

    private function firstAdvent(int $year): CarbonImmutable
    {
        $date = CarbonImmutable::create($year, 11, 27, 0, 0, 0, self::TIMEZONE);

        while ($date->dayOfWeek !== CarbonInterface::SUNDAY) {
            $date = $date->addDay();
        }

        return $date;
    }

    private function isWithin(CarbonImmutable $date, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $date->betweenIncluded($start, $end);
    }
}
