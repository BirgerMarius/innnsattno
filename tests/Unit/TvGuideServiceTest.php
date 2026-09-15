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

            return Http::response([['channel' => ['slug' => 'tv3-plus'], 'listings' => $listings]]);
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

    private function service(): TvGuideService
    {
        return app(TvGuideService::class);
    }
}
