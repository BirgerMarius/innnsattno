<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

abstract class SchibstedCompetitionService
{
    private const TIMEZONE = 'Europe/Oslo';
    private const UPCOMING_LIMIT = 12;
    private const RESULTS_LIMIT = 12;

    public function getCompetitionData(): array
    {
        $seasonId = $this->seasonId();

        if (!$seasonId) {
            return $this->emptyCompetitionData(false, false);
        }

        try {
            $schedule = $this->fetchSeasonEndpoint($seasonId, 'schedule');
            $standings = $this->fetchSeasonEndpoint($seasonId, 'standings');
            $data = $this->normalizeCompetitionData($schedule, $standings);

            Cache::put($this->lastGoodCacheKey($seasonId), $data, now()->addDays(7));

            return $data;
        } catch (Throwable $exception) {
            $this->logApiFailure($exception);

            $cached = Cache::get($this->lastGoodCacheKey($seasonId));

            if (is_array($cached)) {
                $cached['apiError'] = true;
                $cached['usingStaleData'] = true;

                return $cached;
            }

            return $this->emptyCompetitionData(true, false);
        }
    }

    public function getStandings(): array
    {
        return $this->getCompetitionData()['standings'];
    }

    public function getUpcomingFixtures(): array
    {
        return $this->getCompetitionData()['upcomingFixtures'];
    }

    public function getRecentResults(): array
    {
        return $this->getCompetitionData()['recentResults'];
    }

    public function getLastUpdated(): ?Carbon
    {
        return $this->getCompetitionData()['lastUpdated'];
    }

    public function getPrintData(string $leagueName, ?Carbon $now = null): array
    {
        $competition = $this->getCompetitionData();
        $now = ($now ?: now(self::TIMEZONE))->copy()->timezone(self::TIMEZONE);
        $resultsStart = $now->copy()->subDays(7);
        $fixturesEnd = $now->copy()->addDays(7);
        $matches = $competition['matches'] ?? [];

        $results = array_values(array_filter($matches, function (array $match) use ($resultsStart, $now) {
            return $match['isFinished']
                && $match['startsAt']
                && $match['startsAt']->betweenIncluded($resultsStart, $now);
        }));
        $fixtures = array_values(array_filter($matches, function (array $match) use ($now, $fixturesEnd) {
            return !$match['isFinished']
                && $match['startsAt']
                && $match['startsAt']->betweenIncluded($now, $fixturesEnd);
        }));

        $sortChronologically = function (array $a, array $b) {
            return $a['startsAt']->getTimestamp() <=> $b['startsAt']->getTimestamp();
        };
        usort($results, $sortChronologically);
        usort($fixtures, $sortChronologically);

        return array_merge($competition, [
            'leagueName' => $leagueName,
            'generatedAt' => $now,
            'resultsPeriod' => ['start' => $resultsStart, 'end' => $now->copy()],
            'fixturesPeriod' => ['start' => $now->copy(), 'end' => $fixturesEnd],
            'printResults' => $results,
            'printFixtures' => $fixtures,
        ]);
    }

    /**
     * Return one team's complete league season from the already normalised
     * competition data. Team IDs, never names, are used for matching.
     */
    public function getTeamSeasonData(int $teamId, string $leagueName): ?array
    {
        $competition = $this->getCompetitionData();
        $team = $competition['teams'][$teamId] ?? null;

        if (!$team) {
            return null;
        }

        $matches = array_values(array_filter($competition['matches'], function (array $match) use ($teamId) {
            return $match['homeTeamId'] === $teamId || $match['awayTeamId'] === $teamId;
        }));

        usort($matches, function (array $a, array $b) {
            if (!$a['startsAt'] && !$b['startsAt']) {
                return 0;
            }

            if (!$a['startsAt']) {
                return 1;
            }

            if (!$b['startsAt']) {
                return -1;
            }

            return $a['startsAt']->getTimestamp() <=> $b['startsAt']->getTimestamp();
        });

        foreach ($matches as &$match) {
            $match['isHome'] = $match['homeTeamId'] === $teamId;
            $match['opponent'] = $match['isHome'] ? $match['awayTeam'] : $match['homeTeam'];
            $match['opponentEmblemUrl'] = $match['isHome']
                ? ($match['awayEmblemUrl'] ?? null)
                : ($match['homeEmblemUrl'] ?? null);
            $match['teamScore'] = $match['isHome'] ? $match['homeScore'] : $match['awayScore'];
            $match['opponentScore'] = $match['isHome'] ? $match['awayScore'] : $match['homeScore'];
        }
        unset($match);

        return array_merge($competition, [
            'leagueName' => $leagueName,
            'seasonName' => $competition['seasonName'] ?? null,
            'team' => $team,
            'teamMatches' => $matches,
            'seasonStats' => $this->teamSeasonStats($matches, $competition['standings'], $teamId),
        ]);
    }

