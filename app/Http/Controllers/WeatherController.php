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

        abort_unless($context['features']['weather'] ?? false, 404);

        return $this->show($weather, $prison, 'Værmelding – '.$context['name']);
    }

    private function show(WeatherForecastService $weather, string $locationKey, string $heading)
    {
        try {
            $forecast = $weather->forecast($locationKey);

            return view('weather.index', [
                'forecast' => $forecast,
                'error' => null,
                'heading' => $heading,
                'status' => empty($forecast['days'])
                    ? 'unavailable'
                    : ($weather->coversToday($forecast) ? 'current' : 'outdated'),
            ]);
        } catch (Throwable $exception) {
            return view('weather.index', [
                'forecast' => null,
                'error' => 'Værinformasjon er ikke tilgjengelig akkurat nå.',
                'heading' => $heading,
                'status' => 'unavailable',
            ]);
        }
    }
}
