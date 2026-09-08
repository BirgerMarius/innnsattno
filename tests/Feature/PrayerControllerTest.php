<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrayerControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('services.prayer_times.api_token', 'test-token');
    }

    public function test_prayer_pages_render_a_controlled_message_when_the_api_is_unavailable(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([], 500)]);

        $this->get('/bonnetider')->assertOk()->assertSee('Bønnetider kunne ikke hentes akkurat nå');
        $this->get('/bonnetider/utskrift')->assertOk()->assertSee('Bønnetider kunne ikke hentes akkurat nå');
        $this->get('/bonnetider-ilseng')->assertOk()->assertSee('Bønnetider kunne ikke hentes akkurat nå');
        $this->get('/bonnetider-ilseng/utskrift')->assertOk()->assertSee('Bønnetider kunne ikke hentes akkurat nå');
    }

    public function test_prayer_and_print_pages_render_the_existing_day_structure_for_a_valid_payload(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([[
            'location' => 'Ringerike',
            'date' => '2026-09-01',
            'fajr' => '04:11:00',
            'duhr' => '13:18:00',
            'asr' => '17:03:00',
            'maghrib' => '20:56:00',
            'isha' => '22:53:00',
        ]])]);

        $this->get('/bonnetider?year=2026&month=9')
            ->assertOk()
            ->assertSee('2026-09-01')
            ->assertSee('04:11:00')
            ->assertSee('13:18:00')
            ->assertSee('17:03:00')
            ->assertSee('20:56:00')
            ->assertSee('22:53:00');

        $this->get('/bonnetider/utskrift?year=2026&month=9')
            ->assertOk()
            ->assertSee('2026-09-01')
            ->assertSee('04:11:00')
            ->assertSee('13:18:00')
            ->assertSee('17:03:00')
            ->assertSee('20:56:00')
            ->assertSee('22:53:00');
    }
}
