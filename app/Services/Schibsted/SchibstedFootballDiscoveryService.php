<?php

namespace App\Services\Schibsted;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Throwable;

class SchibstedFootballDiscoveryService
{
    private const PROVIDER = 'Schibsted SportsNext';
    private const SPORT = 'football';

    private const SEASON_CAPABILITY_ENDPOINTS = [
        'schedule' => 'matches',
        'standings' => 'standings',
    ];

    private array $errors = [];

    public function discover(?string $filter = null): array
    {
        $this->errors = [];
        $verifiedAt = now()->toIso8601String();
        $knownSeasons = $this->knownSeasonIds($filter);
        $catalog = [];

        if (!$filter || $filter === '7767' || str_contains(strtolower($filter), 'world')) {
            $entry = $this->manualWorldCupEntry($verifiedAt);
            $catalog[$this->catalogKey($entry)] = $entry;
        }

        if (!$filter || str_contains(strtolower($filter), 'premier')) {
            $entry = $this->manualPremierLeagueEntry($verifiedAt);
            $catalog[$this->catalogKey($entry)] = $entry;
        }

        if (!$filter || str_contains(strtolower($filter), 'elite')) {
            $entry = $this->manualEliteserienEntry($verifiedAt);
            $catalog[$this->catalogKey($entry)] = $entry;
        }

        foreach ($knownSeasons as $seasonId) {
            $entry = $this->discoverSeason($seasonId, $verifiedAt);
            $key = $this->catalogKey($entry);
            $catalog[$key] = isset($catalog[$key])
                ? $this->mergeTournamentEntry($catalog[$key], $entry)
                : $entry;
        }

        if (empty($catalog) && !$filter) {
            $entry = $this->emptyKnownSeasonEntry(7767, $verifiedAt);
            $catalog[$this->catalogKey($entry)] = $entry;
        }

        $tournaments = array_values($catalog);
        usort($tournaments, function (array $a, array $b) {
            return [$a['country'] ?? '', $a['name'] ?? '', $a['current_season_id'] ?? 0]
                <=> [$b['country'] ?? '', $b['name'] ?? '', $b['current_season_id'] ?? 0];
        });

        return [
            'provider' => self::PROVIDER,
            'sport' => self::SPORT,
            'last_verified_at' => $verifiedAt,
            'source' => [
                'base_url' => $this->baseUrl(),
                'discovery_strategy' => 'Bruk manuelle referanseoppføringer og live-test kun eksplisitt kjente sesongendepunkter.',
                'network_notes' => $this->errors,
            ],
            'tournaments' => $tournaments,
        ];
    }

    public function writeCatalog(array $catalog, ?string $path = null): string
    {
        $path = $path ?: $this->catalogPath();
        $directory = dirname($path);

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($path, json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL);

        return $path;
    }

    public function catalogPath(): string
    {
        return config('services.schibsted_sports.catalog_path');
    }

    public function summarize(array $catalog): array
    {
        $tournaments = $catalog['tournaments'] ?? [];
        $seasonCount = 0;
        $capabilities = [];

        foreach ($tournaments as $tournament) {
            $seasonCount += count($tournament['available_seasons'] ?? []);

            foreach (($tournament['capabilities'] ?? []) as $capability => $available) {
                if ($available === true || $available === 'confirmed') {
                    $capabilities[$capability] = ($capabilities[$capability] ?? 0) + 1;
                }
            }
        }

        ksort($capabilities);

        return [
            'tournaments' => count($tournaments),
            'seasons' => $seasonCount,
            'capabilities' => $capabilities,
            'errors' => $catalog['source']['network_notes'] ?? [],
        ];
    }

