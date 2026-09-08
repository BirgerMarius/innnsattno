<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;
use UnexpectedValueException;

class FootballWorldCupService
{
    private const SEASON_ID = 7767;
    private const FRESH_TTL_SECONDS = 900;
    private const STALE_TTL_SECONDS = 604800;

    public function __construct(private ExternalDataFailureNotifier $failureNotifier)
    {
    }

    public function getData(): array
    {
        return [
            'schedule' => $this->endpoint('schedule'),
            'standings' => $this->endpoint('standings'),
        ];
    }

    private function endpoint(string $endpoint): array
    {
        $keys = $this->cacheKeys($endpoint);
        $fresh = Cache::get($keys['fresh']);

        if (is_array($fresh)) {
            return $fresh;
        }

        try {
            $data = $this->fetch($endpoint);

            Cache::put($keys['fresh'], $data, now()->addSeconds(self::FRESH_TTL_SECONDS));
            Cache::put($keys['stale'], $data, now()->addSeconds(self::STALE_TTL_SECONDS));

            return $data;
        } catch (Throwable $exception) {
            $this->failureNotifier->report('sportsnext-football', $this->summary($endpoint, $exception), [
                'operation' => 'world-cup-'.$endpoint,
                'failure_kind' => $this->failureKind($exception),
                'status' => $exception instanceof RequestException ? $exception->response?->status() : null,
            ]);

            $stale = Cache::get($keys['stale']);

            return is_array($stale) ? $stale : $this->emptyEndpoint($endpoint);
        }
    }

    private function fetch(string $endpoint): array
    {
        $response = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::acceptJson()
                    ->connectTimeout(3)
                    ->timeout(10)
                    ->get($this->url($endpoint));

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
            throw new ConnectionException('SportsNext svarte ikke.');
        }

        $response->throw();

        try {
            $payload = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('SportsNext returnerte ugyldig JSON.', 0, $exception);
        }

        if (! $this->isValidPayload($endpoint, $payload)) {
            throw new UnexpectedValueException('SportsNext mangler nødvendige '.$endpoint.'-data.');
        }

        return $payload;
    }

    private function isValidPayload(string $endpoint, mixed $payload): bool
    {
        if (! is_array($payload) || ! is_array($payload['participants'] ?? null)) {
            return false;
        }

        if ($endpoint === 'schedule') {
            if (! is_array($payload['events'] ?? null) || $payload['events'] === []) {
                return false;
            }

            foreach ($payload['events'] as $event) {
                if (! is_array($event)
                    || ! is_string($event['startDate'] ?? null)
                    || ! is_array($event['participantIds'] ?? null)
                    || count($event['participantIds']) < 2
                    || ! is_array($event['status'] ?? null)) {
                    return false;
                }
            }

            return true;
        }

        if (! is_array($payload['standings'] ?? null) || $payload['standings'] === []) {
            return false;
        }

        $hasTeams = false;

        foreach ($payload['standings'] as $group) {
            if (! is_array($group) || ! is_string($group['groupName'] ?? null) || ! is_array($group['teamStandings'] ?? null)) {
                return false;
            }

            foreach ($group['teamStandings'] as $team) {
                $teamId = is_array($team) ? ($team['teamId'] ?? null) : null;
                if (! is_array($team)
                    || $teamId === null
                    || ! is_array($payload['participants'][$teamId] ?? null)
                    || ! is_string($payload['participants'][$teamId]['name'] ?? null)) {
                    return false;
                }

                foreach (['rank', 'played', 'wins', 'draws', 'losses', 'goalsFor', 'goalsAgainst', 'points'] as $field) {
                    if (! array_key_exists($field, $team)) {
                        return false;
                    }
                }

                $hasTeams = true;
            }
        }

        return $hasTeams;
    }

    private function emptyEndpoint(string $endpoint): array
    {
        return $endpoint === 'schedule'
            ? ['participants' => [], 'events' => []]
            : ['participants' => [], 'standings' => []];
    }

    private function cacheKeys(string $endpoint): array
    {
        $suffix = 'season-'.self::SEASON_ID.'.'.$endpoint;

        return [
            'fresh' => 'sportsnext-football.fresh.'.$suffix,
            'stale' => 'sportsnext-football.stale.'.$suffix,
        ];
    }

    private function url(string $endpoint): string
    {
        return rtrim((string) config('services.schibsted_sports.base_url'), '/')
            .'/tournaments/seasons/'.self::SEASON_ID.'/'.$endpoint;
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

        return 'unexpected_error';
    }

    private function summary(string $endpoint, Throwable $exception): string
    {
        if ($exception instanceof RequestException) {
            return 'SportsNext '.$endpoint.' svarte med HTTP-status '.$exception->response?->status().'.';
        }

        if ($exception instanceof ConnectionException) {
            return 'SportsNext '.$endpoint.' kunne ikke nås.';
        }

        if ($exception instanceof UnexpectedValueException) {
            return $exception->getMessage();
        }

        return 'SportsNext '.$endpoint.' kunne ikke hentes.';
    }
}
