<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TidsfordrivController extends Controller
{
    public function index()
    {
        return view('tidsfordriv.index');
    }

    public function printSudoku(Request $request)
    {
        return view('tidsfordriv.sudoku-print', [
            'difficulty' => $request->difficulty,
            'count' => $request->count,
            'solution' => $request->has('solution'),
        ]);
    }
}