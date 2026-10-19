<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use JsonException;
use RuntimeException;
use Throwable;

class NamedayService
{
    public function __construct(
        private ResilientDateCache $cache,
        private ExternalDataFailureNotifier $notifier,
        private TodayDateNotificationPolicy $notificationPolicy,
    )
    {
    }

    public function forDate(CarbonInterface $date, bool $check = false): array
    {
        $key = sprintf('namedays.%02d-%02d', $date->month, $date->day);

        $success = false;
        $result = $this->cache->remember(
            $key,
            (int) config('services.today.namedays_cache_ttl', 86400),
            function () use ($date, &$success) {
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

                    $this->notifier->reportSuccess('namedays-webapi', 'namedays');
                    $success = true;
                    return ['names' => $names, 'source_url' => 'https://webapi.no/'];
                }

                throw new RuntimeException('Datoen mangler i navnedagssvaret.');
            },
            [],
            fn (Throwable $exception) => $this->reportFailure($exception, $date),
            (int) config('services.today.namedays_failure_cache_ttl', 86400),
            $check,
        );
        if ($check) app(ExternalDataMonitor::class)->checked('namedays-webapi', 'today', 'calendar', 'Navnedager '.$date->format('d.m.'), false, $success, array_key_exists('names', $result), 'Navnedager kunne ikke hentes.');
        return $result;
    }

    public function refresh(CarbonInterface $date): void
    {
        $lock = Cache::lock('fetch.namedays.'.$date->format('m-d'), 20);
        if (! $lock->get()) return;
        try { $this->forDate($date, true); } finally { $lock->release(); }
    }

    private function reportFailure(Throwable $exception, CarbonInterface $date): void
    {
        $context = [
            'operation' => 'namedays',
            'send_notification' => $this->notificationPolicy->shouldNotify($date),
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
