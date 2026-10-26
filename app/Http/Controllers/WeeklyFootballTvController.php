<?php

namespace App\Http\Controllers;

use App\Services\TvGuideService;
use App\Services\WeeklyFootballTeamLogoService;
use Illuminate\Http\Request;

class WeeklyFootballTvController extends Controller
{
    private const STANDARD_COMPETITIONS = ['Premier League', 'Eliteserien', 'Champions League'];

    public function index(Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        return $this->render('ringerike', $request, $tvGuideService, $teamLogos);
    }

    public function prisonIndex(string $prison, Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        return $this->render($prison, $request, $tvGuideService, $teamLogos);
    }

    public function print(Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        return $this->render($prison = 'ringerike', $request, $tvGuideService, $teamLogos, true);
    }

    public function prisonPrint(string $prison, Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos)
    {
        return $this->render($prison, $request, $tvGuideService, $teamLogos, true);
    }

    private function render(string $prison, Request $request, TvGuideService $tvGuideService, WeeklyFootballTeamLogoService $teamLogos, bool $print = false)
    {
        $weekOffset = $request->query('uke') === 'neste' ? 1 : 0;
        $showAll = $request->boolean('vis_alle');
        $weeklyFootball = $tvGuideService->getWeeklyFootballMatches(
            now('Europe/Oslo'),
            $prison,
            'weekly-football-tv-print-'.$prison,
            $weekOffset,
        );
        if (! $showAll) {
            $weeklyFootball['matches'] = $this->standardCompetitions($weeklyFootball['matches']);
            $weeklyFootball['mondayNightMatches'] = $this->standardCompetitions($weeklyFootball['mondayNightMatches'] ?? []);
        }
        $weeklyFootball['matches'] = $this->withTeamLogos($weeklyFootball['matches'], $teamLogos);
        $weeklyFootball['mondayNightMatches'] = $this->withTeamLogos($weeklyFootball['mondayNightMatches'] ?? [], $teamLogos);

        return view($print ? 'weekly-football-tv.print' : 'weekly-football-tv.index', compact('weeklyFootball', 'prison', 'weekOffset', 'showAll'));
    }

    private function standardCompetitions(array $matches): array
    {
        return array_values(array_filter($matches, fn (array $match) => in_array($match['competition'], self::STANDARD_COMPETITIONS, true)));
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
