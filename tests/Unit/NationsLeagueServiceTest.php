<?php

namespace Tests\Unit;

use App\Services\NationsLeagueService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class NationsLeagueServiceTest extends TestCase
{
    /** @test */
    public function it_uses_verified_nations_league_ids_and_finds_norways_group(): void
    {
        $service = new NationsLeagueService();
        $this->assertSame(187, $service->tournamentId());
        $this->assertSame(8889, $service->seasonId());
        $this->assertStringEndsWith('/tournaments/seasons/8889/standings', $service->endpoints()['standings']);

        $data = $service->normalizeCompetitionData(['participants' => [11667 => ['name' => 'Norge'], 2 => ['name' => 'Danmark']], 'events' => []], ['standings' => [
            ['groupName' => 'A4', 'stageName' => 'League A, Group 4', 'teamStandings' => [
                ['teamId' => 2, 'rank' => 1, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'points' => 0],
                ['teamId' => 11667, 'rank' => 2, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'points' => 0, 'rule' => ['name' => 'Nedrykkskvalifisering']],
            ]],
        ]]);

        $this->assertSame('A4', $service->norwayGroup($data['standingsGroups'])['groupName']);
        $this->assertSame('Nedrykkskvalifisering', $service->norwayGroup($data['standingsGroups'])['rows'][1]['rule']['name']);
    }

    /** @test */
    public function it_selects_all_matches_from_monday_through_sunday_in_oslo_time(): void
    {
        $service = new NationsLeagueService();
        $data = $service->normalizeCompetitionData(['participants' => [1 => ['name' => 'Norge'], 2 => ['name' => 'Danmark']], 'events' => [
            ['id' => 1, 'startDate' => '2026-09-24T18:45:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'notStarted']],
            ['id' => 2, 'startDate' => '2026-09-27T18:45:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'notStarted']],
            ['id' => 3, 'startDate' => '2026-09-28T18:45:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'notStarted']],
        ]], ['standings' => []]);

        $week = $service->matchesForCalendarWeek($data['matches'], Carbon::parse('2026-09-21 10:00:00', 'Europe/Oslo'));
        $this->assertSame([1, 2], array_column($week, 'id'));
    }
}
