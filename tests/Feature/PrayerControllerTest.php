<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PrayerControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-09-08 12:00:00', 'Europe/Oslo'));
        Mail::fake();
        config()->set('feedback.notification_email', 'varsling@example.test');
        config()->set('services.prayer_times.api_token', 'test-token');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_current_previous_and_twelve_months_ahead_are_accepted(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay()])]);

        $this->get('/bonnetider?year=2026&month=9')->assertOk();
        $this->get('/bonnetider?year=2026&month=8')->assertOk();
        $this->get('/bonnetider?year=2027&month=9')->assertOk();

        Http::assertSentCount(3);
        Mail::assertNothingSent();
    }

    public function test_invalid_periods_redirect_without_calling_bonnetid_or_notifying(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([], 500)]);

        foreach ([
            '/bonnetider?year=2026&month=0',
            '/bonnetider?year=2026&month=13',
            '/bonnetider?year=1927&month=9',
            '/bonnetider?year=2027&month=10',
        ] as $url) {
            $this->get($url)->assertRedirect('/bonnetider?year=2026&month=9');
        }

        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_invalid_period_redirects_to_the_matching_prison_current_month_page(): void
    {
        Http::fake();

        $this->get('/bonnetider?year=1927&month=9')
            ->assertRedirect('/bonnetider?year=2026&month=9');
        $this->get('/bonnetider-ilseng?year=1927&month=9')
            ->assertRedirect('/bonnetider-ilseng?year=2026&month=9');

        Http::assertNothingSent();
    }

    public function test_invalid_print_url_redirects_to_the_normal_current_month_page(): void
    {
        Http::fake();

        $this->get('/bonnetider/utskrift?year=1927&month=9')
            ->assertRedirect('/bonnetider?year=2026&month=9');
        $this->get('/bonnetider-ilseng/utskrift?year=2027&month=10')
            ->assertRedirect('/bonnetider-ilseng?year=2026&month=9');

        Http::assertNothingSent();
        Mail::assertNothingSent();
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
            ->assertSee('22:12')
            ->assertSee('const prayerTimesUrl = "\/bonnetider?month=9\\u0026year=2026";', false)
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('window.location.replace(prayerTimesUrl);', false)
            ->assertSee('window.print();', false);
    }

    public function test_ilseng_print_returns_to_the_same_month_view(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([[
            'date' => '01-09-2026',
            'fajr' => '03:39',
            'duhr' => '13:29',
            'asr' => '17:02',
            'maghrib' => '20:30',
            'isha' => '22:12',
        ]])]);

        $this->get('/bonnetider-ilseng/utskrift?year=2026&month=9')
            ->assertOk()
            ->assertSee('const prayerTimesUrl = "\/bonnetider-ilseng?month=9\\u0026year=2026";', false)
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('window.location.replace(prayerTimesUrl);', false);
    }

    private function bonnetidDay(): array
    {
        return [
            'location' => 'Ringerike',
            'date' => '01-09-2026',
            'fajr' => '03:39',
            'duhr' => '13:29',
            'asr' => '17:02',
            'maghrib' => '20:30',
            'isha' => '22:12',
        ];
    }
}