    public function normalizeTournament(array $raw, string $verifiedAt): array
    {
        $seasons = $this->normalizeSeasons($raw);
        $currentSeason = $this->findCurrentSeason($raw, $seasons);
        $ids = $this->extractIds($raw);

        return [
            'provider' => self::PROVIDER,
            'sport' => self::SPORT,
            'country' => $this->firstString($raw, ['country.name', 'country', 'countryName']),
            'region' => $this->firstString($raw, ['region.name', 'region', 'area.name', 'area']),
            'gender' => $this->inferGender($raw),
            'competition_type' => $this->firstString($raw, ['competitionType', 'competition_type', 'type']),
            'name' => $this->firstString($raw, ['name', 'tournament.name', 'competition.name']) ?: 'Ukjent turnering',
            'short_name' => $this->firstString($raw, ['shortName', 'short_name', 'abbreviation']),
            'tournament_id' => $ids['tournament_id'],
            'tournament_slug' => $ids['tournament_slug'],
            'current_season_name' => $currentSeason['name'] ?? null,
            'current_season_id' => $currentSeason['season_id'] ?? null,
            'available_seasons' => $seasons,
            'start_date' => $currentSeason['start_date'] ?? $this->firstString($raw, ['startDate', 'start_date']),
            'end_date' => $currentSeason['end_date'] ?? $this->firstString($raw, ['endDate', 'end_date']),
            'endpoints' => [],
            'endpoint_status' => [],
            'capabilities' => $this->emptyCapabilities(),
            'sample_team_ids' => [],
            'sample_match_ids' => [],
            'last_verified_at' => $verifiedAt,
            'source' => 'live_discovery',
            'verification_status' => 'unknown',
            'notes' => [],
        ];
    }

    public function normalizeSeasons(array $raw): array
    {
        $seasonItems = $this->firstArray($raw, ['seasons', 'availableSeasons', 'tournament.seasons']);

        if (!$seasonItems) {
            $seasonId = $this->firstScalar($raw, ['seasonId', 'season.id', 'currentSeason.id', 'id']);

            return $seasonId ? [[
                'season_id' => (int) $seasonId,
                'name' => $this->firstString($raw, ['seasonName', 'season.name', 'currentSeason.name', 'name']),
                'start_date' => $this->firstString($raw, ['startDate', 'season.startDate', 'currentSeason.startDate']),
                'end_date' => $this->firstString($raw, ['endDate', 'season.endDate', 'currentSeason.endDate']),
                'active' => null,
            ]] : [];
        }

        $seasons = [];

        foreach ($seasonItems as $season) {
            if (!is_array($season)) {
                continue;
            }

            $seasonId = $this->firstScalar($season, ['id', 'seasonId']);

            $seasons[] = [
                'season_id' => $seasonId !== null ? (int) $seasonId : null,
                'name' => $this->firstString($season, ['name', 'seasonName']),
                'start_date' => $this->firstString($season, ['startDate', 'start_date']),
                'end_date' => $this->firstString($season, ['endDate', 'end_date']),
                'active' => $this->firstBool($season, ['active', 'current', 'isCurrent']),
            ];
        }

        return $seasons;
    }

    public function normalizeCapabilities(array $endpointResults): array
    {
        $capabilities = $this->emptyCapabilities();

        foreach ($endpointResults as $endpoint => $result) {
            $capability = self::SEASON_CAPABILITY_ENDPOINTS[$endpoint] ?? null;

            if (!$capability) {
                continue;
            }

            if (($result['ok'] ?? false) === true) {
                $capabilities[$capability] = true;

                continue;
            }

            if (str_starts_with((string) ($result['reason'] ?? ''), 'http_404')) {
                $capabilities[$capability] = false;
            }
        }

        if ($capabilities['matches'] !== null) {
            $capabilities['results'] = $capabilities['matches'];
            $capabilities['upcoming_matches'] = $capabilities['matches'];
            $capabilities['live_matches'] = $capabilities['matches'];
            $capabilities['match_status'] = $capabilities['matches'];
        }

        return $capabilities;
    }