    public function seasonId(): ?int
    {
        $seasonId = config($this->seasonConfigKey());

        return $seasonId ? (int) $seasonId : null;
    }

    public function tournamentId(): ?int
    {
        $tournamentId = config($this->tournamentConfigKey());

        return $tournamentId ? (int) $tournamentId : null;
    }

    public function endpoints(): array
    {
        $seasonId = $this->seasonId();

        if (!$seasonId) {
            return [];
        }

        $endpoints = [
            'season_details' => $this->seasonDetailsUrl($seasonId),
            'schedule' => $this->seasonEndpointUrl($seasonId, 'schedule'),
            'standings' => $this->seasonEndpointUrl($seasonId, 'standings'),
        ];

        $tournamentId = $this->tournamentId();

        if ($tournamentId) {
            $endpoints['tournament_details'] = $this->tournamentUrl($tournamentId);
            $endpoints['tournament_seasons'] = $this->tournamentEndpointUrl($tournamentId, 'seasons');
        }

        return $endpoints;
    }

    public function normalizeCompetitionData(array $schedule, array $standings): array
    {
        $participants = $this->participantsFrom($schedule, $standings);
        $events = $this->eventsFrom($schedule);
        $matches = $this->normalizeMatches($events, $participants);

        $upcoming = array_values(array_filter($matches, function (array $match) {
            return !$match['isFinished'] && (!$match['startsAt'] || $match['startsAt']->greaterThanOrEqualTo(now(self::TIMEZONE)->startOfDay()));
        }));

        $results = array_values(array_filter($matches, function (array $match) {
            return $match['isFinished'];
        }));

        usort($upcoming, function (array $a, array $b) {
            return strcmp((string) $a['sortDate'], (string) $b['sortDate']);
        });

        usort($results, function (array $a, array $b) {
            return strcmp((string) $b['sortDate'], (string) $a['sortDate']);
        });

        return [
            'standings' => $this->normalizeStandings($standings, $participants),
            'teams' => $this->normalizeTeams($participants),
            'matches' => $matches,
            'upcomingFixtures' => array_slice($upcoming, 0, self::UPCOMING_LIMIT),
            'recentResults' => array_slice($results, 0, self::RESULTS_LIMIT),
            'lastUpdated' => $this->findLastUpdated($schedule, $standings),
            'seasonName' => $this->seasonName($schedule, $standings),
            'apiConfigured' => true,
            'apiError' => false,
            'usingStaleData' => false,
        ];
    }

