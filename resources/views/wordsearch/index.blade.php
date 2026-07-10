@extends('layouts.app')

@section('title', 'Ordjakt')

@section('content')
<div class="container page-container wordsearch-page">
    @include('partials.header')

    <main>
        <div class="wordsearch-toolbar">
            <div>
                <h1>Ordjakt</h1>
                <p class="text-muted mb-0">Kategori: {{ $categoryName }}</p>
            </div>

            <div class="wordsearch-actions">
                <form method="GET" action="/ordjakt" class="wordsearch-category-form">
                    <label for="wordsearchCategory" class="form-label">Velg kategori</label>
                    <select id="wordsearchCategory" name="kategori" class="form-select">
                        @foreach($categories as $key => $category)
                            <option value="{{ $key }}" @selected($selectedCategory === $key)>
                                {{ $category['name'] }}
                            </option>
                        @endforeach
                    </select>
                </form>

                <a href="/ordjakt?kategori={{ $selectedCategory }}" class="btn btn-primary">
                    Ny ordjakt
                </a>

                <a href="/ordjakt/utskrift?kategori={{ $selectedCategory }}" class="btn btn-success">
                    Utskriftsversjon
                </a>
            </div>
        </div>

        <div id="wordsearchComplete" class="alert alert-success wordsearch-complete" role="status">
            Alle ordene er funnet.
        </div>

        <div class="wordsearch-layout">
            <div class="wordsearch-board-wrap" aria-label="Ordjaktbrett">
                <table id="wordsearchGrid" class="word-grid">
                    @foreach($grid as $rowIndex => $row)
                        <tr>
                            @foreach($row as $colIndex => $letter)
                                <td data-row="{{ $rowIndex }}" data-col="{{ $colIndex }}">{{ $letter }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </div>

            <aside class="wordsearch-list-panel">
                <h2>Finn disse ordene</h2>

                <ul id="wordsearchList" class="word-list">
                    @foreach($words as $word)
                        <li data-word="{{ $word['word'] }}">
                            <span>{{ $word['display'] }}</span>
                            @if($word['display'] !== $word['word'])
                                <small>{{ $word['word'] }}</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </main>

    @include('partials.footer')
</div>
@endsection

@push('scripts')
<script>
const wordsearchWords = @json($words);
</script>
<script>
(function () {
    const grid = document.getElementById('wordsearchGrid');
    const completeMessage = document.getElementById('wordsearchComplete');
    const categorySelect = document.getElementById('wordsearchCategory');
    const wordsByCells = new Map();
    const foundWords = new Set();
    let startCell = null;
    let previewCells = [];

    wordsearchWords.forEach(function (word) {
        wordsByCells.set(cellKey(word.cells), word);
    });

    categorySelect.addEventListener('change', function () {
        this.form.submit();
    });

    grid.addEventListener('pointerdown', function (event) {
        const cell = event.target.closest('td');

        if (!cell) {
            return;
        }

        startCell = readCell(cell);
        clearPreview();
        cell.classList.add('is-selecting');
        previewCells = [cell];
        grid.setPointerCapture(event.pointerId);
    });

    grid.addEventListener('pointermove', function (event) {
        if (!startCell) {
            return;
        }

        const cell = document.elementFromPoint(event.clientX, event.clientY)?.closest('#wordsearchGrid td');

        if (!cell) {
            return;
        }

        showPreview(startCell, readCell(cell));
    });

    grid.addEventListener('pointerup', function (event) {
        if (!startCell) {
            return;
        }

        const cell = document.elementFromPoint(event.clientX, event.clientY)?.closest('#wordsearchGrid td');
        const endCell = cell ? readCell(cell) : startCell;
        const selectedCells = cellsBetween(startCell, endCell);

        clearPreview();
        markIfWord(selectedCells);
        startCell = null;
        grid.releasePointerCapture(event.pointerId);
    });

    function showPreview(start, end) {
        clearPreview();
        previewCells = cellsBetween(start, end).map(function (position) {
            const cell = findCell(position);
            cell.classList.add('is-selecting');
            return cell;
        });
    }

    function markIfWord(cells) {
        const key = cellKey(cells);
        const reverseKey = cellKey(cells.slice().reverse());
        const word = wordsByCells.get(key) || wordsByCells.get(reverseKey);

        if (!word || foundWords.has(word.word)) {
            return;
        }

        foundWords.add(word.word);

        word.cells.forEach(function (position) {
            findCell(position).classList.add('is-found');
        });

        document.querySelector('[data-word="' + word.word + '"]').classList.add('is-found');

        if (foundWords.size === wordsearchWords.length) {
            completeMessage.style.display = 'block';
        }
    }

    function cellsBetween(start, end) {
        const rowDiff = end.row - start.row;
        const colDiff = end.col - start.col;
        const rowStep = Math.sign(rowDiff);
        const colStep = Math.sign(colDiff);
        const length = Math.max(Math.abs(rowDiff), Math.abs(colDiff));

        if (rowDiff !== 0 && colDiff !== 0 && Math.abs(rowDiff) !== Math.abs(colDiff)) {
            return [[start.row, start.col]];
        }

        const cells = [];

        for (let i = 0; i <= length; i++) {
            cells.push([start.row + (rowStep * i), start.col + (colStep * i)]);
        }

        return cells;
    }

    function clearPreview() {
        previewCells.forEach(function (cell) {
            cell.classList.remove('is-selecting');
        });
        previewCells = [];
    }

    function readCell(cell) {
        return {
            row: Number(cell.dataset.row),
            col: Number(cell.dataset.col)
        };
    }

    function findCell(position) {
        return grid.querySelector('[data-row="' + position[0] + '"][data-col="' + position[1] + '"]');
    }

    function cellKey(cells) {
        return cells.map(function (cell) {
            return cell[0] + ',' + cell[1];
        }).join('|');
    }
})();
</script>
@endpush
