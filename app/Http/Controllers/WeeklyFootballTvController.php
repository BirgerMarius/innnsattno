<?php

namespace App\Http\Controllers;

use App\Services\TvGuideService;
use App\Services\WeeklyFootballTeamLogoService;
use Illuminate\Http\Request;

class WeeklyFootballTvController extends Controller
{
    public function index(Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        $prison = in_array($request->query('fengsel'), ['ringerike', 'ilseng', 'kongsvinger'], true) ? $request->query('fengsel') : 'ringerike';
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

    public function prisonIndex(string $prison, Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        $request->merge(['fengsel' => $prison]);
        return $this->index($request, $tvGuideService, $teamLogos);
    }

    public function print(Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        $prison = in_array($request->query('fengsel'), ['ringerike', 'ilseng', 'kongsvinger'], true) ? $request->query('fengsel') : 'ringerike';
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

    public function prisonPrint(string $prison, Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        $request->merge(['fengsel' => $prison]);
        return $this->print($request, $tvGuideService, $teamLogos);
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
