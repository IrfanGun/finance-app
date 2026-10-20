<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompleteRecurringReminderRequest extends FormRequest
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
        $reminder = $this->user()?->recurringReminders()
            ->whereKey($this->route('reminder'))
            ->first();
        $log = $reminder?->logs()->whereKey($this->route('log'))->first();

        if ($log?->status === 'completed') {
            return [];
        }

        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date_format:Y-m-d'],
            'account_id' => [
                'required',
                Rule::exists('financial_accounts', 'id')
                    ->where('user_id', $this->user()?->id),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('is_active', true),
            ],
            'title' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
