<?php

namespace App\Services;

use App\Models\MealRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MealRecordService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(User $user, array $data, ?MealRecord $mealRecord = null): MealRecord
    {
        return DB::transaction(function () use ($user, $data, $mealRecord): MealRecord {
            $openingHours = $data['opening_hours'] ?? [];
            unset($data['opening_hours']);

            $mealRecord ??= new MealRecord;
            $mealRecord->fill($data);
            $mealRecord->user()->associate($user);
            $mealRecord->save();

            $mealRecord->openingHours()->delete();

            if ($mealRecord->type === 'buy') {
                $mealRecord->openingHours()->createMany($openingHours);
            }

            return $mealRecord->load('openingHours');
        });
    }

    public function delete(MealRecord $mealRecord): void
    {
        $photoPaths = $mealRecord->photoPaths();
        $mealRecord->delete();

        if ($photoPaths !== []) {
            Storage::disk('local')->delete($photoPaths);
        }
    }

    /**
     * @param  array<int, int>  $indexes
     */
    public function removePhotosAtIndexes(MealRecord $mealRecord, array $indexes): void
    {
        $indexes = array_values(array_unique($indexes));

        if ($indexes === []) {
            return;
        }

        rsort($indexes);
        $removedPaths = DB::transaction(function () use ($mealRecord, $indexes): array {
            $lockedMealRecord = MealRecord::query()
                ->lockForUpdate()
                ->findOrFail($mealRecord->getKey());
            $photoPaths = $lockedMealRecord->photoPaths();
            $removedPaths = [];

            foreach ($indexes as $index) {
                if (! array_key_exists($index, $photoPaths)) {
                    throw ValidationException::withMessages([
                        'remove_photo_indexes' => 'Foto yang dipilih tidak tersedia. Muat ulang halaman lalu coba lagi.',
                    ]);
                }

                $removedPaths[] = $photoPaths[$index];
                array_splice($photoPaths, $index, 1);
            }

            $lockedMealRecord->forceFill([
                'photo_path' => $photoPaths[0] ?? null,
                'photo_paths' => $photoPaths,
            ])->save();

            return $removedPaths;
        });

        Storage::disk('local')->delete($removedPaths);
    }

    public function setPrimaryPhoto(MealRecord $mealRecord, int $index): void
    {
        DB::transaction(function () use ($mealRecord, $index): void {
            $lockedMealRecord = MealRecord::query()
                ->lockForUpdate()
                ->findOrFail($mealRecord->getKey());
            $photoPaths = $lockedMealRecord->photoPaths();

            if (! array_key_exists($index, $photoPaths)) {
                throw ValidationException::withMessages([
                    'primary_photo_index' => 'Foto utama yang dipilih tidak tersedia. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            $primaryPhotoPath = $photoPaths[$index];
            array_splice($photoPaths, $index, 1);
            array_unshift($photoPaths, $primaryPhotoPath);

            $lockedMealRecord->forceFill([
                'photo_path' => $primaryPhotoPath,
                'photo_paths' => $photoPaths,
            ])->save();
        });
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function appendPhotos(MealRecord $mealRecord, array $photos): void
    {
        $newPaths = [];

        try {
            DB::transaction(function () use ($mealRecord, $photos, &$newPaths): void {
                $lockedMealRecord = MealRecord::query()
                    ->lockForUpdate()
                    ->findOrFail($mealRecord->getKey());
                $currentPaths = $lockedMealRecord->photoPaths();

                if (count($currentPaths) + count($photos) > 5) {
                    throw ValidationException::withMessages([
                        'photos' => 'Setiap makanan dapat memiliki maksimal 5 foto.',
                    ]);
                }

                foreach ($photos as $photo) {
                    $photoPath = $photo->store('meal-records', 'local');

                    if (! is_string($photoPath)) {
                        throw new RuntimeException('Foto makanan gagal disimpan.');
                    }

                    $newPaths[] = $photoPath;
                }

                $allPaths = [...$currentPaths, ...$newPaths];

                $lockedMealRecord->forceFill([
                    'photo_path' => $allPaths[0] ?? null,
                    'photo_paths' => $allPaths,
                ])->save();
            });
        } catch (\Throwable $exception) {
            if ($newPaths !== []) {
                Storage::disk('local')->delete($newPaths);
            }

            throw $exception;
        }
    }

    public function openingStatus(MealRecord $mealRecord, ?Carbon $now = null): string
    {
        $openingHours = $mealRecord->openingHours;

        if ($openingHours->isEmpty()) {
            return 'unknown';
        }

        $now ??= Carbon::now(config('app.timezone'));
        $today = $now->isoWeekday();
        $minute = ($now->hour * 60) + $now->minute;
        $previousDay = $today === 1 ? 7 : $today - 1;

        foreach ($openingHours as $session) {
            $opensAt = ((int) substr($session->opens_at, 0, 2) * 60)
                + (int) substr($session->opens_at, 3, 2);
            $closesAt = ((int) substr($session->closes_at, 0, 2) * 60)
                + (int) substr($session->closes_at, 3, 2);

            if ($session->day_of_week === $today
                && $minute >= $opensAt
                && ($session->closes_next_day || $minute < $closesAt)) {
                return 'open';
            }

            if ($session->day_of_week === $previousDay
                && $session->closes_next_day
                && $minute < $closesAt) {
                return 'open';
            }
        }

        return 'closed';
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, MealRecord>
     */
    public function paginate(User $user, array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $user->mealRecords()
            ->with('openingHours')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('place_name', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['price_min'] ?? null, fn (Builder $query, float $min) => $query->where('price_max', '>=', $min))
            ->when($filters['price_max'] ?? null, fn (Builder $query, float $max) => $query->where('price_min', '<=', $max))
            ->when($filters['availability'] ?? null, function (Builder $query, string $availability): void {
                if ($availability === 'unknown') {
                    $query->where('type', 'buy')
                        ->whereDoesntHave('openingHours');

                    return;
                }

                $now = Carbon::now(config('app.timezone'));
                $today = $now->isoWeekday();
                $previousDay = $today === 1 ? 7 : $today - 1;
                $time = $now->format('H:i:s');
                $isOpen = function ($hours) use ($today, $previousDay, $time): void {
                    $hours->where(function ($hours) use ($today, $previousDay, $time): void {
                        $hours->where(function ($hours) use ($today, $time): void {
                            $hours->where('day_of_week', $today)
                                ->where('opens_at', '<=', $time)
                                ->where(function ($hours) use ($time): void {
                                    $hours->where('closes_next_day', true)
                                        ->orWhere('closes_at', '>', $time);
                                });
                        })->orWhere(function ($hours) use ($previousDay, $time): void {
                            $hours->where('day_of_week', $previousDay)
                                ->where('closes_next_day', true)
                                ->where('closes_at', '>', $time);
                        });
                    });
                };

                $query->where('type', 'buy')
                    ->whereHas('openingHours');

                if ($availability === 'open') {
                    $query->whereHas('openingHours', $isOpen);

                    return;
                }

                $query->whereDoesntHave('openingHours', $isOpen);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function recommendations(User $user, array $filters): array
    {
        $timezone = config('app.timezone');
        $at = isset($filters['at'])
            ? Carbon::parse($filters['at'], $timezone)->setTimezone($timezone)
            : Carbon::now($timezone);
        $openOnly = (bool) ($filters['open_only'] ?? false);
        $openAt = fn (Builder $query): Builder => $this->whereOpeningHoursAt($query, $at);

        $mealRecords = $user->mealRecords()
            ->with('openingHours')
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword): void {
                $query->where(function (Builder $query) use ($keyword): void {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('place_name', 'like', "%{$keyword}%")
                        ->orWhere('impression', 'like', "%{$keyword}%");
                });
            })
            ->when($filters['location'] ?? null, function (Builder $query, string $location): void {
                $query->where('address', 'like', "%{$location}%");
            })
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['min_price'] ?? null, fn (Builder $query, float $min) => $query->where('price_min', '>=', $min))
            ->when($filters['max_price'] ?? null, fn (Builder $query, float $max) => $query->where('price_max', '<=', $max))
            ->when($openOnly, function (Builder $query) use ($openAt): void {
                $query->where('type', 'buy')
                    ->whereHas('openingHours', $openAt);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $items = $mealRecords
            ->map(function (MealRecord $mealRecord) use ($at): array {
                return [
                    'id' => $mealRecord->id,
                    'name' => $mealRecord->name,
                    'place_name' => $mealRecord->place_name,
                    'type' => $mealRecord->type,
                    'price_min' => (float) $mealRecord->price_min,
                    'price_max' => (float) $mealRecord->price_max,
                    'rating' => $mealRecord->rating,
                    'address' => $mealRecord->address,
                    'impression' => $mealRecord->impression,
                    'opening_status' => $mealRecord->type === 'buy'
                        ? $this->openingStatus($mealRecord, $at)
                        : null,
                    'opening_hours' => $mealRecord->openingHours->map(fn ($session): array => [
                        'day_of_week' => $session->day_of_week,
                        'opens_at' => substr($session->opens_at, 0, 5),
                        'closes_at' => substr($session->closes_at, 0, 5),
                        'closes_next_day' => $session->closes_next_day,
                    ])->all(),
                ];
            })
            ->values()
            ->all();

        $foodBudgets = $this->foodBudgets($user, $at);

        return [
            'timezone' => $timezone,
            'search_at' => $at->toIso8601String(),
            'open_only' => $openOnly,
            'meal_records' => $items,
            'food_budgets' => $foodBudgets,
            'food_budget_configured' => count($foodBudgets) > 0,
        ];
    }

    private function whereOpeningHoursAt(Builder $query, Carbon $at): Builder
    {
        $day = $at->isoWeekday();
        $previousDay = $day === 1 ? 7 : $day - 1;
        $time = $at->format('H:i:s');

        return $query->where(function (Builder $hours) use ($day, $previousDay, $time): void {
            $hours->where(function (Builder $hours) use ($day, $time): void {
                $hours->where('day_of_week', $day)
                    ->where('opens_at', '<=', $time)
                    ->where(function (Builder $hours) use ($time): void {
                        $hours->where('closes_next_day', true)
                            ->orWhere('closes_at', '>', $time);
                    });
            })->orWhere(function (Builder $hours) use ($previousDay, $time): void {
                $hours->where('day_of_week', $previousDay)
                    ->where('closes_next_day', true)
                    ->where('closes_at', '>', $time);
            });
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function foodBudgets(User $user, Carbon $now): array
    {
        $foodCategoryIds = $user->categories()
            ->get(['id', 'name'])
            ->filter(fn ($category): bool => preg_match(
                '/makanan|makan|food|meal|dining|kuliner|restoran|restaurant/i',
                $category->name,
            ) === 1)
            ->pluck('id')
            ->all();

        return $user->budgets()
            ->with('category:id,name')
            ->whereDate('period_start', '<=', $now->toDateString())
            ->whereDate('period_end', '>=', $now->toDateString())
            ->get()
            ->filter(fn ($budget): bool => preg_match(
                '/makanan|makan|food|meal|dining|kuliner|restoran|restaurant/i',
                ($budget->name ?? '').' '.($budget->category?->name ?? ''),
            ) === 1)
            ->map(function ($budget) use ($user, $foodCategoryIds): array {
                $spentQuery = $user->transactions()
                    ->where('type', 'expense')
                    ->whereBetween('date', [
                        $budget->period_start->toDateString(),
                        $budget->period_end->toDateString(),
                    ])
                    ->whereHas('account', function (Builder $query): void {
                        $query->where(function (Builder $currencyQuery): void {
                            $currencyQuery
                                ->whereRaw('UPPER(currency) = ?', ['IDR'])
                                ->orWhere(function (Builder $legacyAccountQuery): void {
                                    $legacyAccountQuery
                                        ->whereNull('currency')
                                        ->where('type', '!=', 'investment');
                                });
                        });
                    });
                if ($budget->category_id !== null) {
                    $spentQuery->where('category_id', $budget->category_id);
                } elseif ($foodCategoryIds !== []) {
                    $spentQuery->whereIn('category_id', $foodCategoryIds);
                } else {
                    $spentQuery = null;
                }

                $spent = $spentQuery === null
                    ? null
                    : (float) $spentQuery->sum('amount');
                $amount = (float) $budget->amount;

                return [
                    'name' => $budget->name,
                    'category' => $budget->category?->name,
                    'currency' => 'IDR',
                    'amount' => $amount,
                    'spent' => $spent,
                    'remaining' => $spent === null ? null : $amount - $spent,
                    'remaining_known' => $spent !== null,
                    'period_start' => $budget->period_start->toDateString(),
                    'period_end' => $budget->period_end->toDateString(),
                ];
            })
            ->values()
            ->all();
    }
}
