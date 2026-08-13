<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LearningPrototypeTest extends TestCase
{
    public function test_learning_index_shows_categories_and_example_sheets(): void
    {
        $this->get(route('learning.index'))
            ->assertOk()
            ->assertSee('Lær noe nytt')
            ->assertSee('Hverdagsliv og samfunn')
            ->assertSee('Kropp og helse')
            ->assertSee('Kunnskap og teknologi')
            ->assertSee('Et enkelt budsjett')
            ->assertSee('Søvn: kroppens vedlikeholdstid');
    }

    public function test_learning_sheet_has_readable_content_and_print_link(): void
    {
        $this->get(route('learning.show', ['hverdagsliv', 'budsjett']))
            ->assertOk()
            ->assertSee('Dette lærer du')
            ->assertSee('Kort forklart')
            ->assertSee('Visste du at')
            ->assertSee('Test deg selv')
            ->assertSee('Vil du lære mer?')
            ->assertSee('learning-figure--budget', false)
            ->assertSee(route('learning.print', ['hverdagsliv', 'budsjett']), false);
    }

    public function test_each_prototype_sheet_has_an_illustration_and_at_least_five_questions(): void
    {
        $sheets = [
            ['hverdagsliv', 'budsjett', 'learning-figure--budget'],
            ['hverdagsliv', 'nettsvindel', 'learning-figure--scam'],
            ['kropp-og-helse', 'sovn', 'learning-figure--sleep'],
            ['kunnskap-og-teknologi', 'gps', 'learning-figure--gps'],
        ];

        foreach ($sheets as [$category, $sheet, $figureClass]) {
            $response = $this->get(route('learning.show', [$category, $sheet]))
                ->assertOk()
                ->assertSee($figureClass, false)
                ->assertSee('Test deg selv');

            $this->assertGreaterThanOrEqual(5, substr_count($response->getContent(), '<li>'));
        }
    }

    public function test_print_view_excludes_site_navigation_and_has_print_action(): void
    {
        $this->get(route('learning.print', ['kunnskap-og-teknologi', 'gps']))
            ->assertOk()
            ->assertSee('GPS: slik finner telefonen veien')
            ->assertSee('learning-figure--gps', false)
            ->assertSee('Skriv ut')
            ->assertSee("window.addEventListener('afterprint'", false)
            ->assertSee('if (hasReturnedToLearningSheet)', false)
            ->assertSee('const learningSheetUrl = "\\/laer-noe-nytt\\/kunnskap-og-teknologi\\/gps"', false)
            ->assertSee('window.location.replace(learningSheetUrl)', false)
            ->assertSee('window.print()', false)
            ->assertSee('{ once: true }', false)
            ->assertDontSee('INNSATT.NO');
    }

    public function test_homepage_marks_learning_prototype_as_news_and_test(): void
    {
        Http::fake(['*' => Http::response([], 200)]);

        $this->get(route('tv'))
            ->assertOk()
            ->assertSee('Lær noe nytt')
            ->assertSee('aria-label="Ny testfunksjon"', false)
            ->assertSeeInOrder(['Lær noe nytt', 'NYHET', 'TEST']);
    }

    public function test_unknown_learning_sheet_returns_not_found(): void
    {
        $this->get('/laer-noe-nytt/ukjent/ark')->assertNotFound();
    }
}
