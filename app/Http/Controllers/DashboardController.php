<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): Response
    {
        $view = $request->is('cms/dashboard')
            ? 'Cms/DashboardCMS'
            : 'Dashboard';

        $data = $dashboard->dataFor($request->user());
        $mealRecordId = $request->integer('meal_record_id');
        $data['selectedMealRecord'] = $mealRecordId > 0
            ? $request->user()->mealRecords()->findOrFail($mealRecordId)
            : null;

        return Inertia::render($view, $data);
    }
}
