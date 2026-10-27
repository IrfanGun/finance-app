<?php

namespace App\Http\Requests;

use App\Models\BudgetPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $goal = $this->input('type') === 'goal';

        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['spending', 'goal'])],
            'icon' => ['required', Rule::in(array_keys(BudgetPlan::ICONS))],
            'color' => ['required', Rule::in(array_keys(BudgetPlan::COLORS))],
            'amount' => [$goal ? 'nullable' : 'required', 'numeric', 'gt:0', 'decimal:0,2'],
            'target_amount' => [$goal ? 'required' : 'nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'target_date' => [$goal ? 'required' : 'nullable', 'date', 'after_or_equal:starts_on'],
            'saving_id' => [
                $goal ? 'required' : 'nullable',
                'integer',
                Rule::exists('savings', 'id')->where('user_id', $this->user()?->id),
            ],
            'confirm_existing_funds' => ['nullable', 'boolean'],
            'contribution_amount' => ['nullable', 'numeric', 'gt:0', 'decimal:0,2'],
            'currency' => ['required', 'string', 'size:3'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('type', 'expense'),
            ],
            'financial_account_id' => [
                'required',
                'integer',
                Rule::exists('financial_accounts', 'id')->where('user_id', $this->user()?->id),
            ],
            'is_recurring' => ['required', 'boolean'],
            'interval' => ['required', 'integer', 'min:1', 'max:120'],
            'starts_on' => ['required', 'date'],
            'ends_on' => [
                Rule::requiredIf(! $goal && ! $this->boolean('is_recurring')),
                'nullable',
                'date',
                'after_or_equal:starts_on',
            ],
            'timezone' => ['required', 'timezone'],
            'notes' => ['nullable', 'string'],
            'remind' => ['nullable', 'boolean'],
            'reminder_purpose' => ['nullable', Rule::in(['budget_allocation', 'budget_savings_claim'])],
            'items' => ['nullable', 'array'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'items.*.category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('type', 'expense'),
            ],
        ];
    }
}
