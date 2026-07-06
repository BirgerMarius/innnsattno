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
        $sudoku = $this->getSudoku(
    $request->difficulty,
    $request->has('solution')
);

$board = str_split($sudoku['puzzle']);

return view('tidsfordriv.sudoku-print', [
    'difficulty' => $sudoku['difficulty'],
    'board' => $board,
]);
    }

    private function getSudoku(string $difficulty, bool $solution = true)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => env('YOUDOSUDOKU_API_KEY'),
        ])->post('https://www.youdosudoku.com/api', [
            'difficulty' => $difficulty,
            'solution' => $solution,
            'array' => false,
        ]);

       if (!$response->successful()) {
    dd([
        'status' => $response->status(),
        'body' => $response->body(),
    ]);
}

        return $response->json();
    }
}