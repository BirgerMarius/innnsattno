<div class="daily-word">
    <strong class="daily-word__title"><a href="{{ route('prison.daily-word.index', ['prison' => app(\App\Services\PrisonContext::class)->prison()['slug']]) }}">{{ $headerMessage['title'] }}</a></strong>
    <a class="daily-word__print-link" href="{{ route('prison.daily-word.print', ['prison' => app(\App\Services\PrisonContext::class)->prison()['slug'], 'word' => $headerMessage['id']]) }}" data-daily-word-print>
        <span class="daily-word__text">{{ $headerMessage['message'] }}</span>
        <span class="daily-word__print-hint">Trykk for utskrift</span>
    </a>
</div>
