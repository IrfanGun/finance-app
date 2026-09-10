<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:income,expense'],
            'icon' => ['required', 'string', 'max:40'],
            'color' => ['required', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ];
    }
}