    private function discoverSeason(int $seasonId, string $verifiedAt): array
    {
        $endpointResults = [];
        $scheduleData = null;
        $standingsData = null;

        foreach (self::SEASON_CAPABILITY_ENDPOINTS as $endpoint => $capability) {
            $result = $this->get("tournaments/seasons/{$seasonId}/{$endpoint}");
            $endpointResults[$endpoint] = $result;

            if ($result['ok'] && $endpoint === 'schedule') {
                $scheduleData = $result['data'];
            }

            if ($result['ok'] && $endpoint === 'standings') {
                $standingsData = $result['data'];
            }
        }

        $raw = $this->seasonMetadataFromResponses($seasonId, $scheduleData, $standingsData);
        $entry = $this->normalizeTournament($raw, $verifiedAt);
        $entry['current_season_id'] = $seasonId;
        $entry['available_seasons'] = $entry['available_seasons'] ?: [[
            'season_id' => $seasonId,
            'name' => $entry['current_season_name'],
            'start_date' => $entry['start_date'],
            'end_date' => $entry['end_date'],
            'active' => null,
        ]];
        $entry['endpoints'] = $this->successfulEndpoints($seasonId, $endpointResults);
        $entry['endpoint_status'] = $this->endpointStatuses($seasonId, $endpointResults);
        $entry['capabilities'] = $this->normalizeCapabilities($endpointResults);
        $entry['sample_team_ids'] = $this->sampleTeamIds($scheduleData, $standingsData);
        $entry['sample_match_ids'] = $this->sampleMatchIds($scheduleData);
        $entry['source'] = empty($entry['endpoints']) ? 'existing_code' : 'live_discovery';
        $entry['verification_status'] = empty($entry['endpoints']) ? 'unknown' : 'partly_confirmed';

        if (empty($entry['endpoints'])) {
            $entry['notes'][] = 'Ingen sesongendepunkter kunne bekreftes fra dette miljøet.';
        }

        if ($seasonId === 7767) {
            $entry = $this->mergeTournamentEntry($this->manualWorldCupEntry($verifiedAt), $entry);
        }

        if ($seasonId === $this->premierLeagueSeasonId()) {
            $entry = $this->mergeTournamentEntry($this->manualPremierLeagueEntry($verifiedAt), $entry);
        }

        if ($seasonId === $this->eliteserienSeasonId()) {
            $entry = $this->mergeTournamentEntry($this->manualEliteserienEntry($verifiedAt), $entry);
        }

        return $entry;
    }

    private function manualWorldCupEntry(string $verifiedAt): array
    {
        return [
            'provider' => self::PROVIDER,
            'sport' => self::SPORT,
            'country' => 'International',
            'region' => null,
            'gender' => 'men',
            'competition_type' => 'national_teams',
            'name' => 'FIFA World Cup 2026',
            'short_name' => 'World Cup 2026',
            'tournament_id' => 19,
            'tournament_slug' => null,
            'current_season_name' => 'World Cup 2026',
            'current_season_id' => 7767,
            'available_seasons' => [[
                'season_id' => 7767,
                'name' => 'World Cup 2026',
                'start_date' => null,
                'end_date' => null,
                'active' => null,
            ]],
            'start_date' => null,
            'end_date' => null,
            'endpoints' => [
                'schedule' => '/tournaments/seasons/7767/schedule',
                'standings' => '/tournaments/seasons/7767/standings',
                'season_details' => '/tournaments/seasons/7767',
                'tournament_details' => '/tournaments/19',
                'tournament_seasons' => '/tournaments/19/seasons',
                'season_participants_endpoint' => '/tournaments/seasons/7767/participants',
                'season_teams_endpoint' => '/tournaments/seasons/7767/teams',
                'season_statistics_endpoint' => '/tournaments/seasons/7767/statistics',
                'season_lineups_endpoint' => '/tournaments/seasons/7767/lineups',
            ],
            'endpoint_status' => [
                'schedule' => 'confirmed',
                'standings' => 'confirmed',
                'season_details' => 'confirmed',
                'tournament_details' => 'confirmed',
                'tournament_seasons' => 'confirmed',
                'season_participants_endpoint' => 'rejected_404',
                'season_teams_endpoint' => 'rejected_404',
                'season_statistics_endpoint' => 'rejected_404',
                'season_lineups_endpoint' => 'rejected_404',
            ],
            'capabilities' => array_merge($this->emptyCapabilities(), [
                'matches' => true,
                'results' => true,
                'upcoming_matches' => true,
                'match_status' => true,
                'standings' => true,
                'teams' => true,
            ], [
                'schedule' => 'confirmed',
                'standings' => 'confirmed',
                'season_details' => 'confirmed',
                'tournament_details' => 'confirmed',
                'tournament_seasons' => 'confirmed',
                'season_participants_endpoint' => 'rejected_404',
                'season_teams_endpoint' => 'rejected_404',
                'season_statistics_endpoint' => 'rejected_404',
                'season_lineups_endpoint' => 'rejected_404',
            ]),
            'sample_team_ids' => [],
            'sample_match_ids' => [],
            'last_verified_at' => $verifiedAt,
            'verified_at' => '2026-07-14',
            'verification_method' => 'live_api_from_docker01',
            'source' => 'live_verification',
            'verification_status' => 'confirmed',
            'notes' => [
                'Manuell curl og Laravel-kommandoer fra docker01/containeren bekreftet fem endepunkter 2026-07-14.',
                'Schedule-responsen inneholder 104 events og inkluderer participants-data.',
                'Separate participants-, teams-, statistics- og lineups-endepunkter på testet sesongsti returnerte 404.',
                'Codex-kjøremiljø kan fortsatt mangle DNS/nett selv om docker01 fungerer.',
            ],
        ];
    }

