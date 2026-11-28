@extends('layouts.app')
@section('title', 'Kalender – innsatt.no')
@section('content')
<main class="container py-4 py-md-5 calendar-page">
    @include('partials.header')
    <a href="{{ route('prison.home', ['prison' => $prison]) }}">← Tilbake til forsiden</a>
    <h1 class="mt-4">Kalender</h1>
    <p>I dag er det uke {{ $today->isoWeek }}, {{ $today->isoWeek % 2 ? 'oddetallsuke' : 'partallsuke' }}.</p>
    <nav class="calendar-navigation" aria-label="Velg måned">
        @if ($month->year > 1970 || $month->month > 1)<a class="btn btn-outline-primary" href="{{ route('prison.calendar', ['prison' => $prison, 'year' => $month->subMonth()->year, 'month' => $month->subMonth()->month]) }}">← Forrige måned</a>@endif
        <a class="btn btn-outline-secondary" href="{{ route('prison.calendar', ['prison' => $prison]) }}">I dag</a>
        @if ($month->year < 2099 || $month->month < 12)<a class="btn btn-outline-primary" href="{{ route('prison.calendar', ['prison' => $prison, 'year' => $month->addMonth()->year, 'month' => $month->addMonth()->month]) }}">Neste måned →</a>@endif
    </nav>
    <form class="calendar-form" method="get" action="{{ route('prison.calendar', ['prison' => $prison]) }}">
        <label>Måned <select name="month">@foreach(range(1,12) as $number)<option value="{{ $number }}" @selected($number == $month->month)>{{ ucfirst($month->month($number)->locale('nb')->translatedFormat('F')) }}</option>@endforeach</select></label>
        <label>År <input type="number" name="year" min="1970" max="2099" value="{{ $month->year }}" required></label>
        <button class="btn btn-primary">Vis måned</button>
    </form>
    <h2>{{ ucfirst($month->locale('nb')->translatedFormat('F Y')) }}</h2>
    <div class="calendar-scroll"><table class="calendar-table">
        <caption>Kalender for {{ $month->locale('nb')->translatedFormat('F Y') }}. Dagens dato og inneværende uke er markert.</caption>
        <thead><tr><th scope="col">Uke</th>@foreach(['Mandag','Tirsdag','Onsdag','Torsdag','Fredag','Lørdag','Søndag'] as $weekday)<th scope="col">{{ $weekday }}</th>@endforeach</tr></thead>
        <tbody>@foreach($weeks as $week)<tr class="{{ $week['start']->isSameDay($today->startOfWeek()) ? 'calendar-current-week' : '' }}">
            <th scope="row">{{ $week['start']->isoWeek }} @if($week['start']->isSameDay($today->startOfWeek()))<span class="calendar-note">Inneværende uke</span>@endif</th>
            @foreach($week['days'] as $day)
                @php($key = $day->toDateString())
                <td class="{{ $day->month !== $month->month ? 'calendar-other-month' : '' }} {{ $day->isSameDay($today) ? 'calendar-today' : '' }} {{ ($data['selected'] ?? '') === $key ? 'calendar-selected' : '' }}">
                    <time datetime="{{ $key }}">{{ $day->day }}.{{ $day->month }}</time>
                    @if($day->isSameDay($today))<span class="calendar-note">I dag</span>@endif
                    @if(($data['selected'] ?? '') === $key)<span class="calendar-note">Valgt dato</span>@endif
                    @if(isset($holidays[$key]))<span class="calendar-holiday">{{ in_array($key, [$day->year.'-05-01', $day->year.'-05-17']) ? 'Offentlig høytidsdag' : 'Helligdag' }}: {{ $holidays[$key] }}</span>@elseif($day->dayOfWeek === 0)<span class="calendar-holiday">Helligdag: Søndag</span>@endif
                    @if(isset($flagDays[$key]))<span class="calendar-flag">Offisiell flaggdag: {{ $flagDays[$key] }}</span>@endif
                </td>
            @endforeach
        </tr>@endforeach</tbody>
    </table></div>
    <p class="mt-3"><strong>Neste særskilte helligdag eller offentlige høytidsdag:</strong> {{ $nextHoliday['name'] }}, {{ \Carbon\CarbonImmutable::parse($nextHoliday['date'], 'Europe/Oslo')->locale('nb')->translatedFormat('j. F Y') }}. <strong>Neste flaggdag:</strong> {{ $nextFlag['name'] }}, {{ $nextFlag['date']->locale('nb')->translatedFormat('j. F Y') }}.</p>
    <p>Flaggdager vises fra 2026. Stortingsvalgdagen er også offisiell flaggdag, men er ikke med i denne kalenderen. Kontroller <a href="{{ \App\Services\FlagDayService::OFFICIAL_OVERVIEW_URL }}">den offisielle oversikten over flaggdager</a>.</p>
    <section class="calendar-tools" aria-labelledby="tools-heading">
        <h2 id="tools-heading">Datoverktøy</h2>
        @if($errors->any())<div class="alert alert-danger">Kontroller datoene og feltene: {{ $errors->first() }}</div>@endif
        @if($error)<div class="alert alert-danger" role="alert">{{ $error }}</div>@endif
        @php($weekdays = ['Søndag','Mandag','Tirsdag','Onsdag','Torsdag','Fredag','Lørdag'])
        <div class="calendar-tool-grid">
            <section><h3>Hvor lenge til eller siden?</h3>
                <form method="get" class="calendar-tool-form"><input type="hidden" name="year" value="{{ $month->year }}"><input type="hidden" name="month" value="{{ $month->month }}"><input type="hidden" name="tool" value="distance">
                    <label>Dato <input type="date" name="date" min="1970-01-01" max="2099-12-31" value="{{ $data['date'] ?? $today->toDateString() }}" required></label><button class="btn btn-primary">Beregn</button>
                </form>
                @if(($result['type'] ?? '') === 'distance')<p class="calendar-result"><strong>{{ $result['days'] === 0 ? 'I dag' : $result['days'].' kalenderdager '.($result['past'] ? 'siden' : 'til') }}</strong> ({{ intdiv($result['days'], 7) }} uker og {{ $result['days'] % 7 }} dager). {{ ucfirst($result['date']->locale('nb')->translatedFormat('l j. F Y')) }}, uke {{ $result['date']->isoWeek }}.</p>@endif
            </section>
            <section><h3>Tell dager i en periode</h3>
                <form method="get" class="calendar-tool-form"><input type="hidden" name="year" value="{{ $month->year }}"><input type="hidden" name="month" value="{{ $month->month }}"><input type="hidden" name="tool" value="period">
                    <label>Fra <input type="date" name="from" min="1970-01-01" max="2099-12-31" value="{{ $data['from'] ?? $month->toDateString() }}" required></label>
                    <label>Til <input type="date" name="to" min="1970-01-01" max="2099-12-31" value="{{ $data['to'] ?? $month->endOfMonth()->toDateString() }}" required></label>
                    <label>Tell ukedag <select name="weekday">@foreach($weekdays as $n => $name)<option value="{{ $n }}" @selected(($data['weekday'] ?? 0) == $n)>{{ $name }}</option>@endforeach</select></label><button class="btn btn-primary">Tell dager</button>
                </form>
                @if(($result['type'] ?? '') === 'period')<p class="calendar-result"><strong>{{ $result['days'] }} kalenderdager</strong>, inkludert både start- og sluttdato. Antall {{ mb_strtolower($weekdays[$result['weekday']]) }}er: {{ $result['count'] }}.</p>@endif
            </section>
            <section><h3>Hvilken dato blir det?</h3>
                <form method="get" class="calendar-tool-form"><input type="hidden" name="year" value="{{ $month->year }}"><input type="hidden" name="month" value="{{ $month->month }}"><input type="hidden" name="tool" value="shift">
                    <label>Startdato <input type="date" name="start" min="1970-01-01" max="2099-12-31" value="{{ $data['start'] ?? $today->toDateString() }}" required></label>
                    <label>Antall dager <input type="number" name="days" min="0" max="36500" value="{{ $data['days'] ?? 0 }}" required></label>
                    <label>Retning <select name="direction"><option value="after" @selected(($data['direction'] ?? '') === 'after')>Etter</option><option value="before" @selected(($data['direction'] ?? '') === 'before')>Før</option></select></label><button class="btn btn-primary">Finn dato</button>
                </form>
                @if(($result['type'] ?? '') === 'shift')<p class="calendar-result"><strong>{{ ucfirst($result['date']->locale('nb')->translatedFormat('l j. F Y')) }}</strong>, uke {{ $result['date']->isoWeek }}. <a href="{{ route('prison.calendar', ['prison' => $prison, 'year' => $result['date']->year, 'month' => $result['date']->month, 'selected' => $result['date']->toDateString()]) }}">Vis i kalenderen</a></p>@endif
            </section>
        </div>
    </section>
    @include('partials.footer')
</main>
@endsection
