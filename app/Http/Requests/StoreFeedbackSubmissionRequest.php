<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedbackSubmissionRequest extends FormRequest
{
    public const TYPES = [
        'new_idea' => 'Ny idé',
        'improvement' => 'Forbedring',
        'bug' => 'Feil på nettsiden',
        'missing_information' => 'Manglende informasjon',
        'other' => 'Annet',
    ];

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => [
                'required',
                Rule::in(array_keys(self::TYPES)),
            ],
            'title' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
            'submission_type' => ['required', Rule::in(['anonymous', 'contact'])],
            'name' => ['nullable', 'required_if:submission_type,contact', 'string', 'max:150'],
            'email' => ['nullable', 'required_if:submission_type,contact', 'email', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Velg hva innspillet gjelder.',
            'type.in' => 'Velg en gyldig type innspill.',
            'title.required' => 'Skriv inn en kort tittel.',
            'title.max' => 'Tittelen kan ikke være lengre enn 150 tegn.',
            'message.required' => 'Skriv en beskrivelse av innspillet.',
            'message.max' => 'Beskrivelsen kan ikke være lengre enn 5000 tegn.',
            'submission_type.required' => 'Velg innsendingstype.',
            'submission_type.in' => 'Velg en gyldig innsendingstype.',
            'name.required_if' => 'Skriv inn navn når du velger at du kan kontaktes.',
            'name.max' => 'Navnet kan ikke være lengre enn 150 tegn.',
            'email.required_if' => 'Skriv inn e-postadresse når du velger at du kan kontaktes.',
            'email.email' => 'Skriv inn en gyldig e-postadresse.',
            'email.max' => 'E-postadressen kan ikke være lengre enn 255 tegn.',
        ];
    }

    public function attributes()
    {
        return [
            'type' => 'hva innspillet gjelder',
            'title' => 'kort tittel',
            'message' => 'beskrivelse',
            'submission_type' => 'innsendingstype',
            'name' => 'navn',
            'email' => 'e-post',
        ];
    }

}