    private function manualPremierLeagueEntry(string $verifiedAt): array
    {
        return [
            'provider' => self::PROVIDER,
            'sport' => self::SPORT,
            'country' => 'England',
            'region' => null,
            'gender' => null,
            'competition_type' => 'club',
            'name' => 'Premier League',
            'short_name' => 'Premier League',
            'tournament_id' => 3,
            'tournament_slug' => null,
            'current_season_name' => '2026/27',
            'current_season_id' => 9186,
            'available_seasons' => [[
                'season_id' => 9186,
                'name' => '2026/27',
                'start_date' => null,
                'end_date' => null,
                'active' => null,
            ]],
            'start_date' => null,
            'end_date' => null,
            'endpoints' => [
                'season_details' => '/tournaments/seasons/9186',
                'schedule' => '/tournaments/seasons/9186/schedule',
                'standings' => '/tournaments/seasons/9186/standings',
                'tournament_details' => '/tournaments/3',
                'tournament_seasons' => '/tournaments/3/seasons',
            ],
            'endpoint_status' => [
                'season_details' => 'confirmed',
                'schedule' => 'confirmed',
                'standings' => 'confirmed',
                'tournament_details' => 'confirmed',
                'tournament_seasons' => 'confirmed',
            ],
            'capabilities' => array_merge($this->emptyCapabilities(), [
                'matches' => true,
                'results' => true,
                'upcoming_matches' => true,
                'match_status' => true,
                'teams' => true,
            ], [
                'season_details' => 'confirmed',
                'schedule' => 'confirmed',
                'standings' => 'confirmed',
                'tournament_details' => 'confirmed',
                'tournament_seasons' => 'confirmed',
            ]),
            'observed_response' => [
                'schedule_event_count' => 380,
                'schedule_top_level_fields' => [
                    'events',
                    'participants',
                    'tournament',
                    'tournamentSeason',
                    'countries',
                ],
                'standings_group_count' => 1,
                'standings_team_count' => 20,
                'standings_top_level_fields' => [
                    'standings',
                    'tournament',
                    'tournamentSeason',
                    'participants',
                    'countries',
                ],
                'tournament_season_count' => 34,
                'participants_embedded_in' => [
                    'schedule',
                    'standings',
                ],
            ],
            'sample_team_ids' => [],
            'sample_match_ids' => [],
            'last_verified_at' => $verifiedAt,
            'verified_at' => '2026-07-14',
            'verification_method' => 'live_api_from_docker01',
            'source' => 'live_verification',
            'verification_status' => 'confirmed',
            'notes' => [
                'Fem endepunkter returnerte HTTP 200 ved live-verifikasjon fra docker01/containeren 2026-07-14.',
                'Schedule-responsen inneholder 380 events.',
                'Standings-responsen inneholder 1 tabell med 20 lag.',
                'Tournament seasons-responsen inneholder 34 sesonger.',
                'Participants-data følger med i schedule og standings.',
            ],
        ];
    }

