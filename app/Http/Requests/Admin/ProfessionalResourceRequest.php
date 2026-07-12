<?php

namespace App\Http\Requests\Admin;

use App\ProfessionalResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfessionalResourceRequest extends FormRequest
{
    public function authorize()
    {
        return $this->session()->get('admin_authenticated') === true;
    }

    public function rules()
    {
        return [
            'category_id' => ['required', 'integer', 'exists:resource_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:2048', 'starts_with:https://'],
            'comment' => [
                Rule::requiredIf($this->input('status') === ProfessionalResource::STATUS_PUBLISHED),
                'nullable',
                'string',
                'max:5000',
            ],
            'publisher' => ['nullable', 'string', 'max:255'],
            'content_type' => ['nullable', 'string', 'max:100'],
            'media_type' => [
                Rule::requiredIf($this->input('status') === ProfessionalResource::STATUS_PUBLISHED),
                'nullable',
                Rule::in(array_keys(ProfessionalResource::MEDIA_TYPES)),
            ],
            'tags' => ['nullable', 'string', 'max:1000'],
            'publication_year' => ['nullable', 'integer', 'digits:4', 'min:1900', 'max:' . (date('Y') + 1)],
            'is_featured' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(array_keys(ProfessionalResource::STATUSES))],
            'sort_order' => ['required', 'integer', 'min:-100000', 'max:100000'],
            'last_checked_at' => ['nullable', 'date'],
        ];
    }
}
