<?php

namespace Tests\Feature;

use Tests\TestCase;

class VisitationTest extends TestCase
{
    public function testVisitationPageIsAvailable()
    {
        $response = $this->get(route('visitation.index'));

        $response
            ->assertStatus(200)
            ->assertSee('Visitasjon')
            ->assertSee('Trekk celle');
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
