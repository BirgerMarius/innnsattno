<?php

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClockChangeReminderTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public function testHomepageShowsReminderOnlyOnTheTwoRelevantDays(): void
    {
        Http::fake();

        foreach (['2026-10-24 12:00:00', '2026-10-25 12:00:00'] as $date) {
            $this->travelTo(CarbonImmutable::parse($date, 'Europe/Oslo'));
            $this->get(route('tv'))->assertOk()
                ->assertSee('Husk å stille klokken tilbake')
                ->assertSee('03:00 → 02:00')
                ->assertSee('site-header--clock-change', false);
        }

        $this->travelTo(CarbonImmutable::parse('2026-10-26 00:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()->assertDontSee('Husk å stille klokken');
    }

    public function testSharedHeaderWorksWithoutReminderData(): void
    {
        $this->assertStringNotContainsString('clock-change-reminder', view('partials.header')->render());
    }
}
