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

    public function test_current_previous_and_next_month_are_accepted(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay()])]);

        $this->get('/bonnetider?year=2026&month=9')->assertOk();
        $this->get('/bonnetider?year=2026&month=8')->assertOk();
        $this->get('/bonnetider?year=2026&month=10')->assertOk();

        Http::assertSentCount(3);
        Mail::assertNothingSent();
    }

    public function test_prayer_pages_allow_normal_traffic_and_rate_limit_each_ip_independently(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay()])]);

        for ($request = 0; $request < 60; $request++) {
            $this->getFromIp('/bonnetider?year=2026&month=9', '203.0.113.10')->assertOk();
        }

        $this->getFromIp('/bonnetider?year=2026&month=9', '203.0.113.10')->assertStatus(429);
        $this->getFromIp('/bonnetider-ilseng?year=2026&month=9', '203.0.113.11')->assertOk();

        Mail::assertNothingSent();
    }

    public function test_prayer_print_pages_have_a_stricter_separate_rate_limit(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay()])]);

        for ($request = 0; $request < 20; $request++) {
            $this->getFromIp('/bonnetider/utskrift?year=2026&month=9', '203.0.113.20')->assertOk();
        }

        $this->getFromIp('/bonnetider/utskrift?year=2026&month=9', '203.0.113.20')->assertStatus(429);
        $this->getFromIp('/bonnetider?year=2026&month=9', '203.0.113.20')->assertOk();

        Mail::assertNothingSent();
    }

    public function test_invalid_periods_redirect_without_calling_bonnetid_or_notifying(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([], 500)]);

        foreach ([
            '/bonnetider?year=2026&month=0',
            '/bonnetider?year=2026&month=13',
            '/bonnetider?year=1927&month=9',
            '/bonnetider?year=2026&month=11',
            '/bonnetider?year=2027&month=7',
            '/bonnetider?year=2027&month=10',
        ] as $url) {
            $this->get($url)->assertRedirect('/ringerike/bonnetider?year=2026&month=9');
        }

        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_invalid_period_redirects_to_the_matching_prison_current_month_page(): void
    {
        Http::fake();

        $this->get('/bonnetider?year=1927&month=9')
            ->assertRedirect('/ringerike/bonnetider?year=2026&month=9');
        $this->get('/bonnetider-ilseng?year=1927&month=9')
            ->assertRedirect('/ilseng/bonnetider?year=2026&month=9');

        Http::assertNothingSent();
    }

    public function test_invalid_print_url_redirects_to_the_normal_current_month_page(): void
    {
        Http::fake();

        $this->get('/bonnetider/utskrift?year=1927&month=9')
            ->assertRedirect('/ringerike/bonnetider?year=2026&month=9');
        $this->get('/bonnetider-ilseng/utskrift?year=2027&month=10')
            ->assertRedirect('/ilseng/bonnetider?year=2026&month=9');

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
            ->assertSee('const returnUrl = "/ringerike/bonnetider?year=2026&month=9";', false)
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('window.location.replace(returnUrl);', false)
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
            ->assertSee('const returnUrl = "/ilseng/bonnetider?year=2026&month=9";', false)
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('window.location.replace(returnUrl);', false);
    }

    public function test_ringerike_front_page_prayer_print_flow_keeps_the_prefix_when_query_context_conflicts(): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay()])]);

        $frontPage = $this->get('/ringerike')->assertOk();
        $this->assertStringContainsString('href="/ringerike/bonnetider"', $frontPage->getContent());

        $prayerPage = $this->get('/ringerike/bonnetider?prison=ilseng&year=2026&month=9')
            ->assertOk()
            ->assertSee('Bønnetider Ringerike fengsel')
            ->assertDontSee('Bønnetider Ilseng fengsel');

        $this->assertStringContainsString('href="/ringerike/bonnetider/utskrift?year=2026&amp;month=9"', $prayerPage->getContent());

        $this->get('/ringerike/bonnetider/utskrift?prison=ilseng&year=2026&month=9')
            ->assertOk()
            ->assertSee('Bønnetider – Ringerike fengsel')
            ->assertSee('const returnUrl = "/ringerike/bonnetider?year=2026&month=9";', false);

        $this->get('/ringerike/bonnetider?prison=ukjent&year=2026&month=9')
            ->assertOk()
            ->assertSee('Bønnetider Ringerike fengsel')
            ->assertDontSee('Bønnetider Ilseng fengsel');
    }

    /** @dataProvider prisonPrayerRoutes */
    public function test_prefixed_prayer_pages_and_prints_keep_their_prison_and_handle_missing_data(string $prison, string $name, bool $hasProviderLocation): void
    {
        Http::fake(['api.bonnetid.no/*' => Http::response([$this->bonnetidDay(['location' => $name])])]);

        $page = $this->get('/'.$prison.'/bonnetider?year=2026&month=9')
            ->assertOk()
            ->assertSee('Bønnetider '.$name);
        $this->assertStringContainsString('href="/'.$prison.'/bonnetider/utskrift?year=2026&amp;month=9"', $page->getContent());

        $print = $this->get('/'.$prison.'/bonnetider/utskrift?year=2026&month=9')
            ->assertOk()
            ->assertSee('Bønnetider – '.$name);

        if ($hasProviderLocation) {
            $print->assertSee('01-09-2026');
        } else {
            $print->assertSee('under klargjøring');
            Http::assertNothingSent();
        }
    }

    public function prisonPrayerRoutes(): array
    {
        return [
            'Ringerike' => ['ringerike', 'Ringerike fengsel', true],
            'Ilseng' => ['ilseng', 'Ilseng fengsel', true],
            'Kongsvinger uten leverandør-ID' => ['kongsvinger', 'Kongsvinger fengsel', false],
        ];
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

    private function getFromIp(string $url, string $ip)
    {
        return $this->call('GET', $url, [], [], [], ['REMOTE_ADDR' => $ip]);
    }
}
