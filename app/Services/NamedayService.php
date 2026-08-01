<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NamedayService
{
    private ResilientDateCache $cache;

    public function __construct(ResilientDateCache $cache)
    {
        $this->cache = $cache;
    }

    public function forDate(CarbonInterface $date): array
    {
        $key = sprintf('namedays.%02d-%02d', $date->month, $date->day);

        return $this->cache->remember(
            $key,
            (int) config('services.today.namedays_cache_ttl', 604800),
            function () use ($date) {
                $payload = Http::acceptJson()
                    ->withHeaders(['User-Agent' => config('services.today.user_agent')])
                    ->timeout((int) config('services.today.timeout', 5))
                    ->get(config('services.today.namedays_url'))
                    ->throw()
                    ->json();

                $days = data_get($payload, 'data');
                if (!is_array($days)) {
                    throw new RuntimeException('Ugyldig svar fra navnedagstjenesten.');
                }

                foreach ($days as $day) {
                    if ((int) ($day['month'] ?? 0) !== $date->month || (int) ($day['day'] ?? 0) !== $date->day) {
                        continue;
                    }

                    $names = array_values(array_unique(array_filter(array_map(
                        fn ($name) => trim((string) $name),
                        is_array($day['names'] ?? null) ? $day['names'] : []
                    ))));

                    return ['names' => $names, 'source_url' => 'https://webapi.no/'];
                }

                throw new RuntimeException('Datoen mangler i navnedagssvaret.');
            }
        );
    }
}
