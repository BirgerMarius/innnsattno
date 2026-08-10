<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateQuizRequest extends FormRequest
{
    public const COUNTS = [10, 15, 20, 25, 30];

    public const DIFFICULTIES = [
        'easy' => 'Lett',
        'medium' => 'Middels',
        'hard' => 'Vanskelig',
        'mixed' => 'Blandet',
    ];

    public const LANGUAGES = [
        'no' => 'Norsk',
        'en' => 'Engelsk',
    ];

    public const QUESTION_TYPES = [
        'open' => 'Åpne spørsmål',
        'multiple_choice' => 'Flervalg',
    ];

    public const CATEGORIES = [
        'mixed' => 'Blandet',
        'norway' => 'Norge',
        'history' => 'Historie',
        'geography' => 'Geografi',
        'science' => 'Natur og vitenskap',
        'sport' => 'Sport',
        'film_tv' => 'Film og TV',
        'music' => 'Musikk',
        'literature_language' => 'Litteratur og språk',
        'society_politics' => 'Samfunn og politikk',
        'food_drink' => 'Mat og drikke',
        'body_health' => 'Kropp og helse',
        'technology' => 'Teknologi',
        'culture_celebrities' => 'Kultur og kjendiser',
        'animals_nature' => 'Dyr og natur',
    ];

    public const AVAILABLE_CATEGORIES = [
        'mixed', 'norway', 'geography', 'science',
    ];

    public const ENGLISH_DIFFICULTIES = [
        'easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard', 'mixed' => 'Mixed',
    ];

    public const ENGLISH_QUESTION_TYPES = [
        'open' => 'Open questions', 'multiple_choice' => 'Multiple choice',
    ];

    public const ENGLISH_CATEGORIES = [
        'mixed' => 'Mixed', 'norway' => 'Norway', 'history' => 'History', 'geography' => 'Geography',
        'science' => 'Nature and science', 'sport' => 'Sport', 'film_tv' => 'Film and TV', 'music' => 'Music',
        'literature_language' => 'Literature and language', 'society_politics' => 'Society and politics',
        'food_drink' => 'Food and drink', 'body_health' => 'Body and health', 'technology' => 'Technology',
        'culture_celebrities' => 'Culture and celebrities', 'animals_nature' => 'Animals and nature',
    ];

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'question_count' => ['required', 'integer', Rule::in(self::COUNTS)],
            'difficulty' => ['required', Rule::in(array_keys(self::DIFFICULTIES))],
            'language' => ['required', Rule::in(array_keys(self::LANGUAGES))],
            'question_type' => ['required', Rule::in(array_keys(self::QUESTION_TYPES))],
            'categories' => ['required', 'array', 'min:1', 'max:5'],
            'categories.*' => [Rule::in(self::AVAILABLE_CATEGORIES)],
        ];
    }

    public function messages()
    {
        return [
            'question_count.in' => 'Velg et gyldig antall spørsmål.',
            'difficulty.in' => 'Velg en gyldig vanskelighetsgrad.',
            'language.in' => 'Velg et gyldig språk.',
            'question_type.in' => 'Velg en gyldig spørsmålstype.',
            'categories.required' => 'Velg minst én tilgjengelig kategori.',
            'categories.min' => 'Velg minst én tilgjengelig kategori.',
            'categories.max' => 'Velg maksimalt fem kategorier.',
            'categories.*.in' => 'En av de valgte kategoriene er ugyldig.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $categories = (array) $this->input('categories', []);

            if (in_array('mixed', $categories, true) && count($categories) > 1) {
                $validator->errors()->add('categories', 'Blandet kan ikke kombineres med andre kategorier.');
            }
        });
    }
}
