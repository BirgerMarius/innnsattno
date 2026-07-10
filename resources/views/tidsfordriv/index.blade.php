@extends('layouts.app')

@section('title', 'Tidsfordriv - Sudoku')

@section('content')
<div class="container page-container tidsfordriv-page">
    @include('partials.header')

    <main class="tidsfordriv-content">
        <h1>Sudoku</h1>

        <div class="tidsfordriv-info">
            Velg vanskelighetsgrad og antall sider.<br>
            PDF-en åpnes automatisk og kan skrives ut eller lagres.<br>
            Dersom du huker av <strong>Ta med fasit</strong>, legges løsningene bakerst i dokumentet.
        </div>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form id="sudokuForm" method="POST" action="/tidsfordriv/sudoku/print" class="sudoku-form">
            @csrf

            <fieldset>
                <legend>Sudoku</legend>

                <div class="mb-3">
                    <label class="d-block">
                        <input type="radio" name="difficulty" value="easy" checked>
                        Lett
                    </label>

                    <label class="d-block">
                        <input type="radio" name="difficulty" value="medium">
                        Middels
                    </label>

                    <label class="d-block">
                        <input type="radio" name="difficulty" value="hard">
                        Vanskelig
                    </label>
                </div>

                <div class="mb-3">
                    <label for="sudokuPages" class="form-label">Antall sider:</label>
                    <input
                        id="sudokuPages"
                        type="number"
                        name="pages"
                        value="1"
                        min="1"
                        max="6"
                        class="form-control sudoku-pages-input">
                    <small class="text-muted">Maks 6 sider per utskrift.</small>
                </div>

                <div class="mb-3">
                    <label>
                        <input type="checkbox" name="solution">
                        Ta med fasit
                    </label>
                </div>

                <button id="submitButton" type="submit" class="btn btn-primary btn-lg">
                    Skriv ut Sudoku
                </button>

                <p id="loadingMessage" class="sudoku-loading-message">
                    Genererer Sudoku... Dette kan ta noen sekunder.
                </p>
            </fieldset>
        </form>
    </main>

    @include('partials.footer')
</div>
@endsection

@push('scripts')
<script>
document.getElementById('sudokuForm').addEventListener('submit', function () {
    const button = document.getElementById('submitButton');
    const message = document.getElementById('loadingMessage');

    button.disabled = true;
    button.textContent = 'Genererer...';
    message.style.display = 'block';
});
</script>
@endpush
