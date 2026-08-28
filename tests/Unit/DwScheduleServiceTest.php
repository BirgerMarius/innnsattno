<?php

namespace Tests\Unit;

use App\Services\DwScheduleService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DwScheduleServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_normalizes_dw_programs_for_the_print_view(): void
    {
        Http::fake([
            'www.dw.com/graph-api/en/livestream/english' => Http::response($this->response([
                ['startDate' => '2026-07-15T20:00:00Z', 'program' => ['name' => 'DW News']],
                ['startDate' => '2026-07-15T20:30:00Z', 'program' => ['name' => 'The Day']],
            ]), 200),
        ]);

        $channel = app(DwScheduleService::class)->channelForDate(Carbon::parse('2026-07-15', 'Europe/Oslo'));

        $this->assertSame('DW News', $channel['channel']['name']);
        $this->assertSame([
            ['startsAt' => '2026-07-15T20:00:00+00:00', 'title' => ['title' => 'DW News']],
            ['startsAt' => '2026-07-15T20:30:00+00:00', 'title' => ['title' => 'The Day']],
        ], $channel['listings']);
        Http::assertSent(function ($request) {
            return $request->hasHeader('Accept', 'application/json')
                && $request->hasHeader('User-Agent', 'innsatt.no TV-guide/1.0');
        });
    }

    public function test_it_filters_by_oslo_date_across_winter_and_summer_time(): void
    {
        Http::fake([
            'www.dw.com/graph-api/en/livestream/english' => Http::response($this->response([
                ['startDate' => '2026-01-15T23:30:00Z', 'program' => ['name' => 'Winter midnight']],
                ['startDate' => '2026-07-15T21:30:00Z', 'program' => ['name' => 'Summer evening']],
                ['startDate' => '2026-07-15T22:30:00Z', 'program' => ['name' => 'Summer midnight']],
            ]), 200),
        ]);

        $service = app(DwScheduleService::class);

        $winter = $service->channelForDate(Carbon::parse('2026-01-16', 'Europe/Oslo'));
        $summer = $service->channelForDate(Carbon::parse('2026-07-15', 'Europe/Oslo'));
        $nextSummerDay = $service->channelForDate(Carbon::parse('2026-07-16', 'Europe/Oslo'));

        $this->assertSame('Winter midnight', $winter['listings'][0]['title']['title']);
        $this->assertSame('Summer evening', $summer['listings'][0]['title']['title']);
        $this->assertSame('Summer midnight', $nextSummerDay['listings'][0]['title']['title']);
    }

    public function test_it_returns_null_for_invalid_or_unavailable_dw_responses(): void
    {
        Http::fake([
            'www.dw.com/graph-api/en/livestream/english' => Http::response('not json', 200),
        ]);

        $this->assertNull(app(DwScheduleService::class)->channelForDate(Carbon::parse('2026-07-15', 'Europe/Oslo')));

        Cache::flush();
        Http::fake([
            'www.dw.com/graph-api/en/livestream/english' => Http::response([], 503),
        ]);

        $this->assertNull(app(DwScheduleService::class)->channelForDate(Carbon::parse('2026-07-15', 'Europe/Oslo')));

        Cache::flush();
        Http::fake([
            'www.dw.com/graph-api/en/livestream/english' => Http::response($this->response([]), 200),
        ]);

        $this->assertNull(app(DwScheduleService::class)->channelForDate(Carbon::parse('2026-07-15', 'Europe/Oslo')));

        Cache::flush();
        Http::fake(fn () => throw new ConnectionException('Timed out'));

        $this->assertNull(app(DwScheduleService::class)->channelForDate(Carbon::parse('2026-07-15', 'Europe/Oslo')));
    }

    private function response(array $slots): array
    {
        return [
            'data' => [
                'livestreamChannels' => [[
                    'nextTimeSlots' => $slots,
                ]],
            ],
        ];
    }
}
