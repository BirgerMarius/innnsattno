@extends('layouts.app')

@section('title', 'Endringer på innsatt.no')

@section('content')
<div class="container my-5 change-history-page">
    @include('partials.header')

    <main class="change-history-content mx-auto">
        <h1>Endringer på innsatt.no</h1>
        <p class="lead">Innsatt.no utvikles og forbedres fortløpende. Her kan du se hva som har blitt endret over tid.</p>

        @if ($changeHistory['available'])
            <div class="change-history-list">
                @foreach ($changeHistory['groups'] as $date => $changes)
                    <section class="change-history-group" aria-labelledby="changes-{{ $date }}">
                        <h2 id="changes-{{ $date }}">{{ $changes[0]['date']->format('d.m.Y') }}</h2>
                        <ul>
                            @foreach ($changes as $change)
                                <li>
                                    <time datetime="{{ $change['date']->toIso8601String() }}">{{ $change['date']->format('d.m.Y') }}</time>
                                    <span>{{ $change['message'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @else
            <div class="alert alert-light change-history-unavailable" role="status">
                Endringshistorikken er dessverre ikke tilgjengelig akkurat nå. Prøv gjerne igjen senere.
            </div>
        @endif

        <p><a href="{{ route('tv') }}">Tilbake til forsiden</a></p>
    </main>

    @include('partials.footer')
</div>
@endsection
