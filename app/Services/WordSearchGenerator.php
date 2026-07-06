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

    // Tomt rutenett
    $grid = [];

    for ($row = 0; $row < $this->size; $row++) {
        for ($col = 0; $col < $this->size; $col++) {
            $grid[$row][$col] = '';
        }
    }

    // Plasser ordene vannrett
    foreach ($words as $word) {

        $placed = false;

        while (!$placed) {

            $row = rand(0, $this->size - 1);
            $col = rand(0, $this->size - strlen($word));

            $fits = true;

            for ($i = 0; $i < strlen($word); $i++) {

                if (
                    $grid[$row][$col + $i] != '' &&
                    $grid[$row][$col + $i] != mb_substr($word, $i, 1)
                ) {
                    $fits = false;
                    break;
                }
            }

            if ($fits) {

                for ($i = 0; $i < strlen($word); $i++) {

                    $grid[$row][$col + $i] =
                        mb_substr($word, $i, 1);

                }

                $placed = true;

            }

        }

    }

    // Fyll resten med tilfeldige bokstaver
    $letters = preg_split('//u', 'ABCDEFGHIJKLMNOPQRSTUVWXYZÆØÅ', -1, PREG_SPLIT_NO_EMPTY);

    for ($row = 0; $row < $this->size; $row++) {

        for ($col = 0; $col < $this->size; $col++) {

            if ($grid[$row][$col] == '') {

                $grid[$row][$col] = $letters[array_rand($letters)];

            }

        }

    }

    return [

        'grid' => $grid,

        'words' => $words

    ];
}