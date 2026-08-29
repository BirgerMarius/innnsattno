<?php

namespace Tests\Unit;

use App\Services\ChampionsLeagueService;
use Tests\TestCase;

class ChampionsLeagueServiceTest extends TestCase
{
    /** @test */
    public function it_uses_the_verified_champions_league_ids(): void
    {
        $service = new ChampionsLeagueService();

        $this->assertSame(1, $service->tournamentId());
        $this->assertSame(9168, $service->seasonId());
        $this->assertStringEndsWith('/tournaments/seasons/9168/schedule', $service->endpoints()['schedule']);
    }

    /** @test */
    public function it_preserves_uefa_phase_fields_and_a_36_team_league_table(): void
    {
        $service = new ChampionsLeagueService();
        $participants = [];
        $rows = [];

        for ($teamId = 1; $teamId <= 36; $teamId++) {
            $participants[$teamId] = [
                'name' => $teamId === 1 ? 'Bodø/Glimt' : 'Lag '.$teamId,
                'images' => ['clubLogo' => ['url' => 'https://example.test/'.$teamId.'.png']],
            ];
            $rows[] = ['teamId' => $teamId, 'rank' => $teamId, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'goalsDiff' => 0, 'points' => 0, 'rule' => ['code' => 'playoffs']];
        }

        $data = $service->normalizeCompetitionData([
            'participants' => $participants,
            'events' => [[
                'id' => 1, 'startDate' => '2099-09-10T19:00:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'notStarted'],
                'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'stageName' => 'UEFA Champions League 26/27', 'round' => '1'],
            ]],
        ], [
            'participants' => $participants,
            'standings' => [['stageName' => 'UEFA Champions League 26/27', 'teamStandings' => $rows]],
        ]);

        $this->assertCount(36, $data['standings']);
        $this->assertSame('UEFA Champions League 26/27', $data['standingsGroups'][0]['stageName']);
        $this->assertSame(['code' => 'playoffs'], $data['standings'][0]['rule']);
        $this->assertSame('group', $data['matches'][0]['phaseType']);
        $this->assertSame('group', $data['matches'][0]['phase']);
        $this->assertSame('UEFA Champions League 26/27', $data['matches'][0]['stageName']);
        $this->assertSame(1, $data['matches'][0]['roundNumber']);
        $this->assertStringContainsString('rule=clip-64x64', $data['standings'][0]['emblemUrl']);
    }

    /** @test */
    public function it_keeps_multiple_standing_groups_in_source_order(): void
    {
        $data = (new ChampionsLeagueService())->normalizeCompetitionData(['participants' => [1 => ['name' => 'A'], 2 => ['name' => 'B'], 3 => ['name' => 'C'], 4 => ['name' => 'D']], 'events' => []], [
            'standings' => [
                ['groupName' => 'A', 'teamStandings' => [['teamId' => 2, 'rank' => 2], ['teamId' => 1, 'rank' => 1]]],
                ['groupName' => 'B', 'teamStandings' => [['teamId' => 4, 'rank' => 2], ['teamId' => 3, 'rank' => 1]]],
            ],
        ]);

        $this->assertCount(2, $data['standingsGroups']);
        $this->assertSame([1, 2, 3, 4], array_column($data['standings'], 'teamId'));
        $this->assertSame('B', $data['standingsGroups'][1]['groupName']);
    }

    /** @test */
    public function it_builds_data_driven_knockout_ties_and_excludes_qualification(): void
    {
        $service = new ChampionsLeagueService();
        $matches = [
            ['phaseType' => 'cup', 'phase' => 'qualification', 'stage' => 'cupQualificationRound', 'stageName' => 'Qualification', 'roundNumber' => 1],
            ['phaseType' => 'cup', 'phase' => 'knockout', 'stage' => 'roundOf16', 'stageName' => 'Åttedelsfinale', 'roundNumber' => 1, 'homeTeamId' => 1, 'awayTeamId' => 2, 'sortDate' => '2027-02-10', 'homeAggregateScore' => null, 'awayAggregateScore' => null],
            ['phaseType' => 'cup', 'phase' => 'knockout', 'stage' => 'roundOf16', 'stageName' => 'Åttedelsfinale', 'roundNumber' => 1, 'homeTeamId' => 2, 'awayTeamId' => 1, 'sortDate' => '2027-02-17', 'winnerTeamId' => 1, 'homeAggregateScore' => 1, 'awayAggregateScore' => 3],
        ];

        $rounds = $service->buildKnockoutRounds($matches);

        $this->assertCount(1, $rounds);
        $this->assertSame('Åttedelsfinale', $rounds[0]['label']);
        $this->assertCount(1, $rounds[0]['ties']);
        $this->assertCount(2, $rounds[0]['ties'][0]['legs']);
        $this->assertTrue($rounds[0]['ties'][0]['aggregateAvailable']);
        $this->assertSame(1, $rounds[0]['ties'][0]['winnerTeamId']);
    }
}
