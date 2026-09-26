<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmAiChatTransactionRequest extends FormRequest
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
        $rules = [
            'transaction' => [
                'required_without:transactions',
                'array',
            ],
            'transactions' => [
                'required_without:transaction',
                'array',
                'min:1',
                'max:20',
            ],
            'transaction.transaction_type' => [
                'required',
                'string',
                Rule::in(['income', 'expense']),
            ],
            'transaction.amount' => ['required', 'numeric', 'min:0.01'],
            'transaction.account' => [
                'nullable',
                'string',
                'max:100',
                'required_without:transaction.account_id',
            ],
            'transaction.account_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_without:transaction.account',
            ],
            'transaction.category' => ['nullable', 'string', 'max:100'],
            'transaction.description' => ['nullable', 'string', 'max:1000'],
            'transaction.date' => ['nullable', 'date'],
            'transactions.*.transaction_type' => [
                'required',
                'string',
                Rule::in(['income', 'expense']),
            ],
            'transactions.*.amount' => ['required', 'numeric', 'min:0.01'],
            'transactions.*.account' => [
                'nullable',
                'string',
                'max:100',
                'required_without:transactions.*.account_id',
            ],
            'transactions.*.account_id' => [
                'nullable',
                'integer',
                'min:1',
                'required_without:transactions.*.account',
            ],
            'transactions.*.category' => ['nullable', 'string', 'max:100'],
            'transactions.*.description' => ['nullable', 'string', 'max:1000'],
            'transactions.*.date' => ['nullable', 'date'],
            'resources' => ['sometimes', 'array', 'max:2'],
            'resources.*.type' => ['required', Rule::in(['account', 'category'])],
            'resources.*.name' => ['required', 'string', 'max:100'],
        ];

        $resources = $this->input('resources', []);

        if (! is_array($resources)) {
            return $rules;
        }

        foreach ($resources as $index => $resource) {
            if (! is_array($resource)) {
                continue;
            }

            if (($resource['type'] ?? null) === 'account') {
                $rules["resources.{$index}.account_type"] = [
                    'required',
                    Rule::in(['cash', 'bank', 'investment', 'other']),
                ];
                $rules["resources.{$index}.opening_balance"] = [
                    'required',
                    'numeric',
                    'min:0',
                ];
            }

            if (($resource['type'] ?? null) === 'category') {
                $rules["resources.{$index}.category_type"] = [
                    'required',
                    Rule::in(['income', 'expense']),
                ];
                $rules["resources.{$index}.icon"] = ['required', 'string', 'max:40'];
                $rules["resources.{$index}.color"] = ['required', 'string', 'max:20'];
            }
        }

        if ($this->has('transactions')) {
            unset($rules['transaction']);

            foreach (array_keys($rules) as $attribute) {
                if (str_starts_with($attribute, 'transaction.')) {
                    unset($rules[$attribute]);
                }
            }
        } else {
            unset($rules['transactions']);

            foreach (array_keys($rules) as $attribute) {
                if (str_starts_with($attribute, 'transactions.')) {
                    unset($rules[$attribute]);
                }
            }
        }

        return $rules;
    }
}
