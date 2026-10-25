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
                ->assertSee('site-header--has-message', false);
        }

        $this->travelTo(CarbonImmutable::parse('2026-10-26 00:00:00', 'Europe/Oslo'));
        $this->get(route('tv'))->assertOk()
            ->assertDontSee('Husk å stille klokken')
            ->assertSee('Dagens ord');
    }

    public function testHomepageKeepsTheForwardClockChangeReminderInTheSharedHeader(): void
    {
        Http::fake();
        $this->travelTo(CarbonImmutable::parse('2027-03-27 12:00:00', 'Europe/Oslo'));

        $this->get(route('tv'))->assertOk()
            ->assertSee('Husk å stille klokken frem')
            ->assertSee('02:00 → 03:00')
            ->assertSee('site-header__message-slot', false);
    }

    public function testSharedHeaderFallsBackToDailyWordWithoutReminderData(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'Europe/Oslo'));
        $header = view('partials.header')->render();

        $this->assertStringNotContainsString('clock-change-reminder', $header);
        $this->assertStringContainsString('Dagens ord', $header);
        $this->assertStringContainsString('data-daily-word-print', $header);
    }

    public function testHeaderMessageEndpointUsesTheSameClockPriorityAsTheHeader(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-25 12:00:00', 'Europe/Oslo'));
        $this->get(route('header-message.show'))
            ->assertOk()
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'Husk å stille klokken tilbake'));

        $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'Europe/Oslo'));
        $this->get(route('header-message.show'))
            ->assertOk()
            ->assertJsonPath('html', fn (string $html) => str_contains($html, 'Dagens ord'));
    }
}
