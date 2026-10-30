<?php

namespace Tests\Feature;

use App\Mail\ExternalDataIncidentMail;
use App\Services\ExternalDataMonitor;
use App\Services\TvGuideService;
use App\Services\PremierLeagueService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExternalDataMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'monitor@example.test');
        Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_short_failure_is_logged_and_real_recovery_never_sends_email(): void
    {
        $tv = app(TvGuideService::class);
        $sequence = Http::sequence();
        for ($i = 0; $i < 4; $i++) $sequence->push([], 503);
        $sequence->push($this->schedule());
        Http::fake([
            'tvguide.vg.no/*' => $sequence,
            'contentsearch.rikstv.no/*' => Http::response([], 503),
        ]);
        $tv->refreshSchedule(now(), ['nrk1'], 'ringerike');
        Carbon::setTestNow(now()->addMinutes(5));
        $tv->refreshSchedule(now(), ['nrk1'], 'ringerike');
        app(ExternalDataMonitor::class)->evaluate();
        Mail::assertNothingSent();
        $this->assertGreaterThanOrEqual(2, DB::table('external_data_source_events')->count());

        Carbon::setTestNow(now()->addMinutes(5));
        $this->assertTrue($tv->refreshSchedule(now(), ['nrk1'], 'ringerike'));
        app(ExternalDataMonitor::class)->evaluate();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('external_data_checks', ['service' => 'tv-guide-vg', 'failure_count' => 0]);
    }

    public function test_primary_tv_failure_with_a_usable_local_cache_does_not_send_email(): void
    {
        config()->set('external_data.max_age_minutes.tv', 60);
        $tv = app(TvGuideService::class);
        $sequence = Http::fakeSequence()->push($this->schedule());
        for ($i = 0; $i < 10; $i++) $sequence->push([], 503);
        $tv->refreshSchedule(now(), ['nrk1'], 'ringerike');
        foreach ([15, 30, 45, 60, 75] as $minutes) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($minutes));
            $this->assertFalse($tv->refreshSchedule(now(), ['nrk1'], 'ringerike'));
            // Page visits cannot turn cached data into source success.
            $this->assertSame($this->schedule(), $tv->getSchedule(now(), ['nrk1'], 'print'));
            app(ExternalDataMonitor::class)->evaluate();
            Mail::assertNothingSent();
        }
        $row = DB::table('external_data_checks')->where('service', 'tv-guide-vg')->first();
        $this->assertSame(5, (int) $row->failure_count);
        $this->assertSame('2026-10-19 08:00:00', $row->last_success_at);
    }

    public function test_missing_data_alert_groups_coverage_and_partial_success_does_not_recover_incident(): void
    {
        $monitor = app(ExternalDataMonitor::class);
        for ($i = 0; $i < 3; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes(5 * $i));
            $monitor->checked('tv-guide-vg-channel', '2026-10-19|tvnorge', 'tv', 'TVNorge', true, false, false, 'Ingen programdata.');
            $monitor->checked('tv-guide-vg-channel', '2026-10-19|tv2-livsstil', 'tv', 'TV 2 Livsstil', true, false, false, 'Ingen programdata.');
            $monitor->evaluate();
        }
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'tv-guide-vg-channel' && $mail->state === 'critical' && count($mail->coverages) === 2);

        Carbon::setTestNow(now()->addMinutes(5));
        $monitor->checked('tv-guide-vg-channel', '2026-10-19|tvnorge', 'tv', 'TVNorge', true, true, true);
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, 1);
        $monitor->checked('tv-guide-vg-channel', '2026-10-19|tv2-livsstil', 'tv', 'TV 2 Livsstil', true, true, true);
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->state === 'recovered');
        $this->assertDatabaseCount('external_data_incidents', 0);
    }

    public function test_future_unpublished_schedule_is_a_successful_check_not_a_missing_data_alert(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([])]);
        $this->assertTrue(app(TvGuideService::class)->refreshSchedule(now()->addDay(), ['nrk1'], 'ringerike'));
        app(ExternalDataMonitor::class)->evaluate();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('external_data_checks', ['failure_count' => 0, 'usable' => false]);
    }

    public function test_unpublished_future_response_preserves_existing_data_and_does_not_renew_its_source_timestamp(): void
    {
        $date = now()->addDay();
        $tv = app(TvGuideService::class);
        Http::fakeSequence()->push($this->schedule())->push([]);

        $this->assertTrue($tv->refreshSchedule($date, ['nrk1'], 'ringerike'));
        $timestamp = $tv->lastUpdated($date, ['nrk1'])->toIso8601String();
        Carbon::setTestNow(now()->addMinutes(61));
        $this->assertTrue($tv->refreshSchedule($date, ['nrk1'], 'ringerike'));
        $this->assertSame($this->schedule(), $tv->getSchedule($date, ['nrk1'], 'print'));
        $this->assertSame($timestamp, $tv->lastUpdated($date, ['nrk1'])->toIso8601String());
        $this->assertTrue($tv->refreshDeferred($date, ['nrk1']));
        $this->assertDatabaseHas('external_data_checks', ['last_success_at' => '2026-10-19 08:00:00']);
    }

    public function test_partial_channel_coverage_is_monitored_as_a_failure_without_replacing_same_date_data(): void
    {
        $date = now('Europe/Oslo');
        $channels = ['nrk1', 'tv3'];
        Http::fakeSequence()
            ->push([
                ['channel' => ['name' => 'NRK1', 'slug' => 'nrk1'], 'listings' => [['startsAt' => '2026-10-19T12:00:00Z', 'title' => ['title' => 'NRK']]]],
                ['channel' => ['name' => 'TV3', 'slug' => 'tv3'], 'listings' => [['startsAt' => '2026-10-19T12:00:00Z', 'title' => ['title' => 'TV3']]]],
            ])
            ->push([
                ['channel' => ['name' => 'NRK1', 'slug' => 'nrk1'], 'listings' => [['startsAt' => '2026-10-19T12:00:00Z', 'title' => ['title' => 'Ny NRK']]]],
                ['channel' => ['name' => 'TV3', 'slug' => 'tv3'], 'listings' => []],
            ]);
        $tv = app(TvGuideService::class);
        $this->assertTrue($tv->refreshSchedule($date, $channels, 'ringerike'));
        $updated = $tv->lastUpdated($date, $channels)->toIso8601String();
        Carbon::setTestNow(now()->addMinutes(61));
        $this->assertTrue($tv->refreshSchedule($date, $channels, 'ringerike'));

        $schedule = $tv->getSchedule($date, $channels, 'print');
        $this->assertSame('TV3', $schedule[1]['listings'][0]['title']['title']);
        $this->assertSame('fallback', $schedule[1]['programDataStatus']);
        $this->assertSame($updated, $tv->lastUpdated($date, $channels)->toIso8601String());
        $this->assertDatabaseHas('external_data_checks', [
            'service' => 'tv-guide-vg', 'coverage_key' => 'ringerike-2026-10-19', 'failure_count' => 1, 'usable' => true,
        ]);
    }

    public function test_vg_channel_incident_stays_open_while_strim_is_used_and_recovers_only_with_vg_data(): void
    {
        Http::fake([
            'tvguide.vg.no/*' => Http::sequence()
                ->push([['channel' => ['name' => 'TV3', 'slug' => 'tv3'], 'listings' => []]])
                ->push([['channel' => ['name' => 'TV3', 'slug' => 'tv3'], 'listings' => [['startsAt' => '2026-10-19T12:00:00Z', 'title' => ['title' => 'VG-program']]]]]),
            'contentsearch.rikstv.no/1/channel/69/epg/2026-10-19' => Http::response([[
                'broadcastId' => 'health-1', 'seriesName' => 'Strim-program', 'broadcastedTime' => '2026-10-19T12:00:00Z',
                'originChannel' => ['channelId' => 69],
            ]]),
        ]);
        $tv = app(TvGuideService::class);
        $tv->refreshSchedule(now(), ['tv3'], 'ringerike');
        $this->assertDatabaseHas('external_data_checks', ['service' => 'tv-guide-vg-channel', 'coverage_key' => '2026-10-19|tv3', 'failure_count' => 1]);
        $first = DB::table('external_data_checks')->where('service', 'tv-guide-vg-channel')->value('first_failure_at');

        Carbon::setTestNow(now()->addMinutes(61));
        $tv->refreshSchedule(now(), ['tv3'], 'ringerike');
        $this->assertDatabaseHas('external_data_checks', ['service' => 'tv-guide-vg-channel', 'coverage_key' => '2026-10-19|tv3', 'failure_count' => 0]);
        $this->assertNotNull($first);
    }

    public function test_tv_channel_alerts_only_for_the_missing_channel_and_recovers_when_a_reserve_returns(): void
    {
        $monitor = app(ExternalDataMonitor::class);
        foreach ([0, 5, 10] as $minutes) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($minutes));
            // VG is down for both channels, but TV3 remains covered by Strim.
            $monitor->checked('tv-guide-vg-channel', '2026-10-19|tv3', 'tv', 'TV3', true, false, true, 'VG feilet; Strim dekker.');
            $monitor->checked('tv-guide-vg-channel', '2026-10-19|nrk1', 'tv', 'NRK1', true, false, false, 'Ingen programdata.');
            $monitor->checked('tv-guide-vg', 'ringerike-2026-10-19', 'tv', 'Ringerike', true, false, true, 'Delvis VG-feil.');
        }

        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, function ($mail) {
            return $mail->service === 'tv-guide-vg-channel'
                && $mail->state === 'critical'
                && count($mail->coverages) === 1
                && $mail->coverages[0]->coverage_key === '2026-10-19|nrk1';
        });
        Mail::assertSent(ExternalDataIncidentMail::class, 1);

        Carbon::setTestNow(now()->addMinutes(5));
        $monitor->checked('tv-guide-vg-channel', '2026-10-19|nrk1', 'tv', 'NRK1', true, false, true, 'VG feilet; lokal cache dekker.');
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'tv-guide-vg-channel' && $mail->state === 'recovered');
    }

    public function test_forced_tv_command_refreshes_today_with_strim_and_exposes_the_vg_incident_in_admin(): void
    {
        Http::fake([
            'tvguide.vg.no/*' => fn ($request) => Http::response($this->scheduleForRequestWithMissingViasatChannels($request)),
            'contentsearch.rikstv.no/1/channel/69/epg/2026-10-19' => Http::response([[
                'broadcastId' => 'force-tv3', 'seriesName' => 'Strim TV3', 'broadcastedTime' => '2026-10-19T12:00:00Z',
                'broadcastedTimeEnd' => '2026-10-19T13:00:00Z', 'originChannel' => ['channelId' => 69],
            ]]),
            'contentsearch.rikstv.no/1/channel/71/epg/2026-10-19' => Http::response([[
                'broadcastId' => 'force-tv3-plus', 'name' => 'Strim TV3+', 'broadcastedTime' => '2026-10-19T13:00:00Z',
                'broadcastedTimeEnd' => '2026-10-19T14:00:00Z', 'originChannel' => ['channelId' => 71],
            ]]),
        ]);

        $this->artisan('external-data:refresh', ['type' => 'tv', '--force' => true])
            ->expectsOutput('TV ringerike 2026-10-19: kontrollert')
            ->expectsOutput('TV ilseng 2026-10-19: kontrollert')
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => $request->url() === 'https://contentsearch.rikstv.no/1/channel/69/epg/2026-10-19');
        Http::assertSent(fn ($request) => $request->url() === 'https://contentsearch.rikstv.no/1/channel/71/epg/2026-10-19');
        $this->assertDatabaseHas('external_data_checks', ['service' => 'tv-guide-vg-channel', 'coverage_key' => '2026-10-19|tv3', 'failure_count' => 1, 'usable' => true]);
        $this->assertDatabaseHas('external_data_checks', ['service' => 'tv-guide-vg-channel', 'coverage_key' => '2026-10-19|tv3-plus', 'failure_count' => 1, 'usable' => true]);

        foreach (['ringerike' => TvGuideService::ringerikeChannels(), 'ilseng' => TvGuideService::ilsengChannels()] as $selection => $channels) {
            $schedule = app(TvGuideService::class)->getSchedule(now(), $channels, 'test');
            $tv3 = collect($schedule)->firstWhere('channel.slug', 'tv3');
            $tv3Plus = collect($schedule)->firstWhere('channel.slug', 'tv3-plus');
            $this->assertSame('external-fallback', $tv3['programDataStatus']);
            $this->assertSame('Strim TV3', $tv3['listings'][0]['title']['title']);
            $this->assertSame('external-fallback', $tv3Plus['programDataStatus']);
            $this->assertSame('Strim TV3+', $tv3Plus['listings'][0]['title']['title']);
        }

        $this->withSession(['admin_authenticated' => true])->get('/adm')->assertOk()
            ->assertSee('Dagens TV-program')->assertSee('tv3')->assertSee('tv3-plus')
            ->assertSee('Programmer tilgjengelige fra Strim');
    }

    public function test_coverage_lock_prevents_a_second_source_check_and_preserves_cache(): void
    {
        $date = now();
        $identity = $date->format('Y-m-d').'|Europe/Oslo|nrk1';
        $cacheKey = 'tv-guide-vg.stale.'.$date->format('Y-m-d').'.'.sha1($identity);
        Cache::forever($cacheKey, $this->schedule());
        $lock = Cache::lock('fetch.'.$cacheKey, 45);
        $this->assertTrue($lock->get());
        Http::fake();
        $this->assertFalse(app(TvGuideService::class)->refreshSchedule($date, ['nrk1'], 'ringerike'));
        $this->assertSame($this->schedule(), app(TvGuideService::class)->getSchedule($date, ['nrk1'], 'print'));
        Http::assertNothingSent();
        $this->assertDatabaseCount('external_data_checks', 0);
        $lock->release();
    }

    public function test_reminders_are_limited_and_followup_checks_do_not_depend_on_visits(): void
    {
        $monitor = app(ExternalDataMonitor::class);
        for ($i = 0; $i < 3; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 5));
            $monitor->checked('sportsnext', 'league', 'football', 'Liga', true, false, false, 'feil');
        }
        $monitor->evaluate();
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, 1);
        for ($i = 0; $i < 24; $i++) {
            Carbon::setTestNow(now()->addHour());
            $monitor->checked('sportsnext', 'league', 'football', 'Liga', true, false, false, 'feil');
        }
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, 2);
        $monitor->checked('sportsnext', 'league', 'football', 'Liga', true, true, true);
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->state === 'recovered');
    }

    public function test_missing_controls_do_not_count_as_continuous_failure(): void
    {
        $monitor = app(ExternalDataMonitor::class);
        for ($i = 0; $i < 4; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 15));
            $monitor->checked('sportsnext', 'league', 'football', 'Liga', true, false, true, 'feil');
        }
        Carbon::setTestNow(now()->addHours(2));
        $monitor->checked('sportsnext', 'league', 'football', 'Liga', true, false, true, 'feil');
        $monitor->evaluate();
        $this->assertSame(1, (int) DB::table('external_data_checks')->where('service', 'sportsnext')->value('failure_count'));
        Mail::assertNothingSent();
    }

    public function test_scheduled_tv_refresh_builds_both_prison_caches_and_page_views_make_no_requests(): void
    {
        Http::fake([
            'tvguide.vg.no/*' => fn ($request) => Http::response($this->scheduleForRequest($request)),
            'www.dw.com/*' => Http::response(['data' => ['livestreamChannels' => [['nextTimeSlots' => []]]]]),
        ]);
        $this->artisan('external-data:refresh', ['type' => 'tv'])->assertExitCode(0);
        $this->assertDatabaseHas('external_data_checks', ['service' => 'tv-guide-vg', 'coverage_key' => 'ringerike-2026-10-19', 'failure_count' => 0]);
        $this->assertDatabaseHas('external_data_checks', ['service' => 'tv-guide-vg', 'coverage_key' => 'ilseng-2026-10-19', 'failure_count' => 0]);
        $before = Http::recorded()->count();
        $this->get('/print')->assertOk()->assertSee('NRK1');
        $this->get('/print-ilseng')->assertOk()->assertSee('NRK1');
        $this->assertCount($before, Http::recorded());
    }

    public function test_scheduled_checks_retry_a_missing_current_schedule_without_a_page_visit(): void
    {
        config()->set('external_data.tv_batch_size', 2);
        Http::fake([
            'tvguide.vg.no/*' => Http::response([], 503),
            'www.dw.com/*' => Http::response(['data' => ['livestreamChannels' => [['nextTimeSlots' => []]]]]),
        ]);
        for ($i = 0; $i < 3; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 5));
            $this->artisan('external-data:refresh', ['type' => 'tv'])->assertExitCode(0);
        }
        $this->artisan('external-data:evaluate')->assertExitCode(0);
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'tv-guide-vg-channel' && $mail->state === 'critical');
    }

    public function test_scheduled_tv_refresh_rebuilds_a_missing_cache_even_when_its_database_check_is_fresh(): void
    {
        config()->set('external_data.tv_batch_size', 1);
        $tv = app(TvGuideService::class);
        Http::fake(['tvguide.vg.no/*' => fn ($request) => Http::response($this->scheduleForRequest($request))]);

        $dates = collect(range(-8, 13))->map(fn (int $offset) => now('Europe/Oslo')->startOfDay()->addDays($offset));
        foreach (['ringerike' => TvGuideService::ringerikeChannels(), 'ilseng' => TvGuideService::ilsengChannels()] as $selection => $channels) {
            foreach ($dates as $date) {
                $this->assertTrue($tv->refreshSchedule($date, $channels, $selection));
            }
        }

        $date = now('Europe/Oslo')->startOfDay();
        foreach ($this->tvCacheKeys($date, TvGuideService::ringerikeChannels()) as $key) {
            Cache::forget($key);
        }
        Cache::put($this->tvUnpublishedKey($date, TvGuideService::ringerikeChannels()), true, now()->addMinutes(15));
        $this->assertFalse($tv->hasCachedSchedule($date, TvGuideService::ringerikeChannels()));

        $this->artisan('external-data:refresh', ['type' => 'tv'])->assertExitCode(0);

        $this->assertTrue($tv->hasCachedSchedule($date, TvGuideService::ringerikeChannels()));
        $this->assertDatabaseHas('external_data_checks', [
            'service' => 'tv-guide-vg',
            'coverage_key' => 'ringerike-'.$date->format('Y-m-d'),
            'failure_count' => 0,
        ]);
    }

    public function test_football_cache_reads_do_not_reset_failures_or_refresh_the_source_timestamp(): void
    {
        config()->set('external_data.max_age_minutes.football', 60);
        Http::fakeSequence()
            ->push(['events' => [], 'participants' => []])
            ->push(['standings' => [['teamStandings' => []]], 'participants' => []])
            ->push([], 503)->push([], 503)->push([], 503)->push([], 503)->push([], 503);
        $service = app(PremierLeagueService::class);
        $this->assertTrue($service->refreshCompetitionData());
        $original = $service->getCompetitionData()['lastUpdated']->toIso8601String();
        foreach ([15, 30, 45, 60, 75] as $minutes) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($minutes));
            $this->assertFalse($service->refreshCompetitionData());
            $this->assertSame($original, $service->getCompetitionData()['lastUpdated']->toIso8601String());
        }
        $row = DB::table('external_data_checks')->where('service', 'sportsnext')->first();
        $this->assertSame(5, (int) $row->failure_count);
        app(ExternalDataMonitor::class)->evaluate();
        Mail::assertNothingSent();
    }

    public function test_live_competitions_are_refreshed_after_five_minutes(): void
    {
        $start = now()->addMinutes(5)->toIso8601String();
        Http::fake(function ($request) use ($start) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/schedule')) {
                return Http::response(['events' => [['startDate' => $start, 'participantIds' => [1, 2], 'status' => ['type' => 'notstarted']]], 'participants' => [1 => ['name' => 'A'], 2 => ['name' => 'B']]]);
            }
            return Http::response([
                'standings' => [['groupName' => 'A', 'teamStandings' => [[
                    'teamId' => 1, 'rank' => 1, 'played' => 0, 'wins' => 0, 'draws' => 0,
                    'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'points' => 0,
                ]]]],
                'participants' => [1 => ['name' => 'A'], 2 => ['name' => 'B']],
            ]);
        });
        $this->artisan('external-data:refresh', ['type' => 'football'])->assertExitCode(0);
        $before = Http::recorded()->count();
        Carbon::setTestNow(now()->addMinutes(5));
        $this->artisan('external-data:refresh', ['type' => 'football'])->assertExitCode(0);
        $this->assertGreaterThan($before, Http::recorded()->count());
        $row = DB::table('external_data_checks')->where('coverage_key', 'premier_league-9186')->first();
        $this->assertEquals(now()->format('Y-m-d H:i:s'), $row->last_check_at);
    }

    private function schedule(array $channels = ['nrk1']): array
    {
        return array_map(fn (string $channel) => [
            'channel' => ['name' => strtoupper($channel), 'slug' => $channel],
            'listings' => [['startsAt' => '2026-10-19T12:00:00Z', 'title' => ['title' => strtoupper($channel)]]],
        ], $channels);
    }

    private function scheduleForRequest($request): array
    {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return $this->schedule(array_filter(explode(',', (string) ($query['channels'] ?? ''))));
    }

    private function scheduleForRequestWithMissingViasatChannels($request): array
    {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return array_map(function (string $channel): array {
            return [
                'channel' => ['name' => strtoupper($channel), 'slug' => $channel],
                'listings' => in_array($channel, ['tv3', 'tv3-plus'], true) ? [] : [[
                    'startsAt' => '2026-10-19T12:00:00Z', 'title' => ['title' => strtoupper($channel)],
                ]],
            ];
        }, array_filter(explode(',', (string) ($query['channels'] ?? ''))));
    }

    private function tvCacheKeys(Carbon $date, array $channels): array
    {
        $identity = $date->format('Y-m-d').'|Europe/Oslo|'.implode(',', $channels);
        $suffix = $date->format('Y-m-d').'.'.sha1($identity);

        return [
            'tv-guide-vg.fresh.'.$suffix,
            'tv-guide-vg.stale.'.$suffix,
            'tv-guide-vg.updated.'.$suffix,
        ];
    }

    private function tvUnpublishedKey(Carbon $date, array $channels): string
    {
        $identity = $date->format('Y-m-d').'|Europe/Oslo|'.implode(',', $channels);

        return 'tv-guide-vg.updated.'.$date->format('Y-m-d').'.'.sha1($identity).'.unpublished';
    }
}
