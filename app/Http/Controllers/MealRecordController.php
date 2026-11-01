<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMealRecordRequest;
use App\Http\Requests\UploadMealRecordPhotoRequest;
use App\Models\MealRecord;
use App\Services\MealRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
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
            $mealRecord->setAttribute('has_photo', $mealRecord->photo_path !== null);

            return $mealRecord;
        });

        return Inertia::render('Meals/Index', [
            'mealRecords' => $records,
            'filters' => $filters,
            'isCms' => $request->routeIs('cms.meals.*'),
        ]);
    }

    public function store(
        StoreMealRecordRequest $request,
        MealRecordService $mealRecords,
    ): RedirectResponse {
        $mealRecords->save($request->user(), $request->validated());

        return $this->mealIndexRedirect($request)
            ->with('success', 'Pilihan makanan berhasil disimpan.');
    }

    public function show(Request $request, int $meal, MealRecordService $mealRecords): Response
    {
        $mealRecord = $request->user()->mealRecords()
            ->with('openingHours')
            ->findOrFail($meal);
        $mealRecord->setAttribute('has_photo', $mealRecord->photo_path !== null);

        return Inertia::render('Meals/Show', [
            'mealRecord' => $mealRecord,
            'openingStatus' => $mealRecords->openingStatus($mealRecord),
            'isCms' => $request->routeIs('cms.meals.*'),
        ]);
    }

    public function update(
        StoreMealRecordRequest $request,
        int $meal,
        MealRecordService $mealRecords,
    ): RedirectResponse {
        $record = $request->user()->mealRecords()->findOrFail($meal);
        $mealRecords->save($request->user(), $request->validated(), $record);

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
        $mealRecords->replacePhoto($record, $request->file('photo'));

        return back();
    }

    public function photo(Request $request, int $meal): HttpResponse
    {
        $record = $request->user()->mealRecords()->findOrFail($meal);
        abort_if($record->photo_path === null, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($record->photo_path), 404);

        return response($disk->get($record->photo_path), 200, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Type' => $disk->mimeType($record->photo_path) ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function mealIndexRedirect(Request $request): RedirectResponse
    {
        return to_route($request->routeIs('cms.meals.*') ? 'cms.meals.index' : 'meals.index');
    }
}
