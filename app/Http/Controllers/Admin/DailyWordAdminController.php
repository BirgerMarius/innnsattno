<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DailyWordService;

class DailyWordAdminController extends Controller
{
    public function deactivate(string $word, DailyWordService $dailyWords)
    {
        abort_unless($dailyWords->deactivate($word), 404);

        return redirect()->route('admin.index', [], 303)->with('status', 'Dagens ord er deaktivert.');
    }

    public function activate(string $word, DailyWordService $dailyWords)
    {
        abort_unless($dailyWords->activate($word), 404);

        return redirect()->route('admin.index', [], 303)->with('status', 'Dagens ord er aktivert igjen.');
    }
}
