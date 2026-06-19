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
        dd(reset($data['participants']));

        return view('football.index', [
            'data' => $data
        ]);
    }
}