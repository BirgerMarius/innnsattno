<?php

namespace App\Services;

class ObosLigaenService extends SchibstedCompetitionService
{
    public function getCompetitionData(): array
    {
        return $this->filterCompetitionData(parent::getCompetitionData());
    }

    public function normalizeCompetitionData(array $schedule, array $standings): array
    {
        return $this->filterCompetitionData(parent::normalizeCompetitionData($schedule, $standings));
    }

    public function cachedTeams(): array
    {
        return $this->getCompetitionData()['teams'] ?? [];
    }

    protected function tournamentConfigKey(): string { return 'services.schibsted_sports.obos_ligaen_tournament_id'; }
    protected function seasonConfigKey(): string { return 'services.schibsted_sports.obos_ligaen_season_id'; }
    protected function cachePrefix(): string { return 'obos_ligaen'; }
    protected function competitionLogName(): string { return 'OBOS-ligaen'; }

    /**
     * SportsNext currently returns a separate Finnmark competition in the
     * standings payload for tournament 40 / season 8767. The payload has no
     * group ID, so retain only standing groups linked to a participant in the
     * season schedule. This keeps the proper table even before kickoff, when
     * every team has zero matches played, and works for existing last-good
     * cache entries as well as newly refreshed data.
     */
    private function filterCompetitionData(array $competition): array
    {
        $scheduledTeamIds = [];
        foreach ($competition['matches'] ?? [] as $match) {
            foreach (['homeTeamId', 'awayTeamId'] as $field) {
                if (is_numeric($match[$field] ?? null)) {
                    $scheduledTeamIds[(int) $match[$field]] = true;
                }
            }
        }

        if ($scheduledTeamIds === []) {
            return $competition;
        }

        $groups = array_values(array_filter($competition['standingsGroups'] ?? [], function (array $group) use ($scheduledTeamIds): bool {
            foreach ($group['rows'] ?? [] as $row) {
                if (isset($scheduledTeamIds[(int) ($row['teamId'] ?? 0)])) {
                    return true;
                }
            }

            return false;
        }));

        if (array_key_exists('standingsGroups', $competition)) {
            $competition['standingsGroups'] = $groups;
            $competition['standings'] = [];
            foreach ($groups as $group) {
                foreach ($group['rows'] ?? [] as $row) {
                    $competition['standings'][] = $row;
                }
            }
        } else {
            $competition['standings'] = array_values(array_filter($competition['standings'] ?? [], function (array $row) use ($scheduledTeamIds): bool {
                return isset($scheduledTeamIds[(int) ($row['teamId'] ?? 0)]);
            }));
        }

        $competition['teams'] = array_filter($competition['teams'] ?? [], function (array $team) use ($scheduledTeamIds): bool {
            return isset($scheduledTeamIds[(int) ($team['teamId'] ?? 0)]);
        });

        return $competition;
    }
}
