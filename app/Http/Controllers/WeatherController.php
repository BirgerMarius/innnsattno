<?php

namespace App\Http\Controllers;

use App\Services\WeatherForecastService;
use App\Services\PrisonContext;
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

    public function showPrison(string $prison, WeatherForecastService $weather, PrisonContext $prisons)
    {
        $context = $prisons->prison($prison);

        return $this->show($weather, $context['weather_key'], 'Værmelding – '.$context['name']);
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
            return view('weather.index', [
                'forecast' => null,
                'error' => 'Vi klarte dessverre ikke å hente værmeldingen akkurat nå. Prøv igjen litt senere.',
                'heading' => $heading,
            ]);
        }
    }
}
