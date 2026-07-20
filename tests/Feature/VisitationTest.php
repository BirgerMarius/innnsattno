<?php

namespace Tests\Feature;

use Tests\TestCase;

class VisitationTest extends TestCase
{
    public function testVisitationPageIncludesCellDrawAssetsAndData()
    {
        $response = $this->get(route('visitation.index'));

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
        $response = $this->get('/visitasjon');

        foreach (['A', 'B', 'C', 'D'] as $department) {
            $response
                ->assertSee('Avdeling '.$department)
                ->assertSee($department.'-101');
        }
    }

    public function testFrontPageLinksToVisitationAndKeepsOtherMainChoices()
    {
        $response = $this->get(route('tv'));

        $response
            ->assertStatus(200)
            ->assertSee('Visitasjonsrullett')
            ->assertSee('href="'.route('visitation.index').'"', false)
            ->assertDontSee('href="/fotball"', false);

        foreach ([
            'Skriv ut TV-guide for i dag - Ringerike fengsel',
            'Skriv ut TV-guide for i dag - Ilseng fengsel',
            'Bønnetider – Ringerike fengsel',
            'Bønnetider – Ilseng fengsel',
            'Premier League 2026/27',
            'Eliteserien 2026',
            'Tidsfordriv – Sudoku',
            'Tidsfordriv – Ordjakt',
            'Månedskalender – For utskrift',
        ] as $frontPageChoice) {
            $response->assertSee($frontPageChoice);
        }
    }
}
