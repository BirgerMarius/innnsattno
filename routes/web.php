<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\ProfessionalResource;
use App\ResourceCategory;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\FeedbackAdminController;
use App\Http\Controllers\Admin\ProfessionalResourceAdminController;
use App\Http\Controllers\Admin\ResourceCategoryController;
use App\Http\Controllers\EliteserienController;
use App\Http\Controllers\FootballController;
use App\Http\Controllers\FeedbackSubmissionController;
use App\Http\Controllers\PremierLeagueController;
use App\Http\Controllers\ProfessionalResourceController;
use App\Http\Controllers\PrayerController;
use App\Http\Controllers\WordSearchController;
use App\Http\Controllers\SpinWheelController;
use App\Http\Controllers\MonthCalendarController;
use App\Http\Controllers\VisitationController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\Admin\NewsAdminController;
use App\Http\Controllers\Admin\NewsSourceAdminController;

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
})->name('tv');

Route::get('/vaer', [WeatherController::class, 'index'])->name('weather.index');

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

Route::get('/forslag-og-tilbakemeldinger', [FeedbackSubmissionController::class, 'create'])
    ->name('feedback.create');
Route::post('/forslag-og-tilbakemeldinger', [FeedbackSubmissionController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('feedback.store');

Route::get('/fagstoff', [ProfessionalResourceController::class, 'index'])
    ->name('professional-resources.index');
Route::get('/nyheter', [NewsController::class, 'index'])->name('news.index');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLogin'])
        ->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.store');
    Route::middleware('admin.auth')->group(function () {
        Route::get('/', function () {
            $statusCounts = ProfessionalResource::query()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            return view('admin.dashboard', [
                'publishedCount' => (int) ($statusCounts[ProfessionalResource::STATUS_PUBLISHED] ?? 0),
                'draftCount' => (int) ($statusCounts[ProfessionalResource::STATUS_DRAFT] ?? 0),
                'activeCategoryCount' => ResourceCategory::where('is_active', true)->count(),
            ]);
        })->name('index');
        Route::post('/logout', [AdminAuthController::class, 'logout'])
            ->name('logout');
        Route::get('/fagstoff', [ProfessionalResourceAdminController::class, 'index'])
            ->name('professional-resources.index');
        Route::get('/fagstoff/opprett', [ProfessionalResourceAdminController::class, 'create'])
            ->name('professional-resources.create');
        Route::post('/fagstoff', [ProfessionalResourceAdminController::class, 'store'])
            ->name('professional-resources.store');
        Route::get('/fagstoff/kategorier', [ResourceCategoryController::class, 'index'])
            ->name('resource-categories.index');
        Route::get('/fagstoff/kategorier/opprett', [ResourceCategoryController::class, 'create'])
            ->name('resource-categories.create');
        Route::post('/fagstoff/kategorier', [ResourceCategoryController::class, 'store'])
            ->name('resource-categories.store');
        Route::get('/fagstoff/kategorier/{category}/rediger', [ResourceCategoryController::class, 'edit'])
            ->name('resource-categories.edit');
        Route::patch('/fagstoff/kategorier/{category}', [ResourceCategoryController::class, 'update'])
            ->name('resource-categories.update');
        Route::delete('/fagstoff/kategorier/{category}', [ResourceCategoryController::class, 'destroy'])
            ->name('resource-categories.destroy');
        Route::get('/fagstoff/{professionalResource}/rediger', [ProfessionalResourceAdminController::class, 'edit'])
            ->name('professional-resources.edit');
        Route::patch('/fagstoff/{professionalResource}', [ProfessionalResourceAdminController::class, 'update'])
            ->name('professional-resources.update');
        Route::get('/fagstoff/{professionalResource}/forhandsvis', [ProfessionalResourceAdminController::class, 'preview'])
            ->name('professional-resources.preview');
        Route::patch('/fagstoff/{professionalResource}/publiser', [ProfessionalResourceAdminController::class, 'publish'])
            ->name('professional-resources.publish');
        Route::patch('/fagstoff/{professionalResource}/avpubliser', [ProfessionalResourceAdminController::class, 'unpublish'])
            ->name('professional-resources.unpublish');
        Route::delete('/fagstoff/{professionalResource}', [ProfessionalResourceAdminController::class, 'destroy'])
            ->name('professional-resources.destroy');
        Route::get('/forslag', [FeedbackAdminController::class, 'index'])
            ->name('feedback.index');
        Route::patch('/forslag/{feedbackSubmission}', [FeedbackAdminController::class, 'update'])
            ->name('feedback.update');
        Route::get('/nyheter', [NewsAdminController::class, 'index'])->name('news.index');
        Route::patch('/nyheter/{newsArticle}', [NewsAdminController::class, 'update'])->name('news.update');
        Route::patch('/nyheter/{newsArticle}/status', [NewsAdminController::class, 'status'])->name('news.status');
        Route::get('/nyheter/kilder', [NewsSourceAdminController::class, 'index'])->name('news-sources.index');
        Route::patch('/nyheter/kilder/{newsSource}', [NewsSourceAdminController::class, 'toggle'])->name('news-sources.toggle');
        Route::post('/nyheter/kilder/{newsSource}/hent', [NewsSourceAdminController::class, 'fetch'])->name('news-sources.fetch');
    });
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
Route::get('/eliteserien', [EliteserienController::class, 'index'])->name('eliteserien.index');
Route::get('/eliteserien/utskrift', [EliteserienController::class, 'print'])->name('eliteserien.print');
Route::get('/eliteserien/test', [EliteserienController::class, 'test'])->name('eliteserien.test');
Route::get('/premier-league', [PremierLeagueController::class, 'index'])->name('premier-league.index');
Route::get('/premier-league/utskrift', [PremierLeagueController::class, 'print'])->name('premier-league.print');
Route::get('/premier-league/test', [PremierLeagueController::class, 'test']);

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

Route::get('/manedskalender', [MonthCalendarController::class, 'index'])->name('calendar.index');
Route::get('/manedskalender/utskrift', [MonthCalendarController::class, 'print'])->name('calendar.print');

Route::get('/oppdrag', [SpinWheelController::class, 'index']);
Route::get('/visitasjon', [VisitationController::class, 'index'])->name('visitation.index');
