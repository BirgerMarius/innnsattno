<article class="recipe-sheet">
    <header class="recipe-sheet-header">
        <p class="recipe-kicker">{{ $recipe['category'] }}</p>
        <h1>{{ $recipe['title'] }}</h1>
        <p>{{ $recipe['description'] }}</p>
        <dl class="recipe-meta"><div><dt>Arbeidstid</dt><dd>{{ $recipe['work'] }}</dd></div><div><dt>Samlet tid</dt><dd>{{ $recipe['total'] }}</dd></div><div><dt>Porsjoner/antall</dt><dd>{{ $recipe['yield'] }}</dd></div></dl>
    </header>
    <p class="recipe-equipment"><strong>Utstyr:</strong> {{ $recipe['equipment'] }}</p>
    <div class="recipe-content"><section><h2>Ingredienser</h2><ul class="recipe-ingredients">@foreach($recipe['ingredients'] as $ingredient)<li>{{ $ingredient }}</li>@endforeach</ul></section><section><h2>Slik gjør du</h2><ol class="recipe-steps">@foreach($recipe['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol></section></div>
    <div class="recipe-notes"><section><h2>Tips</h2><ul>@foreach($recipe['tips'] as $tip)<li>{{ $tip }}</li>@endforeach</ul></section><section><h2>Allergener</h2><p>{{ count($recipe['allergens']) ? implode(' · ', $recipe['allergens']) : 'Ingen av ingrediensene inneholder vanlige allergener. Sjekk alltid pakningen ved bytter.' }}</p></section></div>
    <footer class="recipe-sender">innsatt.no</footer>
</article>
