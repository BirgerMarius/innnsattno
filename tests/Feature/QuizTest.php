<?php

namespace Tests\Feature;

use App\Services\WikidataQuizSourceService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QuizTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.openai.api_key' => null,
            'services.wikidata.sparql_url' => 'https://query.wikidata.test/sparql',
            'services.wikidata.user_agent' => 'innsatt.no test suite',
            'services.wikidata.cache_ttl' => 3600,
        ]);
    }

    /** @test */
    public function quiz_setup_page_works_without_an_openai_key(): void
    {
        $this->get('/quiz')->assertOk()
            ->assertSee('Lag en quiz')
            ->assertSee('Eget tema')
            ->assertSee('genereres automatisk fra åpne datakilder')
            ->assertDontSee('genereres med kunstig intelligens')
            ->assertSee('Quiz-generatoren er under testing')
            ->assertSee('innsatt@innsatt.no')
            ->assertSee(route('feedback.create'), false)
            ->assertDontSee('Quiz-generatoren er ikke konfigurert');
    }

    /** @test */
    public function custom_topic_is_passive_and_not_sent_to_the_open_data_generator(): void
    {
        $this->fakeFacts();

        $this->get('/quiz')->assertOk()
            ->assertSee('Egendefinerte temaer krever AI-generering')
            ->assertSee('disabled', false)
            ->assertDontSee('name="custom_topic"', false);

        $this->post('/quiz', $this->validInput(['custom_topic' => 'Dette skal ignoreres']))
            ->assertRedirect(route('quiz.show'));

        $this->get('/quiz/resultat')->assertOk()->assertDontSee('Dette skal ignoreres');
    }

    /** @test */
    public function norwegian_open_quiz_is_generated_from_wikidata_without_openai(): void
    {
        $this->fakeFacts('capital');

        $this->post('/quiz', $this->validInput())->assertRedirect(route('quiz.show'));

        $this->get('/quiz/resultat')->assertOk()
            ->assertSee('Quiz fra åpne data')
            ->assertSee('Hva er hovedstaden i')
            ->assertSee('Spørsmålene er generert fra åpne datakilder');

        Http::assertSent(function (Request $request) {
            return str_starts_with($request->url(), 'https://query.wikidata.test/sparql')
                && str_contains($request['query'], 'wd:Q6256')
                && $request->hasHeader('User-Agent');
        });
    }

    /** @test */
    public function english_quiz_uses_english_templates_and_labels(): void
    {
        $this->fakeFacts('element_symbol');

        $this->post('/quiz', $this->validInput([
            'language' => 'en',
            'categories' => ['science'],
        ]))->assertRedirect(route('quiz.show'));

        $this->get('/quiz/resultat')->assertOk()
            ->assertSee('Open data quiz')
            ->assertSee('Quiz preview')
            ->assertSee('Nature and science')
            ->assertSee('What is the chemical symbol for');
    }

    /** @test */
    public function multiple_choice_has_four_plausible_same_type_options_and_a_correct_letter(): void
    {
        $this->fakeFacts('capital');

        $this->post('/quiz', $this->validInput(['question_type' => 'multiple_choice']))
            ->assertRedirect(route('quiz.show'));

        $this->get('/quiz/resultat')->assertOk()->assertSee('Capital 1');
        $this->get('/quiz/utskrift')->assertOk()
            ->assertSee('A.')
            ->assertSee('B.')
            ->assertSee('C.')
            ->assertSee('D.');
        $this->get('/quiz/fasit')->assertOk()->assertSee('Capital 1');

        $quiz = session('generated_quiz.quiz');
        foreach ($quiz['questions'] as $question) {
            $this->assertCount(4, $question['options']);
            $this->assertContains($question['correct_answer'], $question['options']);
            $this->assertSame($question['correct_answer'], $question['options'][ord($question['correct_option']) - ord('A')]);
        }
    }

    /** @test */
    public function element_answers_use_the_requested_chemical_symbols_in_open_and_multiple_choice_quizzes(): void
    {
        $this->fakeElementFacts();
        $sourceFacts = app(WikidataQuizSourceService::class)->facts('science', 'no');
        $answersByElement = array_column($sourceFacts, 'answer', 'subject');
        $this->assertSame('Si', $answersByElement['silisium']);
        $this->assertSame('Ag', $answersByElement['sølv']);

        $this->post('/quiz', $this->validInput(['categories' => ['science']]))
            ->assertRedirect(route('quiz.show'));

        $openQuiz = session('generated_quiz.quiz');
        $this->assertElementAnswersMatchQuestions($openQuiz['questions']);
        $answerKey = $this->get('/quiz/fasit')->assertOk();
        foreach ($openQuiz['questions'] as $question) {
            $answerKey->assertSee($question['correct_answer']);
        }

        Cache::flush();
        $this->fakeElementFacts();
        $this->post('/quiz', $this->validInput([
            'categories' => ['science'],
            'question_type' => 'multiple_choice',
        ]))->assertRedirect(route('quiz.show'));

        $multipleChoiceQuiz = session('generated_quiz.quiz');
        $this->assertElementAnswersMatchQuestions($multipleChoiceQuiz['questions']);
        foreach ($multipleChoiceQuiz['questions'] as $question) {
            $this->assertContains($question['correct_answer'], $question['options']);
            $this->assertSame($question['correct_answer'], $question['options'][ord($question['correct_option']) - ord('A')]);
        }
    }

    /** @test */
    public function category_selection_and_mixed_selection_use_wikidata_queries(): void
    {
        $this->fakeFacts('element_symbol');
        $this->post('/quiz', $this->validInput(['categories' => ['science']]));
        Http::assertSent(fn (Request $request) => str_contains($request['query'], 'wd:Q11344'));

        Cache::flush();
        $this->fakeFacts('capital');
        $this->post('/quiz', $this->validInput(['categories' => ['mixed']]));
        Http::assertSentCount(3);
    }

    /** @test */
    public function each_difficulty_setting_produces_a_quiz_with_the_category_aware_heuristic(): void
    {
        foreach (['easy', 'medium', 'hard', 'mixed'] as $difficulty) {
            Cache::flush();
            $this->fakeFacts('capital', 80);
            $this->post('/quiz', $this->validInput(['difficulty' => $difficulty]))
                ->assertRedirect(route('quiz.show'));
            $this->assertCount(10, session('generated_quiz.quiz.questions'));
        }
    }

    /** @test */
    public function malformed_or_incomplete_wikidata_records_are_filtered(): void
    {
        Http::fake([
            'query.wikidata.test/*' => Http::response(['results' => ['bindings' => [
                $this->binding('Valid country', 'Valid capital', 'capital', 10),
                $this->binding('Q999', 'Ignored answer', 'capital', 9),
                $this->binding('Missing answer', '', 'capital', 8),
                $this->binding('Same', 'Same', 'capital', 7),
            ]]]),
        ]);

        $facts = app(WikidataQuizSourceService::class)->facts('geography', 'no');

        $this->assertCount(1, $facts);
        $this->assertSame('Valid country', $facts[0]['subject']);
    }

    /** @test */
    public function source_failure_is_handled_without_a_crash(): void
    {
        Http::fake(['query.wikidata.test/*' => Http::response(['error' => 'down'], 503)]);

        $this->from('/quiz')->post('/quiz', $this->validInput())
            ->assertRedirect('/quiz')
            ->assertSessionHasErrors(['generation' => 'Den åpne datakilden er midlertidig utilgjengelig. Prøv igjen senere.']);
    }

    /** @test */
    public function too_few_suitable_facts_gives_a_readable_message(): void
    {
        $this->fakeFacts('capital', 4);

        $this->from('/quiz')->post('/quiz', $this->validInput())
            ->assertRedirect('/quiz')
            ->assertSessionHasErrors('generation');
    }

    /** @test */
    public function unavailable_categories_and_invalid_choices_are_rejected_without_source_calls(): void
    {
        Http::fake();

        $this->from('/quiz')->post('/quiz', $this->validInput([
            'categories' => ['food_drink'],
            'question_count' => 11,
        ]))->assertRedirect('/quiz')->assertSessionHasErrors(['categories.0', 'question_count']);

        Http::assertNothingSent();
    }

    /** @test */
    public function easy_science_only_uses_familiar_established_elements(): void
    {
        $this->fakeFacts('element_symbol', 50);

        $this->post('/quiz', $this->validInput([
            'categories' => ['science'],
            'difficulty' => 'easy',
        ]))->assertRedirect(route('quiz.show'));

        foreach (session('generated_quiz.quiz.questions') as $question) {
            $this->assertContains($question['correct_answer'], ['H', 'He', 'C', 'N', 'O', 'Na', 'Mg', 'Al', 'Si', 'P', 'S', 'Cl', 'K', 'Ca', 'Fe', 'Cu', 'Zn', 'Ag', 'Sn', 'I', 'Au', 'Hg', 'Pb']);
            $this->assertNotContains($question['correct_answer'], ['Uue', 'Og', 'Pr']);
        }
    }

    /** @test */
    public function ambiguous_and_disputed_capital_questions_are_not_used(): void
    {
        $bindings = [
            $this->binding('Kongo', 'Brazzaville', 'capital', 100, 'Q971'),
            $this->binding('Staten Palestina', 'Øst-Jerusalem', 'capital', 99, 'Q219060'),
        ];

        foreach (['Q20', 'Q34', 'Q35', 'Q33', 'Q189', 'Q183', 'Q142', 'Q29', 'Q38', 'Q145'] as $number => $id) {
            $bindings[] = $this->binding('Trygt land '.$number, 'Trygg hovedstad '.$number, 'capital', 90 - $number, $id);
        }

        Http::fake(['query.wikidata.test/*' => Http::response(['results' => ['bindings' => $bindings]])]);

        $this->post('/quiz', $this->validInput(['difficulty' => 'easy']))->assertRedirect(route('quiz.show'));
        $page = $this->get('/quiz/resultat')->assertOk();

        $page->assertDontSee('Kongo')->assertDontSee('Staten Palestina');
        $this->assertCount(10, session('generated_quiz.quiz.questions'));
    }

    /** @test */
    public function mixed_quizzes_are_evenly_distributed_across_the_active_categories(): void
    {
        $this->fakeMixedFacts();

        $this->post('/quiz', $this->validInput([
            'question_count' => 20,
            'categories' => ['mixed'],
            'difficulty' => 'medium',
        ]))->assertRedirect(route('quiz.show'));

        $counts = array_count_values(array_column(session('generated_quiz.quiz.questions'), 'category'));
        $this->assertCount(3, $counts);
        $this->assertLessThanOrEqual(1, max($counts) - min($counts));
    }

    /** @test */
    public function birth_year_multiple_choice_distractors_stay_close_to_the_correct_year(): void
    {
        $bindings = [];
        for ($number = 0; $number < 12; $number++) {
            $bindings[] = $this->binding('Norsk person '.$number, (string) (1900 + $number), 'birth_year', 100 - $number, 'Q'.(6000 + $number));
        }
        Http::fake(['query.wikidata.test/*' => Http::response(['results' => ['bindings' => $bindings]])]);

        $this->post('/quiz', $this->validInput([
            'categories' => ['norway'],
            'question_type' => 'multiple_choice',
        ]))->assertRedirect(route('quiz.show'));

        foreach (session('generated_quiz.quiz.questions') as $question) {
            foreach ($question['options'] as $option) {
                $this->assertLessThanOrEqual(25, abs((int) $question['correct_answer'] - (int) $option));
            }
        }
    }

    private function validInput(array $overrides = []): array
    {
        return array_merge([
            'question_count' => 10,
            'difficulty' => 'medium',
            'language' => 'no',
            'question_type' => 'open',
            'categories' => ['geography'],
        ], $overrides);
    }

    private function fakeFacts(string $factType = 'capital', int $count = 50): void
    {
        $bindings = [];

        for ($number = 1; $number <= $count; $number++) {
            $answer = $factType === 'element_symbol'
                ? $this->elementSymbols()[($number - 1) % count($this->elementSymbols())]
                : ucfirst(str_replace('_', ' ', $factType)).' '.$number;
            $subjectId = $factType === 'capital' ? 'Q30' : 'Q'.(1000 + $number);
            $bindings[] = $this->binding('Country '.$number, $answer, $factType, $count - $number, $subjectId);
        }

        Http::fake([
            'query.wikidata.test/*' => Http::response(['results' => ['bindings' => $bindings]]),
        ]);
    }

    private function fakeElementFacts(): void
    {
        $elements = [
            'silisium' => 'Si', 'sølv' => 'Ag', 'oksygen' => 'O', 'jern' => 'Fe', 'karbon' => 'C',
            'kobber' => 'Cu', 'helium' => 'He', 'natrium' => 'Na', 'svovel' => 'S', 'aluminium' => 'Al',
            'fosfor' => 'P', 'klor' => 'Cl',
        ];
        $bindings = [];
        foreach ($elements as $name => $symbol) {
            $bindings[] = $this->binding($name, $symbol, 'element_symbol', 100 - count($bindings), 'Q'.(7000 + count($bindings)));
        }

        Http::fake(['query.wikidata.test/*' => Http::response(['results' => ['bindings' => $bindings]])]);
    }

    private function assertElementAnswersMatchQuestions(array $questions): void
    {
        $expected = ['silisium' => 'Si', 'sølv' => 'Ag'];

        foreach ($questions as $question) {
            foreach ($expected as $element => $symbol) {
                if (str_contains(mb_strtolower($question['question']), $element)) {
                    $this->assertSame($symbol, $question['correct_answer']);
                }
            }
        }
    }

    private function fakeMixedFacts(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request['query'], 'wdt:P27 wd:Q20')) {
                return Http::response(['results' => ['bindings' => $this->bindings('Norwegian', '19', 'birth_year', 100, 'Q'.(2000 + random_int(1, 9999)), 20)]]);
            }

            if (str_contains($request['query'], 'wd:Q6256')) {
                return Http::response(['results' => ['bindings' => $this->bindings('Country', 'Capital', 'capital', 30, 'Q30', 20)]]);
            }

            if (str_contains($request['query'], 'wd:Q11344')) {
                $bindings = [];
                foreach ($this->elementSymbols() as $number => $symbol) {
                    $bindings[] = $this->binding('Element '.$number, $symbol, 'element_symbol', 30 - $number, 'Q'.(3000 + $number));
                }
                return Http::response(['results' => ['bindings' => $bindings]]);
            }

            return Http::response(['results' => ['bindings' => $this->bindings('Book', 'Author', 'author', 30, 'Q'.(4000 + random_int(1, 9999)), 20)]]);
        });
    }

    private function bindings(string $subjectPrefix, string $answerPrefix, string $factType, int $popularity, string $subjectId, int $count): array
    {
        $bindings = [];
        for ($number = 1; $number <= $count; $number++) {
            $bindings[] = $this->binding($subjectPrefix.' '.$number, $answerPrefix.' '.$number, $factType, $popularity - $number, $subjectId.$number);
        }

        return $bindings;
    }

    private function elementSymbols(): array
    {
        return ['H', 'He', 'Li', 'Be', 'B', 'C', 'N', 'O', 'F', 'Ne', 'Na', 'Mg', 'Al', 'Si', 'P', 'S', 'Cl', 'Ar', 'K', 'Ca', 'Fe', 'Cu', 'Zn', 'Ag', 'Sn', 'I', 'Au', 'Hg', 'Pb', 'Og', 'Pr', 'Uue'];
    }

    private function binding(string $subject, string $answer, string $factType, int $popularity, ?string $subjectId = null): array
    {
        return [
            'subject' => ['value' => 'https://www.wikidata.org/entity/'.($subjectId ?? 'Q'.(1000 + $popularity))],
            'subjectLabel' => ['value' => $subject],
            'answerLabel' => ['value' => $answer],
            'factType' => ['value' => $factType],
            'popularity' => ['value' => (string) $popularity],
        ];
    }
}
