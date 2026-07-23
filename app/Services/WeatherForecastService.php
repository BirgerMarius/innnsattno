<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WeatherForecastService
{
    public function forecast(): array
    {
        return Cache::remember(
            'weather.tyristrand.forecast',
            (int) config('services.weather.cache_ttl', 1800),
            fn () => $this->fetchForecast()
        );
    }

    private function fetchForecast(): array
    {
        $response = Http::acceptJson()
            ->withHeaders(['User-Agent' => config('services.weather.user_agent')])
            ->timeout((int) config('services.weather.timeout', 10))
            ->get(config('services.weather.base_url'), [
                'lat' => config('services.weather.latitude'),
                'lon' => config('services.weather.longitude'),
            ])
            ->throw()
            ->json();

        $timeSeries = data_get($response, 'properties.timeseries');

        if (!is_array($timeSeries) || $timeSeries === []) {
            throw new RuntimeException('Værtjenesten returnerte ingen prognosepunkter.');
        }

        $days = collect($timeSeries)
            ->map(function (array $point) {
                $time = Carbon::parse($point['time'])->setTimezone('Europe/Oslo');
                $details = data_get($point, 'data.instant.details', []);
                $nextOneHour = data_get($point, 'data.next_1_hours', []);
                $nextSixHours = data_get($point, 'data.next_6_hours', []);
                $symbolPeriod = $nextOneHour ?: $nextSixHours;

                return [
                    'time' => $time,
                    'temperature' => $details['air_temperature'] ?? null,
                    'wind_speed' => $details['wind_speed'] ?? null,
                    'symbol' => data_get($symbolPeriod, 'summary.symbol_code'),
                    'precipitation_1h' => data_get($nextOneHour, 'details.precipitation_amount'),
                    'precipitation_6h' => data_get($nextSixHours, 'details.precipitation_amount'),
                ];
            })
            ->groupBy(fn (array $point) => $point['time']->toDateString())
            ->take(7)
            ->map(function ($points) {
                $representative = $points->sortBy(
                    fn (array $point) => abs($point['time']->hour - 12)
                )->first();
                $temperatures = $points->pluck('temperature')->filter(fn ($value) => $value !== null);

                return [
                    'date' => $representative['time'],
                    'temperature_min' => $temperatures->isEmpty() ? null : round($temperatures->min()),
                    'temperature_max' => $temperatures->isEmpty() ? null : round($temperatures->max()),
                    'wind_speed' => $representative['wind_speed'] === null
                        ? null
                        : round($representative['wind_speed'], 1),
                    'precipitation' => $this->dailyPrecipitation($points),
                    'description' => $this->description($representative['symbol']),
                    'icon' => $this->icon($representative['symbol']),
                ];
            })
            ->values()
            ->all();

        return [
            'location' => 'Tyristrand / Ringerike fengsel',
            'updated_at' => Carbon::parse(
                data_get($response, 'properties.meta.updated_at', $timeSeries[0]['time'])
            )->setTimezone('Europe/Oslo'),
            'days' => $days,
        ];
    }

    private function dailyPrecipitation($points): ?float
    {
        $total = 0;
        $hasPrecipitation = false;
        $coveredPeriods = [];
        $dayEndsAt = $points->first()['time']->copy()->startOfDay()->addDay();

        foreach ($points->sortBy('time') as $point) {
            if ($point['precipitation_1h'] === null) {
                continue;
            }

            $periodEndsAt = $point['time']->copy()->addHour();

            if ($periodEndsAt->gt($dayEndsAt)) {
                continue;
            }

            $total += $point['precipitation_1h'];
            $hasPrecipitation = true;
            $coveredPeriods[] = [$point['time'], $periodEndsAt];
        }

        foreach ($points->sortBy('time') as $point) {
            if ($point['precipitation_6h'] === null) {
                continue;
            }

            $periodEndsAt = $point['time']->copy()->addHours(6);
            $overlapsCoveredPeriod = collect($coveredPeriods)->contains(
                fn (array $period) => $point['time']->lt($period[1])
                    && $periodEndsAt->gt($period[0])
            );

            if ($periodEndsAt->gt($dayEndsAt) || $overlapsCoveredPeriod) {
                continue;
            }

            $total += $point['precipitation_6h'];
            $hasPrecipitation = true;
            $coveredPeriods[] = [$point['time'], $periodEndsAt];
        }

        return $hasPrecipitation ? round($total, 1) : null;
    }

    private function description(?string $symbol): string
    {
        $symbol = (string) $symbol;

        if (str_contains($symbol, 'thunder')) {
            return 'Tordenvær';
        }
        if (str_contains($symbol, 'snow') || str_contains($symbol, 'sleet')) {
            return 'Snø eller sludd';
        }
        if (str_contains($symbol, 'rain')) {
            return 'Regn';
        }
        if (str_contains($symbol, 'fog')) {
            return 'Tåke';
        }
        if (str_contains($symbol, 'partlycloudy')) {
            return 'Delvis skyet';
        }
        if (str_contains($symbol, 'cloudy')) {
            return 'Skyet';
        }
        if (str_contains($symbol, 'fair')) {
            return 'Lettskyet';
        }
        if (str_contains($symbol, 'clearsky')) {
            return 'Klart vær';
        }

        return 'Værmelding';
    }

    private function icon(?string $symbol): string
    {
        $symbol = (string) $symbol;

        if (str_contains($symbol, 'thunder')) {
            return '⛈️';
        }
        if (str_contains($symbol, 'snow') || str_contains($symbol, 'sleet')) {
            return '🌨️';
        }
        if (str_contains($symbol, 'rain')) {
            return '🌧️';
        }
        if (str_contains($symbol, 'fog')) {
            return '🌫️';
        }
        if (str_contains($symbol, 'partlycloudy') || str_contains($symbol, 'fair')) {
            return '⛅';
        }
        if (str_contains($symbol, 'cloudy')) {
            return '☁️';
        }
        if (str_contains($symbol, 'clearsky')) {
            return '☀️';
        }

        return '🌦️';
    }
}
