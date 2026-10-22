<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class SudokuService
{
    private const LEVELS = ['easy', 'medium', 'hard'];

    public function get(string $difficulty): array
    {
        if (!in_array($difficulty, self::LEVELS, true)) {
            throw new RuntimeException('Ukjent vanskelighetsgrad.');
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(8)
                ->withHeaders(['x-api-key' => env('YOUDOSUDOKU_API_KEY')])
                ->post('https://www.youdosudoku.com/api', [
                    'difficulty' => $difficulty,
                    'solution' => true,
                    'array' => false,
                ]);
        } catch (\Throwable $exception) {
            throw new RuntimeException('Sudoku-tjenesten er ikke tilgjengelig akkurat nå.', 0, $exception);
        }

        if (!$response->successful() || !is_array($response->json())) {
            throw new RuntimeException('Sudoku-tjenesten svarte ikke som forventet.');
        }

        $sudoku = $response->json();
        $puzzle = $sudoku['puzzle'] ?? null;
        $solution = $sudoku['solution'] ?? null;

        if (!is_string($puzzle) || !is_string($solution)
            || !preg_match('/^[0-9]{81}$/', $puzzle)
            || !preg_match('/^[1-9]{81}$/', $solution)
            || !$this->isValidSolution($solution)
            || !$this->matchesPuzzle($puzzle, $solution)) {
            throw new RuntimeException('Sudoku-tjenesten sendte en ugyldig oppgave.');
        }

        return [
            'difficulty' => $difficulty,
            'puzzle' => $puzzle,
            'solution' => $solution,
        ];
    }

    private function matchesPuzzle(string $puzzle, string $solution): bool
    {
        foreach (str_split($puzzle) as $index => $value) {
            if ($value !== '0' && $value !== $solution[$index]) {
                return false;
            }
        }

        return true;
    }

    private function isValidSolution(string $solution): bool
    {
        for ($index = 0; $index < 9; $index++) {
            $row = substr($solution, $index * 9, 9);
            $column = '';
            for ($cell = 0; $cell < 9; $cell++) {
                $column .= $solution[$cell * 9 + $index];
            }

            if (count(array_unique(str_split($row))) !== 9) {
                return false;
            }
            if (count(array_unique(str_split($column))) !== 9) {
                return false;
            }
        }

        for ($row = 0; $row < 9; $row += 3) {
            for ($column = 0; $column < 9; $column += 3) {
                $box = '';
                for ($rowOffset = 0; $rowOffset < 3; $rowOffset++) {
                    $box .= substr($solution, (($row + $rowOffset) * 9) + $column, 3);
                }
                if (count(array_unique(str_split($box))) !== 9) {
                    return false;
                }
            }
        }

        return true;
    }
}
