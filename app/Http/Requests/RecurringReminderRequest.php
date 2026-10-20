<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RecurringReminderRequest extends FormRequest
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
            'type' => ['required', Rule::in(['income', 'expense'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high'])],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'financial_account_id' => [
                'nullable',
                Rule::exists('financial_accounts', 'id')
                    ->where('user_id', $this->user()?->id),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()?->id)
                    ->where('is_active', true)
                    ->where('type', $this->input('type')),
            ],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'interval' => ['required', 'integer', 'min:1'],
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'ends_at' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['starts_at', 'ends_at', 'timezone'])) {
                return;
            }

            $timezone = $this->string('timezone')->toString();
            $startsAt = CarbonImmutable::createFromFormat(
                'Y-m-d\\TH:i',
                $this->string('starts_at')->toString(),
                $timezone,
            );

            if ($startsAt === false) {
                $validator->errors()->add('starts_at', 'Format jadwal pertama tidak valid.');

                return;
            }

            $scheduleChanged = true;
            $reminderId = $this->route('reminder');

            if ($reminderId !== null && is_numeric($reminderId)) {
                $reminder = $this->user()?->recurringReminders()->find((int) $reminderId);

                if ($reminder !== null) {
                    $scheduleChanged = $reminder->frequency !== $this->string('frequency')->toString()
                        || $reminder->interval !== $this->integer('interval')
                        || $reminder->timezone !== $timezone
                        || ! $reminder->starts_at->equalTo($startsAt->setTimezone('UTC'));
                }
            }

            if ($scheduleChanged && $startsAt->lessThan(CarbonImmutable::now($timezone))) {
                $validator->errors()->add('starts_at', 'Jadwal pertama harus sekarang atau di masa mendatang.');

                return;
            }

            if ($this->filled('ends_at')) {
                $endsAt = CarbonImmutable::createFromFormat(
                    'Y-m-d\\TH:i',
                    $this->string('ends_at')->toString(),
                    $timezone,
                );

                if ($endsAt === false || $endsAt->lessThan($startsAt)) {
                    $validator->errors()->add('ends_at', 'Batas akhir harus sama dengan atau setelah jadwal pertama.');
                }
            }
        }];
    }
}
