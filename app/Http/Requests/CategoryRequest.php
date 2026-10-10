<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
                'regex:/^[A-Za-z][A-Za-z0-9]{0,63}$/',
            ],
            'color' => [
                'required',
                'string',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'is_active' => ['boolean'],
        ];
    }
}
