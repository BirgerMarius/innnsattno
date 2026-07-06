<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TidsfordrivController extends Controller
{
    public function index()
    {
        return view('tidsfordriv.index');
    }

    public function printSudoku(Request $request)
    {
        $pages = min(
            max((int) $request->pages, 1),
            6
        );
        $boardsPerPage = 9;

        $sudokus = [];

try {

    for ($i = 0; $i < ($pages * $boardsPerPage); $i++) {

        $sudoku = $this->getSudoku(
            $request->difficulty,
            $request->has('solution')
        );

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

    private function getSudoku(string $difficulty, bool $solution = true)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key'    => env('YOUDOSUDOKU_API_KEY'),
        ])->post('https://www.youdosudoku.com/api', [
            'difficulty' => $difficulty,
            'solution'   => $solution,
            'array'      => false,
        ]);

        if (!$response->successful()) {
    throw new \Exception('Kunne ikke hente Sudoku fra tjenesten.');
}

        return $response->json();
    }
}