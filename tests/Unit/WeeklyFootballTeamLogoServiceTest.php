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
}
