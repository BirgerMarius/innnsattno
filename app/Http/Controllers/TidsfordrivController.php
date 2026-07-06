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
}