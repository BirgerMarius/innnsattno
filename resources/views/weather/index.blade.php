@extends('layouts.app')

@section('title', 'Værmelding – Tyristrand / Ringerike fengsel')

@section('content')
<div class="container page-container weather-page">
    @include('partials.header')

    <main>
        <div class="text-center mb-4">
            <h1>Værmelding</h1>
            <p class="lead mb-0">Tyristrand / Ringerike fengsel</p>
        </div>

        @if ($error)
            <div class="alert alert-warning text-center" role="alert">
                {{ $error }}
            </div>
        @elseif ($forecast && count($forecast['days']))
            @php($today = $forecast['days'][0])

            <section class="weather-today mb-4" aria-labelledby="today-heading">
                <div>
                    <p class="weather-eyebrow mb-1">Dagens vær</p>
                    <h2 id="today-heading" class="h3 mb-2">
                        {{ ucfirst($today['date']->locale('nb')->translatedFormat('l j. F')) }}
                    </h2>
                    <p class="weather-description mb-0">
                        <span class="weather-icon" aria-hidden="true">{{ $today['icon'] }}</span>
                        {{ $today['description'] }}
                    </p>
                </div>
                <div class="weather-today-details">
                    <strong class="weather-temperature">
                        {{ $today['temperature_min'] }}–{{ $today['temperature_max'] }} °C
                    </strong>
                    @if ($today['precipitation'] !== null)
                        <span>💧 Nedbør: {{ number_format($today['precipitation'], 1, ',', ' ') }} mm</span>
                    @endif
                    @if ($today['wind_speed'] !== null)
                        <span>💨 Vind: {{ number_format($today['wind_speed'], 1, ',', ' ') }} m/s</span>
                    @endif
                </div>
            </section>

            <h2 class="h4 mb-3">Kommende dager</h2>
            <div class="weather-grid">
                @foreach (array_slice($forecast['days'], 1) as $day)
                    <article class="weather-card">
                        <h3 class="h5">
                            {{ ucfirst($day['date']->locale('nb')->translatedFormat('l')) }}
                        </h3>
                        <p class="text-muted mb-2">{{ $day['date']->translatedFormat('j. F') }}</p>
                        <div class="weather-card-icon" aria-hidden="true">{{ $day['icon'] }}</div>
                        <p class="weather-card-description">{{ $day['description'] }}</p>
                        <strong class="d-block mb-2">
                            {{ $day['temperature_min'] }}–{{ $day['temperature_max'] }} °C
                        </strong>
                        @if ($day['precipitation'] !== null)
                            <small class="d-block">💧 {{ number_format($day['precipitation'], 1, ',', ' ') }} mm</small>
                        @endif
                        @if ($day['wind_speed'] !== null)
                            <small class="d-block">💨 {{ number_format($day['wind_speed'], 1, ',', ' ') }} m/s</small>
                        @endif
                    </article>
                @endforeach
            </div>

            <p class="text-center text-muted mt-4 mb-0">
                Sist oppdatert:
                {{ $forecast['updated_at']->locale('nb')->translatedFormat('j. F Y \k\l. H:i') }}
                · Værdata fra MET Norge
            </p>
        @else
            <div class="alert alert-warning text-center" role="alert">
                Det finnes ingen værdata å vise akkurat nå.
            </div>
        @endif

        <div class="text-center mt-4">
            <a href="{{ route('tv') }}" class="btn btn-primary">Tilbake til forsiden</a>
        </div>
    </main>

    @include('partials.footer')
</div>
@endsection
