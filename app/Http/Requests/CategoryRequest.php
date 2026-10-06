<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:income,expense'],
            'icon' => [
                'required',
                'string',
                Rule::in(Category::ICONS),
            ],
            'color' => [
                'required',
                'string',
                Rule::in(Category::COLORS),
            ],
            'is_active' => ['boolean'],
        ];
    }
}
