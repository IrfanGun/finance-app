<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiTransactionsRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'transactions' => ['required', 'array', 'min:1', 'max:20'],
            'transactions.*.transaction_type' => [
                'required',
                'string',
                'in:income,expense',
            ],
            'transactions.*.amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'transactions.*.account' => [
                'nullable',
                'string',
                'max:100',
            ],
            'transactions.*.category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'transactions.*.description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'transactions.*.date' => [
                'nullable',
                'date',
            ],
        ];
    }
}
