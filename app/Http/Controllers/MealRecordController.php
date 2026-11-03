<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMealRecordRequest;
use App\Http\Requests\UploadMealRecordPhotoRequest;
use App\Models\FinancialAccount;
use App\Models\MealRecord;
use App\Services\MealRecordService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MealRecordController extends Controller
{
    public function index(Request $request, MealRecordService $mealRecords): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:buy,homemade'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0', 'gte:price_min'],
            'availability' => ['nullable', 'in:open,closed,unknown'],
        ]);
        $records = $mealRecords->paginate($request->user(), $filters);
        $records->getCollection()->transform(function (MealRecord $mealRecord) use ($mealRecords): MealRecord {
            $mealRecord->setAttribute(
                'opening_status',
                $mealRecord->type === 'buy'
                    ? $mealRecords->openingStatus($mealRecord)
                    : null,
            );
            $mealRecord->setAttribute('photo_count', count($mealRecord->photoPaths()));
            $mealRecord->setAttribute('has_photo', $mealRecord->photoPaths() !== []);

            return $mealRecord;
        });

        return Inertia::render('Meals/Index', [
            'mealRecords' => $records,
            'filters' => $filters,
            ...($request->routeIs('cms.meals.*') ? $this->expenseFormOptions($request) : []),
            'isCms' => $request->routeIs('cms.meals.*'),
        ]);
    }

    public function store(
        StoreMealRecordRequest $request,
        MealRecordService $mealRecords,
    ): RedirectResponse {
        $data = $request->validated();
        $photos = $data['photos'] ?? [];
        $removePhotoIndexes = $data['remove_photo_indexes'] ?? [];
        $primaryPhotoIndex = $data['primary_photo_index'] ?? null;
        unset($data['photos']);
        unset($data['remove_photo_indexes']);
        unset($data['primary_photo_index']);

        if ($removePhotoIndexes !== []) {
            throw ValidationException::withMessages([
                'remove_photo_indexes' => 'Foto baru belum dapat dihapus sebelum disimpan.',
            ]);
        }

        $mealRecord = $mealRecords->save($request->user(), $data);

        if ($photos !== []) {
            $mealRecords->appendPhotos($mealRecord, $photos);
        }

        if ($primaryPhotoIndex !== null && $photos !== []) {
            $mealRecords->setPrimaryPhoto($mealRecord, $primaryPhotoIndex);
        }

        return $this->mealIndexRedirect($request)
            ->with('success', 'Pilihan makanan berhasil disimpan.');
    }

    public function show(Request $request, int $meal, MealRecordService $mealRecords): Response
    {
        $mealRecord = $request->user()->mealRecords()
            ->with('openingHours')
            ->findOrFail($meal);
        $mealRecord->setAttribute('photo_count', count($mealRecord->photoPaths()));
        $mealRecord->setAttribute('has_photo', $mealRecord->photoPaths() !== []);

        return Inertia::render('Meals/Show', [
            'mealRecord' => $mealRecord,
            'openingStatus' => $mealRecords->openingStatus($mealRecord),
            ...($request->routeIs('cms.meals.*') ? $this->expenseFormOptions($request) : []),
            'isCms' => $request->routeIs('cms.meals.*'),
        ]);
    }

    public function update(
        StoreMealRecordRequest $request,
        int $meal,
        MealRecordService $mealRecords,
    ): RedirectResponse {
        $record = $request->user()->mealRecords()->findOrFail($meal);
        $data = $request->validated();
        $photos = $data['photos'] ?? [];
        $removePhotoIndexes = array_values(array_unique($data['remove_photo_indexes'] ?? []));
        $primaryPhotoIndex = $data['primary_photo_index'] ?? null;
        unset($data['photos']);
        unset($data['remove_photo_indexes']);
        unset($data['primary_photo_index']);

        $existingPhotoPaths = $record->photoPaths();

        foreach ($removePhotoIndexes as $photoIndex) {
            if (! array_key_exists($photoIndex, $existingPhotoPaths)) {
                throw ValidationException::withMessages([
                    'remove_photo_indexes' => 'Foto yang dipilih tidak tersedia. Muat ulang halaman lalu coba lagi.',
                ]);
            }
        }

        if (count($existingPhotoPaths) - count($removePhotoIndexes) + count($photos) > 5) {
            throw ValidationException::withMessages([
                'photos' => 'Setiap makanan dapat memiliki maksimal 5 foto.',
            ]);
        }

        $record = $mealRecords->save($request->user(), $data, $record);

        if ($removePhotoIndexes !== []) {
            $mealRecords->removePhotosAtIndexes($record, $removePhotoIndexes);
        }

        if ($photos !== []) {
            $mealRecords->appendPhotos($record, $photos);
        }

        $photoCountAfterChanges = count($existingPhotoPaths)
            - count($removePhotoIndexes)
            + count($photos);

        if ($primaryPhotoIndex !== null && $photoCountAfterChanges > 0) {
            $mealRecords->setPrimaryPhoto($record, $primaryPhotoIndex);
        }

        return $this->mealIndexRedirect($request)
            ->with('success', 'Pilihan makanan berhasil diperbarui.');
    }

    public function destroy(Request $request, int $meal, MealRecordService $mealRecords): RedirectResponse
    {
        $record = $request->user()->mealRecords()->findOrFail($meal);
        $mealRecords->delete($record);

        return $this->mealIndexRedirect($request)
            ->with('success', 'Pilihan makanan berhasil dihapus.');
    }

    public function uploadPhoto(
        UploadMealRecordPhotoRequest $request,
        int $meal,
        MealRecordService $mealRecords,
    ): RedirectResponse {
        $record = $request->user()->mealRecords()->findOrFail($meal);
        $mealRecords->appendPhotos($record, [$request->file('photo')]);

        return back();
    }

    public function photo(Request $request, int $meal): HttpResponse
    {
        $record = $request->user()->mealRecords()->findOrFail($meal);
        $photoIndex = $request->integer('index', 0);
        $photoPaths = $record->photoPaths();
        $photoPath = $photoIndex >= 0
            ? ($photoPaths[$photoIndex] ?? null)
            : null;
        abort_if($photoPath === null, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($photoPath), 404);

        return response($disk->get($photoPath), 200, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Type' => $disk->mimeType($photoPath) ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function mealIndexRedirect(Request $request): RedirectResponse
    {
        return to_route($request->routeIs('cms.meals.*') ? 'cms.meals.index' : 'meals.index');
    }

    private function expenseFormOptions(Request $request): array
    {
        $user = $request->user();

        return [
            'mealCurrency' => 'IDR',
            'expenseCategories' => $user->categories()
                ->where('type', 'expense')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'accounts' => $user->financialAccounts()
                ->where(function (Builder $query): void {
                    $query->whereRaw('UPPER(currency) = ?', ['IDR'])
                        ->orWhere(function (Builder $legacyAccountQuery): void {
                            $legacyAccountQuery
                                ->whereNull('currency')
                                ->where('type', '!=', 'investment');
                        });
                })
                ->orderBy('name')
                ->get()
                ->map(fn (FinancialAccount $account): array => [
                    'id' => $account->id,
                    'name' => $account->name,
                    'currency' => strtoupper($account->currencyCode() ?? 'IDR'),
                ])
                ->values(),
        ];
    }
}
