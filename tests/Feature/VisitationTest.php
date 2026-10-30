<?php

namespace Tests\Feature;

use Tests\TestCase;

class VisitationTest extends TestCase
{
    public function testVisitationPageIncludesCellDrawAssetsAndData()
    {
        $response = $this->get(route('prison.visitation', 'ringerike'));

        $response
            ->assertStatus(200)
            ->assertSee('Trekk celle')
            ->assertSee(asset('css/visitation.css'), false)
            ->assertSee(asset('js/visitation.js'), false)
            ->assertSee('id="visitationDepartments"', false)
            ->assertSee('type="application/json"', false)
            ->assertSee('A-101');
    }

    public function testVerifiedDepartmentsAndCellListsAreShown()
    {
        $response = $this->get('/ringerike/visitasjon');

        foreach (['A', 'B', 'C', 'D'] as $department) {
            $response
                ->assertSee('Avdeling '.$department)
                ->assertSee($department.'-101');
        }
    }

    public function testVisitationIsOnlyAvailableForRingerike(): void
    {
        $this->get('/visitasjon?prison=ilseng')
            ->assertRedirect('/ringerike/visitasjon');

        $this->get('/ringerike/visitasjon?prison=ilseng')
            ->assertOk()
            ->assertSee('data-prison-context="ringerike"', false);

        $this->get('/ilseng/visitasjon')->assertNotFound();
        $this->get('/kongsvinger/visitasjon')->assertNotFound();
    }

    public function testRingerikeFrontPageShowsVisitationAndKeepsTheCurrentMainChoices(): void
    {
        $response = $this->get('/ringerike');

        $response
            ->assertStatus(200)
            ->assertSee('Spinn hjulet')
            ->assertSee('Visitasjonsrullett – Ringerike fengsel')
            ->assertSee('href="'.route('prison.visitation', 'ringerike').'"', false)
            ->assertSee('href="/oppdrag?prison=ringerike"', false)
            ->assertSee(
                asset('css/custom/app.css').'?v='.filemtime(public_path('css/custom/app.css')),
                false
            )
            ->assertDontSee('href="/fotball"', false);

        foreach ([
            'Skriv ut TV-guide – Ringerike fengsel',
            'Bønnetider – Ringerike fengsel',
            'Værmelding – Ringerike fengsel',
            'Premier League 2026/27',
            'Eliteserien 2026',
            'Tidsfordriv',
            'Månedskalender – For utskrift',
        ] as $frontPageChoice) {
            $response->assertSee($frontPageChoice);
        }

        $response->assertSee('href="/ringerike/ukens-fotball-pa-tv"', false)
            ->assertSee('href="/ringerike/premier-league"', false)
            ->assertSee('href="/tidsfordriv?prison=ringerike"', false);
    }

    public function testOtherPrisonFrontPagesDoNotShowVisitation(): void
    {
        foreach (['ilseng', 'kongsvinger'] as $prison) {
            $this->get('/'.$prison)
                ->assertOk()
                ->assertDontSee('Visitasjonsrullett')
                ->assertDontSee('/ringerike/visitasjon', false);
        }
    }
}
