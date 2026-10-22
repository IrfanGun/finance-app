<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'target_amount' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'target_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
