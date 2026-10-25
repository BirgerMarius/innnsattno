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
        $ordinaryDay = $service->forDate(CarbonImmutable::parse('2026-10-26 12:00:00', 'Europe/Oslo'));

        $this->assertSame('clock-change', $clockChange['type']);
        $this->assertSame('daily-word', $ordinaryDay['type']);
        $this->assertSame('Dagens ord', $ordinaryDay['title']);
    }
}
