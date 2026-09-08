<?php

namespace Tests\Unit;

use App\Mail\ExternalDataSourceFailureMail;
use App\Services\FootballWorldCupService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FootballWorldCupServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
    }

    public function test_it_fetches_schedule_and_standings_and_uses_fresh_cache(): void
    {
        Http::fake([
            '*schedule' => Http::response($this->schedule()),
            '*standings' => Http::response($this->standings()),
        ]);

        $first = $this->service()->getData();
        $second = $this->service()->getData();

        $this->assertSame($this->schedule(), $first['schedule']);
        $this->assertSame($this->standings(), $first['standings']);
        $this->assertSame($first, $second);
        Http::assertSentCount(2);
    }

    public function test_schedule_http_failure_notifies_and_uses_only_stale_schedule(): void
    {
        $staleSchedule = $this->schedule('Stale hjemme');
        Cache::put($this->staleKey('schedule'), $staleSchedule, now()->addDays(7));
        Http::fake([
            '*schedule' => Http::response([], 500),
            '*standings' => Http::response($this->standings()),
        ]);

        $result = $this->service()->getData();

        $this->assertSame($staleSchedule, $result['schedule']);
        $this->assertSame($this->standings(), $result['standings']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->operation === 'world-cup-schedule' && $mail->status === 500);
    }

    public function test_standings_http_failure_notifies_and_uses_only_stale_standings(): void
    {
        $staleStandings = $this->standings('Stale lag');
        Cache::put($this->staleKey('standings'), $staleStandings, now()->addDays(7));
        Http::fake([
            '*schedule' => Http::response($this->schedule()),
            '*standings' => Http::response([], 500),
        ]);

        $result = $this->service()->getData();

        $this->assertSame($this->schedule(), $result['schedule']);
        $this->assertSame($staleStandings, $result['standings']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->operation === 'world-cup-standings' && $mail->status === 500);
    }

    public function test_connection_failure_notifies_and_uses_stale_data_without_affecting_standings(): void
    {
        Cache::put($this->staleKey('schedule'), $this->schedule('Lagret lag'), now()->addDays(7));
        Http::fake(function ($request) {
            if (str_ends_with(parse_url($request->url(), PHP_URL_PATH), '/schedule')) {
                throw new ConnectionException('Timed out');
            }

            return Http::response($this->standings());
        });

        $result = $this->service()->getData();

        $this->assertSame('Lagret lag', $result['schedule']['participants'][1]['name']);
        $this->assertSame($this->standings(), $result['standings']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'connection-exception');
    }

    public function test_invalid_json_notifies_without_throwing(): void
    {
        Http::fake([
            '*schedule' => Http::response('ikke json'),
            '*standings' => Http::response($this->standings()),
        ]);
        $jsonFailure = $this->service()->getData();
        $this->assertSame([], $jsonFailure['schedule']['events']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-json');

    }

    public function test_missing_schedule_schema_notifies_without_throwing(): void
    {
        Http::fake([
            '*schedule' => Http::response(['participants' => [], 'events' => []]),
            '*standings' => Http::response($this->standings()),
        ]);
        $schemaFailure = $this->service()->getData();
        $this->assertSame([], $schemaFailure['schedule']['events']);
        Mail::assertSent(ExternalDataSourceFailureMail::class, fn ($mail) => $mail->failureKind === 'invalid-payload');
    }

    public function test_no_stale_data_returns_controlled_empty_endpoints(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $result = $this->service()->getData();

        $this->assertSame(['participants' => [], 'events' => []], $result['schedule']);
        $this->assertSame(['participants' => [], 'standings' => []], $result['standings']);
    }

    public function test_notifier_cooldown_suppresses_repeated_schedule_failures(): void
    {
        Http::fake([
            '*schedule' => Http::response([], 500),
            '*standings' => Http::response($this->standings()),
        ]);

        $this->service()->getData();
        $this->service()->getData();

        Mail::assertSent(ExternalDataSourceFailureMail::class, function ($mail) {
            return $mail->operation === 'world-cup-schedule';
        });
        $this->assertCount(1, Mail::sent(ExternalDataSourceFailureMail::class)->filter(
            fn ($mail) => $mail->operation === 'world-cup-schedule'
        ));
    }

    public function test_stale_cache_from_another_season_is_not_used(): void
    {
        Cache::put('sportsnext-football.stale.season-9999.schedule', $this->schedule('Feil sesong'), now()->addDays(7));
        Http::fake([
            '*schedule' => Http::response([], 500),
            '*standings' => Http::response($this->standings()),
        ]);

        $result = $this->service()->getData();

        $this->assertSame([], $result['schedule']['events']);
    }

    private function staleKey(string $endpoint): string
    {
        return 'sportsnext-football.stale.season-7767.'.$endpoint;
    }

    private function schedule(string $homeName = 'Norge'): array
    {
        return [
            'participants' => [
                1 => ['name' => $homeName, 'countryCode' => 'nor'],
                2 => ['name' => 'Sverige', 'countryCode' => 'swe'],
            ],
            'events' => [[
                'startDate' => '2026-06-11T18:00:00Z',
                'participantIds' => [1, 2],
                'status' => ['type' => 'scheduled'],
                'results' => [],
                'tournament' => ['groupName' => 'A', 'phaseType' => 'group', 'stage' => 'groupStage', 'stageName' => 'Gruppe A', 'name' => 'Fotball-VM'],
                'winners' => [],
            ]],
        ];
    }

    private function standings(string $name = 'Norge'): array
    {
        return [
            'participants' => [1 => ['name' => $name, 'countryCode' => 'nor']],
            'standings' => [[
                'groupName' => 'A',
                'teamStandings' => [[
                    'teamId' => 1,
                    'rank' => 1,
                    'played' => 1,
                    'wins' => 1,
                    'draws' => 0,
                    'losses' => 0,
                    'goalsFor' => 2,
                    'goalsAgainst' => 0,
                    'points' => 3,
                ]],
            ]],
        ];
    }

    private function service(): FootballWorldCupService
    {
        return app(FootballWorldCupService::class);
    }
}
