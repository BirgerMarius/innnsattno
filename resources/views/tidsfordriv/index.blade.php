@extends('layouts.app')

@section('title', 'Tidsfordriv - Sudoku')

@section('content')
<div class="container page-container tidsfordriv-page">
    @include('partials.header')

    <main class="tidsfordriv-content">
        <section class="pastime-overview" aria-labelledby="pastimeOverviewHeading">
            <h1 id="pastimeOverviewHeading">Tidsfordriv</h1><p>Spill og oppgaver – på skjerm og papir.</p>
            <div class="pastime-menu">
                <article class="pastime-card"><h2>Ordjakt</h2><p>Finn ordene i rutenettet.</p><a class="btn btn-primary" href="/ordjakt">Spill på skjermen</a> <a class="btn btn-outline-secondary" href="/ordjakt/utskrift">Skriv ut</a></article>
                <article class="pastime-card"><h2>Sudoku</h2><p>Fyll inn tallene 1–9.</p><a class="btn btn-primary" href="#onlineSudokuHeading">Spill på skjermen</a> <a class="btn btn-outline-secondary" href="#sudokuForm">Skriv ut</a></article>
                @foreach($pastimes as $key => $pastime)<article class="pastime-card"><h2>{{ $pastime['title'] }}</h2><p>{{ $pastime['description'] }} <small>{{ $pastime['sizes'] }}</small></p><a class="btn btn-primary" href="/tidsfordriv/{{ $key }}">Spill på skjermen</a> <a class="btn btn-outline-secondary" href="/tidsfordriv/{{ $key }}/utskrift">Skriv ut</a></article>@endforeach
                <article class="pastime-card"><h2>Spillforslag</h2><p>Kortspill og brettspill med regler for utskrift.</p><a class="btn btn-primary" href="{{ route('activities.index') }}">Se spillforslag</a></article>
            </div>
        </section><hr class="my-5">
        <h1>Sudoku</h1>

        <div class="tidsfordriv-info">
            Velg vanskelighetsgrad og antall sider.<br>
            En utskriftsvennlig side åpnes automatisk og kan skrives ut fra nettleseren.<br>
            Dersom du huker av <strong>Ta med fasit</strong>, vises løsningene bakerst på utskriftssiden.
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

        <section class="sudoku-online" aria-labelledby="onlineSudokuHeading">
            <h2 id="onlineSudokuHeading">Spill Sudoku på skjermen</h2>

            <div class="tidsfordriv-info sudoku-online-help">
                <h3>Slik spiller du Sudoku</h3>
                <p>Fyll de tomme rutene med tall fra 1 til 9. Hver rad, hver kolonne og hver av de ni 3 × 3-boksene skal inneholde alle tallene én gang. Tallene som står i brettet fra starten, kan ikke endres.</p>
                <p class="mb-0">Velg en tom rute og skriv inn et tall, eller bruk tallknappene under brettet. Bruk «Sjekk svar» hvis du vil kontrollere det du har fylt inn.</p>
            </div>

            <div class="sudoku-online-controls">
                <fieldset>
                    <legend>Velg vanskelighetsgrad</legend>
                    <label><input type="radio" name="onlineDifficulty" value="easy" checked> Lett</label>
                    <label><input type="radio" name="onlineDifficulty" value="medium"> Middels</label>
                    <label><input type="radio" name="onlineDifficulty" value="hard"> Vanskelig</label>
                </fieldset>
                <button id="newSudoku" type="button" class="btn btn-primary">Ny sudoku</button>
            </div>

            <div id="sudokuStatus" class="alert d-none" role="status" aria-live="polite"></div>
            <div id="sudokuBoard" class="sudoku-board" role="grid" aria-label="Sudoku-brett" aria-describedby="sudokuStatus"></div>

            <div id="sudokuNumberPad" class="sudoku-number-pad" aria-label="Tallknapper">
                @for($number = 1; $number <= 9; $number++)
                    <button type="button" class="btn btn-outline-primary" data-number="{{ $number }}">{{ $number }}</button>
                @endfor
                <button type="button" class="btn btn-outline-secondary sudoku-delete" data-number="delete">Slett valgt svar</button>
            </div>

            <div class="sudoku-online-actions">
                <button id="checkSudoku" type="button" class="btn btn-success">Sjekk svar</button>
                <button id="clearSudoku" type="button" class="btn btn-outline-danger">Tøm mine svar</button>
            </div>
        </section>
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

