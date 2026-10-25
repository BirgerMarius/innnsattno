<?php

namespace Tests\Unit;

use App\Services\DailyWordService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DailyWordServiceTest extends TestCase
{
    public function testItUsesTheOsloCalendarDateAndKeepsTheSameWordThroughoutTheDay(): void
    {
        $service = app(DailyWordService::class);

        $fromUtc = $service->forDate(CarbonImmutable::parse('2025-12-31 23:30:00', 'UTC'));
        $lateInOslo = $service->forDate(CarbonImmutable::parse('2026-01-01 23:59:59', 'Europe/Oslo'));

        $this->assertSame('en-ting-av-gangen', $fromUtc['id']);
        $this->assertSame($fromUtc, $lateInOslo);
    }

    public function testItRotatesWithoutRepeatingOnTheNextDay(): void
    {
        $service = app(DailyWordService::class);
        $first = $service->forDate(CarbonImmutable::parse('2026-01-01', 'Europe/Oslo'));
        $next = $service->forDate(CarbonImmutable::parse('2026-01-02', 'Europe/Oslo'));
        $afterOneFullRotation = $service->forDate(CarbonImmutable::parse('2026-03-03', 'Europe/Oslo'));

        $this->assertNotSame($first['id'], $next['id']);
        $this->assertSame($first['id'], $afterOneFullRotation['id']);
        $this->assertGreaterThanOrEqual(60, count(config('daily_word.words')));
    }

    public function testItFindsAWordByItsStablePrintId(): void
    {
        $word = app(DailyWordService::class)->find('rolig-start');

        $this->assertSame('Det er lov å ta en rolig start.', $word['message']);
        $this->assertSame('rolig-start', $word['id']);
    }
}
