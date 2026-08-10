<?php

namespace App\Services;

use App\Exceptions\QuizGenerationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;
use UnexpectedValueException;

class QuizGeneratorService
{
    public function generate(array $settings): array
    {
        $apiKey = (string) config('services.openai.api_key');

        if ($apiKey === '') {
            throw new QuizGenerationException('Quiz-generatoren er ikke konfigurert ennå. Prøv igjen senere.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($apiKey)
                ->timeout((int) config('services.openai.timeout', 60))
                ->retry(1, 500)
                ->post(rtrim((string) config('services.openai.base_url'), '/').'/responses', [
                    'model' => config('services.openai.quiz_model'),
                    'store' => false,
                    'reasoning' => ['effort' => 'low'],
                    'instructions' => $this->instructions($settings),
                    'input' => $this->prompt($settings),
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'quiz',
                            'strict' => true,
                            'schema' => $this->schema($settings),
                        ],
                    ],
                    'max_output_tokens' => 16000,
                ])
                ->throw();

            $quiz = $this->decodeOutput($response->json());

            return $this->validateQuiz($quiz, $settings);
        } catch (QuizGenerationException $exception) {
            throw $exception;
        } catch (ConnectionException | RequestException $exception) {
            throw new QuizGenerationException('Vi klarte ikke å generere quizen akkurat nå. Prøv igjen.', 0, $exception);
        } catch (Throwable $exception) {
            throw new QuizGenerationException('Quiz-generatoren returnerte et ugyldig resultat. Prøv igjen.', 0, $exception);
        }
    }

    private function instructions(array $settings): string
    {
        $language = $settings['language'] === 'en' ? 'natural English' : 'naturlig norsk';

        return "Du lager en faktabasert quiz på {$language}. Følg JSON-skjemaet nøyaktig. "
            .'Lag entydige spørsmål med etterprøvbare svar. Ikke finn på fakta. Unngå fakta som endrer seg raskt, med mindre temaet uttrykkelig krever det. '
            .'Tilpass vanskelighetsgraden, unngå duplikater og nesten like spørsmål, og ikke avslør svaret i spørsmålet. '
            .'Ved flervalg: lag nøyaktig fire alternativer, ett korrekt og tre plausible feilalternativer. Varier plasseringen av korrekt alternativ. '
            .'Sett uncertain til true bare ved reell, vesentlig usikkerhet om svaret. Forklaring skal være kort og kan være tom når den ikke tilfører verdi.';
    }

    private function prompt(array $settings): string
    {
        $categories = $settings['category_labels'] ? implode(', ', $settings['category_labels']) : 'Ingen fast kategori';
        $topic = $settings['custom_topic'] ?: 'Ikke angitt';
        $type = $settings['question_type'] === 'multiple_choice' ? 'flervalg' : 'åpne spørsmål';

        return "Lag {$settings['question_count']} spørsmål.\n"
            ."Vanskelighetsgrad: {$settings['difficulty_label']}.\n"
            ."Spørsmålstype: {$type}.\n"
            ."Faste kategorier: {$categories}.\n"
            ."Eget tema: {$topic}.\n"
            .'Hvis eget tema er angitt, skal det være førende, mens kategoriene påvirker vinklingen. Hvis Blandet er valgt uten eget tema, fordel spørsmålene på flere vanlige kategorier.';
    }

    private function schema(array $settings): array
    {
        $multipleChoice = $settings['question_type'] === 'multiple_choice';

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['title', 'questions'],
            'properties' => [
                'title' => ['type' => 'string'],
                'questions' => [
                    'type' => 'array',
                    'minItems' => $settings['question_count'],
                    'maxItems' => $settings['question_count'],
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['question', 'category', 'correct_answer', 'options', 'correct_option', 'explanation', 'uncertain'],
                        'properties' => [
                            'question' => ['type' => 'string'],
                            'category' => ['type' => 'string'],
                            'correct_answer' => ['type' => 'string'],
                            'options' => [
                                'type' => 'array',
                                'minItems' => $multipleChoice ? 4 : 0,
                                'maxItems' => $multipleChoice ? 4 : 0,
                                'items' => ['type' => 'string'],
                            ],
                            'correct_option' => ['type' => 'string', 'enum' => $multipleChoice ? ['A', 'B', 'C', 'D'] : ['']],
                            'explanation' => ['type' => 'string'],
                            'uncertain' => ['type' => 'boolean'],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function decodeOutput(array $response): array
    {
        $outputText = $response['output_text'] ?? null;

        if (!is_string($outputText)) {
            foreach (($response['output'] ?? []) as $output) {
                foreach (($output['content'] ?? []) as $content) {
                    if (($content['type'] ?? null) === 'output_text' && is_string($content['text'] ?? null)) {
                        $outputText = $content['text'];
                        break 2;
                    }
                }
            }
        }

        if (!is_string($outputText) || trim($outputText) === '') {
            throw new UnexpectedValueException('OpenAI response did not contain structured output text.');
        }

        try {
            $decoded = json_decode($outputText, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedValueException('OpenAI response contained invalid JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new UnexpectedValueException('OpenAI response JSON was not an object.');
        }

        return $decoded;
    }

    private function validateQuiz(array $quiz, array $settings): array
    {
        if (!is_string($quiz['title'] ?? null) || trim($quiz['title']) === '') {
            throw new UnexpectedValueException('Quiz title was missing.');
        }

        $questions = $quiz['questions'] ?? null;

        if (!is_array($questions) || count($questions) !== $settings['question_count']) {
            throw new UnexpectedValueException('Quiz had the wrong number of questions.');
        }

        foreach ($questions as $index => $question) {
            if (!is_array($question)
                || !is_string($question['question'] ?? null)
                || !is_string($question['category'] ?? null)
                || !is_string($question['correct_answer'] ?? null)
                || !is_string($question['explanation'] ?? null)
                || !is_bool($question['uncertain'] ?? null)
                || !is_array($question['options'] ?? null)) {
                throw new UnexpectedValueException('Quiz question '.($index + 1).' was invalid.');
            }

            if ($settings['question_type'] === 'multiple_choice') {
                $correctOption = $question['correct_option'] ?? null;

                if (count($question['options']) !== 4 || !in_array($correctOption, ['A', 'B', 'C', 'D'], true)) {
                    throw new UnexpectedValueException('Multiple-choice question '.($index + 1).' was invalid.');
                }

                $correctIndex = ord($correctOption) - ord('A');
                if (($question['options'][$correctIndex] ?? null) !== $question['correct_answer']) {
                    throw new UnexpectedValueException('Multiple-choice answer '.($index + 1).' did not match its option.');
                }
            } elseif ($question['options'] !== [] || ($question['correct_option'] ?? null) !== '') {
                throw new UnexpectedValueException('Open question '.($index + 1).' contained answer options.');
            }
        }

        return ['title' => trim($quiz['title']), 'questions' => array_values($questions)];
    }
}
