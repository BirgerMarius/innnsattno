<?php

namespace Tests\Feature;

use Tests\TestCase;

class ActivityRoomPageTest extends TestCase
{
    public function test_activity_room_page_is_prison_prefixed_and_excludes_clarity(): void
    {
        foreach (['ringerike', 'ilseng', 'kongsvinger'] as $prison) {
            $this->get('/'.$prison.'/aktivitetsrom')
                ->assertOk()
                ->assertSee('Aktivitetsrom – test')
                ->assertSee('Midlertidig oversikt. Opplysningene forsvinner når siden oppdateres eller lukkes.')
                ->assertSee('Tilbakemeldinger og ønsker')
                ->assertSee('Send tilbakemelding')
                ->assertSee('data-activity-rooms', false)
                ->assertSee('beforeunload', false)
                ->assertDontSee('www.clarity.ms/tag');
        }
    }

    public function test_clock_links_to_the_current_prisons_activity_room_page_in_a_new_tab(): void
    {
        $this->get('/ilseng')
            ->assertOk()
            ->assertSee(route('prison.activity-rooms', 'ilseng'), false)
            ->assertSee('target="_blank"', false)
            ->assertSee('data-oslo-clock', false);
    }

    public function test_activity_room_client_does_not_use_persistent_browser_storage(): void
    {
        $view = file_get_contents(resource_path('views/activity-rooms/index.blade.php'));

        $this->assertStringContainsString('const rooms=[]', $view);
        $this->assertStringContainsString('new Set()', $view);
        $this->assertStringNotContainsString('localStorage', $view);
        $this->assertStringNotContainsString('sessionStorage', $view);
        $this->assertStringNotContainsString('indexedDB', $view);
        $this->assertStringContainsString('fetch(root.dataset.feedbackUrl', $view);
        $this->assertStringContainsString('data-csrf-token', $view);
    }

    public function test_live_updates_do_not_replace_booking_forms(): void
    {
        $view = file_get_contents(resource_path('views/activity-rooms/index.blade.php'));

        $this->assertStringContainsString('window.setInterval(refreshLiveData,1000)', $view);
        $this->assertStringContainsString('function refreshLiveData()', $view);
        $this->assertStringContainsString('function renderRooms(){roomGrid.replaceChildren', $view);
        $this->assertStringNotContainsString('window.setInterval(renderRooms,1000)', $view);
        $this->assertStringContainsString('data-activity-feedback', $view);
        $this->assertStringNotContainsString('window.setInterval(renderRooms,1000)', $view);
    }

    public function test_activity_room_time_and_duration_controls_are_available(): void
    {
        $view = file_get_contents(resource_path('views/activity-rooms/index.blade.php'));

        $this->assertStringContainsString('for(let m=420;m<=1200;m+=15)', $view);
        $this->assertStringContainsString('Starttid må være mellom 07:00 og 20:00.', $view);
        $this->assertStringContainsString('Egendefinert varighet (minutter)', $view);
        $this->assertStringContainsString('Beregnet sluttid:', $view);
        $this->assertStringContainsString('presetDurations=[15,30,45,60,90,120]', $view);
    }
}
