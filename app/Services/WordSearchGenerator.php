<?php

namespace App\Services;

class WordSearchGenerator
{
    private int $size = 15;

    private array $categories = [
        'dyr' => [
            'name' => 'Dyr',
            'words' => ['Bjørn', 'Elg', 'Rev', 'Ulv', 'Hare', 'Ekorn', 'Mus', 'Sel', 'Gaupe', 'Bever', 'Rådyr', 'Hjort', 'Rein', 'Oter', 'Mink', 'Hund', 'Katt', 'Sau'],
        ],
        'fugler' => [
            'name' => 'Fugler',
            'words' => ['Ugle', 'Ørn', 'Måke', 'Ravn', 'Falk', 'Spurv', 'Due', 'Kråke', 'Svale', 'Stær', 'Hauk', 'Lerke', 'Svane', 'And', 'Gås', 'Tiur', 'Trost', 'Hegre'],
        ],
        'norske-byer' => [
            'name' => 'Norske byer',
            'words' => ['Oslo', 'Bergen', 'Trondheim', 'Stavanger', 'Drammen', 'Tromsø', 'Bodø', 'Molde', 'Hamar', 'Skien', 'Larvik', 'Halden', 'Moss', 'Alta', 'Voss', 'Arendal', 'Lillehammer'],
        ],
        'land-i-verden' => [
            'name' => 'Land i verden',
            'words' => ['Norge', 'Sverige', 'Danmark', 'Finland', 'Island', 'Tyskland', 'Frankrike', 'Spania', 'Italia', 'Polen', 'Canada', 'Brasil', 'India', 'Japan', 'Kina', 'Egypt', 'Kenya'],
        ],
        'yrker' => [
            'name' => 'Yrker',
            'words' => ['Lærer', 'Lege', 'Kokk', 'Snekker', 'Maler', 'Sjåfør', 'Bonde', 'Politi', 'Dommer', 'Frisør', 'Murer', 'Pilot', 'Tannlege', 'Sykepleier', 'Baker', 'Rørlegger'],
        ],
        'sport' => [
            'name' => 'Sport',
            'words' => ['Fotball', 'Håndball', 'Ski', 'Løping', 'Svømming', 'Tennis', 'Golf', 'Boksing', 'Bryting', 'Sykkel', 'Ishockey', 'Basket', 'Volleyball', 'Roing', 'Skøyter'],
        ],
        'mat-og-drikke' => [
            'name' => 'Mat og drikke',
            'words' => ['Brød', 'Melk', 'Ost', 'Egg', 'Fisk', 'Kylling', 'Potet', 'Gulrot', 'Eple', 'Banan', 'Kaffe', 'Te', 'Vann', 'Ris', 'Pasta', 'Suppe', 'Salat'],
        ],
        'natur' => [
            'name' => 'Natur',
            'words' => ['Fjell', 'Skog', 'Elv', 'Innsjø', 'Hav', 'Strand', 'Stein', 'Myr', 'Eng', 'Tre', 'Blomst', 'Gress', 'Snø', 'Is', 'Vind', 'Regn', 'Sol'],
        ],
        'kroppen' => [
            'name' => 'Kroppen',
            'words' => ['Hode', 'Arm', 'Bein', 'Hånd', 'Fot', 'Rygg', 'Mage', 'Hjerte', 'Lunge', 'Øye', 'Øre', 'Nese', 'Munn', 'Tann', 'Kne', 'Albue', 'Skulder'],
        ],
        'hverdagsord' => [
            'name' => 'Hverdagsord',
            'words' => ['Hus', 'Rom', 'Bord', 'Stol', 'Dør', 'Vindu', 'Klokke', 'Telefon', 'Nøkkel', 'Bok', 'Avis', 'Kopp', 'Seng', 'Lampe', 'Sko', 'Jakke', 'Veske'],
        ],
    ];

    public function categories(): array
    {
        return $this->categories;
    }

    public function defaultCategory(): string
    {
        return array_key_first($this->categories);
    }

    public function generate(?string $categoryKey = null): array
    {
        $categoryKey = array_key_exists((string) $categoryKey, $this->categories)
            ? (string) $categoryKey
            : $this->defaultCategory();

        $words = $this->categories[$categoryKey]['words'];
        shuffle($words);

        $grid = array_fill(0, $this->size, array_fill(0, $this->size, ''));
        $placements = [];

        foreach ($words as $displayWord) {
            $boardWord = $this->normalizeWord($displayWord);

            if (strlen($boardWord) > $this->size) {
                continue;
            }

            $placement = $this->placeWord($grid, $boardWord);

            if ($placement) {
                $placements[] = array_merge($placement, [
                    'display' => $displayWord,
                    'word' => $boardWord,
                ]);
            }

            if (count($placements) >= 15) {
                break;
            }
        }

        $this->fillEmptyCells($grid);

        usort($placements, function ($a, $b) {
            return strcasecmp($a['display'], $b['display']);
        });

        return [
            'grid' => $grid,
            'words' => $placements,
            'category' => $this->categories[$categoryKey]['name'],
            'categoryKey' => $categoryKey,
        ];
    }

    private function normalizeWord(string $word): string
    {
        $word = str_replace(['Æ', 'Ø', 'Å', 'æ', 'ø', 'å'], ['AE', 'O', 'A', 'AE', 'O', 'A'], $word);

        return strtoupper(preg_replace('/[^A-Za-z]/', '', $word));
    }

    private function placeWord(array &$grid, string $word): ?array
    {
        $directions = [
            [0, 1],
            [1, 0],
            [1, 1],
            [-1, 1],
        ];

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            [$rowStep, $colStep] = $directions[array_rand($directions)];
            $row = rand(0, $this->size - 1);
            $col = rand(0, $this->size - 1);
            $endRow = $row + ($rowStep * (strlen($word) - 1));
            $endCol = $col + ($colStep * (strlen($word) - 1));

            if ($endRow < 0 || $endRow >= $this->size || $endCol < 0 || $endCol >= $this->size) {
                continue;
            }

            if (!$this->wordFits($grid, $word, $row, $col, $rowStep, $colStep)) {
                continue;
            }

            $cells = [];

            for ($i = 0; $i < strlen($word); $i++) {
                $currentRow = $row + ($rowStep * $i);
                $currentCol = $col + ($colStep * $i);
                $grid[$currentRow][$currentCol] = $word[$i];
                $cells[] = [$currentRow, $currentCol];
            }

            return ['cells' => $cells];
        }

        return null;
    }

    private function wordFits(array $grid, string $word, int $row, int $col, int $rowStep, int $colStep): bool
    {
        for ($i = 0; $i < strlen($word); $i++) {
            $letter = $grid[$row + ($rowStep * $i)][$col + ($colStep * $i)];

            if ($letter !== '' && $letter !== $word[$i]) {
                return false;
            }
        }

        return true;
    }

    private function fillEmptyCells(array &$grid): void
    {
        $letters = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ');

        for ($row = 0; $row < $this->size; $row++) {
            for ($col = 0; $col < $this->size; $col++) {
                if ($grid[$row][$col] === '') {
                    $grid[$row][$col] = $letters[array_rand($letters)];
                }
            }
        }
    }
}
