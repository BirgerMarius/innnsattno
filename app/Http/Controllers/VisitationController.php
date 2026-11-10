<?php

namespace App\Http\Controllers;

use App\Services\PrisonContext;

class VisitationController extends Controller
{

    public function index(string $prison, PrisonContext $prisons)
    {
        $prisonConfig = $prisons->prison($prison);
        abort_unless($prisonConfig['features']['visitation'] ?? false, 404);

        return view('visitation.index', [
            'departments' => $prisonConfig['visitation_departments'],
        ]);
    }
}
