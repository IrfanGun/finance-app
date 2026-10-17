<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMealRecordRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $openingHours = $this->input('opening_hours');

        if (! is_array($openingHours)) {
            return;
        }

        $openingHours = array_map(function (mixed $session): mixed {
            if (! is_array($session)
                || ! is_string($session['opens_at'] ?? null)
                || ! is_string($session['closes_at'] ?? null)) {
                return $session;
            }

            $session['closes_next_day'] = $session['closes_at'] < $session['opens_at'];

            return $session;
        }, $openingHours);

        $this->merge([
            'opening_hours' => $openingHours,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'place_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:buy,homemade'],
            'price_min' => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'price_max' => ['required', 'numeric', 'min:0', 'max:9999999999999.99', 'gte:price_min'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'address' => ['nullable', 'string', 'max:10000'],
            'impression' => ['nullable', 'string', 'max:10000'],
            'opening_hours' => ['nullable', 'array', 'max:56'],
            'opening_hours.*.day_of_week' => ['required', 'integer', 'between:1,7'],
            'opening_hours.*.opens_at' => ['required', 'date_format:H:i'],
            'opening_hours.*.closes_at' => ['required', 'date_format:H:i'],
            'opening_hours.*.closes_next_day' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('type') !== 'buy'
                || $validator->errors()->has('opening_hours')) {
                return;
            }

            $intervals = [];
            $sessions = $this->input('opening_hours') ?? [];

            if (! is_array($sessions)) {
                return;
            }

            foreach ($sessions as $index => $session) {
                if (! is_array($session)
                    || ! isset($session['day_of_week'], $session['opens_at'], $session['closes_at'])
                    || ! preg_match('/^\d{2}:\d{2}$/', $session['opens_at'])
                    || ! preg_match('/^\d{2}:\d{2}$/', $session['closes_at'])) {
                    continue;
                }

                [$openHour, $openMinute] = array_map('intval', explode(':', $session['opens_at']));
                [$closeHour, $closeMinute] = array_map('intval', explode(':', $session['closes_at']));
                $opensAt = ($openHour * 60) + $openMinute;
                $closesAt = ($closeHour * 60) + $closeMinute;
                $closesNextDay = filter_var($session['closes_next_day'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (($closesNextDay && $closesAt >= $opensAt)
                    || (! $closesNextDay && $closesAt <= $opensAt)) {
                    $validator->errors()->add(
                        "opening_hours.{$index}.closes_at",
                        'Jam tutup harus setelah jam buka dan durasi sesi harus kurang dari 24 jam.',
                    );

                    continue;
                }

                $dayOffset = (((int) $session['day_of_week']) - 1) * 1440;
                $intervals[] = [
                    'index' => $index,
                    'start' => $dayOffset + $opensAt,
                    'end' => $dayOffset + $closesAt + ($closesNextDay ? 1440 : 0),
                ];
            }

            $weekMinutes = 7 * 1440;

            foreach ($intervals as $firstIndex => $first) {
                foreach ($intervals as $secondIndex => $second) {
                    if ($secondIndex <= $firstIndex) {
                        continue;
                    }

                    foreach ([-$weekMinutes, 0, $weekMinutes] as $offset) {
                        if ($first['start'] < $second['end'] + $offset
                            && $first['end'] > $second['start'] + $offset) {
                            $validator->errors()->add(
                                "opening_hours.{$second['index']}.opens_at",
                                'Sesi jam buka tidak boleh bertumpang tindih dengan sesi lain.',
                            );

                            break 2;
                        }
                    }
                }
            }
        }];
    }
}
