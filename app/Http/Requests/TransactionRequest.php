<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'account_id' => [
                'required',
                Rule::exists('financial_accounts', 'id')
                    ->where('user_id', $this->user()?->id),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()?->id),
            ],
            'meal_record_id' => [
                'nullable',
                Rule::exists('meal_records', 'id')
                    ->where('user_id', $this->user()?->id),
            ],
        ];
    }
}
