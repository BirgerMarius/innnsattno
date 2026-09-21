<?php

namespace Tests\Unit;

use App\Services\NationsLeagueService;
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
}
