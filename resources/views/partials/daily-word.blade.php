<a class="daily-word" href="{{ route('daily-word.print', ['word' => $headerMessage['id']]) }}" data-daily-word-print>
    <strong class="daily-word__title">{{ $headerMessage['title'] }}</strong>
    <span class="daily-word__text">{{ $headerMessage['message'] }}</span>
    <span class="daily-word__print-hint">Trykk for utskrift</span>
</a>
