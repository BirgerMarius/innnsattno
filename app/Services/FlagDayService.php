<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class FlagDayService
{
    public const TIMEZONE = 'Europe/Oslo';

    public const OFFICIAL_OVERVIEW_URL = 'https://www.regjeringen.no/no/dep/ud/dep/diplomatiske-forbindelser-og-protokoll/norges-flagg-forskrift/id449230/#tocNode_2';

    public function overview(?CarbonInterface $now = null): array
    {
        $today = $now
            ? CarbonImmutable::instance($now)->setTimezone(self::TIMEZONE)->startOfDay()
            : CarbonImmutable::now(self::TIMEZONE)->startOfDay();

        $flagDays = array_merge(
            $this->forYear($today->year),
            $this->forYear($today->year + 1)
        );

        $upcoming = array_values(array_filter(
            $flagDays,
            fn (array $flagDay) => $flagDay['date']->greaterThanOrEqualTo($today)
        ));

        return [
            'today' => $today,
            'next' => $upcoming[0],
            'is_flag_day' => $upcoming[0]['date']->isSameDay($today),
            'upcoming' => array_slice($upcoming, 1, 3),
            'official_overview_url' => self::OFFICIAL_OVERVIEW_URL,
            'mourning_flagging' => $this->mourningFlagging($today),
        ];
    }

    /**
     * Returns a temporary official mourning notice independently of ordinary flag days.
     */
    public function mourningFlagging(?CarbonInterface $date = null): ?array
    {
        if (! config('mourning_flag.enabled', false)) {
            return null;
        }

        $from = $this->mourningDate(config('mourning_flag.from'));
        $until = $this->mourningDate(config('mourning_flag.until'));
        $funeralDate = $this->mourningDate(config('mourning_flag.funeral_date'));

        if (! $from || ($until && $until->lessThan($from))) {
            return null;
        }

        $selectedDate = $date
            ? CarbonImmutable::instance($date)->setTimezone(self::TIMEZONE)->startOfDay()
            : CarbonImmutable::now(self::TIMEZONE)->startOfDay();

        if ($selectedDate->lessThan($from) || ($until && $selectedDate->greaterThan($until))) {
            return null;
        }

        $isFuneralDay = $funeralDate && $selectedDate->isSameDay($funeralDate);

        return [
            'title' => (string) config($isFuneralDay ? 'mourning_flag.funeral_title' : 'mourning_flag.title'),
            'message' => (string) config($isFuneralDay ? 'mourning_flag.funeral_message' : 'mourning_flag.message'),
            'source_url' => config('mourning_flag.source_url'),
            'source_name' => (string) config('mourning_flag.source_name'),
            'from' => $from,
            'until' => $until,
            'half_staff' => (bool) config('mourning_flag.half_staff', true),
        ];
    }

    private function mourningDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, self::TIMEZONE);
        } catch (\Throwable) {
            return null;
        }

        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }

    public function forYear(int $year): array
    {
        $fixed = [
            ['month' => 1, 'day' => 1, 'name' => '1. nyttårsdag', 'information_url' => 'https://snl.no/nytt%C3%A5rsdag'],
            ['month' => 1, 'day' => 21, 'name' => 'H.K.H. Prinsesse Ingrid Alexandra', 'birth_year' => 2004, 'information_url' => 'https://www.kongehuset.no/kongehuset/hennes-kongelige-hoyhet-prinsessen/prinsesse-ingrid-alexandras-biografi'],
            ['month' => 2, 'day' => 6, 'name' => 'Samenes nasjonaldag', 'information_url' => 'https://snl.no/samenes_nasjonaldag'],
            ['month' => 2, 'day' => 21, 'name' => 'H.M. Kong Harald V', 'birth_year' => 1937, 'information_url' => 'https://www.kongehuset.no/kongehuset/hans-majestet-kongen/kong-haralds-biografi'],
            ['month' => 5, 'day' => 1, 'name' => 'Arbeidernes dag', 'information_url' => 'https://snl.no/1._mai'],
            ['month' => 5, 'day' => 8, 'name' => 'Frigjørings- og veterandagen', 'information_url' => 'https://snl.no/frigj%C3%B8ringen'],
            ['month' => 5, 'day' => 17, 'name' => 'Grunnlovsdagen', 'information_url' => 'https://snl.no/17._mai_-_dato'],
            ['month' => 6, 'day' => 7, 'name' => 'Unionsoppløsningen 1905', 'information_url' => 'https://snl.no/Unionsoppl%C3%B8sningen_i_1905'],
            ['month' => 7, 'day' => 4, 'name' => 'H.M. Dronning Sonja', 'birth_year' => 1937, 'information_url' => 'https://www.kongehuset.no/kongehuset/hennes-majestet-dronningen/dronning-sonjas-biografi'],
            ['month' => 7, 'day' => 20, 'name' => 'H.K.H. Kronprins Haakon', 'birth_year' => 1973, 'information_url' => 'https://www.kongehuset.no/kongehuset/hans-kongelige-hoyhet-kronprinsen/kronprins-haakons-biografi'],
            ['month' => 7, 'day' => 29, 'name' => 'Olsokdagen', 'information_url' => 'https://snl.no/olsok'],
            ['month' => 8, 'day' => 19, 'name' => 'H.K.H. Kronprinsesse Mette-Marit', 'birth_year' => 1973, 'information_url' => 'https://www.kongehuset.no/kongehuset/hennes-kongelige-hoyhet-kronprinsessen/kronprinsesse-mette-marits-biografi'],
            ['month' => 12, 'day' => 25, 'name' => '1. juledag', 'information_url' => 'https://snl.no/jul'],
        ];

        $flagDays = array_map(function (array $flagDay) use ($year) {
            $flagDay['date'] = CarbonImmutable::create(
                $year,
                $flagDay['month'],
                $flagDay['day'],
                0,
                0,
                0,
                self::TIMEZONE
            );

            if (isset($flagDay['birth_year'])) {
                $flagDay['age'] = $year - $flagDay['birth_year'];
            }

            unset($flagDay['month'], $flagDay['day']);

            return $flagDay;
        }, $fixed);

        $easterSunday = CarbonImmutable::create($year, 3, 21, 0, 0, 0, self::TIMEZONE)
            ->addDays(easter_days($year));

        $flagDays[] = [
            'date' => $easterSunday,
            'name' => '1. påskedag',
            'information_url' => 'https://snl.no/p%C3%A5ske',
        ];
        $flagDays[] = [
            'date' => $easterSunday->addDays(49),
            'name' => '1. pinsedag',
            'information_url' => 'https://snl.no/pinse',
        ];

        usort($flagDays, fn (array $a, array $b) => $a['date']->timestamp <=> $b['date']->timestamp);

        return $flagDays;
    }
}
