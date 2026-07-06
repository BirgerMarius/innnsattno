<?php

namespace App\Http\Controllers;

use App\Services\WordSearchGenerator;

class WordSearchController extends Controller
{
    protected WordSearchGenerator $generator;

    public function __construct(WordSearchGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function index()
    {
        $puzzle = $this->generator->generate();

        return view('wordsearch.index', [
            'grid'  => $puzzle['grid'],
            'words' => $puzzle['words'],
        ]);
    }

    public function print()
    {
        $puzzle = $this->generator->generate();

        return view('wordsearch.print', [
            'grid'  => $puzzle['grid'],
            'words' => $puzzle['words'],
        ]);
    }
}