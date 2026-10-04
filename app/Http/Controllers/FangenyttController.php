<?php

namespace App\Http\Controllers;

use App\Services\FangenyttArchive;
use Illuminate\Support\Facades\Storage;

class FangenyttController extends Controller
{
    public function index(FangenyttArchive $archive)
    {
        return view('fangenytt.index', [
            'issues' => $archive->issues(),
        ]);
    }

    public function pdf(int $number, FangenyttArchive $archive)
    {
        $issue = $archive->findIssue($number);

        abort_unless($issue, 404);

        $disk = Storage::disk(FangenyttArchive::DISK);
        abort_unless($disk->exists($issue['local_file']), 404, 'Fangenytt-PDF-en er ikke arkivert lokalt ennå.');

        return response()->file($disk->path($issue['local_file']), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$issue['local_file'].'"',
        ]);
    }

    public function cover(int $number, FangenyttArchive $archive)
    {
        $issue = $archive->findIssue($number);

        abort_unless($issue, 404);

        $disk = Storage::disk(FangenyttArchive::DISK);
        $coverFile = $archive->coverFile($issue);
        abort_unless($disk->exists($coverFile), 404);

        return response()->file($disk->path($coverFile), [
            'Content-Type' => 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.basename($coverFile).'"',
        ]);
    }
}
