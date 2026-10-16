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

        return Inertia::render($view, $dashboard->dataFor($request->user()));
    }
}
