<?php

namespace App\Http\Controllers;

use App\Services\HeaderMessageService;

class HeaderMessageController extends Controller
{
    public function show(HeaderMessageService $messages)
    {
        return response()->json([
            'html' => view('partials.header-message', ['headerMessage' => $messages->forDate()])->render(),
            'refresh_at' => $messages->nextChangeAt()?->toIso8601String(),
        ])->header('Cache-Control', 'no-store, max-age=0');
    }
}
