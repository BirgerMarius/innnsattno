<?php

namespace App\Services\FootballApi;

use App\Services\FootballApi\Contracts\FootballProviderInterface;
use Illuminate\Support\Facades\Cache;

class FootballService
{
    private const FINISHED_STATUSES = 'FT-AET-PEN';

    private FootballProviderInterface $provider;

    public function __construct(FootballProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function premierLeagueStandings(?int $season = null): array
    {
        return $this->standings($this->premierLeagueId(), $season);
    }

    public function premierLeagueFixtures(?int $season = null): array
    {
        return $this->fixtures($this->premierLeagueId(), $season);
    }

    public function premierLeagueResults(?int $season = null): array
    {
        return $this->results($this->premierLeagueId(), $season);
    }

    public function standings(int $leagueId, ?int $season = null): array
    {
        $season = $season ?: $this->season();

        return $this->remember("standings.{$leagueId}.{$season}", function () use ($leagueId, $season) {
            return $this->provider->get('standings', [
                'league' => $leagueId,
                'season' => $season,
            ]);
        });
    }

    public function fixtures(int $leagueId, ?int $season = null): array
    {
        return $this->fixturesByStatus($leagueId, $season, null, 'fixtures');
    }

    public function results(int $leagueId, ?int $season = null): array
    {
        return $this->fixturesByStatus($leagueId, $season, self::FINISHED_STATUSES, 'results');
    }

    public function season(): int
    {
        $configuredSeason = config('services.football_api.season');

        if ($configuredSeason) {
            return (int) $configuredSeason;
        }

        return now()->month >= 8 ? now()->year : now()->year - 1;
    }

    private function premierLeagueId(): int
    {
        return (int) config('services.football_api.leagues.premier_league', 39);
    }

    private function fixturesByStatus(int $leagueId, ?int $season = null, ?string $status = null, string $cachePrefix = 'fixtures'): array
    {
        $season = $season ?: $this->season();
        $cacheKey = "{$cachePrefix}.{$leagueId}.{$season}";

        return $this->remember($cacheKey, function () use ($leagueId, $season, $status) {
            $query = [
                'league' => $leagueId,
                'season' => $season,
            ];

            if ($status) {
                $query['status'] = $status;
            }

            return $this->provider->get('fixtures', $query);
        });
    }

    private function remember(string $key, callable $callback): array
    {
        $ttl = (int) config('services.football_api.cache_ttl', 900);

        return Cache::remember('football_api.'.$key, now()->addSeconds($ttl), $callback);
    }
}
