<?php

namespace App\Http\Controllers;

use App\Services\TvGuideService;
use App\Services\WeeklyFootballTeamLogoService;
use Illuminate\Http\Request;

class WeeklyFootballTvController extends Controller
{
    private const STANDARD_COMPETITIONS = [
        'Premier League',
        'Eliteserien',
        'Champions League',
        'Nations League',
    ];

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
        $showAll = $request->query('utvalg') === 'utvidet';
        $remainingOnly = $request->query('periode') === 'gjenstaende';
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
        $weeklyFootball['selectionLabel'] = $showAll ? 'Utvidet' : 'Standard';
        $weeklyFootball['selectionDescription'] = $showAll
            ? 'Alle fotballkamper i TV-guiden i valgt periode. Repriser, høydepunkter og studioprogrammer filtreres bort så langt datagrunnlaget tillater.'
            : 'TV-sendte kamper fra Eliteserien, Premier League, Nations League og Champions League i valgt periode.';
        if ($remainingOnly) {
            $weeklyFootball['matches'] = array_values(array_filter($weeklyFootball['matches'], fn (array $match) => ! $match['isFinished']));
            $weeklyFootball['mondayNightMatches'] = array_values(array_filter($weeklyFootball['mondayNightMatches'], fn (array $match) => ! $match['isFinished']));
        }
        $weeklyFootball['periodLabel'] = $remainingOnly ? 'Bare gjenstående kamper' : 'Hele uken';
        $statusUpdates = array_filter(array_merge(
            array_column($weeklyFootball['matches'], 'sportsNextUpdatedAt'),
            array_column($weeklyFootball['mondayNightMatches'], 'sportsNextUpdatedAt'),
        ));
        $weeklyFootball['sportsNextLastUpdated'] = $statusUpdates !== []
            ? \Carbon\Carbon::parse(min($statusUpdates))->setTimezone('Europe/Oslo')
            : null;
        $weeklyFootball['matches'] = $this->withTeamLogos($weeklyFootball['matches'], $teamLogos);
        $weeklyFootball['mondayNightMatches'] = $this->withTeamLogos($weeklyFootball['mondayNightMatches'] ?? [], $teamLogos);

        return view($print ? 'weekly-football-tv.print' : 'weekly-football-tv.index', compact('weeklyFootball', 'prison', 'weekOffset', 'showAll', 'remainingOnly'));
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