    private function fetchSeasonEndpoint(int $seasonId, string $endpoint): array
    {
        $cacheKey = sprintf('schibsted_sports.%s.%d.%s', $this->cachePrefix(), $seasonId, $endpoint);
        $ttl = (int) config('services.schibsted_sports.cache_ttl', 900);

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                return $cached;
            }
        }

        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.schibsted_sports.timeout', 10))
                ->retry(
                    (int) config('services.schibsted_sports.retry_times', 1),
                    (int) config('services.schibsted_sports.retry_sleep', 250)
                )
                ->get($this->seasonEndpointUrl($seasonId, $endpoint))
                ->throw();
        } catch (ConnectionException $exception) {
            throw $exception;
        } catch (RequestException $exception) {
            throw $exception;
        }

        $data = $response->json();

        if (!is_array($data)) {
            throw new UnexpectedValueException('Schibsted Sports API returned invalid JSON.');
        }

        Cache::put($cacheKey, $data, now()->addSeconds($ttl));

        return $data;
    }

    private function seasonEndpointUrl(int $seasonId, string $endpoint): string
    {
        return sprintf('%s/%s', $this->seasonDetailsUrl($seasonId), ltrim($endpoint, '/'));
    }

    private function seasonDetailsUrl(int $seasonId): string
    {
        $baseUrl = rtrim(config('services.schibsted_sports.base_url'), '/');

        return sprintf('%s/tournaments/seasons/%d', $baseUrl, $seasonId);
    }

    private function tournamentUrl(int $tournamentId): string
    {
        $baseUrl = rtrim(config('services.schibsted_sports.base_url'), '/');

        return sprintf('%s/tournaments/%d', $baseUrl, $tournamentId);
    }

    private function tournamentEndpointUrl(int $tournamentId, string $endpoint): string
    {
        return sprintf('%s/%s', $this->tournamentUrl($tournamentId), ltrim($endpoint, '/'));
    }

    private function normalizeStandings(array $standings, array $participants): array
    {
        $groups = $standings['standings'] ?? [];
        $table = [];

        foreach ($groups as $group) {
            foreach (($group['teamStandings'] ?? []) as $teamStanding) {
                $teamId = $teamStanding['teamId'] ?? null;
                $participant = $teamId && isset($participants[$teamId]) ? $participants[$teamId] : [];
                $goalsFor = $this->nullableInt($teamStanding['goalsFor'] ?? null);
                $goalsAgainst = $this->nullableInt($teamStanding['goalsAgainst'] ?? null);

                $table[] = [
                    'rank' => $this->nullableInt($teamStanding['rank'] ?? null),
                    'teamId' => $teamId,
                    'teamName' => $participant['name'] ?? $teamStanding['teamName'] ?? 'Ukjent lag',
                    'shortName' => $participant['shortName'] ?? $participant['abbreviation'] ?? null,
                    'emblemUrl' => $this->extractImageUrl($participant),
                    'played' => $this->nullableInt($teamStanding['played'] ?? null),
                    'wins' => $this->nullableInt($teamStanding['wins'] ?? null),
                    'draws' => $this->nullableInt($teamStanding['draws'] ?? null),
                    'losses' => $this->nullableInt($teamStanding['losses'] ?? null),
                    'goalsFor' => $goalsFor,
                    'goalsAgainst' => $goalsAgainst,
                    'goalDifference' => $this->goalDifference($teamStanding, $goalsFor, $goalsAgainst),
                    'points' => $this->nullableInt($teamStanding['points'] ?? null),
                    'groupName' => $group['groupName'] ?? null,
                ];
            }
        }

        usort($table, function (array $a, array $b) {
            return ($a['rank'] ?? 999) <=> ($b['rank'] ?? 999);
        });

        return $table;
    }

    private function normalizeMatches(array $events, array $participants): array
    {
        $matches = [];

        foreach ($events as $event) {
            $homeId = $event['participantIds'][0] ?? null;
            $awayId = $event['participantIds'][1] ?? null;
            $startsAt = $this->parseDate($event['startDate'] ?? null);
            $statusType = strtolower((string) ($event['status']['type'] ?? ''));
            $statusSubtype = strtolower((string) ($event['status']['subtype'] ?? ''));
            $isFinished = $this->isFinishedStatus($statusType, $statusSubtype);

            $matches[] = [
                'id' => $event['id'] ?? null,
                'startsAt' => $startsAt,
                'sortDate' => $startsAt ? $startsAt->toIso8601String() : '',
                'dateKey' => $startsAt ? $startsAt->format('Y-m-d') : 'ukjent',
                'dateLabel' => $startsAt ? $startsAt->format('d.m.Y') : 'Dato ikke satt',
                'timeLabel' => $startsAt ? $startsAt->format('H:i') : '',
                'homeTeamId' => $this->nullableInt($homeId),
                'awayTeamId' => $this->nullableInt($awayId),
                'homeTeam' => $this->teamName($participants, $homeId),
                'awayTeam' => $this->teamName($participants, $awayId),
                'homeEmblemUrl' => $this->teamEmblem($participants, $homeId),
                'awayEmblemUrl' => $this->teamEmblem($participants, $awayId),
                'homeScore' => $isFinished ? $this->scoreFor($event, $homeId) : null,
                'awayScore' => $isFinished ? $this->scoreFor($event, $awayId) : null,
                'status' => $statusType,
                'statusSubtype' => $statusSubtype,
                'statusLabel' => $this->statusLabel($statusType, $statusSubtype),
                'isFinished' => $isFinished,
                'round' => $this->roundLabel($event),
            ];
        }

        return $matches;
    }

    private function normalizeTeams(array $participants): array
    {
        $teams = [];

        foreach ($participants as $teamId => $participant) {
            if (!is_array($participant) || !is_numeric($teamId)) {
                continue;
            }

            $teams[(int) $teamId] = [
                'teamId' => (int) $teamId,
                'teamName' => $participant['name'] ?? 'Ukjent lag',
                'shortName' => $participant['shortName'] ?? $participant['abbreviation'] ?? null,
                'emblemUrl' => $this->extractImageUrl($participant),
            ];
        }

        return $teams;
    }

    /**
     * Build team-page season statistics from the existing normalised league
     * schedule. Only completed matches with both final scores are included.
     */
    private function teamSeasonStats(array $matches, array $standings, int $teamId): array
    {
        $standing = null;

        foreach ($standings as $row) {
            if ($this->nullableInt($row['teamId'] ?? null) === $teamId) {
                $standing = $row;
                break;
            }
        }

        $finishedMatches = array_values(array_filter($matches, function (array $match) {
            return $match['isFinished']
                && $match['teamScore'] !== null
                && $match['opponentScore'] !== null;
        }));

        $home = ['wins' => 0, 'draws' => 0, 'losses' => 0];
        $away = ['wins' => 0, 'draws' => 0, 'losses' => 0];
        $form = [];
        $largestWin = null;
        $largestLoss = null;
        $highestScoringMatch = null;
        $cleanSheets = 0;

        foreach ($finishedMatches as $match) {
            $teamScore = $match['teamScore'];
            $opponentScore = $match['opponentScore'];
            $result = $teamScore > $opponentScore ? 'V' : ($teamScore === $opponentScore ? 'U' : 'T');

            if ($result === 'V') {
                if ($match['isHome']) {
                    $home['wins']++;
                } else {
                    $away['wins']++;
                }
            } elseif ($result === 'U') {
                if ($match['isHome']) {
                    $home['draws']++;
                } else {
                    $away['draws']++;
                }
            } else {
                if ($match['isHome']) {
                    $home['losses']++;
                } else {
                    $away['losses']++;
                }
            }

            if ($opponentScore === 0) {
                $cleanSheets++;
            }

            $form[] = ['result' => $result];
            $record = [
                'opponent' => $match['opponent'],
                'teamScore' => $teamScore,
                'opponentScore' => $opponentScore,
                'isHome' => $match['isHome'],
                'startsAt' => $match['startsAt'],
            ];
            $margin = $teamScore - $opponentScore;
            $totalGoals = $teamScore + $opponentScore;

            if ($margin > 0 && ($largestWin === null || $margin > $largestWin['margin'])) {
                $largestWin = $record + ['margin' => $margin];
            }

            if ($margin < 0 && ($largestLoss === null || $margin < $largestLoss['margin'])) {
                $largestLoss = $record + ['margin' => $margin];
            }

            if ($highestScoringMatch === null || $totalGoals > $highestScoringMatch['totalGoals']) {
                $highestScoringMatch = $record + ['totalGoals' => $totalGoals];
            }
        }

        return [
            'hasFinishedMatches' => count($finishedMatches) > 0,
            'keyFigures' => [
                ['label' => 'Tabellplass', 'value' => $standing['rank'] ?? null],
                ['label' => 'Kamper spilt', 'value' => $standing['played'] ?? null],
                ['label' => 'Seire', 'value' => $standing['wins'] ?? null],
                ['label' => 'Uavgjort', 'value' => $standing['draws'] ?? null],
                ['label' => 'Tap', 'value' => $standing['losses'] ?? null],
                ['label' => 'Mål for', 'value' => $standing['goalsFor'] ?? null],
                ['label' => 'Mål mot', 'value' => $standing['goalsAgainst'] ?? null],
                ['label' => 'Målforskjell', 'value' => $standing['goalDifference'] ?? null],
                ['label' => 'Poeng', 'value' => $standing['points'] ?? null],
            ],
            'form' => array_slice($form, -5),
            'home' => $home,
            'away' => $away,
            'records' => [
                'largestWin' => $largestWin,
                'largestLoss' => $largestLoss,
                'highestScoringMatch' => $highestScoringMatch,
                'cleanSheets' => $cleanSheets,
            ],
        ];
    }

    private function seasonName(array ...$responses): ?string
    {
        foreach ($responses as $response) {
            $name = $response['tournamentSeason']['name'] ?? null;

            if (is_string($name) && trim($name) !== '') {
                return $name;
            }
        }

        return null;
    }

    private function participantsFrom(array $schedule, array $standings): array
    {
        return ($schedule['participants'] ?? []) + ($standings['participants'] ?? []);
    }

    private function eventsFrom(array $schedule): array
    {
        return is_array($schedule['events'] ?? null) ? $schedule['events'] : [];
    }

    private function scoreFor(array $event, $participantId): ?int
    {
        if (!$participantId) {
            return null;
        }

        return $this->nullableInt($event['results'][$participantId]['runningScore'] ?? null);
    }

    private function isFinishedStatus(string $statusType, string $statusSubtype): bool
    {
        return in_array($statusType, ['finished', 'done', 'ended'], true)
            || in_array($statusSubtype, ['finished', 'fulltime', 'full_time', 'ft'], true);
    }

    private function statusLabel(string $statusType, string $statusSubtype): string
    {
        $status = $statusSubtype ?: $statusType;

        $labels = [
            'finished' => 'Ferdig',
            'done' => 'Ferdig',
            'ended' => 'Ferdig',
            'postponed' => 'Utsatt',
            'cancelled' => 'Avlyst',
            'canceled' => 'Avlyst',
            'delayed' => 'Forsinket',
            'interrupted' => 'Avbrutt',
            'live' => 'Pågår',
            'inprogress' => 'Pågår',
            'in_progress' => 'Pågår',
            'notstarted' => 'Ikke startet',
            'not_started' => 'Ikke startet',
            'scheduled' => 'Ikke startet',
            'fixture' => 'Ikke startet',
        ];

        return $labels[$status] ?? ($status ? ucfirst($status) : 'Ikke startet');
    }

    private function roundLabel(array $event): ?string
    {
        foreach (['roundName', 'stageName', 'stage', 'groupName', 'name'] as $key) {
            $value = $event['tournament'][$key] ?? $event[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function teamName(array $participants, $teamId): string
    {
        if (!$teamId || !isset($participants[$teamId])) {
            return 'Ukjent lag';
        }

        return $participants[$teamId]['name'] ?? 'Ukjent lag';
    }

    private function teamEmblem(array $participants, $teamId): ?string
    {
        if (!$teamId || !isset($participants[$teamId])) {
            return null;
        }

        return $this->extractImageUrl($participants[$teamId]);
    }

    private function extractImageUrl(array $participant): ?string
    {
        // SportsNext supplies club crests as participants.{id}.images.clubLogo.url.
        // VG Live's image component turns that resource URL into
        // ?rule=clip-{size}x{size}; the bare resource URL is not an image.
        $clubLogoUrl = $participant['images']['clubLogo']['url'] ?? null;

        if (is_string($clubLogoUrl) && filter_var($clubLogoUrl, FILTER_VALIDATE_URL)) {
            return $this->sportsNextClubLogoVariantUrl($clubLogoUrl, 64);
        }

        foreach ($participant as $key => $value) {
            if (is_array($value)) {
                $nested = $this->extractImageUrl($value);

                if ($nested) {
                    return $nested;
                }

                continue;
            }

            if (!is_string($value) || !preg_match('/^https?:\/\//', $value)) {
                continue;
            }

            if (preg_match('/(logo|crest|emblem|badge|image|picture)/i', (string) $key)) {
                return $value;
            }
        }

        return null;
    }

    private function sportsNextClubLogoVariantUrl(string $url, int $size): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'rule=clip-'.$size.'x'.$size;
    }

    private function goalDifference(array $teamStanding, ?int $goalsFor, ?int $goalsAgainst): ?int
    {
        foreach (['goalDifference', 'goalsDifference', 'goaldifference'] as $key) {
            if (array_key_exists($key, $teamStanding)) {
                return $this->nullableInt($teamStanding[$key]);
            }
        }

        if ($goalsFor === null || $goalsAgainst === null) {
            return null;
        }

        return $goalsFor - $goalsAgainst;
    }

    private function findLastUpdated(array ...$responses): ?Carbon
    {
        foreach ($responses as $response) {
            $date = $this->findDateByKey($response, ['lastUpdated', 'updatedAt', 'generatedAt', 'updated']);

            if ($date) {
                return $date;
            }
        }

        return null;
    }

    private function findDateByKey(array $data, array $keys): ?Carbon
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $nested = $this->findDateByKey($value, $keys);

                if ($nested) {
                    return $nested;
                }

                continue;
            }

            if (in_array((string) $key, $keys, true)) {
                return $this->parseDate($value);
            }
        }

        return null;
    }

    private function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->timezone(self::TIMEZONE);
        } catch (Throwable $exception) {
            return null;
        }
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function emptyCompetitionData(bool $apiError, bool $usingStaleData): array
    {
        return [
            'standings' => [],
            'teams' => [],
            'matches' => [],
            'upcomingFixtures' => [],
            'recentResults' => [],
            'lastUpdated' => null,
            'seasonName' => null,
            'apiConfigured' => (bool) $this->seasonId(),
            'apiError' => $apiError,
            'usingStaleData' => $usingStaleData,
        ];
    }

    private function lastGoodCacheKey(int $seasonId): string
    {
        return 'schibsted_sports.'.$this->cachePrefix().'.'.$seasonId.'.last_good';
    }

    private function logApiFailure(Throwable $exception): void
    {
        Log::warning('Could not fetch '.$this->competitionLogName().' data from Schibsted Sports API.', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
        ]);
    }

    abstract protected function tournamentConfigKey(): string;

    abstract protected function seasonConfigKey(): string;

    abstract protected function cachePrefix(): string;

    abstract protected function competitionLogName(): string;
}
