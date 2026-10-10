<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinancialAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['cash', 'bank', 'investment', 'other'])],
            'currency' => [
                'nullable',
                Rule::requiredIf(fn (): bool => in_array($this->input('type'), ['cash', 'bank'], true)),
                'string',
                'regex:/^[A-Z]{3}$/',
            ],
            'investment_unit' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('type') === 'investment'),
                Rule::in(['gram', 'lot']),
            ],
            'opening_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
}
