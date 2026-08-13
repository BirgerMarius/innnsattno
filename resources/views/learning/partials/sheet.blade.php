<article class="learning-sheet">
    <header class="learning-sheet-header">
        <div class="learning-sheet-icon" aria-hidden="true">@include('learning.partials.icon', ['icon' => $categoryData['icon']])</div>
        <div><p class="learning-kicker">{{ $categoryData['name'] }}</p><h1>{{ $sheetData['title'] }}</h1><p class="learning-lead">{{ $sheetData['intro'] }}</p></div>
    </header>

    <section class="learning-outcomes" aria-labelledby="outcomes"><h2 id="outcomes">Dette lærer du</h2><ul>@foreach($sheetData['learn'] as $item)<li>{{ $item }}</li>@endforeach</ul></section>

    <div class="learning-sheet-body">
        @foreach($sheetData['sections'] as $sectionIndex => $section)
            <section class="learning-section"><h2>{{ $section['title'] }}</h2>@foreach($section['paragraphs'] as $paragraph)<p>{{ $paragraph }}</p>@endforeach</section>
            @if($sectionIndex === 1)
                @include('learning.partials.figure', ['figure' => $sheetData['figure']])
            @endif
        @endforeach
        <aside class="learning-box"><h2>{{ $sheetData['box']['title'] }}</h2><p>{{ $sheetData['box']['text'] }}</p></aside>
        <aside class="learning-fact"><h2>Visste du at …</h2><p>{{ $sheetData['fact'] }}</p></aside>
        <section class="learning-questions"><h2>Test deg selv</h2><ol>@foreach($sheetData['questions'] as $question)<li>{{ $question }}</li>@endforeach</ol></section>
        <section class="learning-more" aria-labelledby="learn-more"><h2 id="learn-more">Vil du lære mer?</h2><ul>@foreach($sheetData['learnMore'] as $item)<li>{{ $item }}</li>@endforeach</ul></section>
    </div>
</article>
