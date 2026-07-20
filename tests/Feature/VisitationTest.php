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

    public function testVisitationIsNotLinkedFromFrontPage()
    {
        $this->get(route('tv'))
            ->assertStatus(200)
            ->assertDontSee('/visitasjon');
    }
}
