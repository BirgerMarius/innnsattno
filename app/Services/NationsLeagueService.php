<?php

namespace App\Services;

class NationsLeagueService extends SchibstedCompetitionService
{
    public const NORWAY_TEAM_ID = 11667;

    protected function tournamentConfigKey(): string { return 'services.schibsted_sports.nations_league_tournament_id'; }
    protected function seasonConfigKey(): string { return 'services.schibsted_sports.nations_league_season_id'; }
    protected function cachePrefix(): string { return 'nations_league'; }
    protected function competitionLogName(): string { return 'UEFA Nations League'; }

    public function norwayGroup(array $standingsGroups): ?array
    {
        foreach ($standingsGroups as $group) {
            foreach ($group['rows'] as $row) {
                if ((int) ($row['teamId'] ?? 0) === self::NORWAY_TEAM_ID) {
                    return $group;
                }
            }
        }

        return null;
    }

    public function groupForTeam(array $standingsGroups, int $teamId): ?array
    {
        foreach ($standingsGroups as $group) {
            foreach ($group['rows'] as $row) {
                if ((int) ($row['teamId'] ?? 0) === $teamId) {
                    return $group;
                }
            }
        }

        return null;
    }
}