(function () {
    const storageKey = 'innsatt.sudoku.online.v1';
    const board = document.getElementById('sudokuBoard');
    const status = document.getElementById('sudokuStatus');
    const newButton = document.getElementById('newSudoku');
    const checkButton = document.getElementById('checkSudoku');
    const clearButton = document.getElementById('clearSudoku');
    const numberPad = document.getElementById('sudokuNumberPad');
    let game = null;
    let selectedIndex = null;

    function difficulty() {
        return document.querySelector('input[name="onlineDifficulty"]:checked').value;
    }

    function showStatus(message, type) {
        status.textContent = message;
        status.className = 'alert alert-' + type;
    }

    function clearStatus() {
        status.textContent = '';
        status.className = 'alert d-none';
    }

    function validSolution(value) {
        if (!/^[1-9]{81}$/.test(value)) return false;
        for (let i = 0; i < 9; i++) {
            const row = value.slice(i * 9, i * 9 + 9);
            const column = Array.from({ length: 9 }, (_, n) => value[n * 9 + i]).join('');
            if (new Set(row).size !== 9 || new Set(column).size !== 9) return false;
        }
        for (let row = 0; row < 9; row += 3) for (let col = 0; col < 9; col += 3) {
            let box = '';
            for (let y = 0; y < 3; y++) box += value.slice((row + y) * 9 + col, (row + y) * 9 + col + 3);
            if (new Set(box).size !== 9) return false;
        }
        return true;
    }

    function validGame(value) {
        return value && typeof value.id === 'string' && ['easy', 'medium', 'hard'].includes(value.difficulty)
            && typeof value.puzzle === 'string' && /^[0-9]{81}$/.test(value.puzzle)
            && typeof value.solution === 'string' && validSolution(value.solution)
            && Array.isArray(value.answers) && value.answers.length === 81
            && value.answers.every(answer => answer === '' || /^[1-9]$/.test(answer))
            && value.answers.every((answer, index) => value.puzzle[index] === '0' || answer === '')
            && [...value.puzzle].every((cell, index) => cell === '0' || cell === value.solution[index]);
    }

    function save() {
        try { window.localStorage.setItem(storageKey, JSON.stringify(game)); } catch (error) { /* Lagring er valgfritt. */ }
    }

    function restore() {
        try {
            const stored = JSON.parse(window.localStorage.getItem(storageKey));
            if (validGame(stored)) return stored;
            window.localStorage.removeItem(storageKey);
        } catch (error) { /* Ugyldig eller utilgjengelig lokal lagring ignoreres. */ }
        return null;
    }

    function render() {
        board.innerHTML = '';
        if (!game) return;
        board.classList.remove('is-loading');
        for (let index = 0; index < 81; index++) {
            const given = game.puzzle[index] !== '0';
            const cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'sudoku-cell' + (given ? ' is-given' : '');
            cell.dataset.index = index;
            cell.disabled = given;
            cell.textContent = given ? game.puzzle[index] : game.answers[index];
            cell.setAttribute('role', 'gridcell');
            cell.setAttribute('aria-label', 'Rad ' + (Math.floor(index / 9) + 1) + ', kolonne ' + ((index % 9) + 1) + (given ? ', låst tall ' + game.puzzle[index] : ', ditt svar'));
            if (!given && index === selectedIndex) cell.classList.add('is-selected');
            board.appendChild(cell);
        }
    }

    function select(index, focus) {
        if (!game || game.puzzle[index] !== '0') return;
        selectedIndex = index;
        render();
        if (focus) board.querySelector('[data-index="' + index + '"]').focus();
    }

    function updateAnswer(value) {
        if (!game || selectedIndex === null || game.puzzle[selectedIndex] !== '0') return;
        game.answers[selectedIndex] = value;
        game.checked = false;
        save();
        clearStatus();
        render();
        if (value && isSolved()) finish();
    }

    function isSolved() {
        const combined = [...game.puzzle].map((value, index) => value === '0' ? game.answers[index] : value).join('');
        return combined === game.solution && validSolution(combined);
    }

    function celebrate() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const confetti = document.createElement('div');
        confetti.className = 'sudoku-confetti';
        confetti.setAttribute('aria-hidden', 'true');
        const colors = ['#e63946', '#f4a261', '#f1c40f', '#2a9d8f', '#457b9d', '#9b5de5'];
        for (let index = 0; index < 30; index++) {
            const piece = document.createElement('span');
            piece.className = 'sudoku-confetti-piece';
            piece.style.left = (5 + Math.random() * 90) + '%';
            piece.style.backgroundColor = colors[index % colors.length];
            piece.style.animationDelay = (Math.random() * .3) + 's';
            piece.style.setProperty('--confetti-drift', ((Math.random() * 160) - 80) + 'px');
            piece.style.setProperty('--confetti-rotation', (Math.random() * 720) + 'deg');
            confetti.appendChild(piece);
        }
        document.body.appendChild(confetti);
        window.setTimeout(() => confetti.remove(), 2800);
    }

    function finish() {
        if (game.completed) {
            showStatus('🎉 Gratulerer! Du løste sudokuen!', 'success');
            return;
        }
        game.completed = true;
        save();
        showStatus('🎉 Gratulerer! Du løste sudokuen!', 'success');
        celebrate();
    }

    function checkAnswers() {
        if (!game) return;
        let wrong = 0;
        board.querySelectorAll('.sudoku-cell').forEach(function (cell) {
            const index = Number(cell.dataset.index);
            const incorrect = game.puzzle[index] === '0' && game.answers[index] !== '' && game.answers[index] !== game.solution[index];
            cell.classList.toggle('is-incorrect', incorrect);
            if (incorrect) wrong++;
        });
        if (isSolved()) return finish();
        showStatus(wrong ? 'Det er ' + wrong + ' feil utfylt ' + (wrong === 1 ? 'rute.' : 'ruter.') : 'Ingen feil i svarene du har fylt inn ennå.', wrong ? 'warning' : 'info');
    }

    async function loadNew() {
        if (game && !game.completed && game.answers.some(Boolean) && !window.confirm('Du har utfylte svar som ikke er ferdige. Vil du hente en ny sudoku?')) return;
        newButton.disabled = true;
        showStatus('Henter en ny sudoku …', 'info');
        try {
            const response = await fetch('/tidsfordriv/sudoku?difficulty=' + encodeURIComponent(difficulty()), { headers: { Accept: 'application/json' } });
            const puzzle = await response.json();
            if (!response.ok || !validGame({ id: 'new', difficulty: puzzle.difficulty, puzzle: puzzle.puzzle, solution: puzzle.solution, answers: Array(81).fill('') })) throw new Error('invalid');
            game = { id: Date.now().toString(36) + Math.random().toString(36).slice(2), difficulty: puzzle.difficulty, puzzle: puzzle.puzzle, solution: puzzle.solution, answers: Array(81).fill(''), completed: false };
            selectedIndex = null;
            save();
            render();
            showStatus('Ny ' + ({ easy: 'lett', medium: 'middels', hard: 'vanskelig' })[game.difficulty] + ' sudoku er klar.', 'info');
        } catch (error) {
            showStatus('Kunne ikke hente en Sudoku akkurat nå. Prøv igjen om litt.', 'danger');
        } finally { newButton.disabled = false; }
    }

    board.addEventListener('click', event => { const cell = event.target.closest('.sudoku-cell'); if (cell) select(Number(cell.dataset.index)); });
    board.addEventListener('keydown', event => {
        if (!game) return;
        const index = Number(event.target.dataset.index);
        if (/^[1-9]$/.test(event.key)) { event.preventDefault(); select(index); updateAnswer(event.key); }
        else if (event.key === 'Backspace' || event.key === 'Delete') { event.preventDefault(); select(index); updateAnswer(''); }
        else if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(event.key)) {
            event.preventDefault();
            const move = { ArrowUp: -9, ArrowDown: 9, ArrowLeft: -1, ArrowRight: 1 }[event.key];
            let next = index + move;
            if (next >= 0 && next < 81 && (event.key !== 'ArrowLeft' || index % 9) && (event.key !== 'ArrowRight' || index % 9 !== 8)) select(next, true);
        }
    });
    numberPad.addEventListener('click', event => { const value = event.target.dataset.number; if (value) updateAnswer(value === 'delete' ? '' : value); });
    newButton.addEventListener('click', loadNew);
    checkButton.addEventListener('click', checkAnswers);
    clearButton.addEventListener('click', function () {
        if (!game || !game.answers.some(Boolean) || !window.confirm('Vil du tømme svarene du har fylt inn?')) return;
        game.answers = Array(81).fill(''); save(); clearStatus(); render();
    });

    game = restore();
    if (game) {
        document.querySelector('input[name="onlineDifficulty"][value="' + game.difficulty + '"]').checked = true;
        render();
        if (game.completed && isSolved()) showStatus('🎉 Gratulerer! Du løste sudokuen!', 'success');
    } else loadNew();
}());
</script>
@endpush
