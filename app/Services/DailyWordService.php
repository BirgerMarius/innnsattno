<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class DailyWordService
{
    public const TIMEZONE = 'Europe/Oslo';

    /**
     * Choose one shared daily word from the configured, repeating rotation.
     *
     * @return array{type: string, title: string, message: string, date: string}|null
     */
    public function forDate(?DateTimeInterface $date = null): ?array
    {
        $today = $date
            ? CarbonImmutable::instance($date)->setTimezone(self::TIMEZONE)->startOfDay()
            : CarbonImmutable::now(self::TIMEZONE)->startOfDay();
        $words = array_values(array_filter(config('daily_word.words', []), fn (mixed $word) => is_array($word)
            && is_string($word['id'] ?? null) && is_string($word['text'] ?? null)));

        if ($words === []) {
            return null;
        }

        $anchor = CarbonImmutable::createFromFormat(
            '!Y-m-d',
            (string) config('daily_word.anchor_date', '2026-01-01'),
            self::TIMEZONE
        );

        if (! $anchor) {
            $anchor = CarbonImmutable::create(2026, 1, 1, 0, 0, 0, self::TIMEZONE);
        }

        $offset = $anchor->diffInDays($today, false);
        $index = (($offset % count($words)) + count($words)) % count($words);

        return [
            'type' => 'daily-word',
            'title' => (string) config('daily_word.title', 'Dagens ord'),
            'id' => $words[$index]['id'],
            'message' => $words[$index]['text'],
            'date' => $today->toDateString(),
        ];
    }

    /** @return array{type: string, title: string, id: string, message: string}|null */
    public function find(string $id): ?array
    {
        foreach (config('daily_word.words', []) as $word) {
            if (is_array($word) && ($word['id'] ?? null) === $id && is_string($word['text'] ?? null)) {
                return [
                    'type' => 'daily-word',
                    'title' => (string) config('daily_word.title', 'Dagens ord'),
                    'id' => $id,
                    'message' => $word['text'],
                ];
            }
        }

        return null;
    }
}
