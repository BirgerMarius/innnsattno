<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTvPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Carbon::setTestNow(Carbon::parse('2026-09-21 10:00:00', 'Europe/Oslo'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_has_a_tv_print_date_picker_with_today_as_default_and_both_prisons(): void
    {
        $this->withSession(['admin_authenticated' => true])->get('/adm')
            ->assertOk()
            ->assertSee('TV-utskrift')
            ->assertSee('type="date"', false)
            ->assertSee('name="date" value="2026-09-21"', false)
            ->assertSee('<option value="ringerike">Ringerike</option>', false)
            ->assertSee('<option value="ilseng">Ilseng</option>', false)
            ->assertSee(route('admin.tv-print'), false);
    }

    public function test_admin_tv_print_requires_existing_admin_login(): void
    {
        $this->get('/admin/tv-utskrift?date=2026-09-24&prison=ringerike')
            ->assertRedirect(route('admin.login'));
    }

    public function test_selected_date_is_sent_to_ringerike_print_with_its_known_channel_list(): void
    {
        Http::fake([
            'tvguide.vg.no/*' => Http::response([], 200),
            'www.dw.com/graph-api/en/livestream/english' => Http::response($this->dwResponse(), 200),
        ]);

        $this->adminGet('2026-09-24', 'ringerike')
            ->assertOk()
            ->assertSee('24.09.2026');

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $channels = explode(',', $query['channels'] ?? '');

            return ($query['date'] ?? null) === '2026-09-24'
                && in_array('viasat-explore', $channels, true)
                && in_array('bbc-world-news', $channels, true)
                && ! in_array('v-film-premiere', $channels, true);
        });
    }

    public function test_selected_date_is_sent_to_ilseng_print_with_its_known_channel_list(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([], 200)]);

        $this->adminGet('2026-09-27', 'ilseng')
            ->assertOk()
            ->assertSee('27.09.2026');

        Http::assertSent(function ($request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);
            $channels = explode(',', $query['channels'] ?? '');

            return ($query['date'] ?? null) === '2026-09-27'
                && in_array('v-film-premiere', $channels, true)
                && in_array('bbc-nordic', $channels, true)
                && ! in_array('viasat-explore', $channels, true);
        });
    }

    public function test_invalid_prison_and_date_are_rejected(): void
    {
        $this->adminGet('2026-09-24', 'vilkarlig-kanal')
            ->assertRedirect()
            ->assertSessionHasErrors('prison');

        $this->adminGet('2026-02-30', 'ringerike')
            ->assertRedirect()
            ->assertSessionHasErrors('date');

        $this->adminGet('2026-10-05', 'ringerike')
            ->assertRedirect()
            ->assertSessionHasErrors('date');
    }

    public function test_explicit_date_keeps_the_norway_match_highlight(): void
    {
        Http::fake([
            'tvguide.vg.no/*' => Http::response([[
                'channel' => ['name' => 'TV 2 Direkte', 'slug' => 'tv2-direkte'],
                'listings' => [$this->norwayMatch('Norge - Danmark', '2026-09-24T18:35:00+00:00')],
            ]], 200),
            'www.dw.com/graph-api/en/livestream/english' => Http::response($this->dwResponse(), 200),
        ]);

        $this->adminGet('2026-09-24', 'ringerike')->assertOk()->assertSee('⚽ Norge – Danmark');

    }

    public function test_explicit_date_keeps_the_norway_match_highlight_for_ilseng(): void
    {
        Http::fake(['tvguide.vg.no/*' => Http::response([[
            'channel' => ['name' => 'TV 2 Direkte', 'slug' => 'tv2-direkte'],
            'listings' => [$this->norwayMatch('Norge - Portugal', '2026-09-27T18:35:00+00:00')],
        ]], 200)]);

        $this->adminGet('2026-09-27', 'ilseng')->assertOk()->assertSee('⚽ Norge – Portugal');
    }

    private function adminGet(string $date, string $prison)
    {
        return $this->withSession(['admin_authenticated' => true])
            ->get('/admin/tv-utskrift?date='.$date.'&prison='.$prison);
    }

    private function dwResponse(): array
    {
        return ['data' => ['attributes' => ['schedule' => ['nextTimeSlots' => []]]]];
    }

    private function norwayMatch(string $name, string $startsAt): array
    {
        return [
            'title' => ['id' => 572756, 'type' => 'sportsTitle', 'title' => 'UEFA Nations League', 'slug' => 'uefa-nations-league'],
            'sportsEvent' => ['name' => $name],
            'startsAt' => $startsAt,
            'isRerun' => false,
        ];
    }
}
