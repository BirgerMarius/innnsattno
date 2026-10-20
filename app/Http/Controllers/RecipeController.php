<?php

namespace App\Http\Controllers;

use App\Services\RecipeCatalog;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index(RecipeCatalog $catalog) { return view('recipes.index', ['recipes' => $catalog->all()]); }
    public function show(string $recipe, RecipeCatalog $catalog) { return view('recipes.show', ['recipe' => $this->recipe($recipe, $catalog)]); }
    public function print(string $recipe, RecipeCatalog $catalog) { return view('recipes.print', ['recipes' => [$this->recipe($recipe, $catalog)], 'returnUrl' => route('recipes.show', $recipe, false)]); }
    public function printSelected(Request $request, RecipeCatalog $catalog)
    {
        $slugs = $request->query('oppskrift', []);
        abort_unless(is_array($slugs) && count($slugs) && count($slugs) <= 20, 404);
        $recipes = [];
        foreach (array_unique($slugs) as $slug) { abort_unless(is_string($slug) && $catalog->find($slug), 404); $recipes[] = $catalog->find($slug); }
        return view('recipes.print', ['recipes' => $recipes, 'returnUrl' => route('recipes.index', [], false)]);
    }
    private function recipe(string $slug, RecipeCatalog $catalog): array { abort_unless($recipe = $catalog->find($slug), 404); return $recipe; }
}
