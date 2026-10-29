<?php

namespace Tests\Unit;

use App\Services\WeeklyFootballTeamLogoService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class WeeklyFootballTeamLogoServiceTest extends TestCase
{
    public function test_confirmed_tv_name_aliases_resolve_from_precached_sportsnext_team_ids(): void
    {
        Cache::put('schibsted_sports.eliteserien.8766.last_good', ['teams' => [
            40394 => ['teamId' => 40394, 'teamName' => 'KFUM', 'emblemUrl' => 'https://example.test/kfum.png'],
            22987 => ['teamId' => 22987, 'teamName' => 'Vålerenga', 'emblemUrl' => 'https://example.test/valerenga.png'],
            22988 => ['teamId' => 22988, 'teamName' => 'Viking', 'emblemUrl' => 'https://example.test/viking.png'],
            23345 => ['teamId' => 23345, 'teamName' => 'Fredrikstad', 'emblemUrl' => 'https://example.test/fredrikstad.png'],
        ]], now()->addDay());
        Cache::put('schibsted_sports.premier_league.9186.last_good', ['teams' => [
            20092 => ['teamId' => 20092, 'teamName' => 'Aston Villa', 'emblemUrl' => 'https://example.test/villa.png'],
        ]], now()->addDay());

        $logos = app(WeeklyFootballTeamLogoService::class);
        $this->assertSame('https://example.test/kfum.png', $logos->logosFor('Eliteserien', 'KFUM Oslo – Vålerenga')['homeLogoUrl']);
        $this->assertSame('https://example.test/valerenga.png', $logos->logosFor('Eliteserien', 'KFUM Oslo – Vålerenga')['awayLogoUrl']);
        $this->assertSame('https://example.test/viking.png', $logos->logosFor('Eliteserien', 'Viking FK – Fredrikstad FK')['homeLogoUrl']);
        $this->assertSame('https://example.test/fredrikstad.png', $logos->logosFor('Eliteserien', 'Viking FK – Fredrikstad FK')['awayLogoUrl']);
        $this->assertSame('https://example.test/villa.png', $logos->logosFor('Premier League', 'Aston Villa FC – Test FC')['homeLogoUrl']);
        $this->assertNull($logos->logosFor('Premier League', 'Aston Villa FC – Test FC')['awayLogoUrl']);
    }

    public function test_national_team_visuals_keep_the_cached_flag_type(): void
    {
        Cache::put('schibsted_sports.nations_league.8889.last_good', ['teams' => [
            11667 => ['teamId' => 11667, 'teamName' => 'Norge', 'emblemUrl' => 'https://example.test/no-flag', 'emblemType' => 'flag'],
            11662 => ['teamId' => 11662, 'teamName' => 'Danmark', 'emblemUrl' => 'https://example.test/dk-flag', 'emblemType' => 'flag'],
        ]], now()->addDay());

        $visuals = app(WeeklyFootballTeamLogoService::class)->logosFor('Nations League', 'Norge – Danmark');

        $this->assertSame('https://example.test/no-flag', $visuals['homeLogoUrl']);
        $this->assertSame('flag', $visuals['homeVisualType']);
        $this->assertSame('flag', $visuals['awayVisualType']);
    }

    public function test_verified_champions_league_tv_aliases_use_only_the_expected_cached_team_ids(): void
    {
        Cache::put('schibsted_sports.champions_league.9168.last_good', ['teams' => [
            ['teamId' => 1156, 'teamName' => 'Atletico Madrid', 'emblemUrl' => 'https://example.test/atletico.png'],
            ['teamId' => 4070, 'teamName' => 'Bayern München', 'emblemUrl' => 'https://example.test/bayern.png'],
            ['teamId' => 3419, 'teamName' => 'Paris Saint-Germain', 'emblemUrl' => 'https://example.test/psg.png'],
            ['teamId' => 32503, 'teamName' => 'VfB Stuttgart', 'emblemUrl' => 'https://example.test/stuttgart.png'],
            ['teamId' => 4087, 'teamName' => 'RB Leipzig', 'emblemUrl' => 'https://example.test/leipzig.png'],
            ['teamId' => 5710, 'teamName' => 'Fenerbahce', 'emblemUrl' => 'https://example.test/fenerbahce.png'],
            ['teamId' => 6344, 'teamName' => 'AEK Athen', 'emblemUrl' => 'https://example.test/aek.png'],
            ['teamId' => 360471, 'teamName' => 'Sabah FC Masazir', 'emblemUrl' => 'https://example.test/sabah.png'],
            ['teamId' => 4072, 'teamName' => 'Borussia Dortmund', 'emblemUrl' => 'https://example.test/dortmund.png'],
            ['teamId' => 58170, 'teamName' => 'Shakhtar Donetsk', 'emblemUrl' => 'https://example.test/shakhtar.png'],
        ]], now()->addDay());

        $logos = app(WeeklyFootballTeamLogoService::class);

        foreach ([
            'Atlético' => 'atletico.png', 'Bayern' => 'bayern.png', 'PSG' => 'psg.png', 'Stuttgart' => 'stuttgart.png',
            'Leipzig' => 'leipzig.png', 'Fenerbahçe' => 'fenerbahce.png', 'Aek' => 'aek.png', 'Sabah' => 'sabah.png',
            'Dortmund' => 'dortmund.png', 'Shakhtar' => 'shakhtar.png',
        ] as $tvName => $image) {
            $this->assertSame('https://example.test/'.$image, $logos->logosFor('Champions League', $tvName.' – Motstander')['homeLogoUrl']);
        }
    }

    public function test_champions_league_alias_requires_the_expected_cached_team_and_a_valid_image(): void
    {
        Cache::put('schibsted_sports.champions_league.9168.last_good', ['teams' => [
            ['teamId' => 1156, 'teamName' => 'Wrong Atletico', 'emblemUrl' => null],
            ['teamId' => 9999, 'teamName' => 'Atletico Madrid', 'emblemUrl' => 'https://example.test/wrong-team.png'],
        ]], now()->addDay());

        $visuals = app(WeeklyFootballTeamLogoService::class)->logosFor('Champions League', 'Atlético – Motstander');

        $this->assertNull($visuals['homeLogoUrl']);
        $this->assertNull($visuals['homeVisualType']);
    }

    public function test_ambiguous_exact_names_still_do_not_receive_a_logo(): void
    {
        Cache::put('schibsted_sports.champions_league.9168.last_good', ['teams' => [
            ['teamId' => 1, 'teamName' => 'Dobbelt navn', 'emblemUrl' => 'https://example.test/one.png'],
            ['teamId' => 2, 'teamName' => 'Dobbelt navn', 'emblemUrl' => 'https://example.test/two.png'],
        ]], now()->addDay());

        $visuals = app(WeeklyFootballTeamLogoService::class)->logosFor('Champions League', 'Dobbelt navn – Motstander');

        $this->assertNull($visuals['homeLogoUrl']);
        $this->assertNull($visuals['homeVisualType']);
    }

    public function test_champions_league_aliases_do_not_apply_to_other_competitions(): void
    {
        Cache::put('schibsted_sports.premier_league.9186.last_good', ['teams' => [
            ['teamId' => 1156, 'teamName' => 'Atletico Madrid', 'emblemUrl' => 'https://example.test/atletico.png'],
        ]], now()->addDay());

        $visuals = app(WeeklyFootballTeamLogoService::class)->logosFor('Premier League', 'Atlético – Motstander');

        $this->assertNull($visuals['homeLogoUrl']);
        $this->assertNull($visuals['homeVisualType']);
    }
}
