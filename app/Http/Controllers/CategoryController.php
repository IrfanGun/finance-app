<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Services\CategoryService;
use App\Http\Requests\CategoryRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request, CategoryService $categories): Response
    {
        return Inertia::render('Categories/Index', ['categories' => $categories->listFor($request->user())]);
    }
    public function show(Request $request, Category $category): Response
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        return Inertia::render('Categories/Show', ['category' => $category, 'transactions' => []]);
    }
    public function store(CategoryRequest $request, CategoryService $categories): RedirectResponse
    {
        $categories->create($request->user(), $request->validated());
        return back();
    }
    public function update(CategoryRequest $request, Category $category, CategoryService $categories): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        $categories->update($category, $request->validated());
        return back();
    }
    public function destroy(Request $request, Category $category, CategoryService $categories): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 403);
        $categories->delete($category);
        return back();
    }
}
