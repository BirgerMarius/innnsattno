<?php

namespace Tests\Feature;

use App\Services\ConferenceLeagueService;
use App\Services\EuropaLeagueService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UefaCompetitionControllerTest extends TestCase
{
    /** @test */
    public function europa_league_uses_verified_ids_and_reuses_the_uefa_views(): void
    {
        $this->assertSame(14, (new EuropaLeagueService())->tournamentId());
        $this->assertSame(9170, (new EuropaLeagueService())->seasonId());

        $this->fakeCompetition(9170, 8);

        $this->get('/europa-league')->assertOk()
            ->assertSee('Europa League')
            ->assertSee('/europa-league/lag/101', false)
            ->assertSee('Ligafasemotstander')
            ->assertSee('Trykk på et lagnavn i tabellen for å se lagets kamper.')
            ->assertDontSee('Beta')
            ->assertDontSee('Kvalifiseringsmotstander')
            ->assertSee('Åttedelsfinale');
        $this->get('/europa-league/print')->assertOk()->assertSee('@page')->assertSee('Europa League');
        $this->get('/europa-league/lag/101')->assertOk()
            ->assertSee('Runde 8')
            ->assertDontSee('Kvalifiseringsmotstander');
        $this->get('/europa-league/lag/101/print')->assertOk()->assertSee('Alle seriekamper');
        $this->get('/europa-league/lag/99999')->assertNotFound();
    }

    /** @test */
    public function conference_league_handles_its_six_match_league_phase(): void
    {
        $this->assertSame(469, (new ConferenceLeagueService())->tournamentId());
        $this->assertSame(9171, (new ConferenceLeagueService())->seasonId());

        $this->fakeCompetition(9171, 6);

        $this->get('/conference-league')->assertOk()
            ->assertSee('Conference League')
            ->assertSee('/conference-league/lag/101', false)
            ->assertSee('Ligafasemotstander')
            ->assertSee('Trykk på et lagnavn i tabellen for å se lagets kamper.')
            ->assertDontSee('Beta')
            ->assertDontSee('Kvalifiseringsmotstander');
        $this->get('/conference-league/print')->assertOk()->assertSee('@page')->assertSee('Conference League');
        $this->get('/conference-league/lag/101')->assertOk()
            ->assertSee('Runde 6')
            ->assertDontSee('Runde 7')
            ->assertDontSee('Kvalifiseringsmotstander');
        $this->get('/conference-league/lag/101/print')->assertOk()->assertSee('Alle seriekamper');
    }

    private function fakeCompetition(int $seasonId, int $leagueMatchCount): void
    {
        Cache::flush();
        Http::fake([
            '*/tournaments/seasons/'.$seasonId.'/schedule' => Http::response($this->schedulePayload($leagueMatchCount), 200),
            '*/tournaments/seasons/'.$seasonId.'/standings' => Http::response($this->standingsPayload(), 200),
        ]);
    }

    private function schedulePayload(int $leagueMatchCount): array
    {
        $participants = [
            101 => ['name' => 'Testklubb', 'images' => ['clubLogo' => ['url' => 'https://example.test/testklubb.png']]],
            102 => ['name' => 'Kvalifiseringsmotstander'],
            103 => ['name' => 'Ligafasemotstander', 'images' => ['clubLogo' => ['url' => 'https://example.test/motstander.png']]],
            104 => ['name' => 'Sluttspillmotstander'],
        ];
        $events = [[
            'id' => 1, 'startDate' => '2026-08-01T19:00:00Z', 'participantIds' => [101, 102], 'status' => ['type' => 'finished'],
            'results' => [101 => ['runningScore' => 1], 102 => ['runningScore' => 0]],
            'tournament' => ['phaseType' => 'cup', 'phase' => 'qualification', 'stage' => 'cupQualificationRound', 'stageName' => 'Kvalifisering', 'round' => '3'],
        ], [
            'id' => 2, 'startDate' => '2027-02-10T19:00:00Z', 'participantIds' => [101, 104], 'status' => ['type' => 'notStarted'],
            'tournament' => ['phaseType' => 'cup', 'phase' => 'knockout', 'stage' => 'roundOf16', 'stageName' => 'Åttedelsfinale', 'round' => '1'],
        ]];

        for ($round = 1; $round <= $leagueMatchCount; $round++) {
            $events[] = [
                'id' => 10 + $round, 'startDate' => '2026-08-10T19:00:00Z', 'participantIds' => [$round % 2 ? 101 : 103, $round % 2 ? 103 : 101],
                'status' => ['type' => $round === 1 ? 'finished' : 'notStarted'],
                'results' => $round === 1 ? [101 => ['runningScore' => 2], 103 => ['runningScore' => 1]] : [],
                'tournament' => ['phaseType' => 'group', 'phase' => 'group', 'stageName' => 'Ligafase', 'round' => (string) $round],
            ];
        }

        return ['participants' => $participants, 'events' => $events];
    }

    private function standingsPayload(): array
    {
        $rows = [];
        for ($teamId = 1; $teamId <= 36; $teamId++) {
            $rows[] = ['teamId' => $teamId === 1 ? 101 : $teamId + 200, 'rank' => $teamId, 'played' => 1, 'wins' => 1, 'draws' => 0, 'losses' => 0, 'goalsFor' => 2, 'goalsAgainst' => 1, 'goalsDiff' => 1, 'points' => 3];
        }

        return ['participants' => [101 => ['name' => 'Testklubb', 'images' => ['clubLogo' => ['url' => 'https://example.test/testklubb.png']]]], 'standings' => [['stageName' => 'Ligafase', 'teamStandings' => $rows]]];
    }
}
