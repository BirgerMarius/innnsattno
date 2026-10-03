<?php

namespace App\Http\Controllers;

class FangenyttController extends Controller
{
    public function index()
    {
        return view('fangenytt.index', [
            'issues' => config('fangenytt.issues', []),
        ]);
    }
}