    private function premierLeagueSeasonId(): int
    {
        return (int) config('services.schibsted_sports.premier_league_season_id', 9186);
    }

    private function manualEliteserienEntry(string $verifiedAt): array
    {
        return [
            'provider' => self::PROVIDER,
            'sport' => self::SPORT,
            'country' => 'Norway',
            'region' => null,
            'gender' => 'men',
            'competition_type' => 'club',
            'name' => 'Eliteserien',
            'short_name' => 'Eliteserien',
            'tournament_id' => 38,
            'tournament_slug' => null,
            'current_season_name' => 'Eliteserien 2026',
            'current_season_id' => 8766,
            'available_seasons' => [[
                'season_id' => 8766,
                'name' => 'Eliteserien 2026',
                'start_date' => '2026-03-14',
                'end_date' => '2026-12-13',
                'active' => null,
            ]],
            'start_date' => '2026-03-14',
            'end_date' => '2026-12-13',
            'endpoints' => [
                'tournament_details' => '/tournaments/38',
                'tournament_seasons' => '/tournaments/38/seasons',
                'season_details' => '/tournaments/seasons/8766',
                'schedule' => '/tournaments/seasons/8766/schedule',
                'standings' => '/tournaments/seasons/8766/standings',
            ],
            'endpoint_status' => [
                'tournament_details' => 'confirmed',
                'tournament_seasons' => 'confirmed',
                'season_details' => 'confirmed',
                'schedule' => 'confirmed',
                'standings' => 'confirmed',
            ],
            'capabilities' => array_merge($this->emptyCapabilities(), [
                'matches' => true,
                'results' => true,
                'upcoming_matches' => true,
                'match_status' => true,
                'teams' => true,
            ], [
                'schedule' => 'confirmed',
                'standings' => 'confirmed',
                'season_details' => 'confirmed',
                'tournament_details' => 'confirmed',
                'tournament_seasons' => 'confirmed',
            ]),
            'observed_response' => [
                'schedule_event_count' => 240,
                'schedule_top_level_fields' => [
                    'events',
                    'participants',
                    'tournament',
                    'tournamentSeason',
                    'countries',
                ],
                'standings_group_count' => 1,
                'standings_team_count' => 16,
                'standings_top_level_fields' => [
                    'standings',
                    'tournament',
                    'tournamentSeason',
                    'participants',
                    'countries',
                ],
                'tournament_season_count' => 19,
                'participants_embedded_in' => [
                    'schedule',
                    'standings',
                ],
            ],
            'sample_team_ids' => [],
            'sample_match_ids' => [],
            'last_verified_at' => $verifiedAt,
            'verified_at' => '2026-07-14',
            'verification_method' => 'live_api_from_docker01',
            'source' => 'live_verification',
            'verification_status' => 'confirmed',
            'notes' => [
                'Fem endepunkter returnerte HTTP 200 ved live-verifikasjon fra docker01/containeren 2026-07-14.',
                'Schedule-responsen inneholder 240 events.',
                'Standings-responsen inneholder 1 tabell med 16 lag.',
                'Tournament seasons-responsen inneholder 19 sesonger.',
                'Participants-data følger med i schedule og standings.',
            ],
        ];
    }

    private function eliteserienSeasonId(): int
    {
        return (int) config('services.schibsted_sports.eliteserien_season_id', 8766);
    }

