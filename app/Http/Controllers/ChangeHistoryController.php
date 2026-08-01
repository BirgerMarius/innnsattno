<?php

namespace App\Http\Controllers;

use App\Services\ChangeHistoryService;

class ChangeHistoryController extends Controller
{
    public function index(ChangeHistoryService $changeHistory)
    {
        return view('change-history.index', [
            'changeHistory' => $changeHistory->get(),
        ]);
    }
}
