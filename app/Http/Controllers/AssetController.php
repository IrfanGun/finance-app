<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisposeAssetRequest;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request, AssetService $assets): Response
    {
        Gate::authorize('viewAny', Asset::class);

        $view = $request->is('cms/assets')
            ? 'Cms/AssetsCMS'
            : 'Assets/Index';

        return Inertia::render($view, [
            'assets' => $assets->listFor($request->user()),
            'currencyOptions' => $assets->currencyOptionsFor($request->user()),
        ]);
    }

    public function store(StoreAssetRequest $request, AssetService $assets): RedirectResponse
    {
        Gate::authorize('create', Asset::class);

        $asset = $assets->create($request->user(), $request->validated());

        $refererPath = parse_url(
            (string) $request->headers->get('referer'),
            PHP_URL_PATH,
        );

        if ($refererPath === '/cms/assets') {
            return to_route('cms.assets.index');
        }

        Inertia::flash('asset_success', 'created');

        return to_route('assets.show', $asset);
    }

    public function show(Request $request, int $asset, AssetService $assets): Response
    {
        $asset = $assets->findFor($request->user(), $asset);
        Gate::authorize('view', $asset);

        return Inertia::render('Assets/Show', [
            'asset' => $assets->details($asset),
            'logs' => $assets->logsFor($asset),
        ]);
    }

    public function edit(Request $request, int $asset, AssetService $assets): Response
    {
        $asset = $assets->findFor($request->user(), $asset);
        Gate::authorize('update', $asset);

        return Inertia::render('Assets/Edit', [
            'asset' => $asset,
            'currencyOptions' => $assets->currencyOptionsFor(
                $request->user(),
                $asset->currency,
            ),
        ]);
    }

    public function update(
        UpdateAssetRequest $request,
        int $asset,
        AssetService $assets,
    ): RedirectResponse {
        $asset = $assets->findFor($request->user(), $asset);
        Gate::authorize('update', $asset);

        $assets->update($asset, $request->validated());
        Inertia::flash('asset_success', 'updated');

        return to_route('assets.show', $asset);
    }

    public function destroy(Request $request, int $asset, AssetService $assets): RedirectResponse
    {
        $asset = $assets->findFor($request->user(), $asset);
        Gate::authorize('delete', $asset);
        $assets->delete($asset);

        return to_route('assets.index');
    }

    public function dispose(
        DisposeAssetRequest $request,
        int $asset,
        AssetService $assets,
    ): RedirectResponse {
        $asset = $assets->findFor($request->user(), $asset);
        Gate::authorize('update', $asset);
        $assets->dispose($asset);

        return back();
    }
}