    private function emptyKnownSeasonEntry(int $seasonId, string $verifiedAt): array
    {
        return [
            'provider' => self::PROVIDER,
            'sport' => self::SPORT,
            'country' => null,
            'region' => null,
            'gender' => null,
            'competition_type' => null,
            'name' => 'Ukjent Schibsted-turnering',
            'short_name' => null,
            'tournament_id' => null,
            'tournament_slug' => null,
            'current_season_name' => null,
            'current_season_id' => $seasonId,
            'available_seasons' => [[
                'season_id' => $seasonId,
                'name' => null,
                'start_date' => null,
                'end_date' => null,
                'active' => null,
            ]],
            'start_date' => null,
            'end_date' => null,
            'endpoints' => [
                'schedule' => $this->url("tournaments/seasons/{$seasonId}/schedule"),
                'standings' => $this->url("tournaments/seasons/{$seasonId}/standings"),
            ],
            'endpoint_status' => [
                'schedule' => 'unknown',
                'standings' => 'unknown',
            ],
            'capabilities' => array_merge($this->emptyCapabilities(), [
                'matches' => null,
                'results' => null,
                'upcoming_matches' => null,
                'match_status' => null,
                'standings' => null,
            ]),
            'sample_team_ids' => [],
            'sample_match_ids' => [],
            'last_verified_at' => $verifiedAt,
            'source' => 'existing_code',
            'verification_status' => 'unknown',
            'notes' => [
                'Opprettet fra eksisterende kode fordi live discovery ikke returnerte data.',
                'Schedule og standings er kodebekreftede URL-mønstre, men respons ble ikke live-bekreftet i denne kjøringen.',
            ],
        ];
    }

