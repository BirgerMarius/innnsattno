<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WeeklyFootballTeamLogoService
{
    private const ALIASES = [
        'kfum oslo' => 'kfum',
        'kfum-kameratene oslo' => 'kfum',
        'kfum kameratene oslo' => 'kfum',
        'vålerenga fotball' => 'vålerenga',
        'vålerenga if' => 'vålerenga',
        'viking fk' => 'viking',
        'fredrikstad fk' => 'fredrikstad',
        'aston villa fc' => 'aston villa',
    ];

    /**
     * Verified VG TV-guide abbreviations for Champions League participants.
     *
     * These are deliberately ID-bound rather than fuzzy name matches.  The
     * image is still taken only from the already cached Champions League
     * payload, and only when that payload contains the expected participant.
     */
    private const CHAMPIONS_LEAGUE_TV_TEAM_IDS = [
        'atlético' => 1156,
        'bayern' => 4070,
        'psg' => 3419,
        'stuttgart' => 32503,
        'leipzig' => 4087,
        'fenerbahçe' => 5710,
        'aek' => 6344,
        'sabah' => 360471,
        'dortmund' => 4072,
        'shakhtar' => 58170,
    ];
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
                return $this->emptyVisuals();
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
                $byName[$key] = [
                    'url' => $team['emblemUrl'],
                    'type' => $team['emblemType'] ?? 'crest',
                ];
            }

            $homeVisual = $this->visualFor($competition, $parts[1], $byName, $teams);
            $awayVisual = $this->visualFor($competition, $parts[2], $byName, $teams);

            return [
                'homeLogoUrl' => $homeVisual['url'] ?? null,
                'awayLogoUrl' => $awayVisual['url'] ?? null,
                'homeVisualType' => $homeVisual['type'] ?? null,
                'awayVisualType' => $awayVisual['type'] ?? null,
            ];
        });
    }

    private function emptyVisuals(): array
    {
        return ['homeLogoUrl' => null, 'awayLogoUrl' => null, 'homeVisualType' => null, 'awayVisualType' => null];
    }

    private function visualFor(string $competition, string $name, array $byName, array $teams): ?array
    {
        $key = $this->key($name);
        $exactVisual = $byName[$key] ?? null;
        if (is_array($exactVisual)) {
            return $exactVisual;
        }

        if ($competition !== 'Champions League' || ! isset(self::CHAMPIONS_LEAGUE_TV_TEAM_IDS[$key])) {
            return null;
        }

        $expectedTeamId = self::CHAMPIONS_LEAGUE_TV_TEAM_IDS[$key];
        foreach ($teams as $team) {
            if (! is_array($team) || (int) ($team['teamId'] ?? 0) !== $expectedTeamId) {
                continue;
            }

            $url = $team['emblemUrl'] ?? null;
            if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            return ['url' => $url, 'type' => $team['emblemType'] ?? 'crest'];
        }

        return null;
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
        $key = Str::lower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));

        return self::ALIASES[$key] ?? $key;
    }
}
