<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WeeklyFootballTeamLogoService
{
    public function __construct(
        private PremierLeagueService $premierLeague,
        private EliteserienService $eliteserien,
        private ChampionsLeagueService $championsLeague,
        private EuropaLeagueService $europaLeague,
        private ConferenceLeagueService $conferenceLeague,
        private NationsLeagueService $nationsLeague,
    ) {
    }

    /**
     * TV-guide names never carry a SportsNext team ID. Match only exact,
     * normalized participant names from already cached competition payloads;
     * alternate or ambiguous names deliberately receive no crest.
     */
    public function logosFor(string $competition, string $fixture): array
    {
        $cacheKey = 'weekly-football-tv.logos.'.sha1($competition.'|'.$fixture);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($competition, $fixture): array {
            if (preg_match('/^\s*(.+?)\s+–\s+(.+?)\s*$/u', $fixture, $parts) !== 1) {
                return ['homeLogoUrl' => null, 'awayLogoUrl' => null];
            }

            $teams = $this->serviceFor($competition)?->cachedTeams() ?? [];
            $byName = [];
            foreach ($teams as $team) {
                if (! is_array($team) || ! is_string($team['teamName'] ?? null) || ! is_string($team['emblemUrl'] ?? null)) {
                    continue;
                }
                $key = $this->key($team['teamName']);
                if (isset($byName[$key])) {
                    $byName[$key] = null;
                    continue;
                }
                $byName[$key] = $team['emblemUrl'];
            }

            return [
                'homeLogoUrl' => $byName[$this->key($parts[1])] ?? null,
                'awayLogoUrl' => $byName[$this->key($parts[2])] ?? null,
            ];
        });
    }

    private function serviceFor(string $competition): ?SchibstedCompetitionService
    {
        return match ($competition) {
            'Premier League' => $this->premierLeague,
            'Eliteserien' => $this->eliteserien,
            'Champions League' => $this->championsLeague,
            'Europa League' => $this->europaLeague,
            'Conference League' => $this->conferenceLeague,
            'Nations League' => $this->nationsLeague,
            default => null,
        };
    }

    private function key(string $name): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }
}
