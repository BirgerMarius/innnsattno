<?php

namespace App\Http\Controllers;

use App\Services\DailyWordService;

class DailyWordPrintController extends Controller
{
    public function show(string $word, DailyWordService $dailyWords)
    {
        $dailyWord = $dailyWords->find($word);

        abort_if($dailyWord === null, 404);

        return view('daily-word.print', ['dailyWord' => $dailyWord]);
    }
}
