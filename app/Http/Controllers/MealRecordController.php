<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMealRecordRequest;
use App\Models\MealRecord;
use App\Services\MealRecordService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

            return $mealRecord;
        });

        return Inertia::render('Meals/Index', [
            'mealRecords' => $records,
            'filters' => $filters,
        ]);
    }

    public function store(
        StoreMealRecordRequest $request,
        MealRecordService $mealRecords,
    ): RedirectResponse {
        $mealRecords->save($request->user(), $request->validated());

        return redirect()->route('meals.index')->with('success', 'Pilihan makanan berhasil disimpan.');
    }

    public function show(Request $request, int $meal, MealRecordService $mealRecords): Response
    {
        $mealRecord = $request->user()->mealRecords()
            ->with('openingHours')
            ->findOrFail($meal);

        return Inertia::render('Meals/Show', [
            'mealRecord' => $mealRecord,
            'openingStatus' => $mealRecords->openingStatus($mealRecord),
        ]);
    }

    public function update(
        StoreMealRecordRequest $request,
        int $meal,
        MealRecordService $mealRecords,
    ): RedirectResponse {
        $record = $request->user()->mealRecords()->findOrFail($meal);
        $mealRecords->save($request->user(), $request->validated(), $record);

        return redirect()->route('meals.index')->with('success', 'Pilihan makanan berhasil diperbarui.');
    }

    public function destroy(Request $request, int $meal, MealRecordService $mealRecords): RedirectResponse
    {
        $record = $request->user()->mealRecords()->findOrFail($meal);
        $mealRecords->delete($record);

        return redirect()->route('meals.index')->with('success', 'Pilihan makanan berhasil dihapus.');
    }
}
