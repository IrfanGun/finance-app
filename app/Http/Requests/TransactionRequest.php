<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'account_id' => ['required', 'exists:financial_accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ];
    }
}
