<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SportsNextSeasonControlService;
use App\Services\SportsNextSeasonSelectionService;
use Illuminate\Http\Request;

class SportsNextSeasonAdminController extends Controller
{
    public function check(SportsNextSeasonControlService $control, string $competition)
    {
        abort_unless(config('services.schibsted_sports.competitions.'.$competition), 404);
        $control->check($competition);
        return back()->with('sportsnext_season_message', 'Sesongdata er kontrollert.');
    }
    public function automatic(Request $request, SportsNextSeasonSelectionService $selections, string $competition)
    {
        $definition = config('services.schibsted_sports.competitions.'.$competition); abort_unless($definition, 404);
        $enabled = $request->boolean('automatic_enabled');
        if ($enabled && ! ($definition['automatic_supported'] ?? false)) return back()->with('sportsnext_season_message', 'Denne turneringen krever manuell godkjenning.');
        $selections->setAutomatic($competition, $enabled);
        return back()->with('sportsnext_season_message', $enabled ? 'Automatisk sesongbytte er aktivert.' : 'Automatisk sesongbytte er avslått.');
    }
    public function approve(SportsNextSeasonSelectionService $selections, string $competition)
    {
        $row = $selections->row($competition, 0); abort_unless($row && $row->candidate_season_id && $row->status === 'Klar for manuell godkjenning', 422);
        $definition = config('services.schibsted_sports.competitions.'.$competition);
        $selections->select($competition, (int) $row->candidate_season_id, 'manual', 'Administrator godkjente validert kandidat.', false, (int) config('services.schibsted_sports.'.$definition['season_config']));
        return back()->with('sportsnext_season_message', 'Ny sesong er valgt manuelt.');
    }
    public function rollback(Request $request, SportsNextSeasonSelectionService $selections, string $competition)
    {
        $season = $request->validate(['season_id' => ['required', 'integer', 'min:1']])['season_id'];
        $selections->select($competition, (int) $season, 'rollback', 'Administrator gikk tilbake til forrige sesong.', true);
        return back()->with('sportsnext_season_message', 'Tidligere sesong er valgt og automatikk er stanset.');
    }
    public function read(SportsNextSeasonSelectionService $selections, string $competition)
    {
        $selections->upsert($competition, ['notice_read_at' => now()]);
        return back();
    }
}
