<?php

namespace App\Services;

use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

class PrayerTimeService
{
    private const FRESH_TTL_SECONDS = 21600;
    private const STALE_TTL_SECONDS = 604800;
    /**
     * These are the keys consumed by the prayer views. Bønnetid.no returns the
     * same lower-case names (including its historic spelling, "duhr").
     */
    private const PRAYER_FIELDS = ['fajr', 'duhr', 'asr', 'maghrib', 'isha'];

    public function __construct(private ExternalDataFailureNotifier $failureNotifier)
    {
    }

    public function getMonth(int $locationId, int $year, int $month, string $operation = 'prayer-times', bool $check = false): array
    {
        $keys = $this->cacheKeys($locationId, $year, $month);

        $fresh = Cache::get($keys['fresh']);
        if (! $check && is_array($fresh)) {
            return $fresh;
        }

        try {
            $days = $this->fetch($locationId, $year, $month);

            $old = Cache::get($keys['stale'], []);
            if ($check) {
                $this->recordCoverage($locationId, $year, $month, $days, true, is_array($old) ? $old : []);
            }
            // A partial response must not discard previously available dates.
            $byDate = [];
            foreach (array_merge(is_array($old) ? $old : [], $days) as $day) $byDate[$day['date']] = $day;
            $days = array_values($byDate);

            Cache::put($keys['fresh'], $days, now()->addSeconds(self::FRESH_TTL_SECONDS));
            Cache::forever($keys['stale'], $days);
            $this->failureNotifier->reportSuccess('bonnetid-no', $operation);

            return $days;
        } catch (Throwable $exception) {
            $stale = Cache::get($keys['stale']);
            $hasUsableStaleData = is_array($stale) && $stale !== [];
            if ($check) {
                $this->recordCoverage($locationId, $year, $month, $hasUsableStaleData ? $stale : [], false);
            }
            $context = [
                'operation' => $operation,
                'failure_kind' => $this->failureKind($exception),
                'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
                'location_id' => $locationId,
                'year' => $year,
                'month' => $month,
                // Keep logging every failed fetch, but only email when the
                // current or next displayed month has no usable fallback.
                'send_notification' => $this->isRelevantPeriod($year, $month) && ! $hasUsableStaleData,
                'notification_cooldown_seconds' => 86400,
            ];

            if ($exception instanceof PrayerTimePayloadException) {
                $context = array_merge($context, $exception->diagnostics());
            }

            $context['notification_key'] = $this->notificationKey($context, $exception);

            $this->failureNotifier->report('bonnetid-no', $this->summary($exception), $context);

            return $hasUsableStaleData ? $stale : [];
        }
    }

    public function refreshMonth(int $locationId, int $year, int $month): void
    {
        $lock = Cache::lock("fetch.prayer.{$locationId}.{$year}.{$month}", 45);
        if (! $lock->get()) return;
        try {
            $this->getMonth($locationId, $year, $month, 'prayer-times', true);
        } finally {
            $lock->release();
        }
    }

    private function recordCoverage(int $locationId, int $year, int $month, array $days, bool $sourceSuccess, array $fallback = []): void
    {
        $period = \Carbon\Carbon::create($year, $month, 1, 0, 0, 0, 'Europe/Oslo');
        $today = now('Europe/Oslo')->startOfDay();
        $incident = \Illuminate\Support\Facades\DB::table('external_data_incidents')->where('service', 'bonnetid-no')->first();
        $alerted = $incident ? (json_decode($incident->affected_keys, true) ?: []) : [];
        $first = $period;
        $dates = array_fill_keys(array_column($days, 'date'), true);
        $available = array_fill_keys(array_column(array_merge($fallback, $days), 'date'), true);
        for ($date = $first->copy(); $date->lte($period->copy()->endOfMonth()); $date->addDay()) {
            $coverageKey = $locationId.'-'.$date->toDateString();
            if (($date->lt($today) || $date->gt($today->copy()->addDays(7))) && ! in_array($coverageKey, $alerted, true)) continue;
            $covered = isset($dates[$date->format('d-m-Y')]);
            $usable = isset($available[$date->format('d-m-Y')]);
            // One actual month request controls each date; old but date-correct times remain usable.
            app(ExternalDataMonitor::class)->checked('bonnetid-no', $coverageKey, 'prayer', 'Bønnetider '.$locationId.' – '.$date->toDateString(), $date->isSameDay($today), $sourceSuccess && $covered, $usable, $sourceSuccess ? 'Dato mangler i kildesvaret.' : 'Kildehenting feilet.', $date->copy()->endOfDay()->setTimezone(config('app.timezone')));
        }
    }

    private function fetch(int $locationId, int $year, int $month): array
    {
        $token = config('services.prayer_times.api_token');
        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('Bønnetid.no API-token er ikke konfigurert.');
        }

        $response = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::acceptJson()
                    ->withHeaders(['api-token' => $token])
                    ->connectTimeout(3)
                    ->timeout(10)
                    ->get(rtrim((string) config('services.prayer_times.base_url'), '/')."/{$locationId}/{$year}/{$month}/");

