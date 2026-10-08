<?php

namespace App\Http\Controllers;

use App\Services\WordSearchGenerator;
use Illuminate\Http\Request;

class WordSearchController extends Controller
{
    protected WordSearchGenerator $generator;

    public function __construct(WordSearchGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function index(Request $request)
    {
        $category = $request->query('kategori', $this->generator->defaultCategory());
        $puzzle = $this->generator->generate($category);
        $puzzleId = (string) \Illuminate\Support\Str::uuid();
        $request->session()->put('wordsearch.puzzles.'.$puzzleId, $puzzle);

        return view('wordsearch.index', [
            'grid' => $puzzle['grid'],
            'words' => $puzzle['words'],
            'categories' => $this->generator->categories(),
            'selectedCategory' => $puzzle['categoryKey'],
            'categoryName' => $puzzle['category'],
            'puzzleId' => $puzzleId,
        ]);
    }

    public function print(Request $request)
    {
        $puzzleId = (string) $request->query('puzzle');
        $puzzle = $request->session()->get('wordsearch.puzzles.'.$puzzleId);
        if (! is_array($puzzle)) { $puzzle = $this->generator->generate($request->query('kategori', $this->generator->defaultCategory())); $puzzleId = (string) \Illuminate\Support\Str::uuid(); $request->session()->put('wordsearch.puzzles.'.$puzzleId, $puzzle); }

        return view('wordsearch.print', [
            'grid' => $puzzle['grid'],
            'words' => $puzzle['words'],
            'puzzle' => $puzzle,
            'categoryName' => $puzzle['category'],
            'puzzleId' => $puzzleId,
        ]);
    }
}
