<?php

namespace Tests\Feature;

use App\Services\SportsNextSeasonControlService;
use App\Services\SportsNextSeasonSelectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SportsNextSeasonControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.schibsted_sports.competitions', ['test' => ['name' => 'Testliga', 'tournament_id' => 9, 'season_config' => 'test_season_id', 'automatic_supported' => true]]);
        config()->set('services.schibsted_sports.test_season_id', 1);
        config()->set('services.schibsted_sports.season_switch_grace_days', 14);
        config()->set('services.schibsted_sports.season_switch_observation_hours', 12);
        Carbon::setTestNow('2027-06-20 12:00:00 Europe/Oslo');
    }
    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    public function test_current_season_change_does_not_switch_before_old_end_date(): void
    {
        Carbon::setTestNow('2026-05-01 12:00:00 Europe/Oslo'); $this->fakeCatalog('2026-05-30');
        app(SportsNextSeasonControlService::class)->check('test');
        Carbon::setTestNow('2026-05-02 13:00:00 Europe/Oslo'); $this->fakeCatalog('2026-05-30');
        app(SportsNextSeasonControlService::class)->check('test');
        $this->assertSame(1, app(SportsNextSeasonSelectionService::class)->effectiveSeasonId('test', 1));
        $this->assertStringContainsString('karenstid', DB::table('sportsnext_season_selections')->where('competition_key', 'test')->value('status_reason'));
    }

    public function test_unresolved_old_match_blocks_switch_after_end_date(): void
    {
        $this->observeCandidate(); Carbon::setTestNow('2027-06-21 13:00:00 Europe/Oslo');
        $this->fakeCatalog('2027-05-30', [['status' => ['type' => 'postponed']]]);
        app(SportsNextSeasonControlService::class)->check('test');
        $this->assertSame(1, app(SportsNextSeasonSelectionService::class)->effectiveSeasonId('test', 1));
        $this->assertNotSame('Byttet', DB::table('sportsnext_season_selections')->where('competition_key', 'test')->value('status'));
    }

    public function test_two_valid_observations_switch_when_automatic_is_enabled_and_preserve_old_cache_key(): void
    {
        app(SportsNextSeasonSelectionService::class)->setAutomatic('test', true); $this->observeCandidate();
        Carbon::setTestNow('2027-06-21 13:00:00 Europe/Oslo'); $this->fakeCatalog('2027-05-30');
        app(SportsNextSeasonControlService::class)->check('test');
        $this->assertSame(2, app(SportsNextSeasonSelectionService::class)->effectiveSeasonId('test', 1));
        $this->assertDatabaseHas('sportsnext_season_switches', ['competition_key' => 'test', 'from_season_id' => 1, 'to_season_id' => 2, 'method' => 'automatic']);
    }

    public function test_rollback_disables_automatic_and_blocks_same_candidate(): void
    {
        $selection = app(SportsNextSeasonSelectionService::class); $selection->setAutomatic('test', true); $selection->select('test', 2, 'manual', 'test'); $selection->select('test', 1, 'rollback', 'test', true);
        $row = $selection->row('test', 1); $this->assertFalse((bool) $row->automatic_enabled); $this->assertSame(2, (int) $row->blocked_candidate_season_id);
    }

    private function observeCandidate(): void { $this->fakeCatalog('2027-05-30'); app(SportsNextSeasonControlService::class)->check('test'); }
    private function fakeCatalog(string $end, array $oldEvents = null): void
    {
        $oldEvents ??= [['status' => ['type' => 'finished']]];
        Http::fake(function ($request) use ($end, $oldEvents) {
            if (str_contains($request->url(), '/tournaments/9/seasons')) return Http::response(['tournament' => ['currentSeasonId' => 2], 'tournamentSeasons' => [['id' => 1, 'name' => 'Gammel', 'startDate' => '2026-08-01', 'endDate' => $end], ['id' => 2, 'name' => 'Ny', 'startDate' => '2027-08-01', 'endDate' => '2028-05-30']]], 200);
            if (str_contains($request->url(), '/tournaments/seasons/1/schedule')) return Http::response(['events' => $oldEvents], 200);
            if (str_contains($request->url(), '/tournaments/seasons/2/schedule')) return Http::response(['events' => [['status' => ['type' => 'scheduled']]], 'participants' => []], 200);
            if (str_contains($request->url(), '/tournaments/seasons/2/standings')) return Http::response(['standings' => []], 200);
            return Http::response([], 404);
        });
    }
}
