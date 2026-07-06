<?php

namespace App\Http\Controllers;

use App\Services\WordSearchGenerator;
use Illuminate\Support\Facades\File;

class WordSearchController extends Controller
{
    protected WordSearchGenerator $generator;

    public function __construct(WordSearchGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function index()
    {
        $data = $this->createPuzzle();

        return view('wordsearch.index', $data);
    }

    public function print()
    {
        $data = $this->createPuzzle();

        return view('wordsearch.print', $data);
    }

    private function createPuzzle(): array
    {
        $path = storage_path('app/wordsearch/animals.json');

        if (! File::exists($path)) {
            abort(500, 'Fant ikke animals.json');
        }

        $words = json_decode(File::get($path), true);

        if (! is_array($words)) {
            abort(500, 'Ugyldig JSON i animals.json');
        }

        $words = array_map(function ($word) {
            return mb_strtoupper(trim($word), 'UTF-8');
        }, $words);

        $words = array_unique($words);

        shuffle($words);

        $selectedWords = array_slice($words, 0, 15);

        $puzzle = $this->generator->generate($selectedWords, 18);

        $placedWords = array_column($puzzle['placed'], 'word');

sort($placedWords);

return [
    'grid'   => $puzzle['grid'],
    'placed' => $puzzle['placed'],
    'words'  => $placedWords,
    'size'   => 18,
];
    }
}