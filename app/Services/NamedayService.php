<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;
use Throwable;

class NamedayService
{
    public function __construct(
        private ResilientDateCache $cache,
        private ExternalDataFailureNotifier $notifier,
    )
    {
    }

    public function forDate(CarbonInterface $date): array
    {
        $key = sprintf('namedays.%02d-%02d', $date->month, $date->day);

        return $this->cache->remember(
            $key,
            (int) config('services.today.namedays_cache_ttl', 604800),
            function () use ($date) {
                $response = Http::acceptJson()
                    ->withHeaders(['User-Agent' => config('services.today.user_agent')])
                    ->timeout((int) config('services.today.timeout', 5))
                    ->get(config('services.today.namedays_url'));

                $response->throw();
                $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);

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
            }, [], fn (Throwable $exception) => $this->reportFailure($exception));
    }

    private function reportFailure(Throwable $exception): void
    {
        $context = [
            'operation' => 'namedays',
            'failure_kind' => match (true) {
                $exception instanceof ConnectionException => 'connection_failure',
                $exception instanceof RequestException => 'http_status',
                $exception instanceof JsonException => 'invalid_json',
                $exception instanceof RuntimeException => 'invalid_payload',
                default => 'unexpected_error',
            },
        ];

        if ($exception instanceof RequestException && $exception->response !== null) {
            $context['status'] = $exception->response->status();
        }

        $this->notifier->report('namedays-webapi', 'Navnedager kunne ikke hentes eller valideres.', $context);
    }
}
