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
            'date' => '01-09-2026',
            'fajr' => '03:39',
            'duhr' => '13:29',
            'asr' => '17:02',
            'maghrib' => '20:30',
            'isha' => '22:12',
        ]])]);

        $this->get('/bonnetider?year=2026&month=9')
            ->assertOk()
            ->assertSee('01-09-2026')
            ->assertSee('03:39')
            ->assertSee('13:29')
            ->assertSee('17:02')
            ->assertSee('20:30')
            ->assertSee('22:12');

        $this->get('/bonnetider/utskrift?year=2026&month=9')
            ->assertOk()
            ->assertSee('01-09-2026')
            ->assertSee('03:39')
            ->assertSee('13:29')
            ->assertSee('17:02')
            ->assertSee('20:30')
            ->assertSee('22:12');
    }
}
