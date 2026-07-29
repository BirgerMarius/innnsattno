<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpinWheelTest extends TestCase
{
    public function test_oppdrag_page_contains_the_wheel_flow_and_assets(): void
    {
        $response = $this->get('/oppdrag');

        $response->assertOk()
            ->assertSee('css/spin.css')
            ->assertSee('js/wheel.js')
            ->assertSee('js/spin.js')
            ->assertSee('Spinn hjulet')
            ->assertSee('Siste person igjen')
            ->assertSee('Fortsatt med')
            ->assertSee('Slått ut')
            ->assertSee('id="eliminationModal"', false)
            ->assertSee('id="eliminationScene"', false)
            ->assertSee('id="winnerModal"', false)
            ->assertSee('id="winnerScene"', false)
            ->assertSee('id="nextRoundButton"', false)
            ->assertDontSee('result-banner')
            ->assertDontSee('id="result"', false)
            ->assertDontSee('La hjulet bestemme');
    }
}
