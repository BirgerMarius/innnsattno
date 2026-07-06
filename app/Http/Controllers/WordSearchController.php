<?php

namespace App\Http\Controllers;

use App\Services\WordSearchGenerator;

class WordSearchController extends Controller
{
    public function index(WordSearchGenerator $generator)
    {
        $data = $generator->generate();

        return view('wordsearch.index', $data);
    }
}