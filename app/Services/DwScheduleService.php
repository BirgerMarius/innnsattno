<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DwScheduleService
{
    private const ENDPOINT = 'https://www.dw.com/graph-api/en/livestream/english';

    private const CACHE_KEY = 'dw.tv-schedule.slots';

    public function channelForDate(?Carbon $date = null): ?array
    {
        $timezone = new \DateTimeZone('Europe/Oslo');
        $localDate = ($date ?? now())->setTimezone($timezone)->format('Y-m-d');
        $slots = $this->slots();

        if ($slots === null) {
            return null;
        }

        $listings = [];

        foreach ($slots as $slot) {
            if (! is_array($slot) || ! is_string($slot['startDate'] ?? null)) {
                continue;
            }

            try {
                $startsAt = Carbon::parse($slot['startDate']);
            } catch (\Throwable $exception) {
                continue;
            }

            if ($startsAt->copy()->setTimezone($timezone)->format('Y-m-d') !== $localDate) {
                continue;
            }

            $title = $slot['program']['name'] ?? $slot['programElement']['name'] ?? null;

            if (! is_string($title) || $title === '') {
                continue;
            }

            $listings[] = [
                'startsAt' => $startsAt->toIso8601String(),
                'title' => ['title' => $title],
            ];
        }

        if ($listings === []) {
            return null;
        }

        return [
            'channel' => [
                'name' => 'DW News',
                'slug' => 'dw-news',
            ],
            'listings' => $listings,
        ];
    }

    private function slots(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY);

        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => 'innsatt.no TV-guide/1.0'])
                ->connectTimeout(3)
                ->timeout(5)
                ->get(self::ENDPOINT);

            if (! $response->successful()) {
                Log::warning('DW TV-guide svarte med HTTP-feil.', ['status' => $response->status()]);

                return null;
            }

            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
            $slots = $payload['data']['livestreamChannels'][0]['nextTimeSlots'] ?? null;

            if (! is_array($slots)) {
                Log::warning('DW TV-guide mangler forventede programdata.');

                return null;
            }

            Cache::put(self::CACHE_KEY, $slots, now()->addMinutes(10));

            return $slots;
        } catch (\Throwable $exception) {
            Log::warning('DW TV-guide kunne ikke hentes.', ['error' => $exception->getMessage()]);

            return null;
        }
    }
}
