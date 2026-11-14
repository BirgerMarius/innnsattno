<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        $words = $this->activeWords();

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
        foreach ($this->activeWords() as $word) {
            if ($word['id'] === $id) {
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

    /** @return array<int, array{id: string, text: string}> */
    public function activeWords(): array
    {
        $disabledIds = array_flip($this->disabledIds());

        return array_values(array_filter($this->configuredWords(), fn (array $word) => ! isset($disabledIds[$word['id']])));
    }

    /** @return array<int, array{id: string, text: string}> */
    public function inactiveWords(): array
    {
        $disabledIds = array_flip($this->disabledIds());

        return array_values(array_filter($this->configuredWords(), fn (array $word) => isset($disabledIds[$word['id']])));
    }

    public function deactivate(string $id): bool
    {
        if (! $this->hasConfiguredWord($id)) {
            return false;
        }

        DB::table('daily_word_statuses')->updateOrInsert(
            ['word_id' => $id],
            ['is_active' => false, 'updated_at' => now(), 'created_at' => now()]
        );

        return true;
    }

    public function activate(string $id): bool
    {
        if (! $this->hasConfiguredWord($id)) {
            return false;
        }

        DB::table('daily_word_statuses')->updateOrInsert(
            ['word_id' => $id],
            ['is_active' => true, 'updated_at' => now(), 'created_at' => now()]
        );

        return true;
    }

    /** @return array<int, array{id: string, text: string}> */
    private function configuredWords(): array
    {
        return array_values(array_filter(config('daily_word.words', []), fn (mixed $word) => is_array($word)
            && is_string($word['id'] ?? null) && is_string($word['text'] ?? null)));
    }

    private function hasConfiguredWord(string $id): bool
    {
        foreach ($this->configuredWords() as $word) {
            if ($word['id'] === $id) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    private function disabledIds(): array
    {
        if (! Schema::hasTable('daily_word_statuses')) {
            return [];
        }

        return DB::table('daily_word_statuses')
            ->where('is_active', false)
            ->pluck('word_id')
            ->all();
    }
}