                if (! $response->serverError() || $attempt === 2) {
                    break;
                }
            } catch (ConnectionException $exception) {
                if ($attempt === 2) {
                    throw $exception;
                }
            }

            usleep(250000);
        }

        if ($response === null) {
            throw new ConnectionException('Bønnetid.no svarte ikke.');
        }

        $response->throw();

        try {
            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('Bønnetid.no returnerte ugyldig JSON.', 0, $exception);
        }

        return $this->normalisePayload($payload);
    }

    /**
     * Bønnetid.no's month endpoint is a flat list of PrayerTime objects. Its
     * OpenAPI schema marks the individual time properties as nullable: in
     * particular, a time can be unavailable at northern latitudes. The old
     * controller passed those values straight through to the views, so retain
     * that behaviour while still rejecting malformed data.
     */
    private function normalisePayload(mixed $payload): array
    {
        if (! is_array($payload) || $payload === [] || array_keys($payload) !== range(0, count($payload) - 1)) {
            throw new PrayerTimePayloadException('Bønnetid.no mangler nødvendige bønnetider.', [
                'payload_type' => get_debug_type($payload),
            ]);
        }

        $days = [];

        foreach ($payload as $rowIndex => $day) {
            $days[] = $this->normaliseDay($day, $rowIndex);
        }

        return $days;
    }

    private function normaliseDay(mixed $day, int $rowIndex): array
    {
        if (! is_array($day)) {
            throw new PrayerTimePayloadException('Bønnetid.no mangler nødvendige bønnetider.', [
                'row_index' => $rowIndex,
                'row_type' => get_debug_type($day),
            ]);
        }

        if (! is_string($day['date'] ?? null) || ! $this->isValidDate($day['date'])) {
            throw new PrayerTimePayloadException('Bønnetid.no returnerte ugyldig eller manglende dato.', [
                'row_index' => $rowIndex,
                'date_type' => get_debug_type($day['date'] ?? null),
            ]);
        }

        foreach (self::PRAYER_FIELDS as $field) {
            if (! array_key_exists($field, $day)) {
                // The published PrayerTime schema makes these fields nullable,
                // but does not mark them as required. DRF can therefore omit an
                // unavailable value instead of serialising it as null.
                $day[$field] = null;
                continue;
            }

            // Nullable is part of Bønnetid.no's PrayerTime schema. Keep null
            // for Blade, which renders it as an empty cell just as it did
            // before the service refactor.
            if ($day[$field] === null) {
                continue;
            }

            if (! is_string($day[$field]) || ! preg_match('/^\d{1,2}:\d{2}(?::\d{2}(?:\.\d+)?)?$/', $day[$field])) {
                throw new PrayerTimePayloadException('Bønnetid.no mangler nødvendige bønnetider.', [
                    'row_index' => $rowIndex,
                    'date' => $day['date'],
                    'invalid_field' => $field,
                    'value_type' => get_debug_type($day[$field]),
                ]);
            }
        }

        return $day;
    }

    private function isValidDate(string $date): bool
    {
        $parsedDate = DateTimeImmutable::createFromFormat('!d-m-Y', $date);
        $errors = DateTimeImmutable::getLastErrors();

        return $parsedDate !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $parsedDate->format('d-m-Y') === $date;
    }

    private function cacheKeys(int $locationId, int $year, int $month): array
    {
        $period = sprintf('%04d-%02d', $year, $month);
        $suffix = $period.'.location-'.$locationId;

        return [
            'fresh' => 'prayer-times.fresh.'.$suffix,
            'stale' => 'prayer-times.stale.'.$suffix,
        ];
    }

    private function isRelevantPeriod(int $year, int $month): bool
    {
        $currentMonth = now('Europe/Oslo')->startOfMonth();
        $requestedMonth = $currentMonth->copy()->setDate($year, $month, 1);

        return $requestedMonth->betweenIncluded($currentMonth, $currentMonth->copy()->addMonth());
    }

    private function notificationKey(array $context, Throwable $exception): string
    {
        $diagnostics = [];
        foreach (['location_id', 'year', 'month', 'row_index', 'date', 'invalid_field', 'payload_type', 'row_type', 'date_type', 'value_type'] as $key) {
            if (array_key_exists($key, $context)) {
                $diagnostics[$key] = $context[$key];
            }
        }

        return hash('sha256', json_encode([
            'failure_kind' => $context['failure_kind'],
            'status' => $context['status'],
            'summary' => $this->summary($exception),
            'diagnostics' => $diagnostics,
        ], JSON_THROW_ON_ERROR));
    }

    private function failureKind(Throwable $exception): string
    {
        if ($exception instanceof ConnectionException) {
            return 'connection_exception';
        }

        if ($exception instanceof RequestException) {
            return 'http_status';
        }

        if ($exception instanceof UnexpectedValueException && $exception->getPrevious() instanceof JsonException) {
            return 'invalid_json';
        }

        if ($exception instanceof UnexpectedValueException) {
            return 'invalid_payload';
        }

        if ($exception instanceof RuntimeException) {
            return 'configuration_error';
        }

        return 'unexpected_error';
    }

    private function summary(Throwable $exception): string
    {
        if ($exception instanceof RequestException) {
            return 'Bønnetid.no svarte med HTTP-status '.$exception->response?->status().'.';
        }

        if ($exception instanceof ConnectionException) {
            return 'Bønnetid.no kunne ikke nås.';
        }

        if ($exception instanceof UnexpectedValueException || $exception instanceof RuntimeException) {
            return $exception->getMessage();
        }

        return 'Bønnetider kunne ikke hentes fra Bønnetid.no.';
    }
}
