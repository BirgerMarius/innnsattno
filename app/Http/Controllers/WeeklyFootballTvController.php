<?php

namespace App\Http\Controllers;

use App\Services\TvGuideService;
use Illuminate\Http\Request;

class WeeklyFootballTvController extends Controller
{
    public function index(Request $request, TvGuideService $tvGuideService)
    {
        $prison = $request->query('fengsel') === 'ilseng' ? 'ilseng' : 'ringerike';
        $weeklyFootball = $tvGuideService->getWeeklyFootballMatches(
            now('Europe/Oslo'),
            $prison,
            'weekly-football-tv-'.$prison,
        );

        return view('weekly-football-tv.index', compact('weeklyFootball', 'prison'));
    }

    public function print(Request $request, TvGuideService $tvGuideService)
    {
        $prison = $request->query('fengsel') === 'ilseng' ? 'ilseng' : 'ringerike';
        $weeklyFootball = $tvGuideService->getWeeklyFootballMatches(
            now('Europe/Oslo'),
            $prison,
            'weekly-football-tv-print-'.$prison,
        );

        return view('weekly-football-tv.print', compact('weeklyFootball', 'prison'));
    }
}
