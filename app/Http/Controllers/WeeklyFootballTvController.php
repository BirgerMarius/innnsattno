<?php

namespace App\Http\Controllers;

use App\Services\TvGuideService;
use App\Services\WeeklyFootballTeamLogoService;
use Illuminate\Http\Request;

class WeeklyFootballTvController extends Controller
{
    public function index(Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        $prison = $request->query('fengsel') === 'ilseng' ? 'ilseng' : 'ringerike';
        $weekOffset = $request->query('uke') === 'neste' ? 1 : 0;
        $weeklyFootball = $tvGuideService->getWeeklyFootballMatches(
            now('Europe/Oslo'),
            $prison,
            'weekly-football-tv-'.$prison,
            $weekOffset,
        );
        $weeklyFootball['matches'] = $this->withTeamLogos($weeklyFootball['matches'], $teamLogos);

        return view('weekly-football-tv.index', compact('weeklyFootball', 'prison', 'weekOffset'));
    }

    public function print(Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        $prison = $request->query('fengsel') === 'ilseng' ? 'ilseng' : 'ringerike';
        $weekOffset = $request->query('uke') === 'neste' ? 1 : 0;
        $weeklyFootball = $tvGuideService->getWeeklyFootballMatches(
            now('Europe/Oslo'),
            $prison,
            'weekly-football-tv-print-'.$prison,
            $weekOffset,
        );
        $weeklyFootball['matches'] = $this->withTeamLogos($weeklyFootball['matches'], $teamLogos);

        return view('weekly-football-tv.print', compact('weeklyFootball', 'prison', 'weekOffset'));
    }

    private function withTeamLogos(array $matches, WeeklyFootballTeamLogoService $teamLogos): array
    {
        foreach ($matches as &$match) {
            $match = array_merge($match, $teamLogos->logosFor($match['competition'], $match['name']));
        }
        unset($match);

        return $matches;
    }
}
