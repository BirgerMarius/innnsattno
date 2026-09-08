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

    public function getMonth(int $locationId, int $year, int $month, string $operation = 'prayer-times'): array
    {
        $keys = $this->cacheKeys($locationId, $year, $month);

        $fresh = Cache::get($keys['fresh']);
        if (is_array($fresh)) {
            return $fresh;
        }

        try {
            $days = $this->fetch($locationId, $year, $month);

            Cache::put($keys['fresh'], $days, now()->addSeconds(self::FRESH_TTL_SECONDS));
            Cache::put($keys['stale'], $days, now()->addSeconds(self::STALE_TTL_SECONDS));

            return $days;
        } catch (Throwable $exception) {
            $this->failureNotifier->report('bonnetid-no', $this->summary($exception), [
                'operation' => $operation,
                'failure_kind' => $this->failureKind($exception),
                'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
            ]);

            $stale = Cache::get($keys['stale']);

            return is_array($stale) ? $stale : [];
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
            throw new UnexpectedValueException('Bønnetid.no mangler nødvendige bønnetider.');
        }

        $days = [];

        foreach ($payload as $day) {
            $days[] = $this->normaliseDay($day);
        }

        return $days;
    }

    private function normaliseDay(mixed $day): array
    {
        if (! is_array($day)) {
            throw new UnexpectedValueException('Bønnetid.no mangler nødvendige bønnetider.');
        }

        if (! is_string($day['date'] ?? null) || ! $this->isValidDate($day['date'])) {
            throw new UnexpectedValueException('Bønnetid.no returnerte ugyldig eller manglende dato.');
        }

        foreach (self::PRAYER_FIELDS as $field) {
            if (! array_key_exists($field, $day)) {
                throw new UnexpectedValueException('Bønnetid.no mangler nødvendige bønnetider.');
            }

            // Nullable is part of Bønnetid.no's PrayerTime schema. Keep null
            // for Blade, which renders it as an empty cell just as it did
            // before the service refactor.
            if ($day[$field] === null) {
                continue;
            }

            if (! is_string($day[$field]) || ! preg_match('/^\d{1,2}:\d{2}(?::\d{2}(?:\.\d+)?)?$/', $day[$field])) {
                throw new UnexpectedValueException('Bønnetid.no mangler nødvendige bønnetider.');
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
