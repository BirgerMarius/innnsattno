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

        $matches[] = [
            'date' => $event['startDate'] ?? '',
            'group' => $event['tournament']['groupName'] ?? '',
            'status' => $event['status']['type'] ?? '',
            'home' => $participants[$homeId]['name'] ?? 'Ukjent',
            'away' => $participants[$awayId]['name'] ?? 'Ukjent',
        ];
    }

    return view('football.index', [
        'matches' => $matches
    ]);
}