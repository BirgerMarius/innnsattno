<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LeagueStandingRulesTest extends TestCase
{
    /** @test */
    public function league_pages_and_prints_show_only_source_provided_placement_rules(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-01 12:00:00', 'Europe/Oslo'));

        try {
            foreach ($this->leagues() as [$path, $cacheKey, $rule]) {
                Cache::flush();
                Cache::forever($cacheKey, $this->cachedCompetition($rule));

                $this->get('/ringerike/'.$path)->assertOk()
                    ->assertSee('Tegnforklaring:')
                    ->assertSee('Plassen gir')
                    ->assertSee($rule['name'])
                    ->assertSee('ikke at laget har sikret utfallet');
                $this->get('/ringerike/'.$path.'/utskrift')->assertOk()
                    ->assertSee('Tegnforklaring:')
                    ->assertSee('Merke')
                    ->assertSee('1 '.$rule['name'])
                    ->assertSee('Gjelder tabellplass, ikke sikret utfall.');
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    /** @test */
    public function a_legacy_cache_without_rules_keeps_the_table_without_placement_markup(): void
    {
        Cache::flush();
        Cache::forever('schibsted_sports.premier_league.9186.last_good', $this->cachedCompetition(null));

        $this->get('/ringerike/premier-league')->assertOk()
            ->assertDontSee('Tegnforklaring:')
            ->assertDontSee('Plassen gir');
    }

    private function leagues(): array
    {
        return [
            ['eliteserien', 'schibsted_sports.eliteserien.8766.last_good', ['code' => 'champions_league_qualification', 'name' => 'Champions League-kvalifisering', 'effect' => 1]],
            ['obos-ligaen', 'schibsted_sports.obos_ligaen.8767.last_good', ['code' => 'promotion', 'name' => 'Opprykk', 'effect' => 1]],
            ['premier-league', 'schibsted_sports.premier_league.9186.last_good', ['code' => 'champions_league', 'name' => 'Champions League', 'effect' => 1]],
            ['la-liga', 'schibsted_sports.la_liga.9227.last_good', ['code' => 'conference_league_qualification', 'name' => 'Conference League-kvalifisering', 'effect' => 1]],
            ['bundesliga', 'schibsted_sports.bundesliga.9237.last_good', ['code' => 'relegation_playoffs', 'name' => 'Nedrykkskvalifisering', 'effect' => -1]],
        ];
    }

    private function cachedCompetition(?array $rule): array
    {
        $standing = ['teamId' => 1, 'teamName' => 'Testlag', 'rank' => 1, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'goalDifference' => 0, 'points' => 0, 'emblemUrl' => null, 'rule' => $rule];

        return [
            'standings' => [$standing], 'standingsGroups' => [['rows' => [$standing], 'stageName' => 'Testtabell', 'groupName' => null, 'specialNotes' => []]],
            'teams' => [1 => ['teamId' => 1, 'teamName' => 'Testlag', 'emblemUrl' => null]],
            'matches' => [['id' => 1, 'startsAt' => now()->addDay(), 'homeTeamId' => 1, 'awayTeamId' => 2, 'homeTeam' => 'Testlag', 'awayTeam' => 'Motstander', 'homeEmblemUrl' => null, 'awayEmblemUrl' => null, 'isFinished' => false]],
            'upcomingFixtures' => [], 'recentResults' => [], 'lastUpdated' => now(), 'sourceFetchedAt' => now()->toIso8601String(),
            'apiConfigured' => true, 'apiError' => false, 'usingStaleData' => false,
        ];
    }
}
