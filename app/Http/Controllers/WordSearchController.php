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

        return view('wordsearch.index', [
            'grid' => $puzzle['grid'],
            'words' => $puzzle['words'],
            'categories' => $this->generator->categories(),
            'selectedCategory' => $puzzle['categoryKey'],
            'categoryName' => $puzzle['category'],
        ]);
    }

    public function print(Request $request)
    {
        $category = $request->query('kategori', $this->generator->defaultCategory());
        $puzzle = $this->generator->generate($category);

        return view('wordsearch.print', [
            'grid' => $puzzle['grid'],
            'words' => $puzzle['words'],
            'categoryName' => $puzzle['category'],
        ]);
    }
}
