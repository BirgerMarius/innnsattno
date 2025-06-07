<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
 */

Route::get('/', function () {
    //return view('video.video');
    return redirect('tv');
});

Route::get('/video', function () {
    return view('video.video');
});

Route::get('/gammel', function () {
    return view('video.gammel_video');
});

Route::get('/tv', function () {
    return view('tv.guide');
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

    // tv2-nyhetskanalen, bbc-world-news, cnn-international, al-jazeera-english, cartoon-network, nickelodeon

    $response = Http::acceptJson()->get('https://tvguide.vg.no/backend/api/tv-schedule', [
        'channels' => 'dr1,c-more-hits,nrk1,nrk2,nrk3,tv2-direkte,tv2-livsstil,tv2-sport-1,tv2-sport-2,tv2-zebra,tv3,tv3-plus,tvnorge,rex,investigation-discovery,national-geographic,discovery-channel,bbc-world-news,mtv,fem,eurosport-norge,eurosport-1,al-jazeera-english,nickelodeon',
        'date' => Carbon::parse(now())->format('Y-m-d'),
        'tz' => 'Europe/Oslo',
    ]);
//    'channels' => 'dr1,c-more-hits,nicelodeon,nrk1,nrk2,nrk3,tv2-direkte,tv2-livsstil,tv2-sport-1,tv2-sport-2,tv2-zebra,tv3,tv3-plus,tvnorge,rex,investigation-discovery,history,national-geographic,discovery-channel,bbc-news,mtv,fem,eurosport-norge,eurosport-1,al-jazzera-english,mtv-00s,mtv-80s',

    if ($response->serverError()) {
        return "Innsatt.no klarer ikke hente TV-guide fra vg.no - dette kan være fordi siden er nede.";
    } else {
        $channels = json_decode($response, true);
        // dd($channels);
        return view('pdf')->with(['channels' => $channels]);
    }

});

Route::get('/print2', function () {


    $response = Http::acceptJson()->get('https://tvguide.vg.no/backend/api/tv-schedule', [
        'channels' => 'c-more-first,c-more-hits,c-more-series,nrk1,nrk2,nrk3,tv2-direkte,tv2-livsstil,tv2-sport-1,tv2-sport-2,tv2-zebra,tv3,tv3-plus,tvnorge,rex,investigation-discovery,history,national-geographic,discovery-channel,bbc-earth,mtv,fem,eurosport-norge,eurosport-1,vox,discovery-science',
        'date' => Carbon::parse(now())->format('Y-m-d'),
        'tz' => 'Europe/Oslo',
    ]);


        $channels = json_decode($response, true);
        // dd($channels);
        return view('pdf2')->with(['channels' => $channels]);


});

Route::get('tw', function () {
    return view('welcome');
});

Route::get('/redirect', function () {
    $referer = request()->headers->get('referer');
    return redirect()->away($referer);
});

