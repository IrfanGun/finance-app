<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class AssetDepreciationService
{
    private const BUSINESS_TIMEZONE = 'Asia/Jakarta';

    /**
     * @return array{processed_settings: int, failed_settings: int, logs_created: int}
     */
    public function processDue(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today(self::BUSINESS_TIMEZONE);

        $summary = [
            'processed_settings' => 0,
            'failed_settings' => 0,
            'logs_created' => 0,
        ];

        AssetDepreciation::query()
            ->where('status', AssetDepreciation::STATUS_ACTIVE)
            ->whereDate('next_depreciation_date', '<=', $today->toDateString())
            ->whereHas('asset', function ($query): void {
                $query->where('status', Asset::STATUS_ACTIVE);
            })
            ->chunkById(100, function ($settings) use (&$summary, $today): void {
                foreach ($settings as $setting) {
                    try {
                        $logsCreated = $this->processSetting($setting->id, $today);

                        if ($logsCreated > 0) {
                            $summary['processed_settings']++;
                            $summary['logs_created'] += $logsCreated;
                        }
                    } catch (Throwable $exception) {
                        $summary['failed_settings']++;

                        Log::error('Asset depreciation failed.', [
                            'asset_depreciation_id' => $setting->id,
                            'asset_id' => $setting->asset_id,
                            'exception' => $exception,
                        ]);
                    }
                }
            });

        return $summary;
    }

    public function processSetting(int $settingId, ?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today(self::BUSINESS_TIMEZONE);
        $assetId = AssetDepreciation::query()->whereKey($settingId)->value('asset_id');

        if ($assetId === null) {
            return 0;
        }

        return DB::transaction(function () use ($assetId, $settingId, $today): int {
            $asset = Asset::withTrashed()
                ->whereKey($assetId)
                ->lockForUpdate()
                ->first();
            $setting = AssetDepreciation::query()
                ->whereKey($settingId)
                ->lockForUpdate()
                ->first();

            if (
                $asset === null
                || $asset->trashed()
                || $asset->status !== Asset::STATUS_ACTIVE
                || $setting === null
                || $setting->status !== AssetDepreciation::STATUS_ACTIVE
                || $setting->next_depreciation_date === null
            ) {
                return 0;
            }

            $currentValue = BigDecimal::of($asset->current_value)->toScale(2);
            $purchasePrice = BigDecimal::of($asset->purchase_price)->toScale(2);
            $residualValue = BigDecimal::of($setting->residual_value)->toScale(2);
            $rateSteps = $setting->is_tiered
                ? $setting->rateSteps
                : collect();
            $anchor = CarbonImmutable::parse(
                $setting->depreciation_start_date,
                self::BUSINESS_TIMEZONE,
            )->startOfDay();
            $nextDate = CarbonImmutable::parse(
                $setting->next_depreciation_date,
                self::BUSINESS_TIMEZONE,
            )->startOfDay();
            $logsCreated = 0;
            $lastDepreciationDate = $setting->last_depreciation_date?->toDateString();
            $isDecreasing = $setting->value_direction === AssetDepreciation::DIRECTION_DECREASE;

            while ($nextDate->lessThanOrEqualTo($today)) {
                $availableValue = $isDecreasing
                    ? $currentValue->minus($residualValue)
                    : null;

                if ($availableValue !== null && $availableValue->isLessThanOrEqualTo(0)) {
                    $setting->status = AssetDepreciation::STATUS_COMPLETED;
                    $setting->next_depreciation_date = null;

                    break;
                }

                $basis = $purchasePrice;
                $type = $setting->depreciation_type;
                $value = $setting->depreciation_value;

                if ($setting->is_tiered) {
                    $yearNumber = max(1, $nextDate->year - $anchor->year);
                    $rateStep = $rateSteps->last(
                        fn ($step): bool => $step->year_number <= $yearNumber,
                    );

                    if ($rateStep === null) {
                        throw new LogicException(
                            'Tiered depreciation requires a rate for year one.',
                        );
                    }

                    $value = $rateStep->depreciation_value;
                }

                $periodAmount = $this->periodAmount(
                    $basis->__toString(),
                    $type,
                    (string) $value,
                );
                $actualAmount = $isDecreasing
                    ? BigDecimal::min($periodAmount, $availableValue)
                        ->toScale(2, RoundingMode::HalfUp)
                    : $periodAmount->toScale(2, RoundingMode::HalfUp);

                if ($actualAmount->isLessThanOrEqualTo(0)) {
                    $setting->status = AssetDepreciation::STATUS_COMPLETED;
                    $setting->next_depreciation_date = null;

                    break;
                }

                $valueAfter = ($isDecreasing
                    ? $currentValue->minus($actualAmount)
                    : $currentValue->plus($actualAmount))
                    ->toScale(2, RoundingMode::HalfUp);
                $valueChange = $isDecreasing
                    ? $actualAmount->multipliedBy(-1)->toScale(2, RoundingMode::HalfUp)
                    : $actualAmount;

                $setting->logs()->create([
                    'depreciation_date' => $nextDate->toDateString(),
                    'value_change' => $valueChange->__toString(),
                    'value_before' => $currentValue->toScale(2)->__toString(),
                    'value_after' => $valueAfter->__toString(),
                ]);

                $currentValue = $valueAfter;
                $lastDepreciationDate = $nextDate->toDateString();
                $logsCreated++;

                if ($isDecreasing && $currentValue->isLessThanOrEqualTo($residualValue)) {
                    $setting->status = AssetDepreciation::STATUS_COMPLETED;
                    $setting->next_depreciation_date = null;

                    break;
                }

                $nextDate = $this->nextOccurrence(
                    $anchor,
                    $nextDate,
                    $setting->depreciation_period,
                );
                $setting->next_depreciation_date = $nextDate->toDateString();
            }

            if ($logsCreated > 0) {
                $asset->forceFill([
                    'current_value' => $currentValue->toScale(2)->__toString(),
                ])->save();

                $setting->last_depreciation_date = $lastDepreciationDate;
                $setting->save();
            } elseif ($setting->isDirty()) {
                $setting->save();
            }

            return $logsCreated;
        });
    }

    public function periodAmount(
        string $purchasePrice,
        string $type,
        string $depreciationValue,
    ): BigDecimal {
        $amount = BigDecimal::of($depreciationValue);

        if ($type === 'percentage') {
            return BigDecimal::of($purchasePrice)
                ->multipliedBy($amount)
                ->dividedBy(100, 2, RoundingMode::HalfUp);
        }

        return $amount->toScale(2, RoundingMode::HalfUp);
    }

    public function firstDueDate(string $startDate, string $period): string
    {
        $anchor = CarbonImmutable::parse($startDate, self::BUSINESS_TIMEZONE)->startOfDay();

        return $this->dateAtOffset($anchor, 1, $period)->toDateString();
    }

    private function nextOccurrence(
        CarbonImmutable $anchor,
        CarbonImmutable $currentDate,
        string $period,
    ): CarbonImmutable {
        $offset = $period === 'yearly'
            ? $currentDate->year - $anchor->year
            : (($currentDate->year - $anchor->year) * 12) + ($currentDate->month - $anchor->month);

        return $this->dateAtOffset($anchor, $offset + 1, $period);
    }

    private function dateAtOffset(
        CarbonImmutable $anchor,
        int $offset,
        string $period,
    ): CarbonImmutable {
        if ($period === 'yearly') {
            $year = $anchor->year + $offset;
            $month = $anchor->month;
        } else {
            $monthIndex = ($anchor->year * 12) + ($anchor->month - 1) + $offset;
            $year = intdiv($monthIndex, 12);
            $month = ($monthIndex % 12) + 1;
        }

        $daysInMonth = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            self::BUSINESS_TIMEZONE,
        )->daysInMonth;

        return CarbonImmutable::create(
            $year,
            $month,
            min($anchor->day, $daysInMonth),
            0,
            0,
            0,
            self::BUSINESS_TIMEZONE,
        );
    }
}