    private function get(string $endpoint): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout((int) config('services.schibsted_sports.timeout', 10))
                ->retry(
                    (int) config('services.schibsted_sports.retry_times', 1),
                    (int) config('services.schibsted_sports.retry_sleep', 250)
                )
                ->get($this->url($endpoint));
        } catch (ConnectionException $exception) {
            return $this->failedEndpoint($endpoint, 'connection_error', $exception->getMessage());
        } catch (Throwable $exception) {
            return $this->failedEndpoint($endpoint, 'request_error', $exception->getMessage());
        }

        if (!$response->successful()) {
            return $this->failedEndpoint($endpoint, 'http_'.$response->status(), 'HTTP '.$response->status());
        }

        try {
            $data = $response->json();
        } catch (Throwable $exception) {
            return $this->failedEndpoint($endpoint, 'invalid_json', 'Response body was not valid JSON.');
        }

        if (!is_array($data)) {
            return $this->failedEndpoint($endpoint, 'invalid_json', 'Response JSON was not an object or array.');
        }

        return ['ok' => true, 'endpoint' => $endpoint, 'data' => $data];
    }

    private function failedEndpoint(string $endpoint, string $reason, string $message): array
    {
        $this->errors[] = [
            'endpoint' => $endpoint,
            'reason' => $reason,
            'message' => $this->redact($message),
        ];

        return ['ok' => false, 'endpoint' => $endpoint, 'reason' => $reason];
    }

    private function successfulEndpoints(int $seasonId, array $endpointResults): array
    {
        $endpoints = [];

        foreach ($endpointResults as $endpoint => $result) {
            if (($result['ok'] ?? false) === true) {
                $endpoints[$endpoint] = $this->url("tournaments/seasons/{$seasonId}/{$endpoint}");
            }
        }

        return $endpoints;
    }

    private function endpointStatuses(int $seasonId, array $endpointResults): array
    {
        $statuses = [];

        foreach ($endpointResults as $endpoint => $result) {
            if (($result['ok'] ?? false) === true) {
                $statuses[$endpoint] = 'confirmed';
            } elseif (str_starts_with((string) ($result['reason'] ?? ''), 'http_')) {
                $statuses[$endpoint] = 'rejected';
            } else {
                $statuses[$endpoint] = 'unknown';
            }
        }

        return $statuses;
    }

    private function seasonMetadataFromResponses(int $seasonId, ?array $schedule, ?array $standings): array
    {
        $firstEvent = $schedule['events'][0] ?? null;
        $tournament = is_array($firstEvent) ? ($firstEvent['tournament'] ?? []) : [];
        $participants = array_values(array_filter([
            $schedule['participants'] ?? null,
            $standings['participants'] ?? null,
        ], 'is_array'));

        return array_filter([
            'id' => $this->firstScalar($tournament, ['id', 'tournamentId']),
            'name' => $this->firstString($tournament, ['name', 'tournamentName']),
            'seasonId' => $seasonId,
            'seasonName' => $this->firstString($tournament, ['seasonName']),
            'startDate' => $this->firstString($schedule ?: [], ['events.0.startDate']),
            'country' => $this->countryFromParticipants($participants),
            'type' => $this->firstString($tournament, ['phaseType']),
        ], function ($value) {
            return $value !== null && $value !== '';
        });
    }

    private function extractTournamentLikeItems(array $data): array
    {
        $items = [];

        foreach (['tournaments', 'competitions', 'leagues'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return array_values(array_filter($data[$key], 'is_array'));
            }
        }

        foreach ($data as $value) {
            if (is_array($value) && isset($value['name']) && (isset($value['seasons']) || isset($value['currentSeason']) || isset($value['seasonId']))) {
                $items[] = $value;
            }
        }

        return $items;
    }

    private function findCurrentSeason(array $raw, array $seasons): ?array
    {
        $current = $this->firstArray($raw, ['currentSeason', 'season']);

        if ($current) {
            $normalized = $this->normalizeSeasons(['seasons' => [$current]]);

            return $normalized[0] ?? null;
        }

        foreach ($seasons as $season) {
            if (($season['active'] ?? false) === true) {
                return $season;
            }
        }

        return $seasons[0] ?? null;
    }

    private function mergeTournamentEntry(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if ($key === 'endpoints') {
                $existing[$key] = array_merge($existing[$key] ?? [], $value ?? []);
                continue;
            }

            if ($key === 'capabilities') {
                $existing[$key] = $this->mergeCapabilities($existing[$key] ?? [], $value ?? []);
                continue;
            }

            if ($key === 'endpoint_status') {
                $existing[$key] = $this->mergeEndpointStatuses($existing[$key] ?? [], $value ?? []);
                continue;
            }

            if ($key === 'available_seasons') {
                $existing[$key] = $this->mergeSeasons($existing[$key] ?? [], $value ?? []);
                continue;
            }

            if ($key === 'notes') {
                $existing[$key] = array_values(array_unique(array_merge($existing[$key] ?? [], $value ?? [])));
                continue;
            }

            if (($existing[$key] ?? null) === null && $value !== null) {
                $existing[$key] = $value;
            }
        }

        return $existing;
    }

    private function mergeCapabilities(array $existing, array $incoming): array
    {
        foreach ($incoming as $capability => $available) {
            if (($existing[$capability] ?? null) === true || in_array($existing[$capability] ?? null, ['confirmed', 'rejected_404'], true)) {
                continue;
            }

            if ($available !== null || !array_key_exists($capability, $existing)) {
                $existing[$capability] = $available;
            }
        }

        return $existing;
    }

    private function mergeEndpointStatuses(array $existing, array $incoming): array
    {
        foreach ($incoming as $endpoint => $status) {
            if (($existing[$endpoint] ?? null) === 'confirmed') {
                continue;
            }

            $existing[$endpoint] = $status;
        }

        return $existing;
    }

    private function mergeSeasons(array $existing, array $incoming): array
    {
        $seasons = [];

        foreach (array_merge($existing, $incoming) as $season) {
            $key = (string) ($season['season_id'] ?? json_encode($season));

            if (!isset($seasons[$key])) {
                $seasons[$key] = $season;
                continue;
            }

            foreach ($season as $field => $value) {
                if (!array_key_exists($field, $seasons[$key]) || ($seasons[$key][$field] === null && $value !== null)) {
                    $seasons[$key][$field] = $value;
                }
            }
        }

        return array_values($seasons);
    }

    private function emptyCapabilities(): array
    {
        return [
            'matches' => null,
            'results' => null,
            'upcoming_matches' => null,
            'live_matches' => null,
            'match_status' => null,
            'standings' => null,
            'teams' => null,
            'team_details' => null,
            'team_logos' => null,
            'venue' => null,
            'referee' => null,
            'match_details' => null,
            'events' => null,
            'goalscorers' => null,
            'cards' => null,
            'substitutions' => null,
            'lineups' => null,
            'match_statistics' => null,
            'players' => null,
            'player_profiles' => null,
            'top_scorers' => null,
            'news' => null,
        ];
    }

    private function knownSeasonIds(?string $filter): array
    {
        if ($filter && ctype_digit($filter)) {
            return [(int) $filter];
        }

        $ids = array_values(array_filter(array_map('intval', config('services.schibsted_sports.known_season_ids', []))));
        $configuredPremierLeagueSeason = config('services.schibsted_sports.premier_league_season_id');

        if ($configuredPremierLeagueSeason) {
            $ids[] = (int) $configuredPremierLeagueSeason;
        }

        $configuredEliteserienSeason = config('services.schibsted_sports.eliteserien_season_id');

        if ($configuredEliteserienSeason) {
            $ids[] = (int) $configuredEliteserienSeason;
        }

        return array_values(array_unique($ids));
    }

    private function matchesFilter(array $entry, ?string $filter): bool
    {
        if (!$filter || ctype_digit($filter)) {
            return true;
        }

        $haystack = strtolower(implode(' ', array_filter([
            $entry['name'] ?? null,
            $entry['short_name'] ?? null,
            $entry['country'] ?? null,
            $entry['tournament_slug'] ?? null,
        ])));

        return str_contains($haystack, strtolower($filter));
    }

    private function sampleTeamIds(?array ...$responses): array
    {
        $ids = [];

        foreach ($responses as $response) {
            foreach ((array) ($response['participants'] ?? []) as $id => $participant) {
                if (is_numeric($id)) {
                    $ids[] = (int) $id;
                }
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, 5);
    }

    private function sampleMatchIds(?array $schedule): array
    {
        $ids = [];

        foreach ((array) ($schedule['events'] ?? []) as $event) {
            if (isset($event['id'])) {
                $ids[] = $event['id'];
            }
        }

        return array_slice(array_values(array_unique($ids)), 0, 5);
    }

    private function extractIds(array $raw): array
    {
        return [
            'tournament_id' => $this->firstScalar($raw, ['tournamentId', 'tournament.id', 'competition.id', 'id']),
            'tournament_slug' => $this->firstString($raw, ['slug', 'tournament.slug', 'competition.slug']),
        ];
    }

    private function countryFromParticipants(array $participantSets): ?string
    {
        foreach ($participantSets as $participants) {
            $countryCodes = [];

            foreach ($participants as $participant) {
                if (is_array($participant) && !empty($participant['countryCode'])) {
                    $countryCodes[] = strtolower($participant['countryCode']);
                }
            }

            if (count(array_unique($countryCodes)) > 1) {
                return 'International';
            }
        }

        return null;
    }

    private function inferGender(array $raw): ?string
    {
        $text = strtolower(implode(' ', array_filter([
            $this->firstString($raw, ['name']),
            $this->firstString($raw, ['shortName']),
            $this->firstString($raw, ['gender']),
        ])));

        if (str_contains($text, 'women') || str_contains($text, 'kvinne')) {
            return 'women';
        }

        if (str_contains($text, 'men') || str_contains($text, 'herre')) {
            return 'men';
        }

        return $this->firstString($raw, ['gender']);
    }

    private function firstString(array $data, array $keys): ?string
    {
        $value = $this->firstScalar($data, $keys);

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    private function firstScalar(array $data, array $keys)
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);

            if (is_scalar($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function firstArray(array $data, array $keys): ?array
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);

            if (is_array($value)) {
                return $value;
            }
        }

        return null;
    }

    private function firstBool(array $data, array $keys): ?bool
    {
        foreach ($keys as $key) {
            $value = Arr::get($data, $key);

            if (is_bool($value)) {
                return $value;
            }
        }

        return null;
    }

    private function catalogKey(array $entry): string
    {
        return implode(':', [
            $entry['tournament_id'] ?? 'unknown',
            $entry['tournament_slug'] ?? 'unknown',
            $entry['current_season_id'] ?? 'unknown',
            $entry['name'] ?? 'unknown',
        ]);
    }

    private function redact(string $message): string
    {
        return preg_replace('/(token|key|secret|cookie|authorization)=([^&\s]+)/i', '$1=[redacted]', $message) ?: $message;
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.schibsted_sports.base_url'), '/');
    }

    private function url(string $endpoint): string
    {
        if (str_starts_with($endpoint, 'http://') || str_starts_with($endpoint, 'https://')) {
            return $endpoint;
        }

        return $this->baseUrl().'/'.ltrim($endpoint, '/');
    }
}
