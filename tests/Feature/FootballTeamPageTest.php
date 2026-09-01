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
            ->assertSee('Sesongen så langt')
            ->assertSee('https://example.test/motstander-a.png', false)
            ->assertSee('https://example.test/motstander-b.png', false)
            ->assertSee('Form siste 1')
            ->assertDontSee('Utenfor ligaen')
            ->assertSeeInOrder(['01.08.2026', '08.08.2026', '15.08.2026']);

        $response->assertViewHas('teamMatches', function (array $matches) {
            $this->assertSame('https://example.test/motstander-a.png', $matches[0]['opponentEmblemUrl']);
            $this->assertSame('https://example.test/motstander-b.png', $matches[1]['opponentEmblemUrl']);
            $this->assertNull($matches[2]['opponentEmblemUrl']);

            return true;
        });
    }

    /** @dataProvider leagues */
    public function test_team_print_page_has_compact_one_page_layout_and_returns_to_same_team(string $path, int $seasonId): void
    {
        $this->fakeCompetition($seasonId);
        $escapedReturnPath = str_replace('/', '\\/', $path.'/lag/1');

        $this->get($path.'/lag/1/utskrift')
            ->assertOk()
            ->assertSee('football-team-print-list', false)
            ->assertSee('football-team-print-opponent', false)
            ->assertSee('https://example.test/motstander-a.png', false)
            ->assertSee('https://example.test/motstander-b.png', false)
            ->assertSee('football-team-print-stats', false)
            ->assertSee('Sesongen så langt')
            ->assertSee('Tabellplass:')
            ->assertSee('Hjemme: 1 seier, 0 uavgjort, 0 tap')
            ->assertSee('Største seier: 2–1 mot Motstander A (01.08.2026)')
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('if (hasReturnedToTeamPage)', false)
            ->assertSee('window.location.replace(teamPageUrl)', false)
            ->assertSee('const teamPageUrl = "'.$escapedReturnPath.'"', false)
            ->assertSee('window.print()', false)
            ->assertSee('{ once: true }', false)
            ->assertSee('Merk:');

        $css = File::get(public_path('css/custom/app.css'));
        $this->assertStringContainsString('.football-team-print-list { column-count: 2;', $css);
        $this->assertStringContainsString('@page { size: A4 portrait; margin: 12.7mm; }', $css);
        $this->assertStringContainsString('.football-team-print-page { color: #111; font-family: Arial, sans-serif; font-size: 9.5pt;', $css);
        $this->assertStringContainsString('.football-team-print-list { column-count: 2; column-gap: 7mm; }', $css);
        $this->assertStringContainsString('.football-team-print-stats { break-inside: avoid;', $css);
        $this->assertStringContainsString('.football-team-print-match { break-inside: avoid; border-bottom: .5px solid #999; display: grid; gap: 1.2mm;', $css);
        $this->assertStringContainsString('padding: 1.05mm 0;', $css);
        $this->assertStringContainsString('.football-team-print-note { border-top: .5px solid #777; font-size: 8.25pt;', $css);
    }

    /** @dataProvider leagues */
    public function test_team_page_handles_missing_logo_and_invalid_team_id(string $path, int $seasonId): void
    {
        $this->fakeCompetition($seasonId);

        $this->get($path.'/lag/4')->assertOk()
            ->assertSee('Motstander C')
            ->assertDontSee('football-team-emblem', false);
        $this->get($path.'/lag/999')->assertNotFound();
    }

    /** @dataProvider fullSeasonPrints */
    public function test_full_team_season_print_keeps_all_matches_in_the_two_column_layout(int $seasonId, int $matchCount): void
    {
        $this->fakeFullTeamSeason($seasonId, $matchCount);

        $response = $this->get(($seasonId === 8766 ? '/eliteserien' : '/premier-league').'/lag/1/utskrift')->assertOk();

        $this->assertSame($matchCount, substr_count($response->getContent(), 'football-team-print-match'));
        $this->assertStringContainsString('.football-team-print-list { column-count: 2; column-gap: 7mm; }', File::get(public_path('css/custom/app.css')));
        $response->assertSee('Dato og tidspunkt for kommende kamper kan endres.');
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

    /** @dataProvider leagues */
    public function test_team_page_calculates_shared_season_statistics_from_finished_matches(string $path, int $seasonId): void
    {
        $this->fakeStatisticsCompetition($seasonId);

        $response = $this->get($path.'/lag/1')->assertOk()
            ->assertSee('Sesongen så langt')
            ->assertSee('Form siste 5')
            ->assertSee('Hjemme:')
            ->assertSee('Borte:')
            ->assertSee('Største seier')
            ->assertSee('Største tap')
            ->assertSee('Mest målrike kamp')
            ->assertSee('Clean sheets');

        $response->assertViewHas('seasonStats', function (array $stats) {
            $this->assertTrue($stats['hasFinishedMatches']);
            $this->assertSame(['T', 'U', 'V', 'T', 'V'], array_column($stats['form'], 'result'));
            $this->assertSame(['wins' => 1, 'draws' => 1, 'losses' => 1], $stats['home']);
            $this->assertSame(['wins' => 2, 'draws' => 0, 'losses' => 1], $stats['away']);
            $this->assertSame('Motstander A', $stats['records']['largestWin']['opponent']);
            $this->assertSame([3, 0], [$stats['records']['largestWin']['teamScore'], $stats['records']['largestWin']['opponentScore']]);
            $this->assertSame('01.03.2026', $stats['records']['largestWin']['startsAt']->format('d.m.Y'));
            $this->assertSame('Motstander B', $stats['records']['largestLoss']['opponent']);
            $this->assertSame([1, 4], [$stats['records']['largestLoss']['teamScore'], $stats['records']['largestLoss']['opponentScore']]);
            $this->assertSame('08.03.2026', $stats['records']['largestLoss']['startsAt']->format('d.m.Y'));
            $this->assertSame('Motstander F', $stats['records']['highestScoringMatch']['opponent']);
            $this->assertSame([4, 2], [$stats['records']['highestScoringMatch']['teamScore'], $stats['records']['highestScoringMatch']['opponentScore']]);
            $this->assertSame('05.04.2026', $stats['records']['highestScoringMatch']['startsAt']->format('d.m.Y'));
            $this->assertSame(1, $stats['records']['cleanSheets']);
            $this->assertSame(4, $stats['keyFigures'][0]['value']);
            $this->assertSame(12, $stats['keyFigures'][5]['value']);

            return true;
        });
    }

    /** @dataProvider leagues */
    public function test_team_page_shows_a_message_when_there_are_no_finished_matches(string $path, int $seasonId): void
    {
        $this->fakeNoFinishedCompetition($seasonId);

        $response = $this->get($path.'/lag/1')->assertOk()
            ->assertSee('Sesongen så langt')
            ->assertSee('Det finnes ingen ferdigspilte seriekamper å beregne sesongstatistikk fra ennå.')
            ->assertDontSee('Form siste', false);

        $response->assertViewHas('seasonStats', fn (array $stats) => $stats['hasFinishedMatches'] === false);
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
            2 => ['name' => 'Motstander A', 'logoUrl' => 'https://example.test/motstander-a.png'],
            3 => ['name' => 'Motstander B', 'logoUrl' => 'https://example.test/motstander-b.png'],
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

    private function fakeStatisticsCompetition(int $seasonId): void
    {
        Cache::flush();
        $participants = [1 => ['name' => 'Test FC']];
        foreach (range(2, 9) as $teamId) {
            $participants[$teamId] = ['name' => 'Motstander '.chr(64 + $teamId - 1)];
        }
        $events = [
            $this->finishedEvent(1, '2026-03-01T16:00:00Z', [1, 2], 3, 0),
            $this->finishedEvent(2, '2026-03-08T16:00:00Z', [3, 1], 4, 1),
            $this->finishedEvent(3, '2026-03-15T16:00:00Z', [1, 4], 2, 2),
            $this->finishedEvent(4, '2026-03-22T16:00:00Z', [5, 1], 1, 2),
            $this->finishedEvent(5, '2026-03-29T16:00:00Z', [1, 6], 0, 1),
            $this->finishedEvent(6, '2026-04-05T16:00:00Z', [7, 1], 2, 4),
            ['id' => 7, 'startDate' => '2026-04-12T16:00:00Z', 'participantIds' => [1, 8], 'status' => ['type' => 'finished'], 'results' => [1 => ['runningScore' => 2]]],
            ['id' => 8, 'startDate' => '2026-04-19T16:00:00Z', 'participantIds' => [9, 1], 'status' => ['type' => 'notstarted']],
        ];
        $standing = ['teamId' => '1', 'rank' => 4, 'played' => 6, 'wins' => 3, 'draws' => 1, 'losses' => 2, 'goalsFor' => 12, 'goalsAgainst' => 10, 'points' => 10];

        Http::fake([
            "*/tournaments/seasons/{$seasonId}/schedule" => Http::response(['participants' => $participants, 'events' => $events], 200),
            "*/tournaments/seasons/{$seasonId}/standings" => Http::response(['participants' => $participants, 'standings' => [['teamStandings' => [$standing]]]], 200),
        ]);
    }

    private function fakeNoFinishedCompetition(int $seasonId): void
    {
        Cache::flush();
        $participants = [1 => ['name' => 'Test FC'], 2 => ['name' => 'Motstander A']];
        $events = [
            ['id' => 1, 'startDate' => '2026-08-01T16:30:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'notstarted']],
            ['id' => 2, 'startDate' => '2026-08-08T16:30:00Z', 'participantIds' => [1, 2], 'status' => ['type' => 'finished'], 'results' => [1 => ['runningScore' => 1]]],
        ];

        Http::fake([
            "*/tournaments/seasons/{$seasonId}/schedule" => Http::response(['participants' => $participants, 'events' => $events], 200),
            "*/tournaments/seasons/{$seasonId}/standings" => Http::response(['participants' => $participants, 'standings' => [['teamStandings' => [['teamId' => 1, 'rank' => 1]]]]], 200),
        ]);
    }

    private function finishedEvent(int $id, string $startDate, array $teamIds, int $homeScore, int $awayScore): array
    {
        return [
            'id' => $id,
            'startDate' => $startDate,
            'participantIds' => $teamIds,
            'status' => ['type' => 'finished'],
            'results' => [
                $teamIds[0] => ['runningScore' => $homeScore],
                $teamIds[1] => ['runningScore' => $awayScore],
            ],
        ];
    }
}
