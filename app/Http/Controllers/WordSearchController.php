<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WordSearchController extends Controller
{
    public function index()
    {
        return view('wordsearch.index');
    }
}