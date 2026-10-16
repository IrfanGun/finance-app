<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciation;
use App\Models\AssetDepreciationLog;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetService
{
    public function __construct(
        private readonly AssetDepreciationService $depreciations,
    ) {}

    public function listFor(User $user): LengthAwarePaginator
    {
        return $user->assets()
            ->with('depreciation.rateSteps')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @return array<int, string>
     */
    public function currencyOptionsFor(User $user, ?string $includeCurrency = null): array
    {
        $currencies = $user->financialAccounts()
            ->pluck('currency')
            ->map(fn (?string $currency): string => $currency ?: 'IDR');

        if ($currencies->isEmpty()) {
            $currencies->push('IDR');
        }

        if ($includeCurrency !== null) {
            $currencies->push($includeCurrency);
        }

        return $currencies
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function findFor(User $user, int $assetId): Asset
    {
        return $user->assets()
            ->with('depreciation.rateSteps')
            ->findOrFail($assetId);
    }

    public function details(Asset $asset): Asset
    {
        $depreciation = $asset->depreciation;
        $totalValueChange = $depreciation?->logs()->sum('value_change') ?? '0.00';
        $lastMonthStart = now()->startOfMonth()->subMonth();
        $lastMonthEnd = $lastMonthStart->copy()->endOfMonth();
        $lastMonthValueChange = $depreciation?->logs()
            ->whereBetween('depreciation_date', [
                $lastMonthStart->toDateString(),
                $lastMonthEnd->toDateString(),
            ])
            ->sum('value_change') ?? '0.00';
        $startingValue = $depreciation?->logs()
            ->whereDate('depreciation_date', '<', $lastMonthStart->toDateString())
            ->orderByDesc('depreciation_date')
            ->orderByDesc('id')
            ->value('value_after') ?? $asset->purchase_price;
        $monthlyChangePercentage = BigDecimal::zero()->toScale(2);

        if (BigDecimal::of((string) $startingValue)->isGreaterThan(0)) {
            $monthlyChangePercentage = BigDecimal::of((string) $lastMonthValueChange)
                ->dividedBy((string) $startingValue, 6, RoundingMode::HalfUp)
                ->multipliedBy(100)
                ->toScale(2, RoundingMode::HalfUp);
        }

        return $asset
            ->setAttribute('total_value_change', $totalValueChange)
            ->setAttribute('last_month_value_change_percentage', $monthlyChangePercentage->__toString());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Asset
    {
        return DB::transaction(function () use ($user, $data): Asset {
            $depreciation = $data['depreciation'] ?? null;
            $asset = new Asset(Arr::except($data, ['depreciation']));
            $asset->forceFill([
                'current_value' => $data['purchase_price'],
            ]);
            $user->assets()->save($asset);

            if (is_array($depreciation)) {
                $this->saveDepreciation($asset, $depreciation, null);
            }

            return $asset->load('depreciation.rateSteps');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Asset $asset, array $data): Asset
    {
        return DB::transaction(function () use ($asset, $data): Asset {
            $lockedAsset = Asset::query()
                ->whereKey($asset->id)
                ->lockForUpdate()
                ->firstOrFail();
            $setting = $lockedAsset->depreciation()
                ->lockForUpdate()
                ->first();
            $hasLogs = $setting !== null && $setting->logs()->exists();
            $assetData = Arr::except($data, ['depreciation']);

            if ($hasLogs) {
                $this->ensureProtectedDataUnchanged($lockedAsset, $setting, $assetData, $data);
            }

            $purchaseChanged = $this->purchaseDataChanged($lockedAsset, $assetData);
            $lockedAsset->fill($assetData);

            if (! $hasLogs && $purchaseChanged) {
                $lockedAsset->forceFill([
                    'current_value' => $assetData['purchase_price'] ?? $lockedAsset->purchase_price,
                ]);
            }

            $lockedAsset->save();

            if (! $hasLogs && array_key_exists('depreciation', $data)) {
                $depreciation = $data['depreciation'];

                if ($depreciation === null) {
                    $setting?->delete();
                } elseif (is_array($depreciation)) {
                    $this->saveDepreciation($lockedAsset, $depreciation, $setting);
                }
            } elseif (! $hasLogs && $purchaseChanged && $setting !== null) {
                $startDate = $setting->depreciation_start_date->toDateString();

                if ($startDate < $lockedAsset->purchase_date->toDateString()) {
                    $startDate = $lockedAsset->purchase_date->toDateString();
                }

                $setting->update([
                    'depreciation_start_date' => $startDate,
                    'next_depreciation_date' => $this->depreciations->firstDueDate(
                        $startDate,
                        $setting->depreciation_period,
                    ),
                    'last_depreciation_date' => null,
                    'status' => $setting->status,
                ]);
            }

            return $lockedAsset->refresh()->load('depreciation.rateSteps');
        });
    }

    public function delete(Asset $asset): void
    {
        $asset->delete();
    }

    public function dispose(Asset $asset): Asset
    {
        DB::transaction(function () use ($asset): void {
            $lockedAsset = Asset::query()
                ->whereKey($asset->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedAsset->update([
                'status' => Asset::STATUS_DISPOSED,
            ]);
        });

        return $asset->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDepreciation(Asset $asset, array $data): AssetDepreciation
    {
        return DB::transaction(function () use ($asset, $data): AssetDepreciation {
            $lockedAsset = Asset::query()
                ->whereKey($asset->id)
                ->lockForUpdate()
                ->firstOrFail();
            $setting = $lockedAsset->depreciation()
                ->lockForUpdate()
                ->first();

            if ($setting !== null && $setting->logs()->exists()) {
                $this->ensureDepreciationDataUnchanged($setting, $data);

                return $setting;
            }

            return $this->saveDepreciation($lockedAsset, $data, $setting);
        });
    }

    public function updateDepreciationStatus(Asset $asset, string $status): ?AssetDepreciation
    {
        $setting = DB::transaction(function () use ($asset, $status): ?AssetDepreciation {
            $lockedAsset = Asset::query()
                ->whereKey($asset->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedSetting = $lockedAsset->depreciation()
                ->lockForUpdate()
                ->first();

            if ($lockedSetting === null) {
                throw ValidationException::withMessages([
                    'status' => 'Aset ini belum memiliki pengaturan depresiasi.',
                ]);
            }

            if ($status === AssetDepreciation::STATUS_ACTIVE) {
                if ($lockedAsset->status !== Asset::STATUS_ACTIVE) {
                    throw ValidationException::withMessages([
                        'status' => 'Depresiasi tidak dapat diaktifkan untuk aset yang sudah disposed.',
                    ]);
                }

                if ($lockedSetting->status === AssetDepreciation::STATUS_COMPLETED) {
                    throw ValidationException::withMessages([
                        'status' => 'Depresiasi yang sudah completed tidak dapat diaktifkan kembali.',
                    ]);
                }
            }

            $lockedSetting->update([
                'status' => $status,
            ]);

            return $lockedSetting->refresh();
        });

        if ($status === AssetDepreciation::STATUS_ACTIVE && $setting !== null) {
            $this->depreciations->processSetting($setting->id);

            return $setting->refresh();
        }

        return $setting;
    }

    public function logsFor(Asset $asset): LengthAwarePaginator
    {
        $setting = $asset->depreciation;

        if ($setting === null) {
            return AssetDepreciationLog::query()
                ->whereRaw('1 = 0')
                ->paginate(15);
        }

        return $setting->logs()
            ->orderByDesc('depreciation_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Remove audit rows explicitly before a user account is hard-deleted.
     */
    public function purgeForAccountDeletion(User $user): void
    {
        $settingIds = AssetDepreciation::query()
            ->whereHas('asset', function ($query) use ($user): void {
                $query->withTrashed()->where('user_id', $user->id);
            })
            ->select('id');

        AssetDepreciationLog::query()
            ->whereIn('asset_depreciation_id', $settingIds)
            ->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveDepreciation(
        Asset $asset,
        array $data,
        ?AssetDepreciation $setting,
    ): AssetDepreciation {
        $purchasePrice = BigDecimal::of($asset->purchase_price);
        $residualValue = BigDecimal::of($data['residual_value'] ?? '0.00')->toScale(2);
        $direction = $data['value_direction'];
        $isDecreasing = $direction === AssetDepreciation::DIRECTION_DECREASE;

        if (
            $isDecreasing
            && ($residualValue->isLessThan(0) || $residualValue->isGreaterThan($purchasePrice))
        ) {
            throw ValidationException::withMessages([
                'residual_value' => 'Nilai minimum harus antara nol dan harga beli.',
            ]);
        }

        if ($isDecreasing && $residualValue->isGreaterThanOrEqualTo($asset->current_value)) {
            throw ValidationException::withMessages([
                'residual_value' => 'Nilai minimum harus lebih kecil dari nilai aset saat ini.',
            ]);
        }

        $startDate = $data['depreciation_start_date']
            ?? $asset->purchase_date->toDateString();
        $type = $data['depreciation_type'];
        $isTiered = (bool) ($data['is_tiered'] ?? false);
        $rateSteps = collect($isTiered ? ($data['rate_steps'] ?? []) : [])
            ->sortBy('year_number')
            ->values();
        $depreciationValue = $isTiered
            ? $rateSteps->first()['depreciation_value'] ?? null
            : $data['depreciation_value'];

        if (
            $isTiered
            && ($rateSteps->isEmpty() || (int) $rateSteps->first()['year_number'] !== 1)
        ) {
            throw ValidationException::withMessages([
                'rate_steps' => 'Jadwal depresiasi harus dimulai dari tahun ke-1.',
            ]);
        }

        if (
            ($isTiered || ! $isDecreasing)
            && $data['depreciation_period'] !== 'yearly'
        ) {
            throw ValidationException::withMessages([
                'depreciation_period' => 'Depresiasi bertingkat dan kenaikan nilai hanya mendukung periode tahunan.',
            ]);
        }

        $periodAmount = $this->depreciations->periodAmount(
            (string) $asset->purchase_price,
            $type,
            (string) $depreciationValue,
        );

        if ($periodAmount->isLessThanOrEqualTo(0)) {
            throw ValidationException::withMessages([
                'depreciation_value' => 'Nilai depresiasi harus menghasilkan penyusutan minimal Rp 0,01 per periode.',
            ]);
        }

        $attributes = [
            'depreciation_type' => $type,
            'is_tiered' => $isTiered,
            'value_direction' => $direction,
            'depreciation_value' => $depreciationValue,
            'depreciation_period' => $data['depreciation_period'],
            'residual_value' => $isDecreasing
                ? $residualValue->toScale(2)->__toString()
                : '0.00',
            'depreciation_start_date' => $startDate,
            'next_depreciation_date' => $this->depreciations->firstDueDate(
                $startDate,
                $data['depreciation_period'],
            ),
            'last_depreciation_date' => null,
            'status' => $setting?->status ?? AssetDepreciation::STATUS_ACTIVE,
        ];

        if ($setting === null) {
            $setting = $asset->depreciation()->create($attributes);
        } else {
            $setting->update($attributes);
        }

        $setting->rateSteps()->delete();

        if ($isTiered) {
            foreach ($rateSteps as $rateStep) {
                $setting->rateSteps()->create($rateStep);
            }
        }

        return $setting->refresh()->load('rateSteps');
    }

    /**
     * @param  array<string, mixed>  $assetData
     * @param  array<string, mixed>  $data
     */
    private function ensureProtectedDataUnchanged(
        Asset $asset,
        AssetDepreciation $setting,
        array $assetData,
        array $data,
    ): void {
        foreach (['purchase_price', 'purchase_date', 'currency'] as $field) {
            if (
                array_key_exists($field, $assetData)
                && ! $this->sameValue($field, $assetData[$field], $asset->{$field})
            ) {
                throw ValidationException::withMessages([
                    $field => 'Harga dan detail pembelian tidak dapat diubah setelah depresiasi tercatat.',
                ]);
            }
        }

        if (array_key_exists('depreciation', $data)) {
            if ($data['depreciation'] === null) {
                throw ValidationException::withMessages([
                    'depreciation' => 'Pengaturan tidak dapat dihapus setelah depresiasi tercatat.',
                ]);
            }

            $this->ensureDepreciationDataUnchanged($setting, $data['depreciation']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureDepreciationDataUnchanged(
        AssetDepreciation $setting,
        array $data,
    ): void {
        foreach ([
            'depreciation_type',
            'is_tiered',
            'value_direction',
            'depreciation_value',
            'depreciation_period',
            'residual_value',
            'depreciation_start_date',
        ] as $field) {
            if (
                array_key_exists($field, $data)
                && ! $this->sameValue($field, $data[$field], $setting->{$field})
            ) {
                throw ValidationException::withMessages([
                    $field => 'Aturan depresiasi tidak dapat diubah setelah depresiasi tercatat.',
                ]);
            }
        }

        if (
            array_key_exists('rate_steps', $data)
            && ! $this->rateStepsMatch($setting, $data['rate_steps'])
        ) {
            throw ValidationException::withMessages([
                'rate_steps' => 'Jadwal depresiasi tidak dapat diubah setelah depresiasi tercatat.',
            ]);
        }
    }

    /**
     * @param  array<int, array{year_number: int|string, depreciation_value: int|float|string}>  $rateSteps
     */
    private function rateStepsMatch(AssetDepreciation $setting, array $rateSteps): bool
    {
        $savedSteps = $setting->rateSteps()
            ->orderBy('year_number')
            ->get(['year_number', 'depreciation_value']);
        $requestedSteps = collect($rateSteps)
            ->sortBy('year_number')
            ->values();

        if ($savedSteps->count() !== $requestedSteps->count()) {
            return false;
        }

        foreach ($savedSteps as $index => $savedStep) {
            $requestedStep = $requestedSteps[$index];

            if (
                (int) $requestedStep['year_number'] !== $savedStep->year_number
                || ! BigDecimal::of((string) $requestedStep['depreciation_value'])
                    ->isEqualTo($savedStep->depreciation_value)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $assetData
     */
    private function purchaseDataChanged(Asset $asset, array $assetData): bool
    {
        foreach (['purchase_price', 'purchase_date', 'currency'] as $field) {
            if (
                array_key_exists($field, $assetData)
                && ! $this->sameValue($field, $assetData[$field], $asset->{$field})
            ) {
                return true;
            }
        }

        return false;
    }

    private function sameValue(string $field, mixed $first, mixed $second): bool
    {
        if (in_array($field, ['purchase_price', 'depreciation_value', 'residual_value'], true)) {
            return BigDecimal::of((string) $first)->isEqualTo((string) $second);
        }

        return (string) $first === (string) $second;
    }
}
