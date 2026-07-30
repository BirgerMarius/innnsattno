<?php

namespace App\Http\Controllers;

use App\Services\WeatherForecastService;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherController extends Controller
{
    public function index(WeatherForecastService $weather)
    {
        return $this->show($weather, 'ringerike', 'Værmelding – Tyristrand / Ringerike fengsel');
    }

    public function ilseng(WeatherForecastService $weather)
    {
        return $this->show($weather, 'ilseng', 'Værmelding – Ilseng fengsel');
    }

    private function show(WeatherForecastService $weather, string $locationKey, string $heading)
    {
        try {
            return view('weather.index', [
                'forecast' => $weather->forecast($locationKey),
                'error' => null,
                'heading' => $heading,
            ]);
        } catch (Throwable $exception) {
            Log::warning("Kunne ikke hente værmelding for {$locationKey}.", [
                'exception' => $exception->getMessage(),
            ]);

            return view('weather.index', [
                'forecast' => null,
                'error' => 'Vi klarte dessverre ikke å hente værmeldingen akkurat nå. Prøv igjen litt senere.',
                'heading' => $heading,
            ]);
        }
    }
}
