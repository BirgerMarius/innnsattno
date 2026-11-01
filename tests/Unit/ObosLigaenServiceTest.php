<?php

namespace Tests\Unit;

use App\Services\ObosLigaenService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ObosLigaenServiceTest extends TestCase
{
    /** @test */
    public function it_keeps_the_scheduled_obos_group_with_zero_played_and_excludes_the_unlinked_finnmark_group(): void
    {
        $data = (new ObosLigaenService())->normalizeCompetitionData(
            [
                'events' => [['id' => 1, 'participantIds' => [1, 2], 'status' => ['type' => 'notstarted'], 'startDate' => '2099-04-05T12:00:00Z']],
                // SportsNext may include extra participants in this list. Only
                // event participant IDs establish the current competition.
                'participants' => [1 => ['name' => 'Start'], 2 => ['name' => 'Moss'], 91 => ['name' => 'Alta 2'], 92 => ['name' => 'Hammerfest']],
                'tournament' => ['id' => 40], 'tournamentSeason' => ['id' => 8767],
            ],
            [
                'standings' => [
                    ['stageName' => '1. divisjon 2026', 'specialNotes' => [['comment' => 'Opprykk']], 'teamStandings' => [$this->row(1), $this->row(2)]],
                    ['stageName' => '1. divisjon - Finnmark MiiMetVi 2026', 'teamStandings' => [$this->row(91), $this->row(92)]],
                ],
                'participants' => [1 => ['name' => 'Start'], 2 => ['name' => 'Moss'], 91 => ['name' => 'Alta 2'], 92 => ['name' => 'Hammerfest']],
                'tournament' => ['id' => 40], 'tournamentSeason' => ['id' => 8767],
            ],
        );

        $this->assertCount(1, $data['standingsGroups']);
        $this->assertSame('1. divisjon 2026', $data['standingsGroups'][0]['stageName']);
        $this->assertCount(2, $data['standings']);
        $this->assertSame([1, 2], array_column($data['standings'], 'teamId'));
        $this->assertSame([1, 2], array_keys($data['teams']));
        $this->assertSame([1, 2], array_values(array_unique(array_merge(array_column($data['matches'], 'homeTeamId'), array_column($data['matches'], 'awayTeamId')))));
        $this->assertSame(0, $data['standings'][0]['played']);
    }

    /** @test */
    public function it_filters_the_unlinked_group_from_an_existing_last_good_cache(): void
    {
        Cache::flush();
        Cache::forever('schibsted_sports.obos_ligaen.8767.last_good', [
            'matches' => [['homeTeamId' => 1, 'awayTeamId' => 2]],
            'standingsGroups' => [
                ['stageName' => '1. divisjon 2026', 'rows' => [['teamId' => 1, 'teamName' => 'Start', 'played' => 0]]],
                ['stageName' => '1. divisjon - Finnmark MiiMetVi 2026', 'rows' => [['teamId' => 91, 'teamName' => 'Alta 2', 'played' => 0]]],
            ],
            'standings' => [['teamId' => 1], ['teamId' => 91]],
            'teams' => [1 => ['teamId' => 1, 'teamName' => 'Start'], 91 => ['teamId' => 91, 'teamName' => 'Alta 2']],
            'upcomingFixtures' => [], 'recentResults' => [], 'lastUpdated' => null,
            'sourceFetchedAt' => now()->toIso8601String(), 'apiConfigured' => true, 'apiError' => false, 'usingStaleData' => false,
        ]);

        $data = app(ObosLigaenService::class)->getCompetitionData();

        $this->assertSame([1], array_column($data['standings'], 'teamId'));
        $this->assertSame([1], array_keys($data['teams']));
        $this->assertCount(1, $data['standingsGroups']);
    }

    private function row(int $teamId): array
    {
        return ['teamId' => $teamId, 'rank' => $teamId, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'points' => 0];
    }
}
