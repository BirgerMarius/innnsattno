<?php

namespace Tests\Unit;

use App\Services\HeaderMessageService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class HeaderMessageServiceTest extends TestCase
{
    public function testClockChangeReminderHasPriorityOverDailyWord(): void
    {
        $service = app(HeaderMessageService::class);

        $clockChange = $service->forDate(CarbonImmutable::parse('2026-10-25 12:00:00', 'Europe/Oslo'));
        $dailyWordAfterCutoff = $service->forDate(CarbonImmutable::parse('2026-10-25 13:00:00', 'Europe/Oslo'));
        $ordinaryDay = $service->forDate(CarbonImmutable::parse('2026-10-26 12:00:00', 'Europe/Oslo'));

        $this->assertSame('clock-change', $clockChange['type']);
        $this->assertSame('daily-word', $dailyWordAfterCutoff['type']);
        $this->assertSame('daily-word', $ordinaryDay['type']);
        $this->assertSame('Dagens ord', $ordinaryDay['title']);
        $this->assertSame(
            '2026-10-25 13:00:00',
            $service->nextChangeAt(CarbonImmutable::parse('2026-10-25 12:59:59', 'Europe/Oslo'))->format('Y-m-d H:i:s')
        );
        $this->assertNull($service->nextChangeAt(CarbonImmutable::parse('2026-10-25 13:00:00', 'Europe/Oslo')));
    }
}
