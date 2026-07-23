<?php

namespace App\Http\Controllers;

use App\Services\WeatherForecastService;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherController extends Controller
{
    public function index(WeatherForecastService $weather)
    {
        try {
            return view('weather.index', [
                'forecast' => $weather->forecast(),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Kunne ikke hente værmelding for Tyristrand.', [
                'exception' => $exception->getMessage(),
            ]);

            return view('weather.index', [
                'forecast' => null,
                'error' => 'Vi klarte dessverre ikke å hente værmeldingen akkurat nå. Prøv igjen litt senere.',
            ]);
        }
    }
}
