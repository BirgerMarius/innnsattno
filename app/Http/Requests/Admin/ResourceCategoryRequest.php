<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResourceCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return $this->session()->get('admin_authenticated') === true;
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:-100000', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
