<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FootballController;
use App\Http\Controllers\PrayerController;
use App\Http\Controllers\WordSearchController;
use App\Http\Controllers\SpinWheelController;

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
    'tv2-livsstil',
    'tv2-nyheter',

    'tvnorge',

    'tv3',
    'tv3-plus',
    'tv6',

    'fem',
    'rex',
    'vox',

    'discovery-channel',
    'national-geographic',

    'eurosport-1',
    'eurosport-norge',

    'tv2-sport-1',
    'tv2-sport-2',

    'v-sport-1',
    'v-sport-2',
    'v-sport-3',

    'v-film-premiere',
    'v-film-action',
    'v-series',

    'bbc-nordic',
    'disney-channel',
    'history',
    'tlc',
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

Route::get('/fotball', [FootballController::class, 'index']);
Route::get('/football', [FootballController::class, 'index']);
Route::get('/fotball-utskrift', [FootballController::class, 'print']);

Route::get('/bonnetider', [PrayerController::class, 'ringerike']);
Route::get('/bonnetider/utskrift', [PrayerController::class, 'printRingerike']);

Route::get('/bonnetider-ilseng', [PrayerController::class, 'ilseng']);
Route::get('/bonnetider-ilseng/utskrift', [PrayerController::class, 'printIlseng']);

use App\Http\Controllers\TidsfordrivController;

Route::get('/tidsfordriv', [TidsfordrivController::class, 'index']);
Route::post('/tidsfordriv/sudoku/print', [TidsfordrivController::class, 'printSudoku']);

Route::get('/wordsearch', [WordSearchController::class, 'index']);
Route::get('/wordsearch/print', [WordSearchController::class, 'print']);
Route::get('/ordjakt', [WordSearchController::class, 'index']);
Route::get('/ordjakt/utskrift', [WordSearchController::class, 'print']);

Route::get('/oppdrag', [SpinWheelController::class, 'index']);
