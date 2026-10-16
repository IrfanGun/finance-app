<?php

namespace App\Http\Requests;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAssetDepreciationRequest extends FormRequest
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
        $asset = $this->user()?->assets()->find($this->route('asset'));

        return [
            'depreciation_type' => [
                'required',
                'in:fixed,percentage',
            ],
            'value_direction' => [
                'required',
                'in:decrease,increase',
            ],
            'is_tiered' => [
                'sometimes',
                'boolean',
            ],
            'depreciation_value' => [
                'exclude_if:is_tiered,1',
                'required_unless:is_tiered,1',
                'numeric',
                'gt:0',
                'decimal:0,4',
                Rule::when(
                    $this->input('depreciation_type') === 'percentage',
                    ['max:100'],
                ),
            ],
            'rate_steps' => [
                'exclude_unless:is_tiered,1',
                'required_if:is_tiered,1',
                'array',
                'min:1',
            ],
            'rate_steps.*.year_number' => [
                'required_with:rate_steps',
                'integer',
                'min:1',
                'max:1000',
                'distinct',
            ],
            'rate_steps.*.depreciation_value' => [
                'required_with:rate_steps',
                'numeric',
                'gt:0',
                'decimal:0,4',
                Rule::when(
                    $this->input('depreciation_type') === 'percentage',
                    ['max:100'],
                ),
            ],
            'depreciation_period' => ['required', 'in:monthly,yearly'],
            'residual_value' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'depreciation_start_date' => [
                'required',
                'date',
                'after_or_equal:'.($asset?->purchase_date->toDateString()
                    ?? CarbonImmutable::today('Asia/Jakarta')->toDateString()),
                'before_or_equal:'.CarbonImmutable::today('Asia/Jakarta')->toDateString(),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny([
                'depreciation_type',
                'value_direction',
                'residual_value',
            ])) {
                return;
            }

            $asset = $this->user()?->assets()->find($this->route('asset'));

            if ($asset === null) {
                return;
            }

            $purchasePrice = (string) $asset->purchase_price;
            $currentValue = (string) $asset->current_value;

            if ($this->boolean('is_tiered')) {
                $rateSteps = collect(is_array($this->input('rate_steps'))
                    ? $this->input('rate_steps')
                    : [])
                    ->filter(fn (mixed $step): bool => is_array($step))
                    ->sortBy('year_number')
                    ->values();

                if (
                    $rateSteps->isNotEmpty()
                    && (int) ($rateSteps->first()['year_number'] ?? 0) !== 1
                ) {
                    $validator->errors()->add(
                        'rate_steps',
                        'Jadwal depresiasi harus dimulai dari tahun ke-1.',
                    );
                }

                if (
                    $this->input('depreciation_period') !== 'yearly'
                    && ! $validator->errors()->has('depreciation_period')
                ) {
                    $validator->errors()->add(
                        'depreciation_period',
                        'Depresiasi bertingkat hanya mendukung periode tahunan.',
                    );
                }

                $firstRateStep = $rateSteps->first(
                    fn (array $step): bool => (int) ($step['year_number'] ?? 0) === 1,
                );
                $firstRate = $firstRateStep['depreciation_value'] ?? null;

                if ($firstRate !== null && is_numeric($firstRate)) {
                    $periodAmount = $this->input('depreciation_type') === 'percentage'
                        ? BigDecimal::of($purchasePrice)
                            ->multipliedBy((string) $firstRate)
                            ->dividedBy(100, 2, RoundingMode::HalfUp)
                        : BigDecimal::of((string) $firstRate)
                            ->toScale(2, RoundingMode::HalfUp);

                    if ($periodAmount->isLessThanOrEqualTo(0)) {
                        $validator->errors()->add(
                            'rate_steps',
                            'Nilai tahun pertama harus menghasilkan penyusutan minimal 0,01 per tahun.',
                        );
                    }
                }

                if ($this->input('depreciation_type') === 'fixed') {
                    $requestedSteps = $this->input('rate_steps', []);

                    foreach (is_array($requestedSteps) ? $requestedSteps : [] as $index => $rateStep) {
                        if (
                            is_array($rateStep)
                            && isset($rateStep['depreciation_value'])
                            && is_numeric($rateStep['depreciation_value'])
                            && BigDecimal::of((string) $rateStep['depreciation_value'])
                                ->strippedOfTrailingZeros()
                                ->getScale() > 2
                        ) {
                            $validator->errors()->add(
                                "rate_steps.{$index}.depreciation_value",
                                'Nominal tetap maksimal dua angka desimal.',
                            );
                        }
                    }
                }
            } elseif (! $validator->errors()->has('depreciation_value')) {
                if ($this->input('depreciation_type') === 'fixed'
                    && BigDecimal::of((string) $this->input('depreciation_value'))
                        ->strippedOfTrailingZeros()
                        ->getScale() > 2) {
                    $validator->errors()->add(
                        'depreciation_value',
                        'Depresiasi nominal tetap maksimal dua angka desimal.',
                    );
                }

                $periodAmount = $this->input('depreciation_type') === 'percentage'
                    ? BigDecimal::of($purchasePrice)
                        ->multipliedBy((string) $this->input('depreciation_value'))
                        ->dividedBy(100, 2, RoundingMode::HalfUp)
                    : BigDecimal::of((string) $this->input('depreciation_value'))
                        ->toScale(2, RoundingMode::HalfUp);

                if ($periodAmount->isLessThanOrEqualTo(0)) {
                    $validator->errors()->add(
                        'depreciation_value',
                        'Depresiasi harus menghasilkan penyusutan minimal Rp 0,01 per periode.',
                    );
                }
            }

            if (
                $this->input('value_direction') === 'increase'
                && $this->input('depreciation_period') !== 'yearly'
                && ! $validator->errors()->has('depreciation_period')
            ) {
                $validator->errors()->add(
                    'depreciation_period',
                    'Kenaikan nilai aset hanya mendukung periode tahunan.',
                );
            }

            $residualValue = BigDecimal::of((string) $this->input('residual_value'));

            if (
                $this->input('value_direction') === 'decrease'
                && $residualValue->isGreaterThanOrEqualTo($currentValue)
            ) {
                $validator->errors()->add(
                    'residual_value',
                    'Nilai minimum harus lebih kecil dari nilai aset saat ini.',
                );
            }

            if (
                $this->input('value_direction') === 'decrease'
                && $residualValue->isGreaterThan($purchasePrice)
            ) {
                $validator->errors()->add(
                    'residual_value',
                    'Nilai minimum tidak boleh melebihi harga beli.',
                );
            }
        }];
    }
}
