<?php
use Illuminate\Support\Facades\Storage;

namespace App\Services;

class WordSearchGenerator
{
    private int $size = 15;

    public function generate(): array
    {
       $words = json_decode(
    Storage::get('wordsearch/animals.json'),
    true
);

shuffle($words);

$words = array_slice($words, 0, 10);

        $grid = [];

        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                $grid[$row][$col] = chr(rand(65, 90));
            }
        }

        return [
            'grid' => $grid,
            'words' => $words
        ];
    }
}