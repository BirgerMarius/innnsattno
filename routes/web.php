<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('tv');
});

Route::get('/tv', function () {
    return view('tv.guide');
});

Route::get('/ilseng', function () {
    return redirect('print-ilseng');
});

Route::get('/guide', function () {
    return redirect('tv');
});

Route::get('/tvguide', function () {
    return redirect('tv');
});

Route::get('/tv-guide', function () {
    return redirect('tv');
});

Route::get('/tv-guiden', function () {
    return redirect('tv');
});

Route::get('/print', function () {

    $channels = [
        'nrk1',
        'nrk2',
        'nrk3',
        'tv2-direkte',
        'tv2-zebra',
        'tvnorge',
        'tv3',
        'tv3-plus',

        'tv2-sport-1',
        'tv2-sport-2',
        'eurosport-norge',
        'eurosport-1',

        'c-more-hits',
        'tv2-livsstil',
        'rex',
        'fem',

        'national-geographic',
        'discovery-channel',
        'investigation-discovery',

        'bbc-world-news',
        'al-jazeera-english',

        'nickelodeon',

        'dr1',
        'mtv',
    ];

    $response = Http::acceptJson()->get('https://tvguide.vg.no/backend/api/tv-schedule', [
        'channels' => implode(',', $channels),
        'date' => Carbon::parse(now())->format('Y-m-d'),
        'tz' => 'Europe/Oslo',
    ]);

    if ($response->serverError()) {
        return "Innsatt.no klarer ikke hente TV-guide fra vg.no - dette kan være fordi siden er nede.";
    }

    $tvChannels = json_decode($response, true);

    return view('pdf')->with(['channels' => $tvChannels]);
});

Route::get('/print-ilseng', function () {

    $channels = [
        'nrk1',
        'nrk2',
        'nrk3',
        'tv2-direkte',
        'tv2-zebra',
        'tvnorge',
        'tv3',
        'tv3-plus',

        'tv2-sport-1',
        'tv2-sport-2',
        'eurosport-norge',
        'eurosport-1',
    ];

    $response = Http::acceptJson()->get('https://tvguide.vg.no/backend/api/tv-schedule', [
        'channels' => implode(',', $channels),
        'date' => Carbon::parse(now())->format('Y-m-d'),
        'tz' => 'Europe/Oslo',
    ]);

    if ($response->serverError()) {
        return "Innsatt.no klarer ikke hente TV-guide fra vg.no - dette kan være fordi siden er nede.";
    }

    $tvChannels = json_decode($response, true);

return view('pdf-ilseng')->with(['channels' => $tvChannels]);
});

Route::get('/test', function () {
    return '<h1>Dette er testsiden min</h1>';
});