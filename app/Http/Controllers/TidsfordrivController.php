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

        dd($sudoku);
    }

    private function getSudoku(string $difficulty, bool $solution = true)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'x-api-key' => env('YOUDOSUDOKU_API_KEY'),
        ])->post('https://youdosudoku.com/api/', [
            'difficulty' => $difficulty,
            'solution' => $solution,
            'array' => false,
        ]);

        if (!$response->successful()) {
            return null;
        }

        return $response->json();
    }
}