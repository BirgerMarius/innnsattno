<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SudokuService;

class TidsfordrivController extends Controller
{
    public function index()
    {
        return view('tidsfordriv.index');
    }

    public function printSudoku(Request $request, SudokuService $sudokuService)
    {
        $pages = min(
            max((int) $request->pages, 1),
            6
        );
        $boardsPerPage = 9;

        $sudokus = [];

try {

    for ($i = 0; $i < ($pages * $boardsPerPage); $i++) {

        $sudoku = $sudokuService->get($request->difficulty ?: 'easy');

        $sudokus[] = [
            'difficulty' => $sudoku['difficulty'],
            'board'      => str_split($sudoku['puzzle']),
            'solution'   => $sudoku['solution'] ?? null,
        ];
    }

} catch (\Exception $e) {

    return redirect()
        ->back()
        ->withInput()
        ->with('error', 'Kunne ikke generere Sudoku akkurat nå. Prøv igjen om litt.');

}

        $levels = [
    'easy'   => 'Lett',
    'medium' => 'Middels',
    'hard'   => 'Vanskelig',
    ];
        return view('tidsfordriv.sudoku-print', [
            'difficulty' => $levels[$request->difficulty] ?? $request->difficulty,
            'sudokus'    => $sudokus,
            'pages'      => $pages,
            'showSolution' => $request->has('solution'),
        ]);
    }

    public function onlineSudoku(Request $request, SudokuService $sudokuService)
    {
        $validated = $request->validate(['difficulty' => ['required', 'in:easy,medium,hard']]);

        try {
            return response()->json($sudokuService->get($validated['difficulty']));
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Kunne ikke hente en Sudoku akkurat nå. Prøv igjen om litt.',
            ], 503);
        }
    }
}
