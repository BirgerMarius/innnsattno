<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SudokuOnlineTest extends TestCase
{
    private string $solution = '534678912672195348198342567859761423426853791713924856961537284287419635345286179';

    public function test_online_sudoku_page_has_accessible_game_controls_and_print_options(): void
    {
        $response = $this->get('/tidsfordriv');

        $response->assertOk()
            ->assertSee('Skriv ut Sudoku')
            ->assertSee('Spill Sudoku på skjermen')
            ->assertSee('Slik spiller du Sudoku')
            ->assertSee('Sjekk svar')
            ->assertSee('Tøm mine svar')
            ->assertSee('Ny sudoku');

        $view = File::get(resource_path('views/tidsfordriv/index.blade.php'));
        $this->assertStringContainsString("game.puzzle[index] !== '0'", $view);
        $this->assertStringContainsString("game.answers[selectedIndex] = value", $view);
        $this->assertStringContainsString("game.answers[index] !== '' && game.answers[index] !== game.solution[index]", $view);
        $this->assertStringContainsString("const storageKey = 'innsatt.sudoku.online.v1'", $view);
        $this->assertStringContainsString("if (game.completed) {", $view);
        $this->assertStringContainsString("window.matchMedia('(prefers-reduced-motion: reduce)').matches", $view);
        $this->assertStringContainsString('confetti.remove()', $view);
    }

    public function test_online_endpoint_returns_validated_puzzle_without_exposing_api_key(): void
    {
        Http::fake(['www.youdosudoku.com/api' => Http::response($this->puzzle(), 200)]);

        $this->getJson('/tidsfordriv/sudoku?difficulty=medium')
            ->assertOk()
            ->assertJson($this->puzzle(['difficulty' => 'medium']));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.youdosudoku.com/api'
                && $request['difficulty'] === 'medium'
                && $request['solution'] === true;
        });
    }

    public function test_online_endpoint_handles_service_errors_and_invalid_payloads(): void
    {
        Http::fake(['www.youdosudoku.com/api' => Http::response(['puzzle' => 'nope', 'solution' => 'nope'], 200)]);

        $this->getJson('/tidsfordriv/sudoku?difficulty=easy')
            ->assertStatus(503)
            ->assertJson(['message' => 'Kunne ikke hente en Sudoku akkurat nå. Prøv igjen om litt.']);
    }

    public function test_print_sudoku_still_uses_validated_service_puzzles(): void
    {
        Http::fake(['www.youdosudoku.com/api' => Http::response($this->puzzle(), 200)]);

        $this->post('/tidsfordriv/sudoku/print', ['difficulty' => 'easy', 'pages' => 1, 'solution' => '1'])
            ->assertOk()
            ->assertSee('Sudoku')
            ->assertSee('Fasit');

        Http::assertSentCount(9);
    }

    private function puzzle(array $replace = []): array
    {
        return array_merge([
            'difficulty' => 'easy',
            'puzzle' => '530070000600195000098000060800060003400803001700020006060000280000419005000080079',
            'solution' => $this->solution,
        ], $replace);
    }
}
