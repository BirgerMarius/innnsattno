<?php

namespace Tests\Unit;

use App\Mail\ExternalDataSourceFailureMail;
use App\Services\TvGuideService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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
        $schedule = [$this->channel('NRK1')];
        Http::fake(['tvguide.vg.no/*' => Http::response($schedule)]);

        $first = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');
        $second = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        $this->assertSame($schedule, $first);
        $this->assertSame($schedule, $second);
        Http::assertSentCount(1);
    }

    public function test_http_failure_notifies_and_uses_stale_data_for_the_same_date_and_channels(): void
    {
        $stale = [$this->channel('Gårsdagens NRK1')];
        $this->primeStale($stale);
        Http::fake(['tvguide.vg.no/*' => Http::response([], 500)]);

        $result = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        $this->assertSame($stale, $result);
        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->service === 'tv-guide-vg'
                && $mail->operation === 'ringerike-print'
                && $mail->failureKind === 'http-status'
                && $mail->status === 500;
        });
    }

    public function test_connection_failure_notifies_and_uses_stale_data(): void
    {
        $stale = [$this->channel('Lagret kanal')];
        $this->primeStale($stale);
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $result = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        $this->assertSame($stale, $result);
        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->failureKind === 'connection-exception';
        });
    }

    public function test_invalid_payload_notifies_and_returns_an_empty_schedule_without_stale_data(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response(['unexpected' => true])]);

        $result = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        $this->assertSame([], $result);
        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->failureKind === 'invalid-payload'
                && $mail->operation === 'ringerike-print';
        });
    }

    public function test_invalid_json_notifies_and_returns_an_empty_schedule_without_stale_data(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response('ikke json', 200)]);

        $result = $this->service()->getSchedule($this->date(), self::CHANNELS, 'ilseng-print');

        $this->assertSame([], $result);
        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->failureKind === 'invalid-json'
                && $mail->operation === 'ilseng-print';
        });
    }

    public function test_notifier_cooldown_suppresses_repeated_failures_for_the_same_operation(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([], 500)]);

        $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');
        $this->service()->getSchedule($this->date(), self::CHANNELS, 'ringerike-print');

        Mail::assertSent(ExternalDataSourceFailureMail::class, 1);
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

        $result = $this->service()->getUpcomingPremierLeagueOnTv3Plus($now, 'premier-league-tv3-plus-print', 3);

        $this->assertSame('Brentford – Chelsea', $result['matches'][0]['name']);
        $this->assertCount(3, $result['matches']);
        $this->assertSame('Liverpool – Wolves', $result['matches'][2]['name']);
        $this->assertSame('2026-09-10 20:55', $result['matches'][0]['startsAt']->format('Y-m-d H:i'));
        $this->assertSame(1, $result['missingMatchNames']);
        $this->assertSame(1, $result['excludedNonMatchProgrammes']);
        $this->assertFalse($result['hasSourceFailure']);
        Http::assertSentCount(7);

        $this->service()->getUpcomingPremierLeagueOnTv3Plus($now, 'premier-league-tv3-plus-print', 1);
        Http::assertSentCount(7);
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
        Mail::assertSent(ExternalDataSourceFailureMail::class);
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

        $europa = $this->service()->getUpcomingCompetitionMatches($now, 'europa-league', 'europa-test', 3);
        $eliteserien = $this->service()->getUpcomingCompetitionMatches($now, 'eliteserien', 'eliteserien-test', 3);

        $this->assertSame('Sunderland – AZ Alkmaar', $europa['matches'][0]['name']);
        $this->assertSame('TV3+', $europa['matches'][0]['channel']);
        $this->assertSame([], $eliteserien['matches']);
        $this->assertSame(1, $eliteserien['missingMatchNames']);
        $this->assertSame(1, $eliteserien['excludedNonMatchProgrammes']);
        Http::assertSentCount(7);
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

        $result = $this->service()->getUpcomingCompetitionMatches($now, 'nations-league', 'nations-league-week-test', 0);
        $this->assertSame(['Sverige – Romania', 'Norge – Danmark', 'Tsjekkia – Kroatia', 'Norge – Portugal'], array_column($result['matches'], 'name'));
        $this->assertCount(4, $result['matches']);
        $this->assertSame(1, $result['excludedNonMatchProgrammes']);
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
