<?php

namespace App\Http\Controllers;

use App\Exceptions\QuizGenerationException;
use App\Http\Requests\GenerateQuizRequest;
use App\Services\OpenDataQuizGeneratorService;
use Illuminate\Support\Facades\Log;
use Throwable;

class QuizController extends Controller
{
    private OpenDataQuizGeneratorService $generator;

    public function __construct(OpenDataQuizGeneratorService $generator)
    {
        $this->generator = $generator;
    }

    public function create()
    {
        return view('quiz.create', $this->formOptions());
    }

    public function store(GenerateQuizRequest $request)
    {
        $validated = $request->validated();
        $categoryKeys = array_values($validated['categories'] ?? []);
        $english = $validated['language'] === 'en';
        $difficultyLabels = $english ? GenerateQuizRequest::ENGLISH_DIFFICULTIES : GenerateQuizRequest::DIFFICULTIES;
        $questionTypeLabels = $english ? GenerateQuizRequest::ENGLISH_QUESTION_TYPES : GenerateQuizRequest::QUESTION_TYPES;
        $categoryLabels = $english ? GenerateQuizRequest::ENGLISH_CATEGORIES : GenerateQuizRequest::CATEGORIES;
        $settings = [
            'question_count' => (int) $validated['question_count'],
            'difficulty' => $validated['difficulty'],
            'difficulty_label' => $difficultyLabels[$validated['difficulty']],
            'language' => $validated['language'],
            'language_label' => $english ? 'English' : 'Norsk',
            'question_type' => $validated['question_type'],
            'question_type_label' => $questionTypeLabels[$validated['question_type']],
            'categories' => $categoryKeys,
            'category_labels' => array_map(fn (string $key) => $categoryLabels[$key], $categoryKeys),
            'custom_topic' => '',
        ];

        try {
            $quiz = $this->generator->generate($settings);
        } catch (QuizGenerationException $exception) {
            Log::warning('Quiz generation failed.', [
                'exception' => get_class($exception->getPrevious() ?: $exception),
                'message' => $exception->getPrevious() ? $exception->getPrevious()->getMessage() : $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['generation' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            Log::warning('Quiz generation failed unexpectedly.', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors(['generation' => 'Vi klarte ikke å generere quizen akkurat nå. Prøv igjen.']);
        }

        $request->session()->put('generated_quiz', [
            'quiz' => $quiz,
            'settings' => $settings,
        ]);

        return redirect()->route('quiz.show');
    }

    public function show()
    {
        return view('quiz.show', $this->quizData());
    }

    public function printQuiz()
    {
        return view('quiz.print', $this->quizData());
    }

    public function printAnswerKey()
    {
        return view('quiz.answer-key', $this->quizData());
    }

    private function quizData(): array
    {
        $data = session('generated_quiz');

        abort_unless(is_array($data) && isset($data['quiz'], $data['settings']), 404);

        return $data;
    }

    private function formOptions(): array
    {
        return [
            'questionCounts' => GenerateQuizRequest::COUNTS,
            'difficulties' => GenerateQuizRequest::DIFFICULTIES,
            'languages' => GenerateQuizRequest::LANGUAGES,
            'questionTypes' => GenerateQuizRequest::QUESTION_TYPES,
            'categories' => GenerateQuizRequest::CATEGORIES,
            'availableCategories' => GenerateQuizRequest::AVAILABLE_CATEGORIES,
        ];
    }
}
