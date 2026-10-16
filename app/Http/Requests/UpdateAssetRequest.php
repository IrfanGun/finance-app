<?php

namespace App\Http\Requests;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAssetRequest extends FormRequest
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
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'currency' => [
                'sometimes',
                'required',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::in($this->currencyOptions()),
            ],
            'purchase_price' => ['sometimes', 'required', 'numeric', 'gt:0', 'decimal:0,2'],
            'purchase_date' => [
                'sometimes',
                'required',
                'date',
                'before_or_equal:'.CarbonImmutable::today('Asia/Jakarta')->toDateString(),
            ],
            'depreciation' => ['sometimes', 'nullable', 'array'],
            'depreciation.depreciation_type' => [
                'required_with:depreciation',
                'in:fixed,percentage',
            ],
            'depreciation.value_direction' => [
                'required_with:depreciation',
                'in:decrease,increase',
            ],
            'depreciation.is_tiered' => [
                'sometimes',
                'boolean',
            ],
            'depreciation.depreciation_value' => [
                'exclude_if:depreciation.is_tiered,1',
                'required_unless:depreciation.is_tiered,1',
                'numeric',
                'gt:0',
                'decimal:0,4',
                Rule::when(
                    $this->input('depreciation.depreciation_type') === 'percentage',
                    ['max:100'],
                ),
            ],
            'depreciation.rate_steps' => [
                'exclude_unless:depreciation.is_tiered,1',
                'required_if:depreciation.is_tiered,1',
                'array',
                'min:1',
            ],
            'depreciation.rate_steps.*.year_number' => [
                'required_with:depreciation.rate_steps',
                'integer',
                'min:1',
                'max:1000',
                'distinct',
            ],
            'depreciation.rate_steps.*.depreciation_value' => [
                'required_with:depreciation.rate_steps',
                'numeric',
                'gt:0',
                'decimal:0,4',
                Rule::when(
                    $this->input('depreciation.depreciation_type') === 'percentage',
                    ['max:100'],
                ),
            ],
            'depreciation.depreciation_period' => [
                'required_with:depreciation',
                'in:monthly,yearly',
            ],
            'depreciation.residual_value' => [
                'required_with:depreciation',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'depreciation.depreciation_start_date' => [
                'nullable',
                'date',
                'after_or_equal:purchase_date',
                'before_or_equal:'.CarbonImmutable::today('Asia/Jakarta')->toDateString(),
            ],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $depreciation = $this->input('depreciation');

            if (! is_array($depreciation)
                || $validator->errors()->hasAny([
                    'purchase_price',
                    'depreciation.depreciation_type',
                ])) {
                return;
            }

            $routeAsset = $this->user()?->assets()->find($this->route('asset'));
            $purchasePrice = (string) $this->input(
                'purchase_price',
                $routeAsset?->purchase_price ?? '0',
            );

            $isTiered = (bool) ($depreciation['is_tiered'] ?? false);
            $isIncreasing = ($depreciation['value_direction'] ?? null) === 'increase';

            if ($isTiered) {
                $rateSteps = collect(is_array($depreciation['rate_steps'] ?? null)
                    ? $depreciation['rate_steps']
                    : [])
                    ->filter(fn (mixed $step): bool => is_array($step))
                    ->sortBy('year_number')
                    ->values();

                if (
                    $rateSteps->isNotEmpty()
                    && (int) ($rateSteps->first()['year_number'] ?? 0) !== 1
                ) {
                    $validator->errors()->add(
                        'depreciation.rate_steps',
                        'Jadwal depresiasi harus dimulai dari tahun ke-1.',
                    );
                }

                if (
                    ($depreciation['depreciation_period'] ?? null) !== 'yearly'
                    && ! $validator->errors()->has('depreciation.depreciation_period')
                ) {
                    $validator->errors()->add(
                        'depreciation.depreciation_period',
                        'Depresiasi bertingkat hanya mendukung periode tahunan.',
                    );
                }

                $firstRateStep = $rateSteps->first(
                    fn (array $step): bool => (int) ($step['year_number'] ?? 0) === 1,
                );
                $firstRate = $firstRateStep['depreciation_value'] ?? null;

                if ($firstRate !== null && is_numeric($firstRate)) {
                    $periodAmount = $depreciation['depreciation_type'] === 'percentage'
                        ? BigDecimal::of($purchasePrice)
                            ->multipliedBy((string) $firstRate)
                            ->dividedBy(100, 2, RoundingMode::HalfUp)
                        : BigDecimal::of((string) $firstRate)
                            ->toScale(2, RoundingMode::HalfUp);

                    if ($periodAmount->isLessThanOrEqualTo(0)) {
                        $validator->errors()->add(
                            'depreciation.rate_steps',
                            'Nilai tahun pertama harus menghasilkan penyusutan minimal 0,01 per tahun.',
                        );
                    }
                }

                if ($depreciation['depreciation_type'] === 'fixed') {
                    $requestedSteps = $depreciation['rate_steps'] ?? [];

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
                                "depreciation.rate_steps.{$index}.depreciation_value",
                                'Nominal tetap maksimal dua angka desimal.',
                            );
                        }
                    }
                }
            } elseif (! $validator->errors()->has('depreciation.depreciation_value')) {
                if ($depreciation['depreciation_type'] === 'fixed'
                    && BigDecimal::of((string) $depreciation['depreciation_value'])
                        ->strippedOfTrailingZeros()
                        ->getScale() > 2) {
                    $validator->errors()->add(
                        'depreciation.depreciation_value',
                        'Depresiasi nominal tetap maksimal dua angka desimal.',
                    );
                }

                $periodAmount = $depreciation['depreciation_type'] === 'percentage'
                    ? BigDecimal::of($purchasePrice)
                        ->multipliedBy((string) $depreciation['depreciation_value'])
                        ->dividedBy(100, 2, RoundingMode::HalfUp)
                    : BigDecimal::of((string) $depreciation['depreciation_value'])
                        ->toScale(2, RoundingMode::HalfUp);

                if ($periodAmount->isLessThanOrEqualTo(0)) {
                    $validator->errors()->add(
                        'depreciation.depreciation_value',
                        'Depresiasi harus menghasilkan penyusutan minimal Rp 0,01 per periode.',
                    );
                }
            }

            if (
                $isIncreasing
                && ($depreciation['depreciation_period'] ?? null) !== 'yearly'
                && ! $validator->errors()->has('depreciation.depreciation_period')
            ) {
                $validator->errors()->add(
                    'depreciation.depreciation_period',
                    'Kenaikan nilai aset hanya mendukung periode tahunan.',
                );
            }
        }];
    }

    /**
     * @return array<int, string>
     */
    private function currencyOptions(): array
    {
        $currencies = $this->user()
            ->financialAccounts()
            ->pluck('currency')
            ->map(fn (?string $currency): string => $currency ?: 'IDR')
            ->unique()
            ->values()
            ->all();
        $asset = $this->user()->assets()->find($this->route('asset'));

        if ($asset !== null) {
            $currencies[] = $asset->currency;
        }

        return $currencies === [] ? ['IDR'] : array_values(array_unique($currencies));
    }
}
