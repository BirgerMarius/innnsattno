<?php

namespace Tests\Unit;

use App\Mail\ExternalDataSourceFailureMail;
use App\Services\TvGuideService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Tests\TestCase;

class TvGuideServiceTest extends TestCase
{
    private const CHANNELS = ['nrk1', 'nrk2'];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
    }

    public function test_successful_call_returns_the_schedule_and_uses_fresh_cache(): void
    {
        $schedule = [
            $this->channelWithListing('NRK1', 'nrk1', 'NRK1'),
            $this->channelWithListing('NRK2', 'nrk2', 'NRK2'),
        ];
        Http::fake(['tvguide.vg.no/*' => Http::response($schedule)]);

        $this->service()->refreshSchedule($this->date(), self::CHANNELS, 'ringerike');
        $first = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');
        $second = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        $this->assertSame($schedule, $first);
        $this->assertSame($schedule, $second);
        Http::assertSentCount(1);
    }

    public function test_a_failed_cache_write_is_not_reported_as_a_successful_schedule_refresh(): void
    {
        $date = $this->date();
        $schedule = [
            $this->channelWithListing('NRK1', 'nrk1', 'NRK1'),
            $this->channelWithListing('NRK2', 'nrk2', 'NRK2'),
        ];
        $identity = $date->format('Y-m-d').'|Europe/Oslo|'.implode(',', self::CHANNELS);
        $suffix = $date->format('Y-m-d').'.'.sha1($identity);
        $staleKey = 'tv-guide-vg.stale.'.$suffix;
        $originalCache = Cache::getFacadeRoot();
        $lock = Mockery::mock(Lock::class);
        $lock->shouldReceive('get')->once()->andReturnTrue();
        $lock->shouldReceive('release')->once();
        $cache = Mockery::mock();
        $cache->shouldReceive('lock')->once()->andReturn($lock);
        $cache->shouldReceive('get')->with($staleKey)->once()->andReturn(null);
        $cache->shouldReceive('forget')->once()->andReturnTrue();
        $cache->shouldReceive('put')->once()->andReturnFalse();
        $cache->shouldReceive('forever')->twice()->andReturnTrue();
        Cache::swap($cache);
        Http::fake(['tvguide.vg.no/*' => Http::response($schedule)]);

        try {
            $this->assertFalse($this->service()->refreshSchedule($date, self::CHANNELS, 'ringerike'));
        } finally {
            Cache::swap($originalCache);
        }
    }

    public function test_empty_channel_keeps_same_date_cached_programmes_and_marks_the_refresh_partial(): void
    {
        $date = Carbon::parse('2026-10-19', 'Europe/Oslo');
        $channels = ['nrk1', 'tv3'];
        $previous = [
            $this->channelWithListing('NRK1', 'nrk1', 'Tidligere NRK'),
            $this->channelWithListing('TV3', 'tv3', 'Tidligere TV3'),
        ];
        $partial = [
            $this->channelWithListing('NRK1', 'nrk1', 'Ny NRK'),
            ['channel' => ['name' => 'TV3', 'slug' => 'tv3'], 'listings' => []],
        ];
        Http::fakeSequence()->push($previous)->push($partial);

        $this->assertTrue($this->service()->refreshSchedule($date, $channels, 'ringerike'));
        $updated = $this->service()->lastUpdated($date, $channels)->toIso8601String();
        Carbon::setTestNow(now()->addMinutes(61));
        $this->assertTrue($this->service()->refreshSchedule($date, $channels, 'ringerike'));

        $schedule = $this->service()->getSchedule($date, $channels, 'print');
        $this->assertSame('Ny NRK', $schedule[0]['listings'][0]['title']['title']);
        $this->assertSame('Tidligere TV3', $schedule[1]['listings'][0]['title']['title']);
        $this->assertSame('fallback', $schedule[1]['programDataStatus']);
        $this->assertSame($updated, $this->service()->lastUpdated($date, $channels)->toIso8601String());
    }

    public function test_missing_channel_row_keeps_same_date_cached_programmes(): void
    {
        $date = Carbon::parse('2026-10-19', 'Europe/Oslo');
        $channels = ['nrk1', 'tv3'];
        Http::fakeSequence()
            ->push([$this->channelWithListing('NRK1', 'nrk1', 'NRK'), $this->channelWithListing('TV3', 'tv3', 'TV3')])
            ->push([$this->channelWithListing('NRK1', 'nrk1', 'Ny NRK')]);

        $this->service()->refreshSchedule($date, $channels, 'ringerike');
        Carbon::setTestNow(now()->addMinutes(61));
        $this->service()->refreshSchedule($date, $channels, 'ringerike');

        $schedule = $this->service()->getSchedule($date, $channels, 'print');
        $tv3 = collect($schedule)->firstWhere('channel.slug', 'tv3');
        $this->assertSame('TV3', $tv3['listings'][0]['title']['title']);
        $this->assertSame('fallback', $tv3['programDataStatus']);
    }

    public function test_empty_tv3_uses_validated_strim_backup_without_sports_metadata(): void
    {
        $date = Carbon::parse('2026-10-28', 'Europe/Oslo');
        Http::fake([
            'tvguide.vg.no/*' => Http::response([['channel' => ['name' => 'TV3', 'slug' => 'tv3'], 'listings' => []]]),
            'contentsearch.rikstv.no/1/channel/69/epg/2026-10-28' => Http::response([[
                'broadcastId' => '123', 'seriesName' => 'Strim-program', 'name' => 'Episode 1',
                'broadcastedTime' => '2026-10-28T18:00:00Z', 'broadcastedTimeEnd' => '2026-10-28T19:00:00Z',
                'originChannel' => ['channelId' => 69],
            ]]),
        ]);

        $this->service()->refreshSchedule($date, ['tv3'], 'ringerike');
        $channel = $this->service()->getSchedule($date, ['tv3'], 'print')[0];
        $this->assertSame('external-fallback', $channel['programDataStatus']);
        $this->assertSame('Strim-program', $channel['listings'][0]['title']['title']);
        $this->assertArrayNotHasKey('sportsEvent', $channel['listings'][0]);
        $this->assertArrayNotHasKey('isLive', $channel['listings'][0]);
        Http::assertSent(fn ($request) => $request->url() === 'https://contentsearch.rikstv.no/1/channel/69/epg/2026-10-28');
    }

    public function test_first_partial_fetch_marks_empty_channel_missing_without_borrowing_another_date(): void
    {
        $channels = ['nrk1', 'tv3'];
        $yesterday = Carbon::parse('2026-10-18', 'Europe/Oslo');
        $today = Carbon::parse('2026-10-19', 'Europe/Oslo');
        Http::fakeSequence()
            ->push([$this->channelWithListing('NRK1', 'nrk1', 'Gårsdagens NRK'), $this->channelWithListing('TV3', 'tv3', 'Gårsdagens TV3')])
            ->push([$this->channelWithListing('NRK1', 'nrk1', 'Dagens NRK'), ['channel' => ['name' => 'TV3', 'slug' => 'tv3'], 'listings' => []]]);

        $this->service()->refreshSchedule($yesterday, $channels, 'ringerike');
        $this->service()->refreshSchedule($today, $channels, 'ringerike');

        $schedule = $this->service()->getSchedule($today, $channels, 'print');
        $tv3 = collect($schedule)->firstWhere('channel.slug', 'tv3');
        $this->assertSame([], $tv3['listings']);
        $this->assertSame('missing', $tv3['programDataStatus']);
        $this->assertNull($this->service()->lastUpdated($today, $channels));
    }

    public function test_http_failure_notifies_and_uses_stale_data_for_the_same_date_and_channels(): void
    {
        $stale = [$this->channel('Gårsdagens NRK1')];
        $this->primeStale($stale);
        Http::fake(['tvguide.vg.no/*' => Http::response([], 500)]);

        $result = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        $this->assertSame($stale, $result);
        Mail::assertNothingSent();
    }

    public function test_connection_failure_notifies_and_uses_stale_data(): void
    {
        $stale = [$this->channel('Lagret kanal')];
        $this->primeStale($stale);
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $result = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        $this->assertSame($stale, $result);
        Mail::assertNothingSent();
    }

    public function test_invalid_payload_notifies_and_returns_an_empty_schedule_without_stale_data(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response(['unexpected' => true])]);

        $result = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        $this->assertSame([], $result);
        Mail::assertNothingSent();
    }

    public function test_invalid_json_notifies_and_returns_an_empty_schedule_without_stale_data(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response('ikke json', 200)]);

        $result = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ilseng-print');

        $this->assertSame([], $result);
        Mail::assertNothingSent();
    }

    public function test_notifier_cooldown_suppresses_repeated_failures_for_the_same_operation(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([], 500)]);

        $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');
        $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        Mail::assertNothingSent();
    }

    public function test_display_title_uses_the_sports_event_name_without_duplication_or_a_missing_field_error(): void
    {
        $this->assertSame('Premier League: Brentford – Chelsea', $this->service()->displayTitle([
            'title' => ['title' => 'Premier League'],
            'sportsEvent' => ['name' => 'Brentford - Chelsea'],
        ]));
        $this->assertSame('Premier League: Brentford – Chelsea', $this->service()->displayTitle([
            'title' => ['title' => 'Premier League: Brentford – Chelsea'],
            'sportsEvent' => ['name' => 'Brentford - Chelsea'],
        ]));
        $this->assertSame('Premier League', $this->service()->displayTitle([
            'title' => ['title' => 'Premier League'],
        ]));
        $this->assertSame('UEFA Champions League: Magasin 2026/27', $this->service()->displayTitle([
            'title' => ['title' => 'UEFA Champions League: Magasin'],
            'sportsEvent' => ['name' => 'Magasin 2026/27'],
        ]));
    }

    public function test_it_identifies_verified_norway_mens_nations_league_fixtures_in_both_home_and_away_order(): void
    {
        $this->assertSame('⚽ Norge – Danmark', $this->service()->norwayMensMatchLabel(
            $this->norwayNationsLeagueListing('Norge - Danmark'),
        ));
        $this->assertSame('⚽ Danmark – Norge', $this->service()->norwayMensMatchLabel(
            $this->norwayNationsLeagueListing('Danmark - Norge'),
        ));
    }

    public function test_it_identifies_a_scheduled_norway_match_even_when_vg_has_not_set_is_live(): void
    {
        $listing = $this->norwayNationsLeagueListing('Norge - Portugal');
        $listing['isLive'] = false;

        $this->assertSame('⚽ Norge – Portugal', $this->service()->norwayMensMatchLabel($listing));
    }

    public function test_it_excludes_replays_and_non_fixture_norway_programmes(): void
    {
        $replay = $this->norwayNationsLeagueListing('Norge - Portugal');
        $replay['isRerun'] = true;

        $this->assertNull($this->service()->norwayMensMatchLabel($replay));
        $this->assertNull($this->service()->norwayMensMatchLabel([
            'title' => ['id' => 572756, 'type' => 'sportsTitle', 'title' => 'UEFA Nations League', 'slug' => 'uefa-nations-league'],
            'isRerun' => false,
            'sportsEvent' => null,
        ]));
        $this->assertNull($this->service()->norwayMensMatchLabel([
            'title' => ['id' => 17791, 'type' => 'series', 'title' => 'Norges tøffeste', 'slug' => 'norges-toeffeste'],
            'isRerun' => false,
            'sportsEvent' => ['name' => 'Norge - Danmark'],
        ]));
    }

    public function test_it_excludes_other_sports_and_not_yet_verified_football_competitions(): void
    {
        $otherSport = $this->norwayNationsLeagueListing('Norge - Ungarn');
        $otherSport['title'] = ['id' => 104040, 'type' => 'sportsTitle', 'title' => 'EHF Euro Cup', 'slug' => 'ehf-euro-cup-kvinner'];
        $unsupportedFootball = $this->norwayNationsLeagueListing('Norge - Italia');
        $unsupportedFootball['title'] = ['id' => 999999, 'type' => 'sportsTitle', 'title' => 'VM-kvalifisering', 'slug' => 'vm-kvalifisering'];

        $this->assertNull($this->service()->norwayMensMatchLabel($otherSport));
        $this->assertNull($this->service()->norwayMensMatchLabel($unsupportedFootball));
    }

    public function test_upcoming_tv3_plus_matches_use_seven_cached_daily_requests_and_exclude_non_matches(): void
    {
        $now = Carbon::parse('2026-09-10 10:00:00', 'Europe/Oslo');
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = ($query['date'] ?? null) === '2026-09-10' ? [
                $this->premierLeagueListing('2026-09-10T18:55:00Z', 'Brentford - Chelsea'),
                $this->premierLeagueListing('2026-09-10T19:55:00Z', 'Arsenal - Everton'),
                $this->premierLeagueListing('2026-09-10T20:55:00Z', 'Liverpool - Wolves'),
                $this->premierLeagueListing('2026-09-10T21:55:00Z', 'Newcastle - Fulham'),
                $this->premierLeagueListing('2026-09-10T17:00:00Z', 'Premier League Studio'),
                $this->premierLeagueListing('2026-09-10T16:00:00Z', null),
                array_merge($this->premierLeagueListing('2026-09-10T20:00:00Z', 'Arsenal - Everton'), ['isRerun' => true]),
            ] : [];

            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });

        $this->warmTv($now, 7);

        $result = $this->service()->getUpcomingPremierLeagueOnTv3Plus($now, 'premier-league-tv3-plus-print', 3);

        $this->assertSame('Brentford – Chelsea', $result['matches'][0]['name']);
        $this->assertCount(3, $result['matches']);
        $this->assertSame('Liverpool – Wolves', $result['matches'][2]['name']);
        $this->assertSame('2026-09-10 20:55', $result['matches'][0]['startsAt']->format('Y-m-d H:i'));
        $this->assertSame(1, $result['missingMatchNames']);
        $this->assertSame(1, $result['excludedNonMatchProgrammes']);
        $this->assertFalse($result['hasSourceFailure']);
        Http::assertSentCount(20);

        $this->service()->getUpcomingPremierLeagueOnTv3Plus($now, 'premier-league-tv3-plus-print', 1);
        Http::assertSentCount(20);
    }

    public function test_upcoming_tv3_plus_matches_distinguish_a_source_failure_from_no_listed_match(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([], 503)]);

        $result = $this->service()->getUpcomingPremierLeagueOnTv3Plus(
            Carbon::parse('2026-09-10 10:00:00', 'Europe/Oslo'),
            'premier-league-tv3-plus-print',
            3,
        );

        $this->assertSame([], $result['matches']);
        $this->assertTrue($result['hasSourceFailure']);
        $this->assertSame(0, $result['missingMatchNames']);
        Mail::assertNothingSent();
    }

    public function test_competition_matching_uses_exact_verified_titles_not_unreliable_uefa_slugs(): void
    {
        $now = Carbon::parse('2026-09-10 10:00:00', 'Europe/Oslo');
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = ($query['date'] ?? null) === '2026-09-10' ? [
                $this->competitionListing('UEFA Europa League', 'uefa-conference-league-1', 'Sunderland - AZ Alkmaar'),
                $this->competitionListing('UEFA Champions League: Magasin', 'uefa-champions-league-highlights-1', 'Magasin 2026/27'),
                $this->competitionListing('EHF Champions League', 'ehf-champions-league-2', 'Kolstad - Kielce'),
                $this->competitionListing('Eliteserien', 'eliteserien-7', 'Studio'),
                $this->competitionListing('Eliteserien', 'eliteserien-7', null),
            ] : [];

            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });

        $this->warmTv($now, 7);

        $europa = $this->service()->getUpcomingCompetitionMatches($now, 'europa-league', 'europa-test', 3);
        $eliteserien = $this->service()->getUpcomingCompetitionMatches($now, 'eliteserien', 'eliteserien-test', 3);

        $this->assertSame('Sunderland – AZ Alkmaar', $europa['matches'][0]['name']);
        $this->assertSame('TV3+', $europa['matches'][0]['channel']);
        $this->assertSame([], $eliteserien['matches']);
        $this->assertSame(1, $eliteserien['missingMatchNames']);
        $this->assertSame(1, $eliteserien['excludedNonMatchProgrammes']);
        Http::assertSentCount(20);
    }

    public function test_nations_league_box_shows_scheduled_fixture_broadcasts_but_excludes_non_matches_and_replays(): void
    {
        $now = Carbon::parse('2026-09-24 10:00:00', 'Europe/Oslo');
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = ($query['date'] ?? null) === '2026-09-24' ? [
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Norge - Danmark'), ['startsAt' => '2026-09-24T18:55:00Z']),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Norge - Portugal'), ['startsAt' => '2026-09-25T18:55:00Z', 'isLive' => false]),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Studio'), ['startsAt' => '2026-09-24T16:55:00Z']),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Norge - Danmark'), ['startsAt' => '2026-09-24T20:55:00Z', 'isRerun' => true]),
            ] : [];
            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });

        $this->warmTv($now->copy()->startOfWeek(), 7);

        $result = $this->service()->getUpcomingCompetitionMatches($now, 'nations-league', 'nations-league-test', 3);
        $this->assertSame('Nations League', $result['competitionLabel']);
        $this->assertSame(['Norge – Danmark', 'Norge – Portugal'], array_column($result['matches'], 'name'));
        $this->assertSame(1, $result['excludedNonMatchProgrammes']);
    }

    public function test_nations_league_box_lists_all_current_week_fixtures_in_chronological_order(): void
    {
        $now = Carbon::parse('2026-09-24 10:00:00', 'Europe/Oslo');
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = ($query['date'] ?? null) === '2026-09-24' ? [
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Sverige - Romania'), ['startsAt' => '2026-09-24T16:00:00Z']),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Norge - Danmark'), ['startsAt' => '2026-09-24T18:55:00Z']),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Tsjekkia - Kroatia'), ['startsAt' => '2026-09-24T19:00:00Z']),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Norge - Portugal'), ['startsAt' => '2026-09-27T18:55:00Z', 'isLive' => false]),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Magasin'), ['startsAt' => '2026-09-24T15:00:00Z']),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Norge - Danmark'), ['startsAt' => '2026-09-24T20:00:00Z', 'isRerun' => true]),
                array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Utenfor uke - Lag'), ['startsAt' => '2026-09-28T18:00:00Z']),
            ] : [];
            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });

        $this->warmTv($now->copy()->startOfWeek(), 7);

        $result = $this->service()->getUpcomingCompetitionMatches($now, 'nations-league', 'nations-league-week-test', 0);
        $this->assertSame(['Sverige – Romania', 'Norge – Danmark', 'Tsjekkia – Kroatia', 'Norge – Portugal'], array_column($result['matches'], 'name'));
        $this->assertCount(4, $result['matches']);
        $this->assertSame(1, $result['excludedNonMatchProgrammes']);
    }

    public function test_nations_league_rolling_window_excludes_started_broadcasts_and_includes_its_exact_end(): void
    {
        $now = Carbon::parse('2026-09-24 10:00:00', 'Europe/Oslo');
        Http::fake(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $listings = match ($query['date'] ?? null) {
                '2026-09-24' => [
                    array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Startet - Kamp'), ['startsAt' => '2026-09-24T07:59:59Z']),
                    array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Nå - Kamp'), ['startsAt' => '2026-09-24T08:00:00Z']),
                ],
                '2026-10-01' => [
                    array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Slutt - Kamp'), ['startsAt' => '2026-10-01T08:00:00Z']),
                    array_merge($this->competitionListing('UEFA Nations League', 'uefa-nations-league', 'Etter slutt - Kamp'), ['startsAt' => '2026-10-01T08:00:01Z']),
                ],
                default => [],
            };

            return Http::response([['channel' => ['name' => 'TV3+', 'slug' => 'tv3-plus'], 'listings' => $listings]]);
        });

        $this->warmTv($now, 8);

        $result = $this->service()->getUpcomingCompetitionMatches($now, 'nations-league', 'nations-league-rolling-test', 0, true);

        $this->assertSame(['Nå – Kamp', 'Slutt – Kamp'], array_column($result['matches'], 'name'));
        $this->assertSame('2026-10-01 10:00:00', $result['periodEnd']->format('Y-m-d H:i:s'));
        Http::assertSentCount(22);
    }

    private function primeStale(array $schedule): void
    {
        Cache::put($this->staleCacheKey(), $schedule, now()->addDays(3));
    }

    private function staleCacheKey(): string
    {
        $identity = $this->date()->format('Y-m-d').'|Europe/Oslo|'.implode(',', self::CHANNELS);

        return 'tv-guide-vg.stale.'.$this->date()->format('Y-m-d').'.'.sha1($identity);
    }

    private function date(): Carbon
    {
        return Carbon::parse('2026-09-08 10:00:00', 'Europe/Oslo');
    }

    private function channel(string $name): array
    {
        return ['channel' => ['name' => $name, 'slug' => strtolower(str_replace(' ', '-', $name))], 'listings' => []];
    }

    private function channelWithListing(string $name, string $slug, string $title): array
    {
        return [
            'channel' => ['name' => $name, 'slug' => $slug],
            'listings' => [[
                'startsAt' => '2026-10-19T12:00:00Z',
                'title' => ['title' => $title],
            ]],
        ];
    }

    private function premierLeagueListing(string $startsAt, ?string $eventName): array
    {
        return [
            'title' => ['type' => 'sportsTitle', 'slug' => 'premier-league', 'title' => 'Premier League'],
            'sportsEvent' => $eventName === null ? null : ['id' => crc32($startsAt.$eventName), 'name' => $eventName],
            'startsAt' => $startsAt,
            'isLive' => true,
            'isRerun' => false,
        ];
    }

    private function competitionListing(string $title, string $slug, ?string $eventName): array
    {
        return [
            'title' => ['type' => 'sportsTitle', 'slug' => $slug, 'title' => $title],
            'sportsEvent' => $eventName === null ? null : ['name' => $eventName],
            'startsAt' => '2026-09-10T18:55:00Z',
            'isLive' => true,
            'isRerun' => false,
        ];
    }

    private function norwayNationsLeagueListing(string $eventName): array
    {
        return [
            'title' => ['id' => 572756, 'type' => 'sportsTitle', 'title' => 'UEFA Nations League', 'slug' => 'uefa-nations-league'],
            'sportsEvent' => ['id' => crc32($eventName), 'name' => $eventName],
            'startsAt' => '2026-09-24T18:35:00Z',
            'isLive' => true,
            'isRerun' => false,
        ];
    }

    private function service(): TvGuideService
    {
        return app(TvGuideService::class);
    }
}
