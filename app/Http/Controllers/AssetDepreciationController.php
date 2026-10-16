<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAssetDepreciationRequest;
use App\Http\Requests\UpdateAssetDepreciationStatusRequest;
use App\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssetDepreciationController extends Controller
{
    public function update(
        UpdateAssetDepreciationRequest $request,
        int $asset,
        AssetService $assets,
    ): RedirectResponse {
        $asset = $assets->findFor($request->user(), $asset);
        Gate::authorize('update', $asset);
        $assets->updateDepreciation($asset, $request->validated());

        return back();
    }

    public function updateStatus(
        UpdateAssetDepreciationStatusRequest $request,
        int $asset,
        AssetService $assets,
    ): RedirectResponse {
        $asset = $assets->findFor($request->user(), $asset);
        Gate::authorize('update', $asset);
        $assets->updateDepreciationStatus(
            $asset,
            $request->validated('status'),
        );

        return back();
    }

    public function logs(Request $request, int $asset, AssetService $assets): Response
    {
        $asset = $assets->findFor($request->user(), $asset);
        Gate::authorize('view', $asset);

        return Inertia::render('Assets/Logs', [
            'asset' => $asset,
            'logs' => $assets->logsFor($asset),
        ]);
    }
}
