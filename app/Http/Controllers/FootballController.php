<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class FootballController extends Controller
{
public function index()
{
    $response = Http::get(
        'https://api.sportsnext.schibsted.io/v1/vg/tournaments/seasons/7767/schedule'
    );

    $data = $response->json();

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
    'date' => date(
    'd.m.Y H:i',
    strtotime($event['startDate'])
),
    'group' => $event['tournament']['groupName'] ?? '',
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

return view('football.index', [
    'matches' => $matches
]);
}

}