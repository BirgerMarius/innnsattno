<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WordSearchInteractionTest extends TestCase
{
    public function test_wordsearch_shows_help_and_completion_message(): void
    {
        $response = $this->get('/ordjakt?kategori=dyr');

        $response->assertOk();
        $response->assertSee('Du kan også spille direkte på skjermen! Hold museknappen inne og dra fra første til siste bokstav i ordet. På mobil og nettbrett bruker du fingeren. Ordene markeres automatisk når du finner dem.');
        $response->assertSee('🎉 Gratulerer! Du fant alle ordene!');
    }

    public function test_completion_confetti_is_one_time_accessible_and_excluded_from_print(): void
    {
        $view = File::get(resource_path('views/wordsearch/index.blade.php'));
        $css = File::get(public_path('css/custom/app.css'));

        $this->assertStringContainsString('let completionCelebrated = false;', $view);
        $this->assertStringContainsString('if (completionCelebrated)', $view);
        $this->assertStringContainsString("window.matchMedia('(prefers-reduced-motion: reduce)').matches", $view);
        $this->assertStringContainsString("confetti.setAttribute('aria-hidden', 'true');", $view);
        $this->assertStringContainsString('confetti.remove();', $view);
        $this->assertMatchesRegularExpression('/if \(!word \|\| foundWords\.has\(word\.word\)\) \{\s*return;\s*\}/s', $view);
        $this->assertMatchesRegularExpression('/if \(foundWords\.size === wordsearchWords\.length\) \{\s*completeMessage\.style\.display = \'block\';\s*celebrateCompletion\(\);\s*\}/s', $view);
        $this->assertStringContainsString('.wordsearch-confetti {', $css);
        $this->assertStringContainsString('pointer-events: none;', $css);
        $this->assertMatchesRegularExpression('/@media print \{(?s:.*?\.wordsearch-help,\s*\.wordsearch-confetti \{\s*display: none !important;)/', $css);
    }
}
