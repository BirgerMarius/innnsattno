<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FootballTeamPageTest extends TestCase
{
    /** @dataProvider leagues */
    public function test_team_page_uses_team_ids_and_shows_home_away_results_and_statuses(string $path, int $seasonId): void
    {
        $this->fakeCompetition($seasonId);

        $response = $this->get($path.'/lag/1');

        $response->assertOk()
            ->assertSee('Test FC')
            ->assertSee('https://example.test/test-fc.png', false)
            ->assertSee('Motstander A')
            ->assertSee('Motstander B')
            ->assertSee('Motstander C')
            ->assertSee('Hjemme')
            ->assertSee('Borte')
            ->assertSee('2–1')
            ->assertSee('Utsatt')
            ->assertDontSee('Utenfor ligaen')
            ->assertSeeInOrder(['01.08.2026', '08.08.2026', '15.08.2026']);
    }

    /** @dataProvider leagues */
    public function test_team_print_page_has_compact_one_page_layout_and_returns_to_same_team(string $path, int $seasonId): void
    {
        $this->fakeCompetition($seasonId);
        $escapedReturnPath = str_replace('/', '\\/', $path.'/lag/1');

        $this->get($path.'/lag/1/utskrift')
            ->assertOk()
            ->assertSee('football-team-print-list', false)
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('if (hasReturnedToTeamPage)', false)
            ->assertSee('window.location.replace(teamPageUrl)', false)
            ->assertSee('const teamPageUrl = "'.$escapedReturnPath.'"', false)
            ->assertSee('window.print()', false)
            ->assertSee('{ once: true }', false)
            ->assertSee('Merk:');

        $css = File::get(public_path('css/custom/app.css'));
        $this->assertStringContainsString('.football-team-print-list { column-count: 2;', $css);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 12mm; }', $css);
        $this->assertStringContainsString('font-size: 10pt', $css);
        $this->assertStringContainsString('column-gap: 8mm', $css);
    }

    /** @dataProvider leagues */
    public function test_team_page_handles_missing_logo_and_invalid_team_id(string $path, int $seasonId): void
    {
        $this->fakeCompetition($seasonId);

        $this->get($path.'/lag/2')->assertOk()
            ->assertSee('Motstander A')
            ->assertDontSee('football-team-emblem', false);
        $this->get($path.'/lag/999')->assertNotFound();
    }

    /** @dataProvider fullSeasonPrints */
    public function test_full_team_season_print_keeps_all_matches_in_the_two_column_layout(int $seasonId, int $matchCount): void
    {
        $this->fakeFullTeamSeason($seasonId, $matchCount);

        $response = $this->get(($seasonId === 8766 ? '/eliteserien' : '/premier-league').'/lag/1/utskrift')->assertOk();

        $this->assertSame($matchCount, substr_count($response->getContent(), 'football-team-print-match'));
        $this->assertStringContainsString('font-size: 10pt', File::get(public_path('css/custom/app.css')));
    }

    /** @dataProvider leagues */
    public function test_league_page_links_team_names_and_explains_team_season_pages(string $path, int $seasonId): void
    {
        $this->fakeCompetition($seasonId);

        $this->get($path)->assertOk()
            ->assertSee('Se alle kampene til et lag:')
            ->assertSee($path.'/lag/1', false)
            ->assertSee($path.'/lag/2', false);
    }

    public function leagues(): array
    {
        return [
            'Eliteserien' => ['/eliteserien', 8766],
            'Premier League' => ['/premier-league', 9186],
        ];
    }

    public function fullSeasonPrints(): array
    {
        return ['Eliteserien' => [8766, 30], 'Premier League' => [9186, 38]];
    }

    private function fakeCompetition(int $seasonId): void
    {
        Cache::flush();
        $participants = [
            1 => ['name' => 'Test FC', 'shortName' => 'TFC', 'logoUrl' => 'https://example.test/test-fc.png'],
            2 => ['name' => 'Motstander A'],
            3 => ['name' => 'Motstander B'],
            4 => ['name' => 'Motstander C'],
            5 => ['name' => 'Utenfor ligaen'],
        ];
        $events = [
            ['id' => 3, 'startDate' => '2026-08-15T16:30:00Z', 'participantIds' => [1, 4], 'status' => ['type' => 'postponed']],
            ['id' => 1, 'startDate' => '2026-08-01T16:30:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'finished'], 'results' => [1 => ['runningScore' => 2], 2 => ['runningScore' => 1]]],
            ['id' => 4, 'startDate' => '2026-08-03T16:30:00Z', 'participantIds' => [4, 5], 'status' => ['type' => 'finished'], 'results' => [4 => ['runningScore' => 1], 5 => ['runningScore' => 0]]],
            ['id' => 2, 'startDate' => '2026-08-08T16:30:00Z', 'participantIds' => [3, 1], 'status' => ['type' => 'notstarted']],
        ];
        $standings = [];
        foreach ($participants as $teamId => $participant) $standings[] = ['teamId' => $teamId, 'rank' => $teamId, 'played' => 0, 'points' => 0];

        Http::fake([
            "*/tournaments/seasons/{$seasonId}/schedule" => Http::response(['participants' => $participants, 'events' => $events, 'tournamentSeason' => ['name' => '2026/27']], 200),
            "*/tournaments/seasons/{$seasonId}/standings" => Http::response(['participants' => $participants, 'standings' => [['teamStandings' => $standings]]], 200),
        ]);
    }

    private function fakeFullTeamSeason(int $seasonId, int $matchCount): void
    {
        Cache::flush();
        $participants = [1 => ['name' => 'Test FC'], 2 => ['name' => 'Motstander med et langt klubbnavn']];
        $events = [];

        for ($match = 1; $match <= $matchCount; $match++) {
            $events[] = ['id' => $match, 'startDate' => sprintf('2026-%02d-%02dT16:30:00Z', min(12, intdiv($match - 1, 3) + 1), (($match - 1) % 3) + 1), 'participantIds' => $match % 2 ? [1, 2] : [2, 1], 'status' => ['type' => 'notstarted']];
        }

        Http::fake([
            "*/tournaments/seasons/{$seasonId}/schedule" => Http::response(['participants' => $participants, 'events' => $events], 200),
            "*/tournaments/seasons/{$seasonId}/standings" => Http::response(['participants' => $participants, 'standings' => [['teamStandings' => [['teamId' => 1, 'rank' => 1]]]]], 200),
        ]);
    }
}
