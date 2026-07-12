@extends('layouts.app')

@section('title', 'Månedskalender - For utskrift')

@section('content')
<div class="container page-container calendar-page">
    @include('partials.header')

    <main class="calendar-choice-content">
        <h1>Månedskalender – For utskrift</h1>

        @if($errors->any())
            <div class="alert alert-danger">
                Velg en gyldig periode.
            </div>
        @endif

        <form method="GET" action="{{ route('calendar.print') }}" class="calendar-choice-form">
            <fieldset>
                <legend>Velg periode</legend>

                <div class="mb-3">
                    <label class="d-block">
                        <input type="radio" name="periode" value="current_month" @checked(old('periode', $defaultPeriod) === 'current_month')>
                        Denne måneden
                    </label>

                    <label class="d-block">
                        <input type="radio" name="periode" value="rest_of_year" @checked(old('periode', $defaultPeriod) === 'rest_of_year')>
                        Resten av året
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-lg calendar-generate-button">
                    Generer kalender
                </button>
            </fieldset>
        </form>
    </main>

    @include('partials.footer')
</div>
@endsection
