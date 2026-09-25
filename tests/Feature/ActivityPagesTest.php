<?php

namespace Tests\Feature;

use Tests\TestCase;

class ActivityPagesTest extends TestCase
{
    public function test_activity_index_lists_all_initial_games(): void
    {
        $this->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Spillforslag')
            ->assertSee('7-er-kabal')
            ->assertSee('Vri åtter')
            ->assertSee('Krig')
            ->assertSee('Svarteper')
            ->assertSee('Fisk')
            ->assertSee('Sjakk')
            ->assertSee('Dam')
            ->assertSee('Backgammon');
    }

    public function test_each_activity_has_rules_and_a_printable_page(): void
    {
        foreach (['sju-er-kabal', 'vri-atter', 'krig', 'svarteper', 'fisk', 'sjakk', 'dam', 'backgammon'] as $activity) {
            $this->get(route('activities.show', $activity))
                ->assertOk()
                ->assertSee('Antall deltakere')
                ->assertSee('Du trenger')
                ->assertSee('Sett opp spillet')
                ->assertSee('Slik spiller dere')
                ->assertSee('Slik avsluttes spillet')
                ->assertSee('Eksempel')
                ->assertSee(route('activities.print', $activity), false);

            $this->get(route('activities.print', $activity))
                ->assertOk()
                ->assertSee('window.print()', false)
                ->assertSee('window.location.replace(returnUrl)', false)
                ->assertDontSee('INNSATT.NO');
        }
    }

    public function test_chess_and_backgammon_include_their_important_rules(): void
    {
        $this->get(route('activities.show', 'sjakk'))
            ->assertOk()
            ->assertSee('Sjakk matt')
            ->assertSee('Patt')
            ->assertSee('Rokade')
            ->assertSee('En passant')
            ->assertSee('Bondeforvandling');

        $this->get(route('activities.show', 'backgammon'))
            ->assertOk()
            ->assertSee('Startoppsett for mørk')
            ->assertSee('Dobbelt kast')
            ->assertSee('baren')
            ->assertSee('ta dem ut');
    }

    public function test_unknown_activity_returns_not_found(): void
    {
        $this->get('/spillforslag/ukjent-spill')->assertNotFound();
    }
}
