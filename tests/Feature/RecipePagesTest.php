<?php

namespace Tests\Feature;

use App\Services\RecipeCatalog;
use Tests\TestCase;

class RecipePagesTest extends TestCase
{
    public function test_catalogue_contains_ten_baking_recipes_in_two_categories(): void
    {
        $recipes = app(RecipeCatalog::class)->all();
        $this->assertCount(10, $recipes);
        $this->assertSame(['Brød og rundstykker', 'Enkel bakst og kaker'], array_values(array_unique(array_column($recipes, 'category'))));
        $this->assertCount(10, array_unique(array_column($recipes, 'slug')));
    }

    public function test_index_has_simple_categorised_list_and_print_selection(): void
    {
        $this->get(route('recipes.index'))
            ->assertOk()
            ->assertSee('Mat og oppskrifter')
            ->assertSee('Brød og rundstykker')
            ->assertSee('Rundstykker med bakepulver, uten heving')
            ->assertSee('Skriv ut valgte')
            ->assertDontSee('Maks 15 minutter')
            ->assertDontSee('Søk på navn eller ingrediens');
    }

    public function test_recipe_and_print_routes_use_the_catalogue(): void
    {
        $this->get(route('recipes.show', 'rundstykker-med-bakepulver'))
            ->assertOk()->assertSee('6 rundstykker')->assertSee('Skriv ut oppskriften');
        $this->get(route('recipes.print', 'rundstykker-med-bakepulver'))
            ->assertOk()->assertSee('window.print()', false)->assertSee('innsatt.no')->assertDontSee('INNSATT.NO');
    }

    public function test_unknown_recipe_and_invalid_combined_print_selection_return_404(): void
    {
        $this->get('/mat-oppskrifter/finnes-ikke')->assertNotFound();
        $this->get('/mat-oppskrifter/stekt-ris-med-egg-og-gronnsaker')->assertNotFound();
        $this->get(route('recipes.print-selected', ['oppskrift' => ['rundstykker-med-bakepulver', 'finnes-ikke']]))->assertNotFound();
    }

    public function test_combined_print_starts_each_recipe_as_a_print_item(): void
    {
        $response = $this->get(route('recipes.print-selected', ['oppskrift' => ['rundstykker-med-bakepulver', 'havrekjeks']]));
        $response->assertOk()->assertSee('Rundstykker med bakepulver, uten heving')->assertSee('Havrekjeks');
        $this->assertSame(2, substr_count($response->getContent(), 'recipe-print-item'));
    }
}
