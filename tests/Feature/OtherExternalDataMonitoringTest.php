<?php

namespace Tests\Feature;

use App\Mail\ExternalDataIncidentMail;
use App\Services\ExternalDataMonitor;
use App\Services\PrayerTimeService;
use App\Services\WeatherForecastService;
use App\Services\OnThisDayService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtherExternalDataMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_NOW = '2026-10-19 08:00:00 Europe/Oslo';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); Mail::fake();
        config()->set('feedback.notification_email', 'monitor@example.test');
        config()->set('services.prayer_times.api_token', 'test');
        Carbon::setTestNow(Carbon::parse(self::TEST_NOW));
    }

    protected function tearDown(): void { Carbon::setTestNow(); parent::tearDown(); }

    public function test_prayer_coverage_not_source_age_decides_if_fallback_is_usable(): void
    {
        $sequence = Http::fakeSequence()->push($this->days(8));
        for ($i = 0; $i < 10; $i++) $sequence->push([], 503);
        $service = app(PrayerTimeService::class);
        $service->refreshMonth(146, 2026, 10);
        for ($i = 1; $i <= 5; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 15));
            $service->refreshMonth(146, 2026, 10);
        }
        app(ExternalDataMonitor::class)->evaluate();
        Mail::assertNothingSent();
        $this->assertDatabaseHas('external_data_checks', ['service' => 'bonnetid-no', 'coverage_key' => '146-2026-10-19', 'failure_count' => 5, 'usable' => true]);
        $this->assertCount(8, $service->getMonth(146, 2026, 10));
    }

    public function test_missing_prayer_day_alerts_and_recovers_after_a_real_check_without_a_visit(): void
    {
        $sequence = Http::fakeSequence();
        for ($i = 0; $i < 6; $i++) $sequence->push([], 503);
        $sequence->push($this->days(8));
        $service = app(PrayerTimeService::class);
        for ($i = 0; $i < 3; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 5));
            $service->refreshMonth(146, 2026, 10);
            app(ExternalDataMonitor::class)->evaluate();
        }
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'bonnetid-no' && $mail->state === 'critical');
        Carbon::setTestNow(now()->addMinutes(5));
        $service->refreshMonth(146, 2026, 10);
        app(ExternalDataMonitor::class)->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'bonnetid-no' && $mail->state === 'recovered');
    }

    public function test_partial_prayer_response_keeps_previous_dates_and_records_missing_coverage(): void
    {
        Http::fakeSequence()->push($this->days(8))->push($this->days(1));
        $service = app(PrayerTimeService::class);
        $service->refreshMonth(146, 2026, 10);
        $service->refreshMonth(146, 2026, 10);
        $this->assertCount(8, $service->getMonth(146, 2026, 10));
        $this->assertDatabaseHas('external_data_checks', ['service' => 'bonnetid-no', 'coverage_key' => '146-2026-10-20', 'failure_count' => 1, 'usable' => true]);
    }

    public function test_scheduler_retries_missing_prayer_data_and_can_alert_without_visits(): void
    {
        Http::fake(['*' => Http::response('', 404)]);
        for ($i = 0; $i < 3; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 5));
            $this->artisan('external-data:check-others')->assertExitCode(0);
        }
        app(ExternalDataMonitor::class)->evaluate();
        $this->assertDatabaseHas('external_data_checks', ['service' => 'bonnetid-no', 'coverage_key' => '146-2026-10-19', 'failure_count' => 3]);
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'bonnetid-no' && $mail->state === 'critical');
    }

    public function test_weather_source_checks_continue_while_cached_forecast_is_used(): void
    {
        config()->set('external_data.max_age_minutes.weather', 60);
        $forecast = ['properties' => ['timeseries' => [[
            'time' => now()->toIso8601String(), 'data' => ['instant' => ['details' => ['air_temperature' => 10]]],
        ]]]];
        $sequence = Http::fakeSequence()->push($forecast);
        for ($i = 0; $i < 5; $i++) $sequence->push([], 503);
        $service = app(WeatherForecastService::class);
        $service->refresh('ringerike');
        for ($i = 1; $i <= 5; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 15));
            $service->refresh('ringerike');
            if ($i === 1) {
                $count = Http::recorded()->count();
                $service->forecast('ringerike');
                $this->assertCount($count, Http::recorded());
            }
        }
        $row = DB::table('external_data_checks')->where('service', 'met-weather')->first();
        $this->assertSame(5, (int) $row->failure_count);
        $this->assertSame('2026-10-19 08:00:00', $row->last_success_at);
        app(ExternalDataMonitor::class)->evaluate();
        Mail::assertNothingSent();
    }

    public function test_missing_optional_data_does_not_wait_for_the_age_limit_after_five_failures(): void
    {
        $monitor = app(ExternalDataMonitor::class);
        $monitor->checked('local-news-ringerike-source-0', 'front-page-news-ringerike-source-0', 'news', 'Lokale nyheter', false, true, true);
        for ($i = 1; $i <= 5; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 15));
            $monitor->checked('local-news-ringerike-source-0', 'front-page-news-ringerike-source-0', 'news', 'Lokale nyheter', false, false, false, 'Kilden feilet.');
        }
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'local-news-ringerike-source-0' && $mail->state === 'critical');
    }

    public function test_wikipedia_fallbacks_are_checked_independently_and_cache_alone_cannot_recover(): void
    {
        $healthy = false;
        Http::fake(function ($request) use (&$healthy) {
            if (! $healthy) return Http::response('', 404);
            if (str_contains($request->url(), 'w/api.php')) {
                return ($request->data()['action'] ?? '') === 'parse'
                    ? Http::response(['parse' => ['wikitext' => "== Historie ==\n* [[1900]] – [[Norge]] ble omtalt."]])
                    : Http::response(['query' => ['pages' => [['title' => 'Norge', 'pageprops' => ['wikibase_item' => 'Q20'], 'length' => 10000]]]]);
            }
            return Http::response(['events' => [['year' => 1900]], 'births' => [], 'deaths' => []]);
        });
        $date = now('Europe/Oslo');
        $source = app(OnThisDayService::class);
        for ($i = 0; $i < 5; $i++) {
            Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00', 'Europe/Oslo')->addMinutes($i * 15));
            $source->checkSources($date);
        }
        $monitor = app(ExternalDataMonitor::class);
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'wikipedia-on-this-day' && $mail->state === 'critical' && count($mail->coverages) === 3);
        Cache::forever('today.stale.curated.v3.10-19', ['world_events' => [['title' => 'Lagret']]]);
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, 1);

        $healthy = true;
        Carbon::setTestNow(now()->addMinutes(15));
        $source->checkSources($date);
        $monitor->evaluate();
        Mail::assertSent(ExternalDataIncidentMail::class, fn ($mail) => $mail->service === 'wikipedia-on-this-day' && $mail->state === 'recovered');
    }

    public function test_warm_command_reports_and_fails_when_football_is_missing(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'tvguide.vg.no')) return Http::response($this->completeTvGuideResponse($request));
            return Http::response([], 404);
        });
        $this->artisan('external-data:warm')->expectsOutput('Noen hentinger feilet eller mangler data. Scheduleren prøver på nytt.')->assertExitCode(1);
        $this->assertDatabaseHas('external_data_checks', ['service' => 'tv-guide-vg', 'coverage_key' => 'ringerike-2026-10-19', 'failure_count' => 0]);
        $this->assertGreaterThan(0, DB::table('external_data_checks')->where('service', 'sportsnext')->where('failure_count', '>', 0)->count());
    }

    public function test_warm_command_succeeds_with_complete_valid_tv_and_football_data(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'tvguide.vg.no')) return Http::response($this->completeTvGuideResponse($request));
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/schedule')) return Http::response([
                'events' => [['startDate' => now()->toIso8601String(), 'participantIds' => [1, 2], 'status' => ['type' => 'notstarted']]],
                'participants' => [1 => ['name' => 'A'], 2 => ['name' => 'B']],
            ]);
            return Http::response([
                'standings' => [['groupName' => 'A', 'teamStandings' => [['teamId' => 1, 'rank' => 1, 'played' => 0, 'wins' => 0, 'draws' => 0, 'losses' => 0, 'goalsFor' => 0, 'goalsAgainst' => 0, 'points' => 0]]]],
                'participants' => [1 => ['name' => 'A'], 2 => ['name' => 'B']],
            ]);
        });
        $this->artisan('external-data:warm')->expectsOutput('Forhåndshenting fullført.')->assertExitCode(0);
        $this->assertSame(44, DB::table('external_data_checks')->where('service', 'tv-guide-vg')->count());
        $this->assertSame(8, DB::table('external_data_checks')->where('service', 'sportsnext')->where('failure_count', 0)->count());
    }

    /** A complete date-specific VG response, matching the requested local channel set. */
    private function completeTvGuideResponse($request): array
    {
        $channels = array_filter(explode(',', (string) ($request->data()['channels'] ?? '')));
        $date = Carbon::parse((string) ($request->data()['date'] ?? now('Europe/Oslo')->toDateString()), 'Europe/Oslo')->setTime(12, 0);

        return array_map(fn (string $slug) => [
            'channel' => ['name' => strtoupper($slug), 'slug' => $slug],
            'listings' => [['startsAt' => $date->toIso8601String(), 'title' => ['title' => 'Nyheter']]],
        ], $channels);
    }

    private function days(int $count): array
    {
        $days = [];
        for ($i = 0; $i < $count; $i++) {
            $days[] = ['date' => Carbon::parse('2026-10-19')->addDays($i)->format('d-m-Y'), 'fajr' => '05:00', 'duhr' => '12:00', 'asr' => '15:00', 'maghrib' => '18:00', 'isha' => '20:00'];
        }
        return $days;
    }
}
