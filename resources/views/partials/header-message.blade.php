@if (($headerMessage['type'] ?? null) === 'clock-change')
    @include('partials.clock-change-reminder', ['clockChangeReminder' => $headerMessage, 'compact' => true])
@elseif (($headerMessage['type'] ?? null) === 'daily-word')
    @include('partials.daily-word')
@endif
