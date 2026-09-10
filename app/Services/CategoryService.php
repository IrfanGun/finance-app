<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;

class CategoryService
{
    public function listFor(User $user)
    {
        return $user->categories()->latest()->get();
    }

    public function create(User $user, array $data): Category
    {
        return $user->categories()->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);

        return $category->refresh();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
