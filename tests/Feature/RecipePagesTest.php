<?php

namespace Tests\Feature;

use App\Services\RecipeCatalog;
use Tests\TestCase;

class RecipePagesTest extends TestCase
{
    public function test_catalogue_contains_twenty_recipes_in_three_categories(): void
    {
        $recipes = app(RecipeCatalog::class)->all();
        $this->assertCount(20, $recipes);
        $this->assertSame(['Matretter', 'Brød og rundstykker', 'Enkel bakst og kaker'], array_values(array_unique(array_column($recipes, 'category'))));
        $this->assertCount(20, array_unique(array_column($recipes, 'slug')));
    }

    public function test_index_has_recipes_selection_and_progressive_content(): void
    {
        $this->get(route('recipes.index'))
            ->assertOk()
            ->assertSee('Mat og oppskrifter')
            ->assertSee('Stekt ris med egg og grønnsaker')
            ->assertSee('Skriv ut valgte')
            ->assertSee('Maks 15 minutter');
    }

    public function test_recipe_and_print_routes_use_the_catalogue(): void
    {
        $this->get(route('recipes.show', 'stekt-ris-med-egg-og-gronnsaker'))
            ->assertOk()->assertSee('Ikke bruk ris som har stått framme.')->assertSee('Skriv ut oppskriften');
        $this->get(route('recipes.print', 'stekt-ris-med-egg-og-gronnsaker'))
            ->assertOk()->assertSee('window.print()', false)->assertSee('innsatt.no')->assertDontSee('INNSATT.NO');
    }

    public function test_unknown_recipe_and_invalid_combined_print_selection_return_404(): void
    {
        $this->get('/mat-oppskrifter/finnes-ikke')->assertNotFound();
        $this->get(route('recipes.print-selected', ['oppskrift' => ['stekt-ris-med-egg-og-gronnsaker', 'finnes-ikke']]))->assertNotFound();
    }

    public function test_combined_print_starts_each_recipe_as_a_print_item(): void
    {
        $response = $this->get(route('recipes.print-selected', ['oppskrift' => ['omelett-med-ost-og-gronnsaker', 'havrekjeks']]));
        $response->assertOk()->assertSee('Omelett med ost og grønnsaker')->assertSee('Havrekjeks');
        $this->assertSame(2, substr_count($response->getContent(), 'recipe-print-item'));
    }
}
