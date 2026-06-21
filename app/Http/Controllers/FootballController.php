<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class FootballController extends Controller
{
    public function index()
    {
        return view('football.index', $this->getFootballData());
    }

    public function print()
    {
        return view('football.print', $this->getFootballData());
    }

    private function getFootballData()
    {
        $response = Http::get(
            'https://api.sportsnext.schibsted.io/v1/vg/tournaments/seasons/7767/schedule'
        );

        $data = $response->json();

        $standingsResponse = Http::get(
            'https://api.sportsnext.schibsted.io/v1/vg/tournaments/seasons/7767/standings'
        );

        $standings = $standingsResponse->json();

        $groups = [];

        foreach ($standings['standings'] as $group) {

            $groupName = $group['groupName'];

            $groups[$groupName] = [];

            foreach ($group['teamStandings'] as $team) {

                $groups[$groupName][] = [
                    'rank' => $team['rank'],
                    'name' => $standings['participants'][$team['teamId']]['name'],
                    'played' => $team['played'],
                    'wins' => $team['wins'],
                    'draws' => $team['draws'],
                    'losses' => $team['losses'],
                    'goalsFor' => $team['goalsFor'],
                    'goalsAgainst' => $team['goalsAgainst'],
                    'points' => $team['points'],
                ];
            }
        }

        $participants = $data['participants'];
        $matches = [];

        foreach ($data['events'] as $event) {

            $homeId = $event['participantIds'][0] ?? null;
            $awayId = $event['participantIds'][1] ?? null;

            $homeScore = null;
            $awayScore = null;

            if (($event['status']['type'] ?? '') === 'finished') {

                if (isset($event['results'][$homeId]['runningScore'])) {
                    $homeScore = $event['results'][$homeId]['runningScore'];
                }

                if (isset($event['results'][$awayId]['runningScore'])) {
                    $awayScore = $event['results'][$awayId]['runningScore'];
                }
            }

            $matches[] = [
                'date' => date('d.m.Y H:i', strtotime($event['startDate'])),
                'group' => $event['tournament']['groupName'] ?? '',
                'phaseType' => $event['tournament']['phaseType'] ?? '',
                'stage' => $event['tournament']['stage'] ?? '',
                'stageName' => $event['tournament']['stageName'] ?? '',
                'tournamentName' => $event['tournament']['name'] ?? '',
                'status' => $event['status']['type'] ?? '',
                'home' => $participants[$homeId]['name'] ?? 'Ukjent',
                'away' => $participants[$awayId]['name'] ?? 'Ukjent',
                'homeScore' => $homeScore,
                'awayScore' => $awayScore,
            ];
        }

        usort($matches, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        $todayMatches = [];
        $finishedMatches = [];
        $upcomingMatches = [];
        $playoffMatches = [];
        $groupStageFinished = true;

        foreach ($matches as $match) {

            if ($match['phaseType'] === 'cup') {
                $playoffMatches[] = $match;
            }

            if (
                $match['phaseType'] === 'group' &&
                $match['status'] !== 'finished'
            ) {
                $groupStageFinished = false;
            }

            if ($match['status'] === 'finished') {
                $finishedMatches[] = $match;
            } else {
                $upcomingMatches[] = $match;
            }

            if (date('H') < 8) {
                $start = strtotime('yesterday 08:00');
                $end = strtotime('today 08:00');
            } else {
                $start = strtotime('today 08:00');
                $end = strtotime('tomorrow 08:00');
            }

            $matchTime = strtotime($match['date']);

            if ($matchTime >= $start && $matchTime < $end) {
                $todayMatches[] = $match;
            }
        }

        $playoffStages = [];

        foreach ($playoffMatches as $match) {
            $playoffStages[$match['stage']][] = $match;
        }

        return [
            'matches' => $matches,
            'todayMatches' => $todayMatches,
            'finishedMatches' => array_slice(array_reverse($finishedMatches), 0, 10),
            'upcomingMatches' => array_slice($upcomingMatches, 0, 10),
            'playoffMatches' => $playoffMatches,
            'playoffStages' => $playoffStages,
            'groupStageFinished' => $groupStageFinished,
            'groups' => $groups,
        ];
    }
}