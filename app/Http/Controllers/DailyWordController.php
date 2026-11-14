<?php

namespace App\Http\Controllers;

use App\Services\DailyWordService;

class DailyWordController extends Controller
{
    public function index(string $prison, DailyWordService $dailyWords)
    {
        return view('daily-word.index', ['dailyWords' => $dailyWords->activeWords()]);
    }
}
